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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\share;

abstract class provider {
    /**
     * provider constructor.
     */
    final public function __construct() {
        // Simple construction.
    }

    /**
     * Create an instance of the specific share provider.
     *
     * @param $component
     * @return provider
     */
    public static function create($component): provider {
        $classes = \core_component::get_namespace_classes(
            'totara_engage\\share',
            self::class,
            $component
        );

        // No provider found for component.
        if (empty($classes)) {
            throw new \coding_exception("No provider found for component '{$component}'");
        }

        // More than one provider found.
        if (sizeof($classes) > 1) {
            debugging("More than one provider found for type '{$component}'");
        }

        // Return an instance of the provider.
        $cls = reset($classes);
        return new $cls();
    }

    /**
     * @param int $id
     * @return shareable
     */
    abstract public function get_item_instance(int $id): shareable;

    /**
     * Update the access of the item in order for the user to share it.
     * @param shareable $instance
     * @param int $access
     * @param int $userid
     */
    abstract public function update_access(shareable $instance, int $access, int $userid): void;

    /**
     *  To get the provider type.
     * @return string
     */
    abstract public function get_provider_type(): string;
}