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
 * @author  Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_notification
 */

use totara_notification\delivery\channel\delivery_channel;

/**
 * A mock delivery channel class.
 */
class totara_notification_mock_delivery_channel_third extends delivery_channel {
    /**
     * @var array
     */
    private static $attributes = [];

    /**
     * @return string
     */
    public static function get_component(): string {
        return static::$attributes['component'] ?? 'third';
    }

    /**
     * @return string|null
     */
    public static function get_parent(): ?string {
        return static::$attributes['parent'] ?? null;
    }

    /**
     * @return string
     */
    public static function get_label(): string {
        return 'the third mock channel';
    }

    /**
     * @return int
     */
    public static function get_display_order(): int {
        return 30;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public static function set_attribute(string $key, $value): void {
        static::$attributes[$key] = $value;
    }

    /**
     * @return void
     */
    public static function clear(): void {
        static::$attributes = [];
    }
}