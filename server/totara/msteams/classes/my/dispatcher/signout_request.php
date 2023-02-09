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

namespace totara_msteams\my\dispatcher;

use core\session\manager;
use lang_string;
use totara_msteams\botfw\activity;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\describable;
use totara_msteams\botfw\dispatchable;
use totara_msteams\botfw\exception\auth_required_exception;
use totara_msteams\my\helpers\user_helper;

/**
 * A dispatcher for the signout command.
 */
class signout_request implements dispatchable, describable {
    /**
     * @inheritDoc
     */
    public function get_name(): ?lang_string {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function get_description(): lang_string {
        return new lang_string('botfw:help_signout', 'totara_msteams');
    }

    /**
     * @inheritDoc
     */
    public function get_long_description(): ?lang_string {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(bot $bot, activity $activity): void {
        try {
            $msuser = $bot->get_authoriser()->get_user($activity, $activity->from);
            $name = user_helper::get_friendly_name($msuser);
            $bot->get_authoriser()->delete_user($msuser);
            manager::kill_user_sessions($msuser->userid);
            $bot->reply_text_to($activity, get_string('botfw:msg_signout_done', 'totara_msteams', $name));
        } catch (auth_required_exception $ex) {
            $bot->reply_text_to($activity, get_string('botfw:msg_signout_already', 'totara_msteams'));
        }
    }
}
