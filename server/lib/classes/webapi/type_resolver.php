<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

namespace core\webapi;

abstract class type_resolver {
    /**
     * General type resolver.

     * @param string $field the field name from type
     * @param mixed $source the date returned from type
     * @param array $args field arguments
     * @param execution_context $ec
     * @return mixed
     */
    abstract public static function resolve(string $field, $source, array $args, execution_context $ec);

    /**
     * Complexity points used when resolving this type. This can be overridden by individual resolvers if required.
     *
     * @return int
     */
    public static function cost_per_call(): int {
        return 1;
    }

    /**
     * Override in resolver with any middleware you want to be applied.
     *
     * @return array|middleware[]
     */
    public static function get_middleware(): array {
        return [];
    }
}