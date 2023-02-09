<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */

use totara_notification\placeholder\abstraction\single_placeholder;
use totara_notification\placeholder\option;

class totara_notification_mock_single_placeholder implements single_placeholder {
    /**
     * @var option[]|null
     */
    private static $options;

    /**
     * @var array
     */
    private $map_data;

    /**
     * totara_notification_mock_placeholder constructor.
     * @param array $data A hash map of key and value.
     */
    public function __construct(array $data) {
        $this->map_data = $data;
    }

    /**
     * @param string $key
     * @return string
     */
    public function get(string $key): string {
        return $this->map_data[$key] ?? '';
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        if (!isset(self::$options)) {
            self::$options = [];
        }

        return self::$options;
    }

    /**
     * @param option ...$options
     * @return void
     */
    public static function set_options(option ...$options): void {
        self::$options = $options;
    }

    /**
     * @param option ...$options
     * @return void
     */
    public static function add_options(option ...$options): void {
        if (!isset(self::$options)) {
            self::$options = [];
        }

        self::$options = array_merge(self::$options, $options);
    }

    /**
     * @return void
     */
    public static function clear(): void {
        if (isset(self::$options)) {
            self::$options = null;
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        return false;
    }
}