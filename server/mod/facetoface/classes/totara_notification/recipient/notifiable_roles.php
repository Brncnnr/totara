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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\totara_notification\recipient;

use coding_exception;
use context_module;
use mod_facetoface\role;
use mod_facetoface\seminar_event;
use totara_notification\recipient\recipient;

/**
 * Class notifiable_roles
 *
 * The recipient referred to in this class are users with one or more roles specified in $CFG->facetoface_session_rolesnotify.
 *
 * @package mod_facetoface\recipient
 */
class notifiable_roles implements recipient {

    public static function get_name(): string {
        return get_string('notification_recipient_notifiable_roles', 'mod_facetoface');
    }

    /**
     * Return an array of event role user ids.
     */
    public static function get_user_ids(array $data): array {
        global $CFG;

        $recipients = [];

        if (empty($data['seminar_event_id'])) {
            throw new coding_exception('Missing seminar_event_id for notifiable role recipients');
        }

        $event = new seminar_event($data['seminar_event_id']);
        $cm = get_coursemodule_from_instance('facetoface', $event->get_facetoface());
        $mod_context = context_module::instance($cm->id);

        $roles = $CFG->facetoface_session_rolesnotify;
        $role_users = get_role_users(explode(',', $roles), $mod_context, true, 'u.*');

        foreach ($role_users as $role_user) {
            $recipients[$role_user->id] = $role_user->id;
        }

        return $recipients;
    }
}
