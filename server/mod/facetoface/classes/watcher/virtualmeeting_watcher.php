<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\watcher;

use core\task\manager as task_manager;
use core\orm\query\builder;
use mod_facetoface\event\session_updated;
use mod_facetoface\hook\event_is_being_cancelled;
use mod_facetoface\hook\resources_are_being_updated;
use mod_facetoface\hook\service\seminar_session_resource;
use mod_facetoface\hook\service\seminar_session_resource_dynamic;
use mod_facetoface\hook\sessions_are_being_updated;
use mod_facetoface\room;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\room_virtualmeeting;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface\task\manage_virtualmeetings_adhoc_task;

/**
 * Watch virtual meeting changes.
 */
final class virtualmeeting_watcher {
    /**
     * @param sessions_are_being_updated $hook
     */
    public static function sessions_updated(sessions_are_being_updated $hook): void {
        $assigned_rooms = self::sessions_to_array($hook->sessionstobeinserted);
        $unassigned_rooms = self::sessions_to_array($hook->sessionstobedeleted);
        $updated_rooms = self::sessions_to_array($hook->sessionstobeupdated);
        // combine assigned and updated
        foreach ($updated_rooms as $sessionid => $updated) {
            $roomids = array_unique(array_merge($assigned_rooms[$sessionid] ?? [], $updated));
            if (!empty($roomids)) {
                $assigned_rooms[$sessionid] = $roomids;
            }
        }
        $rooms = [
            'create' => $assigned_rooms,
            'delete' => $unassigned_rooms,
        ];
        self::shove_virtual_room_status($hook->seminarevent, $rooms);
    }

    /**
     * @param event_is_being_cancelled $hook
     */
    public static function event_cancelled(event_is_being_cancelled $hook): void {
        $unassigned_rooms = [];
        foreach ($hook->seminarevent->get_sessions() as $sessionid => $session) {
            /** @var seminar_session $session */
            $roomids = builder::table(room::DBTABLE, 'fr')
                ->join([room_virtualmeeting::DBTABLE, 'frvm'], 'frvm.roomid', 'fr.id')
                ->join(['facetoface_room_dates', 'frd'], 'frd.roomid', 'fr.id')
                ->where('frd.sessionsdateid', $sessionid)
                ->select('fr.id')
                ->map_to(function ($record) {
                    return $record->id;
                })
                ->fetch(true);
            if (!empty($roomids)) {
                $unassigned_rooms[$sessionid] = $roomids;
            }
        }
        $rooms = [
            'create' => [],
            'delete' => $unassigned_rooms,
        ];
        self::shove_virtual_room_status($hook->seminarevent, $rooms);
    }

    /**
     * @param resources_are_being_updated $hook
     */
    public static function resources_updated(resources_are_being_updated $hook): void {
        $created = [];
        $deleted = [];
        foreach ($hook->sessions as $session) {
            $new_roomids = self::get_virtual_meeting_roomids($session);
            $session = seminar_session_resource_dynamic::from_session($session->get_session());
            $old_roomids = self::get_virtual_meeting_roomids($session);
            $created_in_session = array_diff($new_roomids, $old_roomids);
            $deleted_in_session = array_diff($old_roomids, $new_roomids);
            $intersection = array_intersect($new_roomids, $old_roomids);
            $possibly_added_in_session = self::get_switching_to_virtual_meeting_roomids($intersection);
            if (!empty($possibly_added_in_session)) {
                $created_in_session = array_unique(array_merge($created_in_session, $possibly_added_in_session), SORT_NUMERIC);
            }
            if (!empty($created_in_session)) {
                $created[$session->get_session_id()] = $created_in_session;
            }
            if (!empty($deleted_in_session)) {
                $deleted[$session->get_session_id()] = $deleted_in_session;
            }
        }

        $rooms = [
            'create' => $created,
            'delete' => $deleted,
        ];
        self::shove_virtual_room_status($hook->seminarevent, $rooms);
    }

    /**
     * @param session_updated $event
     */
    public static function seminar_event_updated(session_updated $event): void {
        global $USER;
        $eventdata = $event->get_session();
        if (empty($eventdata->id) || empty($USER->id)) {
            return;
        }
        $roomdate_vms = self::get_failing_virtualmeeting_states($eventdata->id, $USER->id);
        if (self::retry_failing_virtualmeetings($roomdate_vms)) {
            $task = manage_virtualmeetings_adhoc_task::create_from_seminar_event_id($eventdata->id);
            task_manager::queue_adhoc_task($task, true);
        }
    }

