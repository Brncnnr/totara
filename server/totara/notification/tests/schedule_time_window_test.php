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
use totara_notification\schedule\time_window;

class totara_notification_schedule_time_window_testcase extends testcase {
    /**
     * @return void
     */
    public function test_check_valid_time_window(): void {
        self::assertFalse((new time_window(1, 0))->is_valid());
        self::assertTrue((new time_window(1, 1))->is_valid());
        self::assertTrue((new time_window(0, 1))->is_valid());
    }

    /**
     * @return void
     */
    public function test_validate_time_window(): void {
        $time_window = new time_window(1, 0);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid time frame that the min value is greater than max value");

        $time_window->validate();
    }
}