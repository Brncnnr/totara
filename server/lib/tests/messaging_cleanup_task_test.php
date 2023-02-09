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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */

use core\entity\notification;
use core\task\messaging_cleanup_task;
use totara_core\hook\manager;
use core_message\hook\purge_check_notification_hook;

class core_messaging_cleanup_task_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_clean_the_read_notifications_that_does_not_have_plugin_to_prevent_it(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $time_now = time();

        $notification = new notification();
        $notification->useridfrom = $user_one->id;
        $notification->useridto = $user_two->id;
        $notification->subject = 'boom';
        $notification->fullmessage = 'dd';
        $notification->fullmessagehtml = /** @lang text */ '<h1>dd</h1>';
        $notification->fullmessageformat = FORMAT_MOODLE;
        $notification->smallmessage = 'ddc';

        // Set the read time two hours ago.
        $notification->timeread = $time_now - (3600 * 2);
        $notification->save();

        self::assertTrue($DB->record_exists(notification::TABLE, ['id' => $notification->id]));
        self::assertEquals(1, $DB->count_records(notification::TABLE));
        $task = new messaging_cleanup_task();

        // Just make a current time now for the test.
        $task->phpunit_set_time_now($time_now);

        // We will set the delay of deleting message to one hour.
        set_config('messagingdeletereadnotificationsdelay', 3600);
        $task->execute();

        // Once the task executed, the notification should be removed.
        self::assertFalse($DB->record_exists(notification::TABLE, ['id' => $notification->id]));
        self::assertEquals(0, $DB->count_records(notification::TABLE));
    }

    /**
     * @return void
     */
    public function test_do_not_clean_the_unread_notification(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $notification = new notification();
        $notification->useridfrom = $user_one->id;
        $notification->useridto = $user_two->id;
        $notification->subject = 'boom';
        $notification->fullmessage = 'dd';
        $notification->fullmessagehtml = /** @lang text */ '<h1>dd</h1>';
        $notification->fullmessageformat = FORMAT_MOODLE;
        $notification->smallmessage = 'ddc';
        $notification->save();

        self::assertTrue($DB->record_exists(notification::TABLE, ['id' => $notification->id]));
        self::assertEquals(1, $DB->count_records(notification::TABLE));

        $task = new messaging_cleanup_task();
        $task->phpunit_set_time_now(15);

        set_config('messagingdeletereadnotificationsdelay', 5);
        $task->execute();

        // Once the task executed, the notification should not be purged, as it has not been read yet.
        self::assertTrue($DB->record_exists(notification::TABLE, ['id' => $notification->id]));
        self::assertEquals(1, $DB->count_records(notification::TABLE));
    }


    /**
     * @return void
     */
    public function test_do_not_clean_the_read_notification_that_does_not_hit_the_time_to_delete_yet(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $notification = new notification();
        $notification->useridfrom = $user_one->id;
        $notification->useridto = $user_two->id;
        $notification->subject = 'boom';
        $notification->fullmessage = 'dd';
        $notification->fullmessagehtml = /** @lang text */ '<h1>dd</h1>';
        $notification->fullmessageformat = FORMAT_MOODLE;
        $notification->smallmessage = 'ddc';

        // Mark this time read as 20, so that it can be larger than the time to delete threshold.
        $notification->timeread = 20;
        $notification->save();

        self::assertTrue($DB->record_exists(notification::TABLE, ['id' => $notification->id]));
        self::assertEquals(1, $DB->count_records(notification::TABLE));

        $task = new messaging_cleanup_task();
        $task->phpunit_set_time_now(20);

        // We will set the delay of deleting message to 10, so that we can have a threshold as 10.
        set_config('messagingdeletereadnotificationsdelay', 10);
        $task->execute();

        // Once the task executed, the notification should not be purged, as it has not been read yet.
        self::assertTrue($DB->record_exists(notification::TABLE, ['id' => $notification->id]));
        self::assertEquals(1, $DB->count_records(notification::TABLE));
    }

    /**
     * @return void
     */
    public function test_do_not_clean_the_read_notification_because_of_the_watcher(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $notification = new notification();
        $notification->useridfrom = $user_one->id;
        $notification->useridto = $user_two->id;
        $notification->subject = 'boom';
        $notification->fullmessage = 'dd';
        $notification->fullmessagehtml = /** @lang text */ '<h1>dd</h1>';
        $notification->fullmessageformat = FORMAT_MOODLE;
        $notification->smallmessage = 'ddc';
        $notification->timeread = 5;
        $notification->save();

        self::assertTrue($DB->record_exists(notification::TABLE, ['id' => $notification->id]));
        self::assertEquals(1, $DB->count_records(notification::TABLE));

        // Modify the hook watcher.
        manager::phpunit_replace_watchers([
            [
                'hookname' => purge_check_notification_hook::class,
                'callback' => function (purge_check_notification_hook $hook) use ($notification): void {
                    $hook_notification = $hook->get_notification();
                    if ($hook_notification->id == $notification->id) {
                        $hook->mark_skip_purge();
                    }
                }
            ]
        ]);

        $task = new messaging_cleanup_task();
        $task->phpunit_set_time_now(10);

        set_config('messagingdeletereadnotificationsdelay', 1);
        $task->execute();

        // Once the task executed, the notification should not be purged, as the watcher had been preventing it from happening.
        self::assertTrue($DB->record_exists(notification::TABLE, ['id' => $notification->id]));
        self::assertEquals(1, $DB->count_records(notification::TABLE));
    }
}