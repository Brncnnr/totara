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
use core_message\task\migrate_message_data;
use totara_message\entity\message_metadata;

class totara_message_migrate_message_metadata_testcase extends advanced_testcase {
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
    public function test_migrate_unread_message_to_notification(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $message_record = new stdClass();
        $message_record->useridfrom = $user_one->id;
        $message_record->useridto = $user_two->id;
        $message_record->subject = 'xx_yz';
        $message_record->fullmessage = 'ddd';
        $message_record->fullmessageformat = FORMAT_MOODLE;
        $message_record->fullmessagehtml = /** @lang text */'<h1>ddd</h1>';
        $message_record->smallmessage = 'ddd';
        $message_record->notification = 1;
        $message_record->contexturl = 'http://example.com';
        $message_record->contexturlname = 'boom!';
        $message_record->timecreated = time();
        $message_record->component = 'totara_message';
        $message_record->eventtype = 'facetoface';
        $unread_message_id = $DB->insert_record('message', $message_record);

        self::assertEquals(1, $DB->count_records('message'));

        $processor_id = $DB->get_field('message_processors', 'id', ['name' => 'totara_task'], MUST_EXIST);

        // Create a record of message working.
        $message_working = new stdClass();
        $message_working->unreadmessageid = $unread_message_id;
        $message_working->processorid = $processor_id;

        $DB->insert_record('message_working', $message_working);

        // Create a record of message metadata.
        $message_metadata = new message_metadata();
        $message_metadata->messageid = $unread_message_id;
        $message_metadata->msgtype = TOTARA_MSG_TYPE_FACE2FACE;
        $message_metadata->msgstatus = TOTARA_MSG_STATUS_NOTOK;
        $message_metadata->urgency = TOTARA_MSG_URGENCY_LOW;
        $message_metadata->processorid = $processor_id;
        $message_metadata->save();

        self::assertNotNull($message_metadata->messageid);
        self::assertNull($message_metadata->messagereadid);
        self::assertNull($message_metadata->notificationid);
        self::assertEquals($unread_message_id, $message_metadata->messageid);
        self::assertNull($message_metadata->timeread);

        // Start migrating the message for user's two.
        migrate_message_data::queue_task($user_two->id);
        $this->executeAdhocTasks();

        $message_metadata->refresh();
        self::assertNull($message_metadata->messageid);
        self::assertNull($message_metadata->messagereadid);
        self::assertNotNull($message_metadata->notificationid);
        self::assertNull($message_metadata->timeread);

        self::assertTrue(
            $DB->record_exists(notification::TABLE, ['id' => $message_metadata->notificationid])
        );

        self::assertEquals(0, $DB->count_records('message'));
    }

    /**
     * @return void
     */
    public function test_migrate_read_message_to_notification(): void {
        global $DB;
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $read_message_record = new stdClass();
        $read_message_record->useridfrom = $user_one->id;
        $read_message_record->useridto = $user_two->id;
        $read_message_record->subject = 'boom';
        $read_message_record->fullmessage = 'kaboom';
        $read_message_record->fullmessageformat = FORMAT_MOODLE;
        $read_message_record->fullmessagehtml = /** @lang text */'<h1>kaboom</h1>';
        $read_message_record->smallmessage = 'kaboom';
        $read_message_record->notification = 1;
        $read_message_record->contexturl = 'http://example.com';
        $read_message_record->contexturlname = 'daada';
        $read_message_record->timecreated = time();
        $read_message_record->timeread = time();
        $read_message_record->component = 'component';
        $read_message_record->eventtype = 'dd';
        $read_message_id = $DB->insert_record('message_read', $read_message_record);

        self::assertEquals(1, $DB->count_records('message_read'));

        // Insert message metadata.
        $message_metadata = new message_metadata();
        $message_metadata->messagereadid = $read_message_id;
        $message_metadata->msgtype = TOTARA_MSG_TYPE_FACE2FACE;
        $message_metadata->msgstatus = TOTARA_MSG_STATUS_OK;
        $message_metadata->urgency = TOTARA_MSG_URGENCY_NORMAL;
        $message_metadata->processorid = $DB->get_field('message_processors', 'id', ['name' => 'totara_task'], MUST_EXIST);
        $message_metadata->save();

        self::assertNotNull($message_metadata->messagereadid);
        self::assertEquals($read_message_id, $message_metadata->messagereadid);

        self::assertNull($message_metadata->notificationid);
        self::assertNull($message_metadata->timeread);

        migrate_message_data::queue_task($user_one->id);
        $this->executeAdhocTasks();

        $message_metadata->refresh();

        self::assertNull($message_metadata->messagereadid);
        self::assertNull($message_metadata->messageid);

        self::assertNotNull($message_metadata->notificationid);
        self::assertNotNull($message_metadata->timeread);
    }
}