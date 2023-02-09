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

/**
 * Interface single_placeholder is used for representing a single item placeholder. For example, a user, or a course.
 */
interface single_placeholder extends placeholder {
    /**
     * Returns the value as a string that associates with the $key, the $key can be from
     * the list of options. If the value is boolean or integer, then the string value equivalent
     * to these values should be either '1'/'0' (or any equivalent string that the child can use for boolean)
     * or numeric string
     *
     * If is_safe_html is true for the given key, make sure to process any user-entered
     * text with format_string or something similar.
     *
     * @param string $key
     * @return string|null When the result expects an empty string, this should return "",
     * while null should return when the data is not available and will result in "<no data available for $key>".
     */
    public function get(string $key): ?string;
}