<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy\observer;

use auth_approved\event\request_approved;
use core\event\user_confirmed;
use tool_sitepolicy\userconsent;

defined('MOODLE_INTERNAL') || die();

class user_confirmed_observer {

    /**
     * Whenever a user has accepted the site policy during signup we need to update
     * the userids in sitepolicy_user_consent to the new user id when the request is
     * approved.
     *
     * @param user_confirmed $event
     */
    public static function update_user_consent(user_confirmed $event): void {
        $user_id = $event->relateduserid;
        $userconsentids = get_user_preferences('auth_email_userconsentids', '', $user_id);
        if (!empty($userconsentids)) {
            $userconsentids = json_decode($userconsentids);
            foreach ($userconsentids as $userconsentid) {
                $consent = new userconsent($userconsentid);
                $consent->set_userid($user_id);
                $consent->save();
            }
        }
        unset_user_preference('auth_email_userconsentids', $user_id);
    }
}
