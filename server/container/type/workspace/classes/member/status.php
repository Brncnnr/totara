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
namespace container_workspace\member;

use totara_engage\query\option\option;

/**
 * Class status
 * @package container_workspace\member
 */
final class status implements option {
    /**
     * Prevent this class from any construction
     * status constructor.
     */
    private function __construct() {
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_code(int $constant): string {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        switch ($constant) {
            case ENROL_USER_ACTIVE:
                return 'ACTIVE';

            case ENROL_USER_SUSPENDED:
                return 'SUSPENDED';

            default:
                throw new \coding_exception("Invalid constant value '{$constant}'");
        }
    }

    /**
     * @return int
     */
    public static function get_active(): int {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        return ENROL_USER_ACTIVE;
    }

    /**
     * @return int
     */
    public static function get_suspended(): int {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        return ENROL_USER_SUSPENDED;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_active(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Value is invalid '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        $status = static::get_active();
        return $status == $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_suspended(int $value): bool {
        if (!static::is_valid($value)) {
            debugging("Value is invalid '{$value}'", DEBUG_DEVELOPER);
            return false;
        }

        $status = static::get_suspended();
        return $status == $value;
    }

    /**
     * @param int $value
     * @return bool
     */
    public static function is_valid(int $value): bool {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        return in_array($value, [ENROL_USER_ACTIVE, ENROL_USER_SUSPENDED]);
    }

    /**
     * @param int $constant
     * @return string
     */
    public static function get_string(int $constant): string {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        switch ($constant) {
            case ENROL_USER_ACTIVE:
                return get_string('active', 'container_workspace');

            case ENROL_USER_SUSPENDED:
                return get_string('suspended', 'container_workspce');

            default:
                throw new \coding_exception("Invalid constant value '{$constant}'");
        }
    }

    /**
     * @param string $constant_name
     * @return int
     */
    public static function get_value(string $constant_name): int {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $constant_name = strtoupper($constant_name);
        switch ($constant_name) {
            case 'ACTIVE':
                return ENROL_USER_ACTIVE;

            case 'SUSPENDED':
                return ENROL_USER_SUSPENDED;

            default:
                throw new \coding_exception("Invalid constant's name '{$constant_name}'");
        }
    }
}