    /**
     * @param seminar_event $event
     * @param array $rooms_of_interest
     */
    private static function shove_virtual_room_status(seminar_event $event, array $rooms_of_interest): void {
        if (empty($rooms_of_interest['create']) && empty($rooms_of_interest['delete'])) {
            return;
        }

        $transaction = builder::get_db()->start_delegated_transaction();

        $roomids_confirmed = [];

        foreach ($rooms_of_interest['create'] as $sessionid => $roomids) {
            self::set_session_room_status_to_update($sessionid, $roomids, $roomids_confirmed);
            // self::set_session_room_status_to_update_in_bulk($sessionid, $roomids, $roomids_confirmed);
        }

        foreach ($rooms_of_interest['delete'] as $sessionid => $roomids) {
            self::set_session_room_status_to_delete($sessionid, $roomids, $roomids_confirmed);
        }

        builder::table(room_virtualmeeting::DBTABLE)
            ->where_in('roomid', array_keys($roomids_confirmed))
            ->update(['status' => room_virtualmeeting::STATUS_CONFIRMED]);

        $transaction->allow_commit();

        if (!empty($roomids_confirmed)) {
            $task = manage_virtualmeetings_adhoc_task::create_from_seminar_event_id($event->get_id());
            task_manager::queue_adhoc_task($task, true);
        }
    }

    /**
     * @param integer $sessionid
     * @param array $roomids
     * @param array $roomids_confirmed
     */
    private static function set_session_room_status_to_update(int $sessionid, array $roomids, array &$roomids_confirmed): void {
        foreach ($roomids as $roomid) {
            $roomdate_vm = room_dates_virtualmeeting::load_by_session_room($sessionid, $roomid);
            if ($roomdate_vm->exists()) {
                $update_status = in_array(
                    $roomdate_vm->get_status(),
                    [
                        room_dates_virtualmeeting::STATUS_AVAILABLE,
                        room_dates_virtualmeeting::STATUS_PENDING_UPDATE,
                        room_dates_virtualmeeting::STATUS_FAILURE_CREATION,
                        room_dates_virtualmeeting::STATUS_FAILURE_UPDATE,
                    ],
                    true
                );
                $nullify_vm = in_array(
                    $roomdate_vm->get_status(),
                    [
                        room_dates_virtualmeeting::STATUS_PENDING_DELETION,
                        room_dates_virtualmeeting::STATUS_FAILURE_DELETION,
                    ],
                    true
                );
                if ($update_status || $nullify_vm) {
                    if ($update_status) {
                        $roomdate_vm->set_status(room_dates_virtualmeeting::STATUS_PENDING_UPDATE);
                    } else if ($nullify_vm) {
                        $roomdate_vm->set_status(room_dates_virtualmeeting::STATUS_PENDING_UPDATE);
                        $roomdate_vm->set_virtualmeetingid(null);
                    }
                    $roomdate_vm->save();
                    $roomids_confirmed[$roomid] = true;
                }
            } else {
                $roomdate_vm = new room_dates_virtualmeeting();
                $roomdate_vm->set_status(room_dates_virtualmeeting::STATUS_PENDING_UPDATE);
                $roomdate_vm->set_sessionsdateid($sessionid);
                $roomdate_vm->set_roomid($roomid);
                $roomdate_vm->set_virtualmeetingid(null);
                $roomdate_vm->save();
                $roomids_confirmed[$roomid] = true;
            }
        }
    }

    // @codeCoverageIgnoreStart
    // TODO: ^^^ remove the ignore tag once set_session_room_status_to_update_in_bulk is stable

