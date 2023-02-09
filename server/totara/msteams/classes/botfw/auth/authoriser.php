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

namespace totara_msteams\botfw\auth;

use moodle_url;
use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\activity;
use totara_msteams\botfw\context;
use totara_msteams\botfw\entity\user;

/**
 * Authoriser interface.
 */
interface authoriser {
    /**
     * Initialiser. This function is called from a bot.
     *
     * @param context $context
     */
    public function initialise(context $context): void;

    /**
     * Get the associated user from an activity and an account.
     *
     * @param activity $activity
     * @param channel_account $account
     * @param boolean $verified Set false to be able to get the user whose verification is not completed
     * @return user
     */
    public function get_user(activity $activity, channel_account $account, bool $verified = true): user;

    /**
     * Delete a user and associated states.
     * Note that this function only cleans up user state for the MS Teams, and does **not** delete a user account.
     *
     * @param user $user
     */
    public function delete_user(user $user): void;

    /**
     * Return the URL for sign in.
     *
     * @param activity $activity
     * @param channel_account $account
     * @return moodle_url
     */
    public function get_login_url(activity $activity, channel_account $account): moodle_url;

    /**
     * Verify user authentication.
     *
     * @param activity $activity
     * @param channel_account $account
     * @return user
     */
    public function verify_login(activity $activity, channel_account $account): user;
}
