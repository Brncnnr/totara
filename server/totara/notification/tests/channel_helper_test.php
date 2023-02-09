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

use core_phpunit\testcase;
use totara_notification\delivery\channel_helper;
use totara_notification\loader\delivery_channel_loader;

class totara_notification_channel_helper_testcase extends testcase {
    /**
     * @return void
     */
    public function test_is_valid_channel_class(): void {
        $channels = delivery_channel_loader::get_built_in_classes();
        foreach ($channels as $channel_clss) {
            self::assertTrue(channel_helper::is_valid_delivery_channel_class($channel_clss));
        }

        self::assertFalse(channel_helper::is_valid_delivery_channel('boom'));
    }

    /**
     * @return void
     */
    public function test_is_valid_channel_identifier(): void {
        $identifiers = array_keys(delivery_channel_loader::get_defaults());
        foreach ($identifiers as $identifier) {
            self::assertTrue(channel_helper::is_valid_delivery_channel($identifier));
        }

        self::assertFalse(channel_helper::is_valid_delivery_channel('lanaya'));
    }

    /**
     * @return void
     */
    public function test_get_delivery_channel_class(): void {
        $delivery_channel_name = 'email';
        $expected_class_name = "message_email\\totara_notification\\delivery\\channel\\delivery_channel";
        $actual_channel_class_name = channel_helper::get_delivery_channel_class($delivery_channel_name);

        self::assertEquals($expected_class_name, $actual_channel_class_name);
    }

    /**
     * @return void
     */
    public function test_get_label(): void {
        $delivery_channel_name = 'email';
        $expected_channel_label = 'Email';

        self::assertTrue($expected_channel_label === channel_helper::get_label($delivery_channel_name));
    }

}