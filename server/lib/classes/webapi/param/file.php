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
 * @package core
 */
namespace core\webapi\param;

use core\webapi\param;
use invalid_parameter_exception;

/**
 * Input parameter that is similar to PARAM_FILE.
 * Note: empty string or NUL value will result to NULL
 */
final class file extends param {
    /**
     * @param mixed $filename
     * @return string|null
     */
    public static function parse_value($filename): ?string {
        if ($filename === null || $filename === '') {
            return null;
        }

        $cleaned_filename = clean_param($filename, PARAM_FILE);
        if ($cleaned_filename !== $filename) {
            throw new invalid_parameter_exception();
        }

        return $cleaned_filename;
    }
}