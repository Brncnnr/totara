<?php
/**
 * This file is part of Totara Perform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_perform
 * @category totara_notification
 */

use core_phpunit\testcase;
use mod_perform\totara_notification\recipient\subject as subject_user_group;

defined('MOODLE_INTERNAL') || die();

/**
 * @group mod_perform
 * @group totara_notification
 */
class mod_perform_totara_notification_recipient_subject_user_testcase extends testcase {
    /**
     * Test the function fails with invalid args
     */
    public function test_missing_args(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Missing subject_user_id");

        subject_user_group::get_user_ids([]);
    }

    /**
     * Test the function returns the given input.
     */
    public function test_result(): void {
        $user_ids = subject_user_group::get_user_ids(['subject_user_id' => 123]);
        $this->assertEquals([123], $user_ids);
    }
}
