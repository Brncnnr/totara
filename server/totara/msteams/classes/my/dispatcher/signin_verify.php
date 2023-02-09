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
use totara_msteams\botfw\exception\botfw_exception;
use totara_msteams\botfw\exception\unexpected_exception;
use totara_msteams\my\helpers\notification_helper;

/**
 * A dispatcher that is indirectly triggered by the MS Teams to verify a sign-in context.
 */
class signin_verify implements dispatchable {
    /**
     * @inheritDoc
     */
    public function dispatch(bot $bot, activity $activity): void {
        try {
            $msuser = $bot->get_authoriser()->verify_login($activity, $activity->from, $activity->value->state);
            notification_helper::subscribe_and_reply($bot, $activity, $msuser);
        } catch (botfw_exception $ex) {
            // failed?
            throw new unexpected_exception('Sign-in failed', 0, $ex);
        }
    }
}
