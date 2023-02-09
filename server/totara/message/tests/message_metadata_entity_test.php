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

class totara_message_message_metadata_entity_testcase extends advanced_testcase {
    /**
     * Note that for this test, we do not want to include the file "/server/totara/message/messagelib.php" just yet.
     * As this is to make sure that the global $TOTARA_MESSAGE_TYPES are would still work without
     * the file being included as the start.
     *
     * @return void
     */
    public function test_set_invalid_message_type(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid message type value '100'");

        $entity = new message_metadata();
        $entity->msgtype = 100;
    }

    /**
     * @return void
     */
    public function test_set_valid_message_type(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/message/messagelib.php");

        $entity = new message_metadata();
        $entity->msgtype = TOTARA_MSG_TYPE_UNKNOWN;

        self::assertEquals(TOTARA_MSG_TYPE_UNKNOWN, $entity->msgtype);
        self::assertNotEquals(TOTARA_MSG_TYPE_CHAT, $entity->msgtype);
    }

    /**
     * @return void
     */
    public function test_set_invalid_message_status(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid message status value '42'");

        $entity = new message_metadata();
        $entity->msgstatus = 42;
    }

    /**
     * @return void
     */
    public function test_set_valid_message_status(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/message/messagelib.php");

        $entity = new message_metadata();

        $entity->msgstatus = TOTARA_MSG_STATUS_UNDECIDED;
        self::assertEquals(TOTARA_MSG_STATUS_UNDECIDED, $entity->msgstatus);

        $entity->msgstatus = TOTARA_MSG_STATUS_NOTOK;
        self::assertEquals(TOTARA_MSG_STATUS_NOTOK, $entity->msgstatus);

        $entity->msgstatus = TOTARA_MSG_STATUS_OK;
        self::assertEquals(TOTARA_MSG_STATUS_OK, $entity->msgstatus);
    }

    /**
     * @return void
     */
    public function test_set_invalid_urgency(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid urgency value '55'");

        $entity = new message_metadata();
        $entity->urgency = 55;
    }

    /**
     * @return void
     */
    public function test_set_valid_urgency(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/message/messagelib.php");

        $entity = new message_metadata();

        $entity->urgency = TOTARA_MSG_URGENCY_LOW;
        self::assertEquals(TOTARA_MSG_URGENCY_LOW, $entity->urgency);

        $entity->urgency = TOTARA_MSG_URGENCY_NORMAL;
        self::assertEquals(TOTARA_MSG_URGENCY_NORMAL, $entity->urgency);

        $entity->urgency = TOTARA_MSG_URGENCY_URGENT;
        self::assertEquals(TOTARA_MSG_URGENCY_URGENT, $entity->urgency);
    }
}