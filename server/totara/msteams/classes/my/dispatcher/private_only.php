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

use totara_msteams\botfw\activity;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\dispatchable;
use totara_msteams\botfw\exception\auth_required_exception;
use totara_msteams\my\helpers\user_helper;

/**
 * A dispatcher for the fallback.
 */
class private_only implements dispatchable {
    /**
     * @inheritDoc
     */
    public function dispatch(bot $bot, activity $activity): void {
        try {
            $msuser = $bot->get_authoriser()->get_user($activity, $activity->from);
            $name = user_helper::get_friendly_name($msuser);
            $text = get_string('botfw:msg_private_name', 'totara_msteams', $name);
        } catch (auth_required_exception $ex) {
            $text = get_string('botfw:msg_private', 'totara_msteams');
        }
        $bot->reply_text_to($activity, $text);
    }
}
