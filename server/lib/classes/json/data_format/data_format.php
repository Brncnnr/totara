<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\json\data_format;

use core\json\type;

/**
 * A data format validator, this does not means we are going to format the data from the output,
 * but to validate the json data from the data format that we expected.
 *
 * For example, we can checked against `PARAM_*`
 */
abstract class data_format {
    /**
     * format constructor.
     */
    final public function __construct() {
        // Prevent the complicate constructor.
    }

    /**
     * Could be the type from the constants declared in {@see type}. This is to tell the schema
     * that the data format is being used for specific primitive types.
     *
     * @return string
     */
    abstract public function get_for_type(): string;

    /**
     * Check the data $value against the data format that we want.
     *
     * @param mixed $value
     * @return bool
     */
    abstract public function validate($value): bool;

    /**
     * @return string
     */
    public static function get_name(): string {
        $parts = explode('\\', static::class);

        $component = reset($parts);
        $name = end($parts);

        if ('core' === $component) {
            return $name;
        }

        return "{$component}-{$name}";
    }
}