    /**
     * @param integer $sessionid
     * @param array $roomids
     * @param array $roomids_confirmed
     */
    private static function set_session_room_status_to_update_in_bulk(int $sessionid, array $roomids, array &$roomids_confirmed): void {
        /** @var array<integer, stdClass> */
        $room_roomdate_vms = builder::table(room_dates_virtualmeeting::DBTABLE, 'frdvm')
            ->join([seminar_session::DBTABLE, 'fsd'], 'frdvm.sessionsdateid', 'fsd.id')
            ->join([room::DBTABLE, 'fr'], 'frdvm.roomid', 'fr.id')
            ->where('frdvm.sessionsdateid', $sessionid)
            ->where_in('frdvm.roomid', $roomids)
            ->select(['frdvm.roomid', 'frdvm.id', 'frdvm.status'])
            ->fetch();
        $updating_rdvmids = [];
        $renewing_rdvmids = [];
        $inserting_rdvms = [];
        foreach ($roomids as $roomid) {
            if (isset($room_roomdate_vms[$roomid])) {
                $rdvm = $room_roomdate_vms[$roomid];
                $status = $rdvm->status;
                if (is_number($status)) {
                    $status = (int)$status;
                }
                $update_status = in_array(
                    $status,
                    [
                        room_dates_virtualmeeting::STATUS_AVAILABLE,
                        room_dates_virtualmeeting::STATUS_PENDING_UPDATE,
                        room_dates_virtualmeeting::STATUS_FAILURE_CREATION,
                        room_dates_virtualmeeting::STATUS_FAILURE_UPDATE,
                    ],
                    true
                );
                $renew_status = in_array(
                    $status,
                    [
                        room_dates_virtualmeeting::STATUS_PENDING_DELETION,
                        room_dates_virtualmeeting::STATUS_FAILURE_DELETION,
                    ],
                    true
                );
                if ($update_status || $renew_status) {
                    if ($update_status) {
                        $updating_rdvmids[] = $rdvm->id;
                    } else if ($renew_status) {
                        $renewing_rdvmids[] = $rdvm->id;
                    }
                    $roomids_confirmed[$roomid] = true;
                }
            } else {
                $roomdate_vm = new room_dates_virtualmeeting();
                $roomdate_vm->set_status(room_dates_virtualmeeting::STATUS_PENDING_UPDATE);
                $roomdate_vm->set_sessionsdateid($sessionid);
                $roomdate_vm->set_roomid($roomid);
                $roomdate_vm->set_virtualmeetingid(null);
                $record = $roomdate_vm->to_record();
                $inserting_rdvms[] = $record;
                $roomids_confirmed[$roomid] = true;
            }
        }
        if (!empty($inserting_rdvms)) {
            builder::get_db()->insert_records(room_dates_virtualmeeting::DBTABLE, $inserting_rdvms);
        }
        if (!empty($updating_rdvmids)) {
            builder::table(room_dates_virtualmeeting::DBTABLE)
                ->where_in('id', $updating_rdvmids)
                ->update(['status' => room_dates_virtualmeeting::STATUS_PENDING_UPDATE]);
        }
        if (!empty($renewing_rdvmids)) {
            builder::table(room_dates_virtualmeeting::DBTABLE)
                ->where_in('id', $renewing_rdvmids)
                ->update(['status' => room_dates_virtualmeeting::STATUS_PENDING_UPDATE, 'virtualmeetingid' => null]);
        }
    }

    // TODO: vvv remove the ignore tag once set_session_room_status_to_update_in_bulk is stable
    // @codeCoverageIgnoreEnd

    /**
     * @param integer $sessionid
     * @param array $roomids
     * @param array $roomids_confirmed
     */
    private static function set_session_room_status_to_delete(int $sessionid, array $roomids, array &$roomids_confirmed): void {
        /** @var array<integer, integer> */
        $roomdate_vm_roomids = builder::table(room_dates_virtualmeeting::DBTABLE, 'frdvm')
            ->join([seminar_session::DBTABLE, 'fsd'], 'frdvm.sessionsdateid', 'fsd.id')
            ->join([room::DBTABLE, 'fr'], 'frdvm.roomid', 'fr.id')
            ->where('fsd.id', $sessionid)
            ->where_in('fr.id', $roomids)
            ->select(['frdvm.id', 'fr.id as roomid'])
            ->map_to(function ($record) {
                return $record->roomid;
            })
            ->fetch();
        builder::table(room_dates_virtualmeeting::DBTABLE)
            ->where_in('id', array_keys($roomdate_vm_roomids))
            ->update(['status' => room_dates_virtualmeeting::STATUS_PENDING_DELETION]);
        foreach ($roomdate_vm_roomids as $roomid) {
            $roomids_confirmed[$roomid] = true;
        }
    }

    /**
     * @param integer[] $roomids
     * @return integer[]
     */
    private static function get_switching_to_virtual_meeting_roomids(array $roomids): array {
        return array_keys(
            builder::table(room::DBTABLE, 'fr')
                ->join([room_virtualmeeting::DBTABLE, 'frvm'], 'frvm.roomid', 'fr.id')
                ->where('frvm.status', room_virtualmeeting::STATUS_PENDING)
                ->where_in('fr.id', $roomids)
                ->select('fr.id')
                ->fetch()
        );
    }

    /**
     * @param seminar_session_resource $session
     * @return array
     */
    private static function get_virtual_meeting_roomids(seminar_session_resource $session): array {
        $rooms = $session->get_room_list()->to_array(true);
        return builder::table(room::DBTABLE, 'fr')
            ->join([room_virtualmeeting::DBTABLE, 'frvm'], 'frvm.roomid', 'fr.id')
            ->where_in('fr.id', array_keys($rooms))
            ->select('fr.id')
            ->map_to(function ($record) {
                return $record->id;
            })
            ->fetch(true);
    }

    /**
     * @param integer $eventid
     * @param integer $userid
     * @return integer[] id => status
     */
    private static function get_failing_virtualmeeting_states(int $eventid, int $userid): array {
        $failures = [
            room_dates_virtualmeeting::STATUS_FAILURE_CREATION,
            room_dates_virtualmeeting::STATUS_FAILURE_UPDATE,
            room_dates_virtualmeeting::STATUS_FAILURE_DELETION
        ];
        return builder::table(room_dates_virtualmeeting::DBTABLE, 'frdvm')
            ->join([seminar_session::DBTABLE, 'fsd'], 'frdvm.sessionsdateid', 'fsd.id')
            ->join([room::DBTABLE, 'fr'], 'frdvm.roomid', 'fr.id')
            ->join([room_virtualmeeting::DBTABLE, 'frvm'], 'frvm.roomid', 'fr.id')
            ->join(['user', 'u'], 'frvm.userid', 'u.id')
            ->where('fsd.sessionid', $eventid)
            ->where('u.id', $userid)
            ->where('u.deleted', 0)
            ->where('u.suspended', 0)
            ->where_in('frdvm.status', $failures)
            ->select(['frdvm.id', 'frdvm.status'])
            ->map_to(function ($record) {
                return $record->status;
            })
            ->fetch(false);
    }

    /**
     * @param integer[] $roomdate_vms id => status
     * @return boolean retried
     */
    private static function retry_failing_virtualmeetings(array $roomdate_vms): bool {
        $updates = array_filter($roomdate_vms, function (int $status) {
            return $status == room_dates_virtualmeeting::STATUS_FAILURE_CREATION || $status == room_dates_virtualmeeting::STATUS_FAILURE_UPDATE;
        });
        $deletes = array_filter($roomdate_vms, function (int $status) {
            return $status == room_dates_virtualmeeting::STATUS_FAILURE_DELETION;
        });
        if (!empty($updates)) {
            builder::table(room_dates_virtualmeeting::DBTABLE)
                ->where_in('id', array_keys($updates))
                ->update(['status' => room_dates_virtualmeeting::STATUS_PENDING_UPDATE]);
        }
        if (!empty($deletes)) {
            builder::table(room_dates_virtualmeeting::DBTABLE)
                ->where_in('id', array_keys($deletes))
                ->update(['status' => room_dates_virtualmeeting::STATUS_PENDING_DELETION]);
        }
        return !empty($updates) || !empty($deletes);
    }

    /**
     * @param seminar_session_resource[] $sessions
     * @return array
     */
    private static function sessions_to_array(array $sessions): array {
        $result = [];
        foreach ($sessions as $session) {
            // ignore non-existent sessions
            if ($session->get_session_id() == 0) {
                continue;
            }
            $roomids = self::get_virtual_meeting_roomids($session);
            if (!empty($roomids)) {
                $result[$session->get_session_id()] = $roomids;
            }
        }
        return $result;
    }

    /**
     * @param array $rooms_of_interest
     * @return array
     * @codeCoverageIgnore
     */
    private static function format_session_rooms(array $rooms_of_interest): array {
        $result = [];
        foreach ($rooms_of_interest as $what => $sessions) {
            $data = [];
            /** @var integer[] $roomids */
            foreach ($sessions as $sessionid => $roomids) {
                $rooms = [];
                foreach ($roomids as $roomid) {
                    $rooms[$roomid] = (new room($roomid))->get_name();
                }
                $session = new seminar_session($sessionid);
                $data[] = [
                    'session' => [
                        'start' => userdate($session->get_timestart(), '%d %B %Y %I:%M:%S %p'),
                        'finish' => userdate($session->get_timefinish(), '%d %B %Y %I:%M:%S %p'),
                    ],
                    'rooms' => $rooms
                ];
            }
            $result[$what] = $data;
        }
        return $result;
    }
}
