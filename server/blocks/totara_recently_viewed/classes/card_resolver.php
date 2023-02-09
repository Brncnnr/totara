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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package block_totara_recently_viewed
 */

namespace block_totara_recently_viewed;

use coding_exception;
use core_component;

/**
 * Card resolver. Taking the specific component type & id, return an extended
 * instance of the card class, to be used by the template system.
 *
 * @package block_totara_recently_viewed\card
 */
final class card_resolver {
    /**
     * @var array
     */
    private static $classes = [];

    /**
     * @var bool
     */
    private static $loaded = false;

    /**
     * card_resolver constructor.
     */
    private function __construct() {
        // Preventing the construction
    }

    /**
     * @param string $component
     * @param int $instanceid
     * @return card|null
     */
    public static function create_card(string $component, int $instanceid): ?card {
        // Check if this class needs loading
        // All cards remain in this plugin, so we're doing a cheap lookup (instead of fancy expensive lookup)
        if (empty(static::$classes[$component])) {
            $class = "block_totara_recently_viewed\\{$component}\\card";
            if (core_component::class_exists($class) && in_array(card::class, class_implements($class))) {
                static::$classes[$component] = $class;
            } else {
                throw new coding_exception("No valid card class found for component '{$component}'");
            }
        }

        // It's possible for the item to have been removed. We don't want to do a extra lookup or join
        // on interactions, so instead let any dml_missing_record_exception exceptions silently fail instead.
        try {
            $class = static::$classes[$component];
            return call_user_func([$class, 'from_id'], $instanceid);
        } catch(\dml_missing_record_exception $ex) {
            return null;
        }
    }
}