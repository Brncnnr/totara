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
use totara_msteams\botfw\builder;
use totara_msteams\botfw\dispatchable;

/**
 * A dispatcher that is called when the conversation update activity is received.
 * https://docs.microsoft.com/en-us/azure/bot-service/bot-builder-howto-proactive-message
 */
class conversation_update implements dispatchable {
    /**
     * @inheritDoc
     */
    public function dispatch(bot $bot, activity $activity): void {
        // The conversationUpdate event is fired at both membersAdded and membersRemoved.
        // We only care about membersAdded.
        if (empty($activity->membersAdded)) {
            return;
        }

        $bot_id = $bot->get_bot_id();
        foreach ($activity->membersAdded as $member) {
            $id = $member->id;
            if ($id == $bot_id) {
                // Do not send a welcome message to the bot.
                continue;
            }
            $message = builder::message()
                ->conversation($activity->conversation)
                ->from($bot->get_account())
                ->recipient($member)
                ->add_attachment(builder::hero_card()
                    ->text(get_string('botfw:msg_welcome', 'totara_msteams'))
                    ->add_button(builder::action()
                        ->message_back(get_string('botfw:msg_signin_button', 'totara_msteams'), get_string('botfw:cmd_signin', 'totara_msteams'))
                        ->build())
                    ->build())
                ->build();
            $message->replyToId = $member->id;
            $bot->reply_to($activity, $message);
        }
    }
}
