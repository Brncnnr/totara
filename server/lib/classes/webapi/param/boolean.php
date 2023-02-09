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

namespace core\webapi\param;

/**
 * Input parameter similar to PARAM_BOOL.
 *
 * NOTE: empty string means null (value is unknown)
 */
final class boolean extends \core\webapi\param {
    /**
     * Parses a value provided by client (usually via variables) to the internal
     * server-side format.
     *
     * @param mixed $value
     * @return null|int 1 means 'true', 0 means 'false'
     */
    public static function parse_value($value): ?int {
        if ($value === null) {
            return null;
        }
        if ($value === '') {
            // This is different from PARAM_BOOL which returns '0'.
            return null;
        }
        if ($value === true or $value === '1' or $value === 1 or $value === 'on' or $value === 'yes' or $value === 'true') {
            return 1;
        }
        if ($value === false or $value === '0' or $value === 0 or $value === 'off' or $value === 'no' or $value === 'false') {
            return 0;
        }
        throw new \invalid_parameter_exception();
    }
}
