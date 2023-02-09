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

/**
 * Sort options for member
 */
final class sort implements option {
    /**
     * Sort by the recently  join time.
     * @var int
     */
    public const RECENT_JOIN = 1;

    /**
     * Sort by name.
     * @var int
     */
    public const NAME = 2;

    /**
     * sort constructor.
     */
    private function __construct() {
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_code(int $constant): string {
        switch ($constant) {
            case static::RECENT_JOIN:
                return 'RECENT_JOIN';

            case static::NAME:
                return 'NAME';

            default:
                throw new \coding_exception("Invalid constant value: '{$constant}'");
        }
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_string(int $constant): string {
        switch ($constant) {
            case static::RECENT_JOIN:
                return get_string('recent_joined', 'container_workspace');

            case static::NAME:
                return get_string('name', 'moodle');

            default:
                throw new \coding_exception("Invalid constant value: '{$constant}'");
        }
    }

    /**
     * @param string $constant_name
     * @return int
     */
    public static function get_value(string $constant_name): int {
        $constant_name = strtoupper($constant_name);
        $name = "static::{$constant_name}";

        if (!defined($name)) {
            throw new \coding_exception("No constant name found for '{$constant_name}'");
        }

        return constant($name);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_valid(int $value): bool {
        return in_array($value, [static::NAME, static::RECENT_JOIN]);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_recent_join(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid member sort value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::RECENT_JOIN == $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_name(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid member sort value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::NAME == $value;
    }
}