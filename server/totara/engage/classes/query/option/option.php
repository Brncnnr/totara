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
namespace totara_engage\query\option;

interface option {
    /**
     * Given the integer constant value, this function should be able to return
     * the string value that machine can also understand.
     *
     * @param int $constant
     * @return string
     */
    public static function get_code(int $constant): string;

    /**
     * Given the integer constant value, this function should be able to return
     * a label string value that human can undertand. Not used for machine to understand.
     *
     * @param int $constant
     * @return string
     */
    public static function get_string(int $constant): string;

    /**
     * Given the string constant name, this function should be able to return
     * an integer constant value that associates with that name. The string constant name
     * must be something that a machine can understand.
     *
     * @param string $constantname
     * @return int
     */
    public static function get_value(string $constantname): int;
}