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
 * Input parameter equivalent to PARAM_LANG.
 *
 * NOTE: empty strings result in null.
 */
final class lang extends \core\webapi\param {
    /**
     * Parses a value provided by client (usually via variables) to the internal
     * server-side format.
     *
     * @param mixed $value
     * @return string|null
     */
    public static function parse_value($value): ?string {
        if ($value === null or $value === '') {
            return null;
        }
        $cleaned = clean_param($value, PARAM_LANG);
        if ($cleaned !== $value) {
            throw new \invalid_parameter_exception();
        }
        return $cleaned;
    }
}
