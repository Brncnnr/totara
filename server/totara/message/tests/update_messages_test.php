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

use totara_message\task\update_messages_task;

class totara_message_update_messages_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_name_present() {
        $task = new update_messages_task();
        self::assertNotEmpty($task->get_name());
    }

    /**
     * @return void
     */
    public function test_execute(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/message/lib.php');


        $this->assertSame(0, $DB->count_records('message'));
        $this->assertSame(0, $DB->count_records('message_metadata'));
        $this->assertSame(0, $DB->count_records('message_read'));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $event = new stdClass;
        $event->userfrom = $user1;
        $event->userto = $user2;
        $event->contexturl = $CFG->wwwroot . '/';
        $event->icon = 'program-approve';
        $event->subject = 'Some alert';
        $event->fullmessage = 'Full alert message';
        $event->fullmessagehtml = '<div style="color:red">Full alert message</div>';
        tm_alert_send(clone($event));
        tm_alert_send(clone($event));
        tm_alert_send(clone($event));

        $event = new stdClass;
        $event->userfrom = $user2;
        $event->userto = $user1;
        $event->contexturl = $CFG->wwwroot . '/';
        $event->icon = 'program-approve';
        $event->subject = 'Some task';
        $event->fullmessage = 'Full task message';
        $event->fullmessagehtml = '<div style="color:red">Full task message</div>';
        tm_task_send(clone($event));
        tm_task_send(clone($event));
        tm_task_send(clone($event));

        $messages = $DB->get_records('notifications', ['timeread' => null], 'id ASC');
        $this->assertCount(6, $messages);
        $messages = array_values($messages);

        tm_message_dismiss($messages[2]->id);
        tm_message_dismiss($messages[5]->id);

        $this->assertSame(6, $DB->count_records('message_metadata'));
        $this->assertSame(4, $DB->count_records('notifications', ['timeread' => null]));

        $this->assertSame(2, $DB->count_records_select('notifications', 'timeread IS NOT NULL'));

        // Nothing should be updated, because the time created of these notifications
        // are not yet exceeding 30 days.
        $task = new update_messages_task();
        $task->execute();

        $this->assertSame(6, $DB->count_records('message_metadata'));
        $this->assertSame(4, $DB->count_records('notifications', ['timeread' => null]));
        $this->assertSame(2, $DB->count_records_select('notifications', "timeread IS NOT NULL"));

        // Update the time created of the messages
        $messages[0]->timecreated = $messages[0]->timecreated - (24*60*60*update_messages_task::TOTARA_MSG_CRON_DISMISS_ALERTS) - 3600;
        $DB->update_record('notifications', $messages[0]);

        $messages[3]->timecreated = $messages[3]->timecreated - (24*60*60*update_messages_task::TOTARA_MSG_CRON_DISMISS_TASKS) - 3600;
        $DB->update_record('notifications', $messages[3]);

        $updated_notifications = [$messages[0]->id, $messages[3]->id];

        $unread_notfications = $DB->get_records('notifications', ['timeread' => null], 'id ASC');
        $unread_notfications = array_values($unread_notfications);

        // There should be 4 at this point.
        $this->assertCount(4, $unread_notfications);

        $task = new update_messages_task();
        $task->execute();

        $this->assertSame(6, $DB->count_records('message_metadata'));
        $this->assertSame(2, $DB->count_records('notifications', ['timeread' => null]));
        $this->assertSame(4, $DB->count_records_select('notifications', 'timeread IS NOT NULL'));

        $new_unread_notifications = $DB->get_records('notifications', ['timeread' => null], 'id ASC');
        $new_unread_notifications = array_values($new_unread_notifications);

        self::assertNotEmpty($new_unread_notifications);
        self::assertCount(2, $new_unread_notifications);

        // Check that the new unread messages should not contains any of updated messages.
        foreach ($new_unread_notifications as $new_unread_notification) {
            self::assertNotContainsEquals($new_unread_notification->id, $updated_notifications);
        }

        // Check that the unrread_notifications and the new_unread_notifications are having the same record.
        $unread_notfications = array_filter(
            $unread_notfications,
            function (stdClass $notification) use ($updated_notifications): bool {
                return !in_array($notification->id, $updated_notifications);
            }
        );

        $unread_notfications = array_values($unread_notfications);
        self::assertEquals($unread_notfications, $new_unread_notifications);

        self::assertEquals($unread_notfications[0], $new_unread_notifications[0]);
        self::assertEquals($unread_notfications[1], $new_unread_notifications[1]);
    }
}