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
namespace totara_engage\access;

use totara_engage\query\option\option;

/**
 * A constant class to validate all the values that are related to the access.
 * Note: this has nothing to do with the access record of engage resources or playlist.
 */
final class access implements option {
    /**
     * Private resource
     * @var int
     */
    public const PRIVATE = 0;

    /**
     * Public resource
     * @var int
     */
    public const PUBLIC = 1;

    /**
     * Only shared with certain people
     * @var int
     */
    public const RESTRICTED = 2;

    /**
     * access constructor.
     */
    private function __construct() {
        // Preventing this class from being constructed.
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_public(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("The value '{$value}' passed in is not a valid value for access", DEBUG_DEVELOPER);
            return false;
        }

        return static::PUBLIC == $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_restricted(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("The value '{$value}' passed in is not a valid value for access", DEBUG_DEVELOPER);
            return false;
        }

        return static::RESTRICTED == $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_private(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("The value '{$value}' passed in is not a valid value for access", DEBUG_DEVELOPER);
            return false;
        }

        return static::PRIVATE == $value;
    }

    /**
     * Checking whether the parameter $accesstype is a valid access value or not.
     *
     * @param int $accesstype
     * @return bool
     */
    public static function is_valid(int $accesstype): bool {
        return in_array($accesstype, [static::PRIVATE, static::PUBLIC, static::RESTRICTED]);
    }

    /**
     * Given the access type, this function will try to return the label
     * that is binding with that access type.
     *
     * @param int $accesstype
     * @return string
     */
    public static function get_string(int $accesstype): string {
        switch ($accesstype) {
            case static::PRIVATE:
                return get_string('private', 'totara_engage');

            case static::PUBLIC:
                return get_string('public', 'totara_engage');

            case static::RESTRICTED:
                return get_string('restricted', 'totara_engage');

            default:
                debugging("Unable to find the string of access type with value '{$accesstype}'", DEBUG_DEVELOPER);
                return "";
        }
    }

    /**
     * Given the access type, this function will give a string associates with that type,
     * which only machine can understand.
     *
     * Note that this string is not meant for human read
     *
     * @param int $accesstype
     * @return string
     */
    public static function get_code(int $accesstype): string {
        switch ($accesstype) {
            case static::PRIVATE:
                return 'PRIVATE';

            case static::PUBLIC:
                return 'PUBLIC';

            case static::RESTRICTED:
                return 'RESTRICTED';

            default:
                throw new \coding_exception("Invalid access type '{$accesstype}'");
        }
    }

    /**
     * @param string $name
     * @return int
     */
    public static function get_value(string $name): int {
        $name = strtoupper($name);
        $constant = "static::{$name}";

        if (!defined($constant)) {
            throw new \coding_exception("The constant '{$constant}' was not defined");
        }

        return constant($constant);
    }
}