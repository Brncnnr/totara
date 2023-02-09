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
namespace container_workspace\query\member;

use totara_engage\query\option\option;

final class member_request_status implements option {
    /**
     * For pending requests.
     * @var int
     */
    public const PENDING = 1;

    /**
     * For accepted requests.
     * @var int
     */
    public const ACCEPTED = 2;

    /**
     * For declined requests.
     * @var int
     */
    public const DECLINED = 3;

    /**
     * For cancelled requests
     * @var int
     */
    public const CANCELLED = 4;

    /**
     * @param int $constant
     * @return string
     */
    public static function get_code(int $constant): string {
        switch ($constant) {
            case static::PENDING:
                return 'PENDING';

            case static::ACCEPTED:
                return 'ACCEPTED';

            case static::DECLINED:
                return 'DECLINED';

            case static::CANCELLED:
                return 'CANCELLED';

            default:
                throw new \coding_exception("Invalid constant's value");
        }
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_string(int $constant): string {
        throw new \coding_exception("Get string function is not supported");
    }

    /**
     * @param string $constant_name
     * @return int
     */
    public static function get_value(string $constant_name): int {
        $constant_name = strtoupper($constant_name);
        $constant = "static::{$constant_name}";

        if (!defined($constant)) {
            throw new \coding_exception("Invalid constant name '{$constant_name}'");
        }

        return constant($constant);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_valid(int $value): bool {
        return in_array($value, [static::PENDING, static::CANCELLED, static::DECLINED, static::ACCEPTED]);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_pending(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid constant value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::PENDING === $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_accepted(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid constant value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::ACCEPTED === $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_declined(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid constant value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::DECLINED === $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_cancelled(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid constant value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::CANCELLED === $value;
    }
}