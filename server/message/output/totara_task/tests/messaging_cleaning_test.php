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
 * @package message_totara_task
 */

use core\entity\notification;
use core\task\messaging_cleanup_task;
use totara_message\entity\message_metadata;

class message_totara_task_messaging_cleaning_testcase extends advanced_testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/message/messagelib.php");
        require_once("{$CFG->dirroot}/message/output/totara_task/message_output_totara_task.php");
    }

    /**
     * @return void
     */
    public function test_do_not_purge_the_notification_because_message_metadata_is_not_yet_read(): void {
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

        // Create message metadata record.
        $metadata = new message_metadata();
        $metadata->notificationid = $notification->id;
        $metadata->msgstatus = TOTARA_MSG_STATUS_OK;
        $metadata->msgtype = TOTARA_MSG_TYPE_UNKNOWN;
        $metadata->urgency = TOTARA_MSG_URGENCY_NORMAL;
        $metadata->processorid = message_output_totara_task::get_processor_id();
        $metadata->save();

        self::assertTrue($DB->record_exists(message_metadata::TABLE, ['id' => $metadata->id]));
        self::assertEquals(1, $DB->count_records(message_metadata::TABLE));

        $task = new messaging_cleanup_task();
        $task->phpunit_set_time_now(20);

        set_config('messagingdeletereadnotificationsdelay', 10);
        $task->execute();

        // Once the task executed, the notification should be removed.
        self::assertTrue($DB->record_exists(notification::TABLE, ['id' => $notification->id]));
        self::assertEquals(1, $DB->count_records(notification::TABLE));

        self::assertTrue($DB->record_exists(message_metadata::TABLE, ['id' => $metadata->id]));
        self::assertEquals(1, $DB->count_records(message_metadata::TABLE));
    }

    /**
     * @return void
     */
    public function test_do_purge_the_notification_because_message_metadata_is_read(): void {
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

        // Create message metadata record.
        $metadata = new message_metadata();
        $metadata->notificationid = $notification->id;
        $metadata->msgstatus = TOTARA_MSG_STATUS_OK;
        $metadata->msgtype = TOTARA_MSG_TYPE_UNKNOWN;
        $metadata->urgency = TOTARA_MSG_URGENCY_NORMAL;
        $metadata->processorid = message_output_totara_task::get_processor_id();
        $metadata->timeread = 1;
        $metadata->save();

        self::assertTrue($DB->record_exists(message_metadata::TABLE, ['id' => $metadata->id]));
        self::assertEquals(1, $DB->count_records(message_metadata::TABLE));

        $task = new messaging_cleanup_task();
        $task->phpunit_set_time_now(20);

        set_config('messagingdeletereadnotificationsdelay', 10);
        $task->execute();

        // Once the task executed, the notification should be removed.
        self::assertFalse($DB->record_exists(notification::TABLE, ['id' => $notification->id]));
        self::assertEquals(0, $DB->count_records(notification::TABLE));

        // The message metadata should not be purged.
        self::assertTrue($DB->record_exists(message_metadata::TABLE, ['id' => $metadata->id]));
        self::assertEquals(1, $DB->count_records(message_metadata::TABLE));
    }
}