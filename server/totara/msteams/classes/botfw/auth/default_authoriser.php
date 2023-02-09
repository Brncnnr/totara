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

use dml_exception;
use moodle_url;
use totara_msteams\auth_helper;
use totara_msteams\botfw\account\channel_account;
use totara_msteams\botfw\activity;
use totara_msteams\botfw\context;
use totara_msteams\botfw\entity\channel;
use totara_msteams\botfw\entity\user;
use totara_msteams\botfw\entity\user_state;
use totara_msteams\botfw\exception\auth_required_exception;
use totara_msteams\botfw\exception\user_not_found_exception;

/**
 * The default implementation of an authoriser.
 */
class default_authoriser implements authoriser {
    private const TIMEOUT = 30;

    /**
     * @inheritDoc
     */
    public function initialise(context $context): void {
    }

    /**
     * @inheritDoc
     */
    public function get_user(activity $activity, channel_account $account, bool $verified = true): user {
        $user = user::repository()->load($account, $activity->channelId, false);
        if ($user === null) {
            throw new auth_required_exception();
        }
        if ($verified && !$user->verified) {
            throw new auth_required_exception();
        }
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function delete_user(user $user): void {
        if (!$user->exists()) {
            throw new user_not_found_exception();
        }
        $user->delete();
    }

    /**
     * @inheritDoc
     */
    public function get_login_url(activity $activity, channel_account $account): moodle_url {
        return new moodle_url('/totara/msteams/botlogin.php');
    }

    /**
     * @inheritDoc
     */
    public function verify_login(activity $activity, channel_account $account): user {
        $channel = $this->load_or_add_channel($activity->channelId);
        $user = $this->load_or_create_user($account, $channel);
        $userstate = user_state::repository()->where('verify_code', $activity->value->state)->one(false);
        if (!$userstate) {
            throw new auth_required_exception();
        }
        /** @var user_state $userstate */
        if ($userstate->timeexpiry <= time()) {
            throw new auth_required_exception(get_string('botfw:error_auth_timeout', 'totara_msteams'));
        }
        $user->verified = true;
        $user->userid = $userstate->userid;
        $user->save();
        // We don't need the user_state anymore.
        $userstate->delete();
        return $user;
    }

    /**
     * @param string $channel_id
     * @return channel
     */
    private function load_or_add_channel(string $channel_id): channel {
        $channel = channel::repository()->where('channel_id', $channel_id)->one(false);
        if (!$channel) {
            $channel = new channel();
            $channel->channel_id = $channel_id;
            $channel->save();
        }
        return $channel;
    }

    /**
     * @param channel_account $account
     * @param channel $channel
     * @return user
     */
    private function load_or_create_user(channel_account $account, channel $channel): user {
        $user = user::repository()->load($account, $channel, false);
        if (!$user) {
            $user = new user();
            $user->mschannelid = $channel->id;
            $user->teams_id = $account->id;
        }
        $user->verified = false;
        return $user;
    }

    /**
     * @return string
     */
    private static function generate_random(): string {
        $data = random_bytes_emulate(32); // 32 bytes binary data
        $out = base64_encode($data);
        return strtr(trim($out, '='), '+/', '-_');
    }

    /**
     * Called from the login page.
     *
     * @return moodle_url
     */
    public static function initiate_login(): moodle_url {
        global $USER;
        $userstate = new user_state();
        $userstate->sesskey = sesskey(); // sesskey is not used
        $userstate->timeexpiry = time() + self::TIMEOUT;
        $userstate->timecreated = time();
        $userstate->save();

        $redirecturl = new moodle_url('/totara/msteams/botlogin.php', ['state' => $userstate->id]);

        if (!empty($USER->id)) {
            // Already logged in.
            redirect($redirecturl);
            exit; // Never reached.
        }

        $issuer = auth_helper::get_oauth2_issuer();
        if ($issuer === null) {
            // Manual login.
            require_login();
            exit; // Never reached.
        }

        // Proceed single sign-on.
        return $redirecturl;
    }

    /**
     * Called from the login page.
     *
     * @return user_state|null
     */
    public static function continue_login(): ?user_state {
        global $DB;
        /** @var \moodle_database $DB */

        $stateid = optional_param('state', 0, PARAM_INT);
        if (empty($stateid)) {
            return null;
        }

        require_login();

        return $DB->transaction(function() use ($stateid) {
            global $USER;
            $userstate = user_state::repository()->where('id', $stateid)->one(true);
            /** @var user_state $userstate */

            if (!empty($userstate->verify_code) || !empty($userstate->userid)) {
                throw new auth_required_exception(get_string('botfw:error_auth_invalid', 'totara_msteams'));
            }
            if ($userstate->timeexpiry <= time()) {
                throw new auth_required_exception(get_string('botfw:error_auth_timeout', 'totara_msteams'));
            }

            $userstate->userid = $USER->id;
            $userstate->verify_code = self::generate_random();
            $userstate->timeexpiry = time() + self::TIMEOUT; // extend timeout
            try {
                $userstate->save();
            } catch (dml_exception $ex) {
                // NOTE: This is very paranoia; The chance of the collision is less than winning $1m Lotto 10 times in a row.
                $userstate->verify_code = self::generate_random();
                $userstate->save();
            }
            return $userstate;
        });
    }
}
