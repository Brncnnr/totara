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

class totara_message_get_event_data_testcase extends advanced_testcase {
    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/message/lib.php");
    }

    /**
     * @return void
     */
    public function test_get_accept_event(): void {
        $accept_event = new stdClass();
        $accept_event->action = 'facetoface';
        $accept_event->data = [];
        $accept_event->text = 'wow';

        $metadata = new stdClass();
        $metadata->onaccept = serialize($accept_event);
        $metadata->notificationid = 45;

        $event_data = totara_message_eventdata(45, 'onaccept', $metadata);

        self::assertObjectHasAttribute('action', $event_data);
        self::assertEquals('facetoface', $event_data->action);

        self::assertObjectHasAttribute('text', $event_data);
        self::assertEquals('wow', $event_data->text);

        self::assertObjectHasAttribute('data', $event_data);
        self::assertEquals([], $event_data->data);
    }

    /**
     * @return void
     */
    public function test_get_accept_event_yield_no_data(): void {
        // Case when onaccept is null.
        $metadata = new stdClass();
        $metadata->onaccept = null;
        $metadata->notificationid = 45;

        self::assertNull(totara_message_eventdata(45, 'onaccept', $metadata));

        // Case when field does not appear in the dummy record data.
        self::assertNull(totara_message_eventdata(45, 'onaccept', new stdClass()));
    }

    /**
     * @return void
     */
    public function test_debugging_on_invalid_event(): void {
        $metadata = new stdClass();
        $metadata->onreject = null;
        $metadata->notificationid = 45;

        self::assertNull(totara_message_eventdata(45, 'o', $metadata));
        self::assertDebuggingCalled("Invalid value of event type, default to 'onreject'");

        self::assertNull(totara_message_eventdata(45, 'ONACCEPT', $metadata));
        self::assertDebuggingCalled("Invalid value of event type, default to 'onreject'");

        // No data in metadata
        self::assertNull(totara_message_eventdata(45, 'onreject', $metadata));
        self::assertNull(totara_message_eventdata(45, 'oninfo', $metadata));
        self::assertNull(totara_message_eventdata(45, 'onaccept', $metadata));

        // Empty serialized string should be same as no data or null.
        $metadata->onaccept = serialize('');
        self::assertNull(totara_message_eventdata(45, 'onaccept', $metadata));
    }

    /**
     * @return void
     */
    public function test_debugging_on_empty_metadata(): void {
        global $DB, $CFG;
        require_once("{$CFG->dirroot}/totara/message/messagelib.php");

        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $notification = new notification();
        $notification->fullmessage = 'd';
        $notification->fullmessagehtml = /** @lang text */'<p>d</p>';
        $notification->fullmessageformat = FORMAT_MOODLE;
        $notification->smallmessage = 'dd';
        $notification->useridfrom = $user_one->id;
        $notification->useridto = $user_two->id;

        $notification->save();

        // Create a task metadata.
        $task_accept = new stdClass();
        $task_accept->action = 'facetoface';
        $task_accept->text = 'boom';
        $task_accept->data = [];

        $task_metadata = new message_metadata();
        $task_metadata->processorid = $DB->get_field('message_processors', 'id', ['name' => 'totara_task'], MUST_EXIST);
        $task_metadata->onaccept = serialize($task_accept);
        $task_metadata->msgtype = TOTARA_MSG_TYPE_UNKNOWN;
        $task_metadata->msgstatus = TOTARA_MSG_STATUS_OK;
        $task_metadata->urgency = TOTARA_MSG_URGENCY_LOW;
        $task_metadata->notificationid = $notification->id;
        $task_metadata->save();

        // Create an alert metadata.
        $alert_accept = new stdClass();
        $alert_accept->action = 'forum';
        $alert_accept->text = 'boom 2';
        $alert_accept->data = [];

        $alert_metadata = new message_metadata();
        $alert_metadata->processorid = $DB->get_field('message_processors', 'id', ['name' => 'totara_alert'], MUST_EXIST);
        $alert_metadata->onaccept = serialize($alert_accept);
        $alert_metadata->msgtype = TOTARA_MSG_TYPE_UNKNOWN;
        $alert_metadata->msgstatus = TOTARA_MSG_STATUS_OK;
        $alert_metadata->urgency = TOTARA_MSG_URGENCY_LOW;
        $alert_metadata->notificationid = $notification->id;
        $alert_metadata->save();

        // Use the function to get the event data of on accept.
        $event_data = totara_message_eventdata($notification->id, 'onaccept');

        self::assertDebuggingCalled(
            "The third parameter of the function is now a part of requirement, " .
            "please provide the record for the accurate record lookup"
        );

        // This should not only return the task_metadata instead of alert_metadata.
        self::assertObjectHasAttribute('action', $event_data);
        self::assertNotEquals('forum', $event_data->action);
        self::assertEquals('facetoface', $event_data->action);

        self::assertObjectHasAttribute('text', $event_data);
        self::assertNotEquals('boom 2', $event_data->text);
        self::assertEquals('boom', $event_data->text);

        self::assertObjectHasAttribute('data', $event_data);
        self::assertEmpty($event_data->data);
    }

    /**
     * @return void
     */
    public function test_get_reject_event(): void {
        $reject_event = new stdClass();
        $reject_event->action = 'forum';
        $reject_event->data = [];
        $reject_event->text = 'dd';

        $metadata = new stdClass();
        $metadata->onreject = serialize($reject_event);
        $metadata->onaccept = null;
        $metadata->notificationid = 42;

        $event_data = totara_message_eventdata(42, 'onreject', $metadata);

        self::assertObjectHasAttribute('action', $event_data);
        self::assertEquals('forum', $event_data->action);

        self::assertObjectHasAttribute('text', $event_data);
        self::assertEquals('dd', $event_data->text);

        self::assertObjectHasAttribute('data', $event_data);
        self::assertEquals([], $event_data->data);
    }

    /**
     * @return void
     */
    public function test_get_info_event(): void {
        $info_event = new stdClass();
        $info_event->action = 'ddd';
        $info_event->data = [];
        $info_event->text = 'ccc';

        $metadata = new stdClass();
        $metadata->oninfo = serialize($info_event);
        $metadata->onaccept = null;
        $metadata->onreject = null;
        $metadata->notificationid = 32;

        $event_data = totara_message_eventdata(32, 'oninfo', $metadata);

        self::assertObjectHasAttribute('action', $event_data);
        self::assertEquals('ddd', $event_data->action);

        self::assertObjectHasAttribute('text', $event_data);
        self::assertEquals('ccc', $event_data->text);

        self::assertObjectHasAttribute('data', $event_data);
        self::assertEquals([], $event_data->data);
    }
}