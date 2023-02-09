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
 * Scalar representing database id fields.
 */
final class id extends \core\webapi\scalar {
    /**
     * Parse database record id.
     *
     * @param mixed $value
     * @return int|null
     */
    public static function parse_value($value): ?int {
        if ($value === 0 or $value === '0' or $value === '' or $value === null) {
            return null;
        }
        if (!is_number($value)) {
            throw new \invalid_parameter_exception();
        }
        $value = intval($value);
        if ($value < 0) {
            throw new \invalid_parameter_exception();
        }
        return $value;
    }

    /**
     * Serializes a server-side 'id' value to null or numeric string.
     *
     * @param mixed $value
     * @return string|null
     */
    public static function serialize($value): ?string {
        if (!$value or $value < 0) {
            return null;
        }
        // NOTE: we cannot use integers because they are too small in Javascript.
        return (string)intval($value);
    }
}
