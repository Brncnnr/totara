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
 * Workspace type - whether it is public, private or hidden.
 */
final class access implements option {
    /**
     * @var int
     */
    public const PUBLIC = 1;

    /**
     * @var int
     */
    public const PRIVATE = 2;

    /**
     * @var int
     */
    public const HIDDEN = 3;

    /**
     * @param int $constant
     * @return string
     */
    public static function get_code(int $constant): string {
        switch ($constant) {
            case static::PUBLIC:
                return 'PUBLIC';

            case static::PRIVATE:
                return 'PRIVATE';

            case static::HIDDEN:
                return 'HIDDEN';

            default:
                throw new \coding_exception("Cannot find code for constant value '{$constant}'");
        }
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_string(int $constant): string {
        switch ($constant) {
            case static::PUBLIC:
                return get_string('public', 'container_workspace');

            case static::PRIVATE:
                return get_string('private', 'container_workspace');

            case static::HIDDEN:
                return get_string('hidden', 'container_workspace');

            default:
                throw new \coding_exception("Undefined constant value '{$constant}'");
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
            throw new \coding_exception("No constant '{$constant}' was found");
        }

        return constant($constant);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_valid(int $value): bool {
        return in_array($value, [static::HIDDEN, static::PRIVATE, static::PUBLIC]);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_hidden(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid type value '{$value}'");
            return false;
        }

        return static::HIDDEN === $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_public(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid type value '{$value}'");
            return false;
        }

        return static::PUBLIC === $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_private(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid type value '{$value}'");
            return false;
        }

        return static::PRIVATE === $value;
    }

    /**
     * @param string $constant_name
     * @return bool
     */
    public static function is_valid_code(string $constant_name): bool {
        $constant_name = strtoupper($constant_name);
        $constant = "static::{$constant_name}";

        return defined($constant);
    }
}