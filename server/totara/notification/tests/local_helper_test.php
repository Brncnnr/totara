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
use totara_notification\local\helper;
use totara_notification\testing\generator;

class totara_notification_local_helper_testcase extends testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/notification/tests/fixtures/totara_notification_mock_notifiable_event.php");
    }

    /**
     * @return void
     */
    public function test_check_valid_notifiable_event_with_non_existing_class(): void {
        self::assertFalse(helper::is_valid_notifiable_event('kboom'));
    }

    /**
     * @return void
     */
    public function test_check_valid_notifiable_event_with_existing_class(): void {
        self::assertTrue(helper::is_valid_notifiable_event(totara_notification_mock_notifiable_event::class));
    }

    /**
     * @return void
     */
    public function test_check_built_in_notification(): void {
        /** @var generator $generator */
        $generator = self::getDataGenerator()->get_plugin_generator('totara_notification');
        $generator->include_mock_built_in_notification();

        self::assertFalse(helper::is_valid_built_in_notification('boom'));
        self::assertTrue(
            helper::is_valid_built_in_notification(
                totara_notification_mock_built_in_notification::class
            )
        );

        self::assertTrue(
            helper::is_valid_built_in_notification(
                '\\totara_notification_mock_built_in_notification'
            )
        );
    }
}