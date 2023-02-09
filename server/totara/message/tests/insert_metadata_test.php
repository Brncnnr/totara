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

use totara_message\entity\message_metadata;

class totara_message_insert_metadata_testcase extends advanced_testcase {
    /**
     * Just a random id to be used inside this test only.
     * @var int
     */
    private const ANSWER_OF_UNIVERSE = 42;

    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/message/messagelib.php");
    }

    /**
     * @return stdClass
     */
    private function dummy_record(): stdClass {
        $event_data = new stdClass();
        $event_data->onaccept = null;
        $event_data->onreject = null;
        $event_data->oninfo = null;
        $event_data->urgency = TOTARA_MSG_URGENCY_NORMAL;
        $event_data->msgtype = TOTARA_MSG_TYPE_UNKNOWN;
        $event_data->msgstatus = TOTARA_MSG_STATUS_OK;
        $event_data->savedmessageid = static::ANSWER_OF_UNIVERSE;

        return $event_data;
    }

    /**
     * @return void
     */
    public function test_insert_metadata_that_of_different_processors(): void {
        global $DB;

        self::assertEquals(0, $DB->count_records(message_metadata::TABLE));

        $first_event = $this->dummy_record();
        $second_event = $this->dummy_record();

        tm_insert_metadata($first_event, 42);
        tm_insert_metadata($second_event, 52);

        self::assertEquals(2, $DB->count_records(message_metadata::TABLE));
        self::assertTrue(
            $DB->record_exists(
                message_metadata::TABLE,
                [
                    'processorid' => 42,
                    'notificationid' => static::ANSWER_OF_UNIVERSE
                ]
            )
        );

        self::assertTrue(
            $DB->record_exists(
                message_metadata::TABLE,
                [
                    'processorid' => 42,
                    'notificationid' => static::ANSWER_OF_UNIVERSE
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_insert_metadata_should_not_populate_field_message_id(): void {
        global $DB;

        self::assertEquals(0, $DB->count_records(message_metadata::TABLE));
        $dummy_event = $this->dummy_record();

        tm_insert_metadata($dummy_event, 42);

        // 1 entry should be created.
        self::assertEquals(1, $DB->count_records(message_metadata::TABLE));

        // However this entry should not have message id nor messagereadid populated.
        self::assertEquals(
            0,
            $DB->count_records_sql('
                SELECT COUNT(id) FROM "ttr_message_metadata" 
                WHERE (messageid IS NOT NULL OR messagereadid IS NOT NULL)
            ')
        );
    }

    /**
     * @return void
     */
    public function test_insert_metadata_does_not_re_insert_record_for_same_processor(): void {
        global $DB;

        // Initial state.
        self::assertEquals(0, $DB->count_records(message_metadata::TABLE));

        $event_data = new stdClass();
        $event_data->savedmessageid = 42;
        $event_data->msgtype = TOTARA_MSG_TYPE_UNKNOWN;
        $event_data->msgstatus = TOTARA_MSG_STATUS_OK;
        $event_data->urgency = TOTARA_MSG_URGENCY_LOW;

        tm_insert_metadata($event_data, 42);
        tm_insert_metadata($event_data, 42);

        // There should be only 1 record added to the table, despite of the API being called multiple times.
        self::assertEquals(1, $DB->count_records(message_metadata::TABLE));

        // Add with different processor.
        tm_insert_metadata($event_data, 52);

        // There shoul be 2 record added to the table now, 1 is for 42 and one is for 52.
        self::assertEquals(2, $DB->count_records(message_metadata::TABLE));
        self::assertEquals(1, $DB->count_records(message_metadata::TABLE, ['processorid' => 42]));
        self::assertEquals(1, $DB->count_records(message_metadata::TABLE, ['processorid' => 52]));
    }

    /**
     * @return void
     */
    public function test_unique_index_of_notification_id(): void {
        global $DB;

        $record_one = new stdClass();
        $record_one->notificationid = 42;
        $record_one->msgtype = TOTARA_MSG_TYPE_UNKNOWN;
        $record_one->msgstatus = TOTARA_MSG_STATUS_OK;
        $record_one->urgency = TOTARA_MSG_URGENCY_LOW;
        $record_one->processorid = 42;

        $record_two = new stdClass();
        $record_two->notificationid = 42;
        $record_two->msgtype = TOTARA_MSG_TYPE_UNKNOWN;
        $record_two->msgstatus = TOTARA_MSG_STATUS_OK;
        $record_two->urgency = TOTARA_MSG_URGENCY_LOW;
        $record_two->processorid = 52;

        self::assertEquals(0, $DB->count_records(message_metadata::TABLE));

        $DB->insert_record(message_metadata::TABLE, $record_one);
        $DB->insert_record(message_metadata::TABLE, $record_two);

        self::assertEquals(2, $DB->count_records(message_metadata::TABLE));

        // Try to save with the same processorid and notificationid.
        $this->expectException(dml_write_exception::class);
        $record_two->processorid = 42;
        $DB->insert_record(message_metadata::TABLE, $record_two);
    }
}