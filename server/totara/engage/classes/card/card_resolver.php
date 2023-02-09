<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\card;

use totara_engage\local\helper;

final class card_resolver {
    /**
     * @var array
     */
    private static $classes;


    /**
     * card_resolver constructor.
     */
    private function __construct() {
        // Preventing the construction
    }

    private static function init(): void {
        if (null !== static::$classes) {
            return;
        }

        static::$classes = [];
        $classes = \core_component::get_namespace_classes('totara_engage\\card', card::class);

        foreach ($classes as $cls) {
            $component = helper::get_component_name($cls);
            if (isset(static::$classes[$component])) {
                // Prevent multi class to be added to cards.
                debugging("A class for card had already been registered", DEBUG_DEVELOPER);
                continue;
            }

            static::$classes[$component] = $cls;
        }
    }

    /**
     * This function will try to invoke {@see card::create()}
     *
     * @param string $component
     * @param array $record
     * @return card
     */
    public static function create_card(string $component, array $record): card {
        static::init();

        if (!isset(static::$classes[$component])) {
            throw new \coding_exception("No card class found for component '{$component}'");
        }

        $cls = static::$classes[$component];
        return call_user_func([$cls, 'create'], $record);
    }
}