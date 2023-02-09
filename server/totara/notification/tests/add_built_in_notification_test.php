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
use totara_notification\entity\notification_preference as entity;
use totara_notification\factory\built_in_notification_factory;

class totara_notification_add_built_in_notification_testcase extends testcase {
    /**
     * @return void
     */
    public function test_sync_built_in_notifications_from_system(): void {
        global $DB, $CFG;
        $DB->delete_records(entity::TABLE);

        self::assertEquals(0, $DB->count_records(entity::TABLE));
        require_once("{$CFG->dirroot}/totara/notification/db/upgradelib.php");

        totara_notification_sync_built_in_notification();
        self::assertNotEquals(0, $DB->count_records(entity::TABLE));

        $all_built_in = built_in_notification_factory::get_notification_classes();
        self::assertCount(
            $DB->count_records(entity::TABLE),
            $all_built_in
        );
    }
}