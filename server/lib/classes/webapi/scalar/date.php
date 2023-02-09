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

namespace core\webapi\scalar;

/**
 * Scalar representing internal timestamps.
 */
final class date extends \core\webapi\scalar {
    /**
     * Number is a timestamp, ISO-8061 string is the only other fully supported option,
     * other formats recognised by PHP DataTime class will likely work too.
     *
     * Current user timezone is used if timezone not specified.
     *
     * @param mixed $value
     * @return int|null
     */
    public static function parse_value($value): ?int {
        if ($value === 0 or $value === '0' or $value === '' or $value === null) {
            return null;
        }
        if (is_number($value)) {
            return intval($value);
        }
        if (!is_string($value)) {
            throw new \invalid_parameter_exception();
        }
        $value = trim($value);
        if ($value === '' or is_number($value)) {
            throw new \invalid_parameter_exception();
        }
        $date = date_create($value, \core_date::get_user_timezone_object());
        if ($date === false) {
            throw new \invalid_parameter_exception();
        }
        return $date->getTimestamp();
    }

    /**
     * Serializes a server-side value
     *   - unix timestamp
     *   - ISO-8061 string
     *   - userdate() string
     *
     * to null or string.
     *
     * @param mixed $value
     * @return string|null
     */
    public static function serialize($value): ?string {
        if ($value === 0 or $value === '0' or $value === null or trim($value) === '') {
            return null;
        }
        // NOTE: Javascript integer is too small for timestamps, so always use strings.
        return (string)$value;
    }
}
