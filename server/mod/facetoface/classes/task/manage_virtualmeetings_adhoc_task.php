<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\task;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use core\entity\user;
use core\orm\query\builder;
use core\plugininfo\virtualmeeting as virtualmeeting_plugininfo;
use core\task\adhoc_task;
use DateTime;
use mod_facetoface\totara_notification\seminar_notification_helper;
use totara_core\http\exception\request_exception;
use totara_core\virtualmeeting\exception\auth_exception;
use \facetoface_notification;
use mod_facetoface\room;
use mod_facetoface\room_virtualmeeting;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use mod_facetoface\totara_notification\resolver\virtual_meeting_creation_failed;
use stdClass;
use Throwable;
use totara_core\virtualmeeting\exception\meeting_exception;
use totara_core\virtualmeeting\virtual_meeting as virtualmeeting_model;

/**
 * This class manages the creation, update, and deletion of virtualmeetings associated with seminar rooms.
 */
class manage_virtualmeetings_adhoc_task extends adhoc_task {

    /**
     * Create ad-hoc task for managing virtualmeeting rooms in a seminar event
     * @param int $seminar_event_id
     * @param int $user_id
     * @return manage_virtualmeetings_adhoc_task
     */
    public static function create_from_seminar_event_id(int $seminar_event_id, int $user_id = 0): manage_virtualmeetings_adhoc_task {
        global $USER;
        if (empty($seminar_event_id)) {
            throw new coding_exception('No seminar event id set.');
        }
        if (empty($user_id)) {
            if (empty($USER->id)) {
                $message = 'No user id set.';
                if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
                    $message .= ' Be sure to call setUser() before adding a seminar session or an ad-hoc task.';
                }
                throw new coding_exception($message);
            }
            $user_id = $USER->id;
        }
        $task = new self();
        $task->set_component('mod_facetoface');
        $task->set_custom_data(['seminar_event_id' => $seminar_event_id, 'user_id' => $user_id]);

        return $task;
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        $custom_data = $this->get_custom_data();

        if (empty($custom_data->seminar_event_id)) {
            throw new coding_exception('No seminar event id set.');
        }
        if (empty($custom_data->user_id)) {
            throw new coding_exception('No user id set. Perhaps a left over task from a previous version.');
        }

        // Load seminarevent and seminar activity
        try {
            $seminarevent = new seminar_event($custom_data->seminar_event_id);
        } catch (\dml_missing_record_exception $e) {
            // This seminar event has been deleted, leave everything for the cleanup task.
            return;
        }

        // Track failures
        $failures = [];

        // Get the list of pending virtualmeeting rooms in this event.
        $records = builder::table(room_dates_virtualmeeting::DBTABLE, 'frdvm')
            ->join([seminar_session::DBTABLE, 'fsd'], 'frdvm.sessionsdateid', 'fsd.id')
            ->join([seminar_event::DBTABLE, 'fs'], 'fsd.sessionid', 'fs.id')
            ->join([room::DBTABLE, 'fr'], 'frdvm.roomid', 'fr.id')
            ->join([room_virtualmeeting::DBTABLE, 'frvm'], 'fr.id', 'frvm.roomid')
            ->join([user::TABLE, 'u'], 'frvm.userid', 'u.id')
            ->where('frdvm.status', [room_dates_virtualmeeting::STATUS_PENDING_UPDATE, room_dates_virtualmeeting::STATUS_PENDING_DELETION])
            ->where('fs.id', $custom_data->seminar_event_id)
            ->where('u.id', $custom_data->user_id)
            ->where('u.deleted', 0)
            ->where('u.suspended', 0)
            ->order_by('status')
            ->order_by('id')
            ->select(['frdvm.*', 'frvm.userid as user_id'])
            ->get();

        // Process
        foreach ($records as $record) {
            $user = new user((int)$record->user_id);
            unset($record->user_id);
            $roomdate_vm = (new room_dates_virtualmeeting())->from_record($record);
            $status = $roomdate_vm->get_status();
            $plugin = '';

            try {
                if ($status === room_dates_virtualmeeting::STATUS_PENDING_DELETION) {
                    $plugin = '(deleting)'; // no plugin info
                    // TODO: handle deletion in TL-29046
                    // self::delete_virtualmeeting($roomdate_vm, $user);
                } else {
                    $room_vm = room_virtualmeeting::from_roomid($roomdate_vm->get_roomid());
                    $plugin = $room_vm->get_plugin();
                    $session = new seminar_session($roomdate_vm->get_sessionsdateid());
                    $room = new room($roomdate_vm->get_roomid());
                    if ($roomdate_vm->get_virtualmeetingid()) {
                        self::update_virtualmeeting($session, $room, $room_vm, $roomdate_vm, $user);
                    } else {
                        self::create_virtualmeeting($session, $room, $room_vm, $roomdate_vm, $user);
                    }
                }
            } catch (auth_exception $e) {
                $failures[] = "Room {$room->get_name()} {$plugin} authorisation problem: {$e->getMessage()}";
            } catch (request_exception $e) {
                $failures[] = "Room {$room->get_name()} {$plugin} request failed: {$e->getMessage()}";
            } catch (\Exception $e) {
                $failures[] = "Room {$room->get_name()} {$plugin} error: {$e->getMessage()}";
            }
        }

        // Send notification of failures
        if (!empty($failures)) {
            // Write the exact failures to the debugging log
            if (!defined('BEHAT_SITE_RUNNING') && (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST)) {
                // @codeCoverageIgnoreStart
                $seminar = new seminar($seminarevent->get_facetoface());
                $failure_report = "Virtual room creation failures in seminar {$seminar->get_name()} event {$seminarevent->get_id()}:\n";
                $failure_report .= implode("\n", $failures);
                debugging($failure_report, DEBUG_DEVELOPER);
               // @codeCoverageIgnoreEnd
            }

            // Centralised notifications.
            $seminar = $seminarevent->get_seminar();
            $data = [
                'seminar_event_id' => $seminarevent->get_id(),
                'seminar_id' => $seminar->get_id(),
                'module_id' => $seminar->get_coursemodule()->id,
                'course_id' => $seminar->get_course()
            ];
            seminar_notification_helper::create_seminar_notifiable_event_queue(
                $seminar,
                new virtual_meeting_creation_failed($data)
            );

            // Legacy notification.
            $sessiondata = ['facetoface' => $seminarevent->get_facetoface()];
            $notification = new facetoface_notification($sessiondata, false);
            $notification->send_notification_virtual_meeting_creation_failure($seminarevent);
        }
    }

    /**
     * @param seminar_session $session
     * @param room_virtualmeeting $room_vm
     * @return stdClass comprising plugin, name, timestart, timefinish
     */
    private static function get_blue_prints(seminar_session $session, room_virtualmeeting $room_vm): stdClass {
        $return = new stdClass();
        $return->plugin = $room_vm->get_plugin();
        $return->name = $session->get_seminar_event()->get_seminar()->get_name();
        $return->timestart = DateTime::createFromFormat('U', $session->get_timestart());
        $return->timefinish = DateTime::createFromFormat('U', $session->get_timefinish());
        return $return;
    }

    /**
     * @param seminar_session $session
     * @param room $room
     * @param room_virtualmeeting $room_vm
     * @param room_dates_virtualmeeting $roomdate_vm
     */
    private static function validate_parameters(seminar_session $session, room $room, room_virtualmeeting $room_vm, room_dates_virtualmeeting $roomdate_vm): void {
        // Use of an unavailable or not-configured plugin IS a failure.
        $plugininfo = virtualmeeting_plugininfo::load($room_vm->get_plugin());
        if (!$plugininfo->is_available()) {
            throw new meeting_exception("virtualmeeting plugin is not configured.");
        }
    }

    /**
     * @param user $user
     * @throws auth_exception
     */
    private static function validate_user_status(user $user): void {
        if ($user->exists() && !$user->suspended && !$user->deleted) {
            return;
        }
        throw new auth_exception('User is not active');
    }

    /**
     * Create a virtual meeting.
     *
     * @param seminar_session $session
     * @param room $room
     * @param room_virtualmeeting $room_vm
     * @param room_dates_virtualmeeting $roomdate_vm
     * @param user $user
     * @return true
     */
    private static function create_virtualmeeting(seminar_session $session, room $room, room_virtualmeeting $room_vm, room_dates_virtualmeeting $roomdate_vm, user $user): bool {
        self::validate_parameters($session, $room, $room_vm, $roomdate_vm);
        try {
            self::validate_user_status($user);
            $data = self::get_blue_prints($session, $room_vm);
            $meeting = virtualmeeting_model::create($data->plugin, $user, $data->name, $data->timestart, $data->timefinish);
            // Let's see if the virtual room has a valid URL before updating the state.
            $meeting->get_join_url(true);

            $roomdate_vm->set_virtualmeetingid($meeting->get_id());
            $roomdate_vm->set_status(room_dates_virtualmeeting::STATUS_AVAILABLE);
            $roomdate_vm->save();
            return true;
        } catch (Throwable $ex) {
            $roomdate_vm->set_status(room_dates_virtualmeeting::STATUS_FAILURE_CREATION);
            $roomdate_vm->save();
            throw $ex;
        }
    }

    /**
     * Update a virtual meeting.
     *
     * @param seminar_session $session
     * @param room $room
     * @param room_virtualmeeting $room_vm
     * @param room_dates_virtualmeeting $roomdate_vm
     * @param user $user
     * @return true
     */
    private static function update_virtualmeeting(seminar_session $session, room $room, room_virtualmeeting $room_vm, room_dates_virtualmeeting $roomdate_vm, user $user): bool {
        self::validate_parameters($session, $room, $room_vm, $roomdate_vm);
        $meeting = $roomdate_vm->get_virtualmeeting();
        if ($meeting === null) {
            // Shouldn't be here.
            return self::create_virtualmeeting($session, $room, $room_vm, $roomdate_vm, $user);
        }
        // Still same user?
        if ($meeting->userid != $user->id) {
            throw new coding_exception("Unable to use a virtualmeeting room which does not belong to you.");
        }
        try {
            self::validate_user_status($user);
            $data = self::get_blue_prints($session, $room_vm);
            // Still same plugin?
            if ($meeting->plugin == $data->plugin) {
                $meeting->update($data->name, $data->timestart, $data->timefinish);
                // Let's see if the virtual room has a valid URL before updating the state.
                $meeting->get_join_url(true);
                $roomdate_vm->set_status(room_dates_virtualmeeting::STATUS_AVAILABLE);
                $roomdate_vm->save();
                return true;
            }
            // Different plugin, blow up
            throw new coding_exception('Unable to switch to a different virtualmeeting provider');
        } catch (Throwable $ex) {
            $roomdate_vm->set_status(room_dates_virtualmeeting::STATUS_FAILURE_UPDATE);
            $roomdate_vm->save();
            throw $ex;
        }
    }

    /**
     * Delete a virtual meeting.
     *
     * @param room_dates_virtualmeeting $roomdate_vm
     * @param user|null $user
     * @return true
     */
    private static function delete_virtualmeeting(room_dates_virtualmeeting $roomdate_vm, ?user $user): bool {
        $meeting = $roomdate_vm->get_virtualmeeting();
        if ($meeting === null) {
            $roomdate_vm->set_status(room_dates_virtualmeeting::STATUS_UNAVAILABLE);
            $roomdate_vm->save();
            // Silently return to fix up the double-delete problem.
            return true;
        }
        if ($user === null) {
            $user = new user($meeting->userid);
        }
        // Still same user?
        if ($meeting->userid != $user->id) {
            throw new coding_exception("Unable to delete a virtualmeeting room which does not belong to you.");
        }
        try {
            self::validate_user_status($user);
            $meeting->delete();
            $roomdate_vm->set_status(room_dates_virtualmeeting::STATUS_UNAVAILABLE);
            $roomdate_vm->save();
            return true;
        } catch (Throwable $ex) {
            $roomdate_vm->set_status(room_dates_virtualmeeting::STATUS_FAILURE_DELETION);
            $roomdate_vm->save();
            throw $ex;
        }
    }
}
