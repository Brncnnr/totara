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

use core\entity\user;
use core_phpunit\testcase;
use totara_notification\placeholder\placeholder_helper;
use totara_notification\testing\generator;
use totara_notification_mock_single_placeholder as single_placeholder;

class totara_notification_placeholder_helper_testcase extends testcase {
    /**
     * @return void
     */
    public function test_check_on_valid_placeholder_class(): void {
        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $generator->include_mock_notifiable_event();

        $generator->include_mock_single_placeholder();

        self::assertTrue(placeholder_helper::is_valid_placeholder_class(single_placeholder::class));
        self::assertFalse(placeholder_helper::is_valid_placeholder_class('world'));
        self::assertFalse(placeholder_helper::is_valid_placeholder_class(user::class));
    }
}
