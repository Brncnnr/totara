<?php
/**
 * This file is part of Totara Learn
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\placeholder\abstraction;

use totara_notification\placeholder\option;

/**
 * Interface placeholder class is used to help resolve the value for
 * any given placeholder key.
 *
 * Each of the placeholder child class will be responsible to given the list of
 * available placeholder keys and also be able to given the value for these keys from
 * the data (mainly given from notifiable event data).
 *
 * The placeholder class is only  be responsible for
 */
interface placeholder {
    /**
     * Returns the list of {@see option} instances.
     *
     * @return option[]
     */
    public static function get_options(): array;

    /**
     * Checks if we are expecting html content from the value that associated with
     * the $key or not.
     *
     * If the corresponding placeholder value comes from user input then make sure to clean it
     * in your "get" function, using format_string or something similar.
     *
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool;
}