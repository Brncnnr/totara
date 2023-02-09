<?php
/**
 * This file is part of Totara Learn
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package mod_facetoface
 * @category totara_notification
 */

use core_phpunit\testcase;
use mod_facetoface\totara_notification\recipient\reservation_managers as recipient_group;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_facetoface_totara_notification_recipient_reservation_managers_testcase extends testcase {

    /**
     * Test the function fails with invalid args
     */
    public function test_missing_args(): void {
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Missing reservation_manager_ids');

        recipient_group::get_user_ids([]);
    }

    /**
     * Test the function returns the given input.
     */
    public function test_result(): void {
        $test_user_ids = [123, 456, 789];
        $user_ids = recipient_group::get_user_ids(['reservation_manager_ids' => $test_user_ids]);
        $this->assertEquals($test_user_ids, $user_ids);
    }
}