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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_program
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\orm\query\builder;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;
use totara_program\event\program_courseset_completed as program_courseset_completed_event;
use totara_notification\recipient\subject;
use totara_program\totara_notification\resolver\course_set_completed as course_set_completed_resolver;
use totara_notification\json_editor\node\placeholder;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/totara_notification_base.php');

/**
 * @group totara_notification
 */
class totara_program_totara_notification_course_set_completed_testcase extends totara_program_totara_notification_base {
    use \totara_notification\testing\notification_log_test_trait;

    public function test_course_set_completed() {
        global $DB;

        $data = $this->setup_programs();

        // Create a custom notification in event context.
        $event_context = extended_context::make_with_context(
            context_program::instance($data->program1->id)
        );
        $notification_generator = notification_generator::instance();
        $preference = $notification_generator->create_notification_preference(
            course_set_completed_resolver::class,
            $event_context,
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [subject::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Test notification body'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:last_name', 'Recipient last name'),
                            placeholder::create_node_from_key_and_label('program:full_name', 'Program full name'),
                            placeholder::create_node_from_key_and_label('managers:last_name', 'All managers last name'),
                            placeholder::create_node_from_key_and_label(
                                'assignment:due_date_criteria',
                                'Assignment due date criteria'
                            ),
                            placeholder::create_node_from_key_and_label(
                                'assignment:due_date',
                                'Assignment due date'
                            ),
                            placeholder::create_node_from_key_and_label('course_set:label', 'Course set label'),
                        ]),
                    ])
                ),
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        // Remove the 'assigned' notifiable event queue record.
        $DB->delete_records('notifiable_event_queue');

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $content = $data->program1->get_content();
        $sets = $content->get_course_sets();
        $set1 = reset($sets);

        $event = program_courseset_completed_event::create([
            'objectid' => $data->program1->id,
            'context' => context_program::instance($data->program1->id),
            'userid' => $data->user1->id,
            'other' => [
                'coursesetid' => $set1->id,
                'certifid' => 0,
            ],
        ]);
        $event->trigger();

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Redirect messages.
        $sink = self::redirectMessages();

        // Run tasks.
        $task = new process_event_queue_task();
        $task->execute();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertEquals('Test notification subject', $message->subject);
        self::assertStringContainsString('Test notification body', $message->fullmessage);
        self::assertStringContainsString('User1 last name', $message->fullmessage);
        self::assertStringContainsString('My program1 full name', $message->fullmessage);
        self::assertStringContainsString('Manager1 last name, Manager2 last name', $message->fullmessage);
        self::assertStringContainsString('Due date criteria not defined', $message->fullmessage);
        self::assertStringContainsString(userdate($data->due_date->getTimestamp(), '%d/%m/%Y', 99, false), $message->fullmessage);
        self::assertStringContainsString('Course set 1', $message->fullmessage);
        self::assertEquals($data->user1->id, $message->userto->id);

        // Check the logs
        $delivery_channels = json_decode($message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'resolver_class_name' => course_set_completed_resolver::class,
                'context_id' => $event_context->get_context()->id,
                'logs' => [
                    [
                        'preference_id' => $preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                    ],
                ],
                'event_name' => get_string('notification_log_course_set_completed_resolver', 'totara_program', [
                    'resolver_title' => course_set_completed_resolver::get_notification_title(),
                    'user' => 'User1 first name User1 last name',
                    'program' => 'My program1 full name',
                    'courseset' => 'Course set 1',
                ])
            ],
        ]);
    }

    public function test_get_scheduled_events(): void {
        global $DB;

        $resolver_class_name = course_set_completed_resolver::class;

        $data = $this->setup_programs();

        // Remove the 'assigned' notifiable event queue record.
        $DB->delete_records('notifiable_event_queue');

        $completed1 = time();
        // No scheduled events because no sets are completed.
        self::assert_scheduled_events($resolver_class_name, 0, $completed1 + 1, []);

        // Set program1 courseset 1 to complete - current time.
        $prog1_courseset1 = $data->program1->get_content()->get_course_sets()[0];

        $set_compl1 = new \stdClass();
        $set_compl1->coursesetid = $prog1_courseset1->id;
        $set_compl1->programid = $data->program1->id;
        $set_compl1->userid = $data->user1->id;
        $set_compl1->timecompleted = $completed1;
        $set_compl1->status = STATUS_COURSESET_COMPLETE;
        self::assertTrue(prog_write_courseset_completion($set_compl1));

        // Set program1 courseset 2 to complete - one hour ago
        $prog1_courseset2 = $data->program1->get_content()->get_course_sets()[1];
        $completed2 = time() - HOURSECS;

        $set_compl2 = new \stdClass();
        $set_compl2->coursesetid = $prog1_courseset2->id;
        $set_compl2->programid = $data->program1->id;
        $set_compl2->userid = $data->user1->id;
        $set_compl2->timecompleted = $completed2;
        $set_compl2->status = STATUS_COURSESET_COMPLETE;
        self::assertTrue(prog_write_courseset_completion($set_compl2));


        // Empty result for min_time after completion.
        self::assert_scheduled_events($resolver_class_name, $completed1 + 1, $completed1 + 2, []);
        // Empty result for max_time before completion.
        self::assert_scheduled_events($resolver_class_name, $completed1 - MINSECS, $completed1 - 1, []);
        // Empty result for max_time = completed time.
        self::assert_scheduled_events($resolver_class_name, $completed1 - MINSECS, $completed1, []);
        // Result expected for min_time = completed time.
        self::assert_scheduled_events($resolver_class_name, $completed1, $completed1 + 1, [
            ['program_id' => $data->program1->id, 'user_id' => $data->user1->id, 'course_set_id' => $prog1_courseset1->id, 'time_completed' => $set_compl1->timecompleted],
        ]);
        // Result expected for min_time < completed time.
        self::assert_scheduled_events($resolver_class_name, $completed1 - 1, $completed1 + 1, [
            ['program_id' => $data->program1->id, 'user_id' => $data->user1->id, 'course_set_id' => $prog1_courseset1->id, 'time_completed' => $set_compl1->timecompleted],
        ]);

        // Only course set 2 completion.
        self::assert_scheduled_events($resolver_class_name, $completed1 - DAYSECS, $completed1 - 1, [
            ['program_id' => $data->program1->id, 'user_id' => $data->user1->id, 'course_set_id' => $prog1_courseset2->id, 'time_completed' => $set_compl2->timecompleted],
        ]);

        // Both completions included in time period.
        self::assert_scheduled_events($resolver_class_name, $completed1 - DAYSECS, $completed1 + 1, [
            ['program_id' => $data->program1->id, 'user_id' => $data->user1->id, 'course_set_id' => $prog1_courseset1->id, 'time_completed' => $set_compl1->timecompleted],
            ['program_id' => $data->program1->id, 'user_id' => $data->user1->id, 'course_set_id' => $prog1_courseset2->id, 'time_completed' => $set_compl2->timecompleted],
        ]);

        // Alter program1 to look like it's not a certification to make sure it's not picked up.
        builder::table('prog')->where('id', $data->program1->id)->update(['certifid' => 1]);
        self::assert_scheduled_events($resolver_class_name, $completed1 - DAYSECS, $completed1 + 1, []);
    }
}
