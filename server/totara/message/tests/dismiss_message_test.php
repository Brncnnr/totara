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
 * @package totara_message
 */

use core\entity\notification;
use totara_message\entity\message_metadata;

class totara_message_dismiss_message_testcase extends advanced_testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/message/messagelib.php");
    }

    /**
     * @return void
     */
    public function test_dismiss_message_should_mark_the_notification_read(): void {
        [$notification, $metadata, $totara_task_id] = $this->setup_notification_data();

        // Marking the message metadata as read should mark the notification as read too.
        $time_read = 100;

        $notification_record = $notification->get_record();
        $notification_record->notification = 1;

        tm_message_mark_message_read($notification_record, $time_read, null, $totara_task_id);

        $notification->refresh();
        $metadata->refresh();

        $this->assert_notification_dismissed($notification, $metadata);
        self::assertEquals($time_read, $metadata->timeread);
        self::assertEquals($time_read, $notification->timeread);
    }

    /**
     * @return void
     */
    public function test_accepting_a_task_should_dismiss_the_notification(): void {
        [$notification, $metadata] = $this->setup_notification_data();

        $time_read = time();

        tm_message_task_accept($notification->id, 'Reason for accepting');

        $notification->refresh();
        $metadata->refresh();

        $this->assert_notification_dismissed($notification, $metadata);
        self::assertGreaterThanOrEqual($time_read, $metadata->timeread);
        self::assertGreaterThanOrEqual($time_read, $notification->timeread);
    }

    /**
     * @return void
     */
    public function test_rejecting_a_task_should_dismiss_the_notification(): void {
        [$notification, $metadata] = $this->setup_notification_data();

        $time_read = time();

        tm_message_task_reject($notification->id, 'Reason for accepting');

        $notification->refresh();
        $metadata->refresh();

        $this->assert_notification_dismissed($notification, $metadata);
        self::assertGreaterThanOrEqual($time_read, $metadata->timeread);
        self::assertGreaterThanOrEqual($time_read, $notification->timeread);
    }

    /**
     * @return void
     */
    public function test_dismiss_message_without_processor_id_should_dismiss_all(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $notification = new notification();
        $notification->useridfrom = $user_one->id;
        $notification->useridto = $user_two->id;
        $notification->fullmessage = 'double damage';
        $notification->fullmessageformat = FORMAT_HTML;
        $notification->fullmessagehtml = /** @lang text */'<p>double damage</p>';
        $notification->smallmessage = 'dd';

        $notification->save();
        $notification->refresh();

        self::assertEquals(
            1,
            $DB->count_records(
                notification::TABLE,
                [
                    'id' => $notification->id,
                    'timeread' => null
                ]
            )
        );

        // Create two different metadata for totara_task and totara_alert processors.
        // Creating task metadata
        $task_metadata = new message_metadata();
        $task_metadata->notificationid = $notification->id;
        $task_metadata->msgtype = TOTARA_MSG_TYPE_UNKNOWN;
        $task_metadata->msgstatus = TOTARA_MSG_STATUS_OK;
        $task_metadata->urgency = TOTARA_MSG_URGENCY_LOW;
        $task_metadata->processorid = $DB->get_field('message_processors', 'id', ['name' => 'totara_task']);
        $task_metadata->save();
        $task_metadata->refresh();

        // Create alert metadata
        $alert_metadata = new message_metadata();
        $alert_metadata->notificationid = $notification->id;
        $alert_metadata->msgtype = TOTARA_MSG_TYPE_UNKNOWN;
        $alert_metadata->msgstatus = TOTARA_MSG_STATUS_OK;
        $alert_metadata->urgency = TOTARA_MSG_URGENCY_LOW;
        $alert_metadata->processorid = $DB->get_field('message_processors', 'id', ['name' => 'totara_alert']);
        $alert_metadata->save();
        $alert_metadata->refresh();

        self::assertEquals(2, $DB->count_records(message_metadata::TABLE, ['timeread' => null]));
        self::assertNull($task_metadata->timeread);
        self::assertNull($alert_metadata->timeread);
        self::assertNull($notification->timeread);

        $notification_record = $notification->get_record();
        $notification_record->notification = 1;

        $time_read = time();
        tm_message_mark_message_read($notification_record, $time_read);

        // The function above should mark the metadata for alert and task processor to be read.
        $notification->refresh();
        $task_metadata->refresh();
        $alert_metadata->refresh();

        self::assertEquals($time_read, $notification->timeread);
        self::assertEquals($time_read, $task_metadata->timeread);
        self::assertEquals($time_read, $alert_metadata->timeread);

        self::assertEquals(0, $DB->count_records(message_metadata::TABLE, ['timeread' => null]));
    }

    private function setup_notification_data(): array {
        global $DB;

        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $totara_task_id = $DB->get_field('message_processors', 'id', ['name' => 'totara_task']);

        self::assertEquals(0, $DB->count_records(notification::TABLE));
        self::assertEquals(0, $DB->count_records(message_metadata::TABLE));

        $notification = new notification();
        $notification->useridfrom = $user_one->id;
        $notification->useridto = $user_two->id;
        $notification->fullmessage = 'full me';
        $notification->fullmessagehtml = /** @lang text */'<p>full me</p>';
        $notification->fullmessageformat = FORMAT_MOODLE;
        $notification->smallmessage = 'full me';
        $notification->save();

        // Reload the notification, to have other fields appear.
        $notification->refresh();

        self::assertEquals(1, $DB->count_records(notification::TABLE));
        self::assertTrue(
            $DB->record_exists(
                notification::TABLE, [
                    'id' => $notification->id,
                    'timeread' => null
                ]
            )
        );

        self::assertNull($notification->timeread);

        $metadata = new message_metadata();
        $metadata->notificationid = $notification->id;
        $metadata->msgtype = TOTARA_MSG_TYPE_UNKNOWN;
        $metadata->msgstatus = TOTARA_MSG_STATUS_OK;
        $metadata->urgency = TOTARA_MSG_URGENCY_LOW;
        $metadata->processorid = $totara_task_id;
        $metadata->save();

        // Reload the metadata, to have other fields appear
        $metadata->refresh();

        self::assertEquals(1, $DB->count_records(message_metadata::TABLE));
        self::assertTrue(
            $DB->record_exists(
                message_metadata::TABLE,
                [
                    'id' => $metadata->id,
                    'timeread' => null
                ]
            )
        );

        self::assertNull($notification->timeread);

        return [$notification, $metadata, $totara_task_id];
    }

    /**
     * @param notification $notification
     * @param message_metadata $metadata
     */
    private function assert_notification_dismissed(notification $notification, message_metadata $metadata): void {
        global $DB;

        self::assertNotNull($metadata->timeread);
        self::assertFalse(
            $DB->record_exists(
                message_metadata::TABLE,
                [
                    'id' => $metadata->id,
                    'timeread' => null
                ]
            )
        );

        self::assertNotNull($notification->timeread);
        self::assertFalse(
            $DB->record_exists(
                notification::TABLE,
                [
                    'id' => $notification->id,
                    'timeread' => null
                ]
            )
        );
    }
}