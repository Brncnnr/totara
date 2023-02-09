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
namespace container_workspace\query\discussion;

use totara_engage\query\option\option;

/**
 * Sort options for discussion
 */
final class sort implements option {
    /**
     * Sort by recent updated.
     * @var int
     */
    public const RECENT = 0;

    /**
     * Sort by time created of the discussion.
     * @var int
     */
    public const DATE_POSTED = 1;

    /**
     * sort constructor.
     * Preventing the class from construction
     */
    private function __construct() {
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_code(int $constant): string {
        switch ($constant) {
            case static::RECENT:
                return 'RECENT';

            case static::DATE_POSTED:
                return 'DATE_POSTED';

            default:
                throw new \coding_exception("Invalid constant value '{$constant}'");
        }
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_string(int $constant): string {
        switch ($constant)  {
            case static::RECENT:
                return get_string('last_updated', 'container_workspace');

            case static::DATE_POSTED:
                return get_string('date_posted', 'container_workspace');

            default:
                throw new \coding_exception("Invalid constant value '{$constant}'");
        }
    }

    /**
     * @param string $constantname
     * @return int
     */
    public static function get_value(string $constantname): int {
        $constantname = strtoupper($constantname);
        $constant = "static::{$constantname}";

        if (!defined($constant)) {
            throw new \coding_exception("Cannot find constant name '{$constantname}'");
        }

        return constant($constant);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_valid(int $value): bool {
        return in_array($value, [static::RECENT, static::DATE_POSTED]);
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
    public static function is_posted_date(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid constant value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::DATE_POSTED == $value;
    }
}