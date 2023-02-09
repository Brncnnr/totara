<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Arshad Anwer <arshad.anwer@totara.com>
 * @package totara_api
 */

namespace totara_api;


/**
 * Constant class to convert value related to response debug.
 */
final class response_debug {
    /**
     * Return generic error with no specific information.
     * @var int
     */
    public const ERROR_RESPONSE_LEVEL_NONE = 0;

    /**
     * Return the type of error (default).
     * @var int
     */
    public const ERROR_RESPONSE_LEVEL_NORMAL = 1;

    /**
     * Return error with stack trace for developers.
     * @var int
     */
    public const ERROR_RESPONSE_LEVEL_DEVELOPER = 2;

    /**
     * @param int $debugtype
     * @return string
     */
    public static function get_string(?int $debugtype): ?string {
        if (is_null($debugtype)) {
            return null;
        }

        switch ($debugtype) {
            case self::ERROR_RESPONSE_LEVEL_NONE:
                return "NONE";
            case self::ERROR_RESPONSE_LEVEL_NORMAL:
                return 'NORMAL';
            case self::ERROR_RESPONSE_LEVEL_DEVELOPER:
                return 'DEVELOPER';
            default:
                debugging("Unable to find the relevant match of debug type with value '{$debugtype}'", DEBUG_DEVELOPER);
                return null;
        }
    }

    /**
     * @param int $debugtype
     * @return int
     */
    public static function get_value(?string $debugtype): ?int {
        if (is_null($debugtype)) {
            return null;
        }

        switch ($debugtype) {
            case "NONE":
                return self::ERROR_RESPONSE_LEVEL_NONE;
            case 'NORMAL':
                return self::ERROR_RESPONSE_LEVEL_NORMAL;
            case 'DEVELOPER':
                return self::ERROR_RESPONSE_LEVEL_DEVELOPER;
            default:
                debugging("Unable to find the relevant match of debug type with value '{$debugtype}'", DEBUG_DEVELOPER);
                return null;
        }
    }
}