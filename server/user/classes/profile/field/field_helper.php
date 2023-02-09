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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_user
 */
namespace core_user\profile\field;

final class field_helper {
    /**
     * field_helper constructor.
     * Preventing this class from construction.
     */
    private function __construct() {
    }

    /**
     * Given the short name of the custom field, this function will return a string that is prefixed
     * the string `custom_` with the shortname.
     *
     * We are using this function to concat the string name as it will make the custom field name the same
     * across the system.
     *
     * @param string $short_name
     * @return string
     */
    public static function format_custom_field_short_name(string $short_name): string {
        return "profile_field_{$short_name}";
    }

    /**
     * Given the position, this function will try to prefix the keyword 'position_' to the string return.
     *
     * @param int $position
     * @return string
     */
    public static function format_position_key(int $position = 0): string {
        return "position_{$position}";
    }
}