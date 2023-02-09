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

use totara_msteams\botfw\activity;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\builder;
use totara_msteams\botfw\entity\user;
use totara_msteams\botfw\exception\botfw_exception;

/**
 * notification_helper class
 */
class notification_helper {
    /**
     * Subscribe the notification, or sign out the user if fails.
     *
     * @param bot $bot
     * @param activity $activity
     * @param user $user
     * @return boolean
     */
    public static function subscribe_and_reply(bot $bot, activity $activity, user $user): bool {
        $botfeatureenabled = get_config('totara_msteams', 'bot_feature_enabled');
        if (!$botfeatureenabled) {
            return false;
        }
        $name = user_helper::get_friendly_name($user);
        $result = $bot->get_notification()->subscribe($user, $activity->conversation->tenantId, $bot->get_account());
        if ($result) {
            $text = get_string('botfw:msg_signin_done', 'totara_msteams', $name);
        } else {
            $text = get_string('botfw:msg_subscribe_already', 'totara_msteams', $name);
        }
        $subscription = $bot->get_notification()->get_subscription($user, $activity->conversation->tenantId, $activity->channelId, $bot->get_bot_id());
        if ($subscription === null) {
            // This should not happen.
            throw new botfw_exception("Cannot find an appropriate subscription record in the database.");
        }
        $message = builder::message()
            ->text($text)
            ->build();
        // Send a message proactively.
        // bot::reply_to() does not work when the messaging extension is used in other channel than the bot.
        $bot->send_notification_message($subscription, $message);
        return $result;
    }
}
