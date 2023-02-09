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

namespace totara_msteams\my;

use lang_string;
use totara_core\advanced_feature;
use totara_msteams\botfw\activity;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\describable;
use totara_msteams\botfw\router\dynamic_router;
use totara_msteams\botfw\router\route;
use totara_msteams\my\dispatcher\cant_hear_you;
use totara_msteams\my\dispatcher\conversation_update;
use totara_msteams\my\dispatcher\messaging_extension;
use totara_msteams\my\dispatcher\private_only;
use totara_msteams\my\dispatcher\show_help;
use totara_msteams\my\dispatcher\signin_request;
use totara_msteams\my\dispatcher\signin_verify;
use totara_msteams\my\dispatcher\signout_request;

/**
 * The router.
 */
class router extends dynamic_router {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->add(['type' => 'invoke', 'name' => 'signin/verifyState'], new signin_verify());
        $this->add(['type' => 'invoke', 'name' => 'composeExtension/query'], new messaging_extension(), route::QUIET | route::TEAM | route::EXTENSION);
        $this->add(['type' => 'conversationUpdate'], new conversation_update(), route::QUIET);
        $this->add(['type' => 'message', 'text' => new lang_string('botfw:cmd_help', 'totara_msteams')], new show_help());
        $this->add(['type' => 'message', 'text' => new lang_string('botfw:cmd_signin', 'totara_msteams')], new signin_request());
        $this->add(['type' => 'message', 'text' => new lang_string('botfw:cmd_signout', 'totara_msteams')], new signout_request());
        $this->add(['type' => 'message'], new cant_hear_you());
        $this->add(['type' => 'message'], new private_only(), route::TEAM);
    }

    /**
     * @return array
     */
    public function get_command_list(): array {
        if (advanced_feature::is_disabled('totara_msteams')) {
            return [];
        }

        $return = [];
        foreach ($this->get_routes_internal() as [$selector, $route]) {
            $dispatcher = $route->get_dispatcher();
            if ($dispatcher instanceof describable) {
                $command = $dispatcher->get_name() ?? $selector['text'];
                $shortdesc = $dispatcher->get_description();
                $return[] = [$command, $shortdesc];
            }
        }
        return $return;
    }

    /**
     * @param string $classname
     * @param bot $bot
     * @param activity $activity
     * @return boolean
     */
    public function direct_dispatch(string $classname, bot $bot, activity $activity): bool {
        foreach ($this->get_routes() as $route) {
            if (get_class($route->get_dispatcher()) === $classname) {
                $route->dispatch($bot, $activity);
                return true;
            }
        }
        $bot->get_logger()->debug("dispatcher '{$classname}' not found");
        return false;
    }
}
