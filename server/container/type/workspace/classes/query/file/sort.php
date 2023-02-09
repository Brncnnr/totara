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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\query\file;

use totara_engage\query\option\option;

/**
 * Sort options for file
 */
final class sort implements option {
    /**
     * Sort by recent uploaded.
     * @var int
     */
    public const RECENT = 1;

    /**
     * Sort by file name.
     * @var int
     */
    public const NAME = 2;

    /**
     * Sort by file size.
     * @var int
     */
    public const SIZE = 3;


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
            case static::NAME:
                return 'NAME';

            case static::SIZE:
                return 'SIZE';

            case static::RECENT:
                return 'RECENT';

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
            case static::RECENT:
                return get_string('uploaded_date', 'container_workspace');

            case static::NAME:
                return get_string('name', 'moodle');

            case static::SIZE:
                return get_string('size', 'moodle');

            default:
                throw new \coding_exception("Invalid constant value: '{$constant}'");
        }

    }

    /**
     * @param string $constantname
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
        return in_array($value, [static::NAME, static::SIZE, static::RECENT]);
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_size(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid file sort value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::SIZE == $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_name(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid file sort value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::NAME == $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_recent(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Invalid file sort value '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        return static::RECENT == $value;
    }
}