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
 * @package totara_playlist
 */
namespace totara_playlist\query\option;

use totara_engage\query\option\option;

/**
 * The source to tell playlists query whether to search for own only, bookmarked only or all.
 */
final class playlist_source implements option {
    /**
     * @var int
     */
    public const OWN = 1;

    /**
     * @var int
     */
    public const BOOKMARKED = 2;

    /**
     * Preventing this class from construction.
     * source constructor.
     */
    private function __construct() {
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_code(int $constant): string {
        switch ($constant) {
            case static::OWN:
                return 'OWN';

            case static::BOOKMARKED:
                return 'BOOKMARKED';

            default:
                throw new \coding_exception("Invalid constant value that is not existing in the system");
        }
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_string(int $constant): string {
        throw new \coding_exception("get_string for source option is not yet supported");
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_valid(int $value): bool {
        return in_array($value, [static::OWN, static::BOOKMARKED]);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_own(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid constant value '{$value}'");
            return false;
        }

        return static::OWN === $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_bookmarked(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid constant value '{$value}'");
            return false;
        }

        return static::BOOKMARKED === $value;
    }

    /**
     * @param string $constant_name
     * @return int
     */
    public static function get_value(string $constant_name): int {
        $constant_name = strtoupper($constant_name);
        $constant = "static::{$constant_name}";

        if (!defined($constant)) {
            throw new \coding_exception("The constant {$constant_name} is not found");
        }

        return constant($constant);
    }
}