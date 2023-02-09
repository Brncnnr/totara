<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\totara_notification\recipient;

use coding_exception;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\signup;
use mod_facetoface\signup_helper;
use mod_facetoface\trainer_helper;
use totara_notification\recipient\recipient;

/**
 * Class event_role
 *
 * The recipient referred to in this class are the approvers for the seminar, which depends on the participant and
 * the configuration of the serminar.
 *
 * @package mod_facetoface\recipient
 */
class approvers implements recipient {

    public static function get_name(): string {
        return get_string('notification_recipient_approvers', 'mod_facetoface');
    }

    /**
     * Return an array of event role user ids.
     */
    public static function get_user_ids(array $data): array {
        if (!isset($data['seminar_event_id'])) {
            throw new coding_exception('Missing seminar_event_id');
        }

        if (!isset($data['user_id'])) {
            throw new coding_exception('Missing user_id');
        }

        $seminar_event = new seminar_event($data['seminar_event_id']);
        $seminar = $seminar_event->get_seminar();

        switch ($seminar->get_approvaltype()) {
            case seminar::APPROVAL_ROLE:
                // Send the booking requested message to the users who are in the event role that is set as approver event role.
                $trainerhelper = new trainer_helper($seminar_event);
                $sessionroles = $trainerhelper->get_trainers_for_role($seminar->get_approvalrole());
                $recipients = [];
                foreach ($sessionroles as $role_user) {
                    $recipients[$role_user->id] = $role_user->id;
                }
                return $recipients;
            case seminar::APPROVAL_ADMIN:
                // Send the booking requested message to the user's manager and system- and seminar-level approval admins.
                $system_approvers = get_users_from_config(
                    get_config(null, 'facetoface_adminapprovers'),
                    'mod/facetoface:approveanyrequest'
                );
                $system_approvers_ids = array_map(function ($manager) {
                    return $manager->id;
                }, $system_approvers);

                $activity_approver_ids = $seminar->get_approvaladmins_list();

                $manager_approver_ids = self::get_manager_approver_ids($data['user_id'], $seminar_event);

                return array_merge($system_approvers_ids, $activity_approver_ids, $manager_approver_ids);
            case seminar::APPROVAL_MANAGER:
                // Send the booking requested message to the user's managers.
                return self::get_manager_approver_ids($data['user_id'], $seminar_event);
            case seminar::APPROVAL_NONE:
            case seminar::APPROVAL_SELF:
                return [];
            default:
                throw new coding_exception('Unexpected approval type');
        }
    }

    private static function get_manager_approver_ids($user_id, $seminar_event): array {
        $signup = signup::create($user_id, $seminar_event); // Creates object but not DB record.
        $managers = signup_helper::find_managers_from_signup($signup);
        return array_map(function ($manager) {
            return $manager->id;
        }, $managers);
    }
}
