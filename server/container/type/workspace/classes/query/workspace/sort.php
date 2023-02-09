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
 * Sort options for workspace
 */
final class sort implements option {
    /**
     * @var int
     */
    public const RECENT = 1;

    /**
     * @var int
     */
    public const ALPHABET = 2;

    /**
     * @var int
     */
    public const SIZE = 3;

    /**
     * @param int $constant
     * @return string
     */
    public static function get_code(int $constant): string {
        switch ($constant) {
            case static::RECENT:
                return 'RECENT';

            case static::ALPHABET:
                return 'ALPHABET';

            case static::SIZE:
                return 'SIZE';

            default:
                throw new \coding_exception("Invalid constant value '{$constant}'");
        }
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_string(int $constant): string {
        switch ($constant) {
            case static::RECENT:
                return get_string('recent', 'container_workspace');

            case static::ALPHABET:
                return get_string('alphabet', 'container_workspace');

            case static::SIZE:
                return get_string('size', 'container_workspace');

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

        if (!defined($constant)) {
            throw new \coding_exception("No constant defined '{$constant_name}'");
        }

        return constant($constant);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_valid(int $value): bool {
        return in_array($value, [static::RECENT, static::ALPHABET, static::SIZE]);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_recent(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid constant value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::RECENT == $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_alphabet(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid constant value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::ALPHABET == $value;
    }
}