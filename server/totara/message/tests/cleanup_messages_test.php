<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_message
 */
defined('MOODLE_INTERNAL') || die();

use core\entity\notification;
use totara_message\entity\message_metadata;
use totara_message\task\cleanup_messages_task;

class totara_message_cleanup_messages_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_name_present(): void {
        $task = new cleanup_messages_task();
        self::assertNotEmpty($task->get_name());
    }

    /**
     * @return void
     */
    public function test_execute_for_notifications(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/message/lib.php');

        $this->assertSame(0, $DB->count_records('message'));
        $this->assertSame(0, $DB->count_records('message_metadata'));
        $this->assertSame(0, $DB->count_records('message_read'));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $event = new stdClass();
        $event->userfrom = $user1;
        $event->userto = $user2;
        $event->contexturl = $CFG->wwwroot . '/';
        $event->icon = 'program-approve';
        $event->subject = 'Some alert';
        $event->fullmessage = 'Full alert message';
        $event->fullmessagehtml = '<div style="color:red">Full alert message</div>';
        tm_alert_send($event);

        $event = new stdClass();
        $event->userfrom = $user2;
        $event->userto = $user1;
        $event->contexturl = $CFG->wwwroot . '/';
        $event->icon = 'program-approve';
        $event->subject = 'Some task';
        $event->fullmessage = 'Full task message';
        $event->fullmessagehtml = '<div style="color:red">Full task message</div>';
        tm_task_send($event);

        $this->assertSame(2, $DB->count_records('notifications', ['timeread' => null]));
        $this->assertSame(2, $DB->count_records('message_metadata'));

        // Zero notifications were read
        $this->assertSame(
            0,
            $DB->count_records_sql('SELECT COUNT(1) FROM "ttr_notifications" WHERE timeread IS NOT NULL')
        );

        $DB->delete_records('message');
        $DB->delete_records('notifications');

        $task = new cleanup_messages_task();
        $task->execute();

        $this->assertSame(0, $DB->count_records('message'));
        $this->assertSame(0, $DB->count_records('message_metadata'));
        $this->assertSame(0, $DB->count_records('message_read'));
    }

    /**
     * @return void
     */
    public function test_execute_for_legacy_messages(): void {
        global $DB, $CFG;
        require_once("{$CFG->dirroot}/totara/message/messagelib.php");

        // Create an orphan message metadata, from the old system
        $metadata_one = new message_metadata();
        $metadata_one->messageid = 52;
        $metadata_one->msgtype = TOTARA_MSG_TYPE_QUIZ;
        $metadata_one->msgstatus = TOTARA_MSG_STATUS_OK;
        $metadata_one->processorid = 42;
        $metadata_one->urgency = TOTARA_MSG_URGENCY_NORMAL;
        $metadata_one->save();

        // Create an orphan message metadata, from the old system
        $metadata_two = new message_metadata();
        $metadata_two->messagereadid = 52;
        $metadata_two->msgtype = TOTARA_MSG_TYPE_QUIZ;
        $metadata_two->msgstatus = TOTARA_MSG_STATUS_OK;
        $metadata_two->processorid = 42;
        $metadata_two->urgency = TOTARA_MSG_URGENCY_NORMAL;
        $metadata_two->save();

        self::assertTrue($DB->record_exists('message_metadata', ['id' => $metadata_one->id]));
        self::assertTrue($DB->record_exists('message_metadata', ['id' => $metadata_two->id]));

        // Create a record of notification, that is not an orphan.
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $notification = new notification();
        $notification->useridto = $user_two->id;
        $notification->useridfrom = $user_one->id;
        $notification->subject = 'boom';
        $notification->fullmessage = 'ddd';
        $notification->fullmessageformat = FORMAT_HTML;
        $notification->fullmessagehtml = /** @lang text */'<h1>ddd</h1>';
        $notification->smallmessage = 'dd';

        $notification->save();

        // Create a metadata from notification.
        $notification_metadata = new message_metadata();
        $notification_metadata->notificationid = $notification->id;
        $notification_metadata->msgtype = TOTARA_MSG_TYPE_QUIZ;
        $notification_metadata->msgstatus = TOTARA_MSG_STATUS_OK;
        $notification_metadata->urgency = TOTARA_MSG_URGENCY_NORMAL;
        $notification_metadata->processorid = 42;
        $notification_metadata->save();

        self::assertTrue($DB->record_exists('message_metadata', ['id' => $notification_metadata->id]));

        // Execute the task and check that if the notifiaction metadata is deleted.
        $task = new cleanup_messages_task();
        $task->execute();

        self::assertFalse($DB->record_exists('message_metadata', ['id' => $metadata_one->id]));
        self::assertFalse($DB->record_exists('message_metadata', ['id' => $metadata_two->id]));

        // But not the notification metadata.
        self::assertTrue($DB->record_exists('message_metadata', ['id' => $notification_metadata->id]));
    }
}