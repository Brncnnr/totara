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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

namespace totara_msteams\my\helpers;

use core_user;
use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\entity\user;

/**
 * user_helper class
 */
class user_helper {
    /**
     * Get a user's name.
     *
     * @param user|integer $userorid
     * @return string
     */
    public static function get_friendly_name($userorid): string {
        if ($userorid instanceof user) {
            $user = core_user::get_user($userorid->userid, '*', MUST_EXIST);
        } else {
            $user = core_user::get_user($userorid, '*', MUST_EXIST);
        }
        return get_string('botfw:friendly_username1', 'totara_msteams', $user) ?: get_string('botfw:friendly_username2', 'totara_msteams', $user);
    }

    /**
     * Get a user's name.
     *
     * @param channel_account $account
     * @return string|null
     */
    public static function get_friendly_name_from_channel(channel_account $account): ?string {
        if (isset($account->name) && is_string($account->name) && $account->name !== '') {
            return $account->name;
        }
        return null;
    }
}
