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

use lang_string;
use totara_msteams\botfw\activity;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\builder;
use totara_msteams\botfw\describable;
use totara_msteams\botfw\dispatchable;

/**
 * A dispatcher for the help command.
 */
class show_help implements dispatchable, describable {
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
        return new lang_string('botfw:help_help', 'totara_msteams');
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
        $entityid = 'help';
        $appid = get_config('totara_msteams', 'manifest_app_id');
        $tab_url = new \moodle_url("https://teams.microsoft.com/l/entity/{$appid}/{$entityid}");

        $message = builder::message()
            ->conversation($activity->conversation)
            ->from($activity->recipient)
            ->recipient($activity->from)
            ->add_attachment(
                builder::hero_card()
                    ->title(get_string('botfw:msg_help_title', 'totara_msteams'))
                    ->text(get_string('botfw:msg_help_body', 'totara_msteams'))
                    ->add_button(
                        builder::action()
                            ->message_back(get_string('botfw:msg_signin_button', 'totara_msteams'), get_string('botfw:cmd_signin', 'totara_msteams'))
                            ->build()
                    )
                    ->add_button(
                        builder::action()
                            ->message_back(get_string('botfw:msg_signout_button', 'totara_msteams'), get_string('botfw:cmd_signout', 'totara_msteams'))
                            ->build()
                    )
                    ->add_button(
                        builder::action()
                            ->title(get_string('botfw:msg_help_button', 'totara_msteams'))
                            ->value($tab_url->out(false))
                            ->build()
                    )
                    ->build()
            );

        $bot->reply_to($activity, $message->build());
    }
}
