<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\action;

/**
 * Factory to prove access to the action classes.
 */
class factory {
    /**
     * A list of the actions provided by this component.
     *
     * @var string[]
     */
    protected static array $concrete_actions = [
        delete_user::class,
    ];

    /**
     * Create a new instance of the specified action.
     *
     * @param string $class
     * @return action_contract
     */
    public static function create(string $class): action_contract {
        $action = new $class();
        if (!$action instanceof action_contract) {
            throw new \coding_exception("Cannot create an action that is not a valid action_contract. Saw '$class'");
        }

        return $action;
    }

    /**
     * Return a list of valid actions, class => name
     *
     * @return array
     */
    public static function get_actions(): array {
        // Eventually we can use the core_component functions to do a namespace search and find any actions.
        $actions = [];
        foreach (self::$concrete_actions as $action_class) {
            $actions[$action_class] = call_user_func([$action_class, 'get_name']);
        }

        return $actions;
    }

    /**
     * @param string $identifier
     * @return string
     */
    public static function get_name(string $identifier): string {
        if (!self::is_valid($identifier)) {
            return get_string('unknown_action', 'totara_useraction');
        }

        return self::get_actions()[$identifier];
    }

    /**
     * Check if the specific action is valid or not.
     *
     * @param string $identifier
     * @return bool
     */
    public static function is_valid(string $identifier): bool {
        if (empty($identifier)) {
            return false;
        }

        $actions = self::get_actions();
        return isset($actions[$identifier]);
    }
}
