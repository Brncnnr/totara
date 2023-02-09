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
 * @package container_workspace
 */
namespace container_workspace\query\workspace;

use totara_engage\query\option\option;

/**
 * Class source
 * @package container_workspace\query
 */
final class source implements option {
    /**
     * @var int
     */
    public const ALL = 1;

    /**
     * @var int
     */
    public const OWNED = 2;

    /**
     * @var int
     */
    public const MEMBER = 3;

    /**
     * @var int
     */
    public const MEMBER_AND_OWNED = 4;

    /**
     * @var int
     */
    public const OTHER = 5;

    /**
     * Preventing this class from being constructed.
     * source constructor.
     */
    private function __construct() {
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_valid(int $value): bool {
        $valid_parameters = [
            static::ALL,
            static::OWNED,
            static::MEMBER,
            static::MEMBER_AND_OWNED,
            static::OTHER
        ];

        return in_array($value, $valid_parameters);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_all(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::ALL === (int) $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_member_and_owned(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::MEMBER_AND_OWNED == $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_member(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::MEMBER == $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_owned_only(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::OWNED == $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_other(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::OTHER == $value;
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_code(int $constant): string {
        switch ($constant) {
            case static::ALL:
                return 'ALL';

            case static::OWNED:
                return 'OWNED';

            case static::MEMBER:
                return 'MEMBER';

            case static::MEMBER_AND_OWNED:
                return 'MEMBER_AND_OWNED';

            case static::OTHER:
                return 'OTHER';

            default:
                throw new \coding_exception("Invalid constant value");
        }
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_string(int $constant): string {
        switch ($constant) {
            case static::ALL:
                return get_string('all', 'container_workspace');

            case static::OWNED:
                return get_string('owner', 'container_workspace');

            case static::MEMBER:
                return get_string('member', 'container_workspace');

            case static::MEMBER_AND_OWNED:
                return get_string('member_and_owned', 'container_workspace');

            case static::OTHER:
                return get_string('non_member', 'container_workspace');

            default:
                throw new \coding_exception("Invalid constant value '{$constant}'");
        }
    }

    /**
     * @param string $constant_name
     * @return int
     */
    public static function get_value(string $constant_name): int {
        $constant_name = strtoupper($constant_name);
        $constant = "static::{$constant_name}";

        if (defined($constant)) {
            return constant($constant);
        }

        throw new \coding_exception("Invalid constant '{$constant_name}'");
    }
}