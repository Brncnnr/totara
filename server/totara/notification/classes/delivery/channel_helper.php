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
namespace totara_notification\delivery;

use coding_exception;
use totara_notification\delivery\channel\delivery_channel;

class channel_helper {
    /**
     * @var array|null
     */
    private static $valid_channels = null;

    /**
     * channel_helper constructor.
     * Preventing this class from instantiation.
     */
    private function __construct() {
    }

    /**
     * Checks whether the given class name is the valid delivery channel class or not.
     * @param string $delivery_channel_class_name
     * @return bool
     */
    public static function is_valid_delivery_channel_class(string $delivery_channel_class_name): bool {
        return is_a($delivery_channel_class_name, delivery_channel::class, true);
    }

    /**
     * Checks whether the given string identifier is the valid delivery channel or not.
     * By checking its static class built-up from the identifier string.
     *
     * @param string $component_name
     * @return bool
     */
    public static function is_valid_delivery_channel(string $component_name): bool {
        // Bypass validation for some specific channels. These are only available during unit tests.
        if (null !== self::$valid_channels) {
            if (!defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
                throw new coding_exception('Can only override the delivery channel validator inside a unit test.');
            }
            return in_array($component_name, self::$valid_channels, true);
        }

        return self::is_valid_delivery_channel_class(
            "message_{$component_name}\\totara_notification\\delivery\\channel\\delivery_channel"
        );
    }

    /**
     * Return the human-readable name of the delivery channel.
     *
     * @param string $delivery_channel
     * @return string
     */
    public static function get_label(string $delivery_channel): string {
        /** @var delivery_channel $delivery_channel_class_name */
        $delivery_channel_class_name = self::get_delivery_channel_class($delivery_channel);
        return $delivery_channel_class_name::get_label();
    }

    /**
     * Return valid delivery channel class name.
     *
     * @param string $delivery_channel
     * @return string
     */
    public static function get_delivery_channel_class(string $delivery_channel): string {
        if (!self::is_valid_delivery_channel($delivery_channel)) {
            throw new coding_exception('The delivery channel '.  $delivery_channel . ' is not valid');
        }

        return "message_{$delivery_channel}\\totara_notification\\delivery\\channel\\delivery_channel";
    }
}