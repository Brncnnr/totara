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
 * @author Gihan Hewaralalage <gihanh.hewaralalage@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\totara_notification\recipient;

use coding_exception;
use mod_facetoface\role;
use mod_facetoface\role_list;
use mod_facetoface\seminar_event;
use totara_notification\recipient\recipient;

/**
 * Class event_role
 *
 * The recipient referred to in this class are the users in approval event role.
 *
 * @package mod_facetoface\recipient
 */
class event_role implements recipient {

    public static function get_name(): string {
        return get_string('notification_recipient_event_roles', 'mod_facetoface');
    }

    /**
     * Return an array of event role user ids.
     */
    public static function get_user_ids(array $data): array {
        if (empty($data['seminar_event_id'])) {
            throw new coding_exception('missing seminar_event_id for event role seminar recipients');
        }

        $event = new seminar_event($data['seminar_event_id']);
        $role_users = role_list::get_distinct_users_from_seminarevent($event);

        $recipients = [];
        /** @var role $role_user */
        foreach ($role_users as $role_user) {
            $recipients[$role_user->get_userid()] = $role_user->get_userid();
        }

        return $recipients;
    }
}
