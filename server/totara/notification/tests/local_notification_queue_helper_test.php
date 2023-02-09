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

use totara_notification\local\notification_queue_helper;
use totara_notification\testing\generator;
use totara_notification\entity\notification_queue;
use core_phpunit\testcase;

class totara_notification_local_notification_queue_helper_testcase extends testcase {
    /**
     * @return void
     */
    public function test_create_queue_with_valid_event(): void {
        global $DB;

        $generator = generator::instance();
        $preference = $generator->add_mock_built_in_notification_for_component();

        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        notification_queue_helper::create_queue_from_preference(
            $preference,
            [
                'expected_context_id' => 123,
            ],
            DAYSECS * 5
        );

        self::assertEquals(1, $DB->count_records(notification_queue::TABLE));
        self::assertTrue(
            $DB->record_exists(
                notification_queue::TABLE,
                ['notification_preference_id' => $preference->get_id()]
            )
        );
    }
}

