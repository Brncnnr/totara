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
 * @package core_container
 */

use core_container\hook\base_redirect;

/**
 * A fix watcher to help us mock testing the redirect hook
 */
final class container_redirect_watcher {
    /**
     * @var int
     */
    private static $total_redirect;

    /**
     * @return int
     */
    public static function get_total_redirect(): int {
        if (!isset(self::$total_redirect)) {
            self::$total_redirect = 0;
        }

        return self::$total_redirect;
    }

    /**
     * @return void
     */
    public static function reset_counter(): void {
        self::$total_redirect = 0;
    }

    /**
     * @param base_redirect $hook
     * @return void
     */
    public static function redirect_me(base_redirect $hook): void {
        global $CFG;

        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            throw new coding_exception("Cannot run the code outside of phpunit environment");
        }

        require_once("{$CFG->dirroot}/container/tests/fixtures/core_container_mock_container.php");
        $container_type = $hook->get_container()->containertype;

        if ($container_type === core_container_mock_container::class) {
            if (!isset(self::$total_redirect)) {
                self::$total_redirect = 0;
            }

            static::$total_redirect += 1;
        }
    }
}