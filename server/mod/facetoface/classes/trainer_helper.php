<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

use mod_facetoface\totara_notification\resolver\trainer_assigned;
use mod_facetoface\totara_notification\resolver\trainer_unassigned;
use mod_facetoface\totara_notification\seminar_notification_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * A class for working with trainers that are assigned to seminar events.
 */
final class trainer_helper {
    /**
     * @var seminar_event
     */
    private $seminarevent;

    /**
     * @var array
     */
    private $trainers;

    /**
     * trainer_helper constructor.
     *
     * @param seminar_event $seminarevent
     * @return void
     */
    public function __construct(seminar_event $seminarevent) {
        $this->seminarevent = $seminarevent;
        $this->trainers = [];
    }

    /**
     * If the $roleid is provided, then an array of users that were assigned to that roles for seminar event will be returned.
     * Otherwise, returned all the roles of the event. The data of returned value will look like somehting below:
     *
     * @example
     *         return [
     *              4 => [
     *                  15 => (object) [
     *                      'username' => 'kianbomba',
     *                      'firstname' => 'kian',
     *                      'lastname' => 'bomba',
     *                      'id' => 15
     *                  ],
     *                  16 => (object) [
     *                      'username' => 'bolobala',
     *                      'firstname' => 'bala',
     *                      'lastname' => 'bolo',
     *                      'id' => 16
     *                  ]
     *              ]
     *         ]
     *
     * @param int|null  $roleid
     * @param bool      $reload
     *
     * @return array Array<int, Array<int \stdClass[]>>
     */
    public function get_trainers(int $roleid = null, bool $reload = false): array {
        if (empty($this->trainers) || $reload) {
            $this->trainers = $this->load_trainers();
        }

        // Using != because we want to include zero and empty string here for checking too.
        if (null != $roleid) {
            if (!isset($this->trainers[$roleid])) {
                return [$roleid => []];
            }

            return [$roleid => $this->trainers[$roleid]];
        }

        return $this->trainers;
    }

    /**
     * This will return an array of a users only base on the $roleid provided. The returned type will be looking something similar
     * as below
     *
     * @example
     *         return [
     *                  15 => (object) [
     *                      'username' => 'kianbomba',
     *                      'firstname' => 'kian',
     *                      'lastname' => 'bomba',
     *                      'id' => 15
     *                  ],
     *
     *                  16 => (object) [
     *                      'username' => 'bolobala',
     *                      'firstname' => 'bala',
     *                      'lastname' => 'bolo',
     *                      'id' => 16
     *                  ]
     *              ]
     *
     * @param int $roleid
     * @return \stdClass[]
     */
    public function get_trainers_for_role(int $roleid): array {
        $trainers = $this->get_trainers($roleid);
        return $trainers[$roleid];
    }

    /**
     * Returning Array<int, Array<int, \stdClass[]>>
     * @return array
     */
    private function load_trainers(): array {
        global $DB;

        if (!$this->seminarevent->exists()) {
            return [];
        }

        $usernamefields = get_all_user_name_fields(true, 'u');
        $sql = "
            SELECT u.id,
            {$usernamefields},
            r.roleid
            FROM {facetoface_session_roles} r
            INNER JOIN {user} u ON u.id = r.userid
            WHERE r.sessionid = ?
            ORDER by u.id
        ";

        $params = [$this->seminarevent->get_id()];
        $records = $DB->get_recordset_sql($sql, $params);
        $data = [];

        foreach ($records as $record) {
            if (!isset($data[$record->roleid])) {
                $data[$record->roleid] = [];
            }

            $data[$record->roleid][$record->id] = $record;
        }

        $records->close();
        return $data;
    }

    /**
     * @param int   $roleid    Role to be added for the list of user ids.
     * @param int[] $userids   An array of user ids to be added as a role/trainer into seminar-event.
     * @param bool  $sendnotification
     *
     * @return string[] Array of userid:roleid pairs
     */
    public function add_trainers(int $roleid, array $userids, bool $sendnotification = true): array {
        if (!$this->seminarevent->exists()) {
            return [];
        }

        $trainers = $this->get_trainers($roleid);
        $sessionid = $this->seminarevent->get_id();

        $added = [];
        foreach ($userids as $userid) {
            if (empty($userid)) {
                continue;
            }
            if (!isset($trainers[$roleid][$userid])) {
                $role = new role();
                $role->set_roleid($roleid);
                $role->set_userid($userid);
                $role->set_sessionid($sessionid);

                $role->save();

                if ($sendnotification) {
                    // Legacy notification.
                    notice_sender::trainer_confirmation($role->get_userid(), $this->seminarevent);

                    // Centralised notifications.
                    $seminar = $this->seminarevent->get_seminar();
                    $cn_data = [
                        'seminar_event_id' => $sessionid,
                        'trainer_user_id' => $userid,
                        'seminar_id' => $seminar->get_id(),
                        'module_id' => $seminar->get_coursemodule()->id,
                        'course_id' => $seminar->get_course()
                    ];
                    seminar_notification_helper::create_seminar_notifiable_event_queue(
                        $seminar,
                        new trainer_assigned($cn_data)
                    );
                }
            }

            $added[] = $userid . ':' . $roleid;
        }

        return $added;
    }

    /**
     * Removing the trainers/roles from the seminar_event, as if user does not exist in the list. If the list is empty, it means
     * that external usage call want to remove all the trainers from the event.
     *
     * @param string[] $excludeusers    An array of userid:roleid pairs.
     * @param bool  $sendnotification   Determine whether it should send the notification to the deleted users or not.
     *                                  By default, it is.
     *
     * @return bool
     */
    public function remove_trainers(array $excludeusers = [], bool $sendnotification = true): bool {
        if (!$this->seminarevent->exists()) {
            // Don't bother, if seminarevent is not in system yet.
            return false;
        }

        // For removing trainers/role users from the seminar event, at least, the functionality should be able to
        // perform the action with the latest data set.
        $trainers = $this->get_trainers(null, true);
        $sessionid = $this->seminarevent->get_id();

        foreach ($trainers as $roleid => $users) {
            /** @var \stdClass $user */
            foreach ($users as $user) {
                $userid = $user->id;
                // If the current trainer is existing in the list of excluded user, then we should skip it.
                if (in_array($userid . ':' . $roleid, $excludeusers)) {
                    continue;
                }

                $role = role::find_from($userid, $sessionid, $roleid);
                if (!$role->exists()) {
                    debugging(
                        "There was no role existing in seminar event ({$sessionid}) for user {$userid} with role ({$roleid})",
                        DEBUG_DEVELOPER
                    );

                    continue;
                }

                $role->delete();
                if ($sendnotification) {
                    notice_sender::event_trainer_unassigned($userid, $this->seminarevent);

                    // totara_notification
                    $seminar = $this->seminarevent->get_seminar();

                    $cn_data = [
                        'seminar_event_id' => $sessionid,
                        'trainer_user_id' => $userid,
                        'seminar_id' => $seminar->get_id(),
                        'module_id' => $seminar->get_coursemodule()->id,
                        'course_id' => $seminar->get_course()
                    ];
                    seminar_notification_helper::create_seminar_notifiable_event_queue(
                        $seminar,
                        new trainer_unassigned($cn_data)
                    );
                }
            }
        }

        // Reloading the trainers after deleting a bunch of them.
        $this->trainers = $this->load_trainers();
        return true;
    }

    /**
     * Return array of trainer roles configured for seminar
     * @param \context $context
     *
     * @return array
     */
    public static function get_trainer_roles(\context $context): array {
        global $CFG, $DB;

        // Check that roles have been selected
        if (empty($CFG->facetoface_session_roles)) {
            return [];
        }

        // Parse roles
        $cleanroles = clean_param($CFG->facetoface_session_roles, PARAM_SEQUENCE);
        [$psql, $params] = $DB->get_in_or_equal(explode(',', $cleanroles));

        // Load role names
        $sql = "
            SELECT r.id, r.name
            FROM {role} r
            WHERE r.id {$psql}
            AND r.id <> 0
        ";

        $rolenames = $DB->get_records_sql($sql, $params);

        // Return roles and names
        if (!$rolenames) {
            return [];
        }

        $rolenames = role_fix_names($rolenames, $context);
        return $rolenames;
    }
}
