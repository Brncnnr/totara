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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_certification
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use totara_certification\totara_notification\resolver\expiry_date as expiry_date_resolver;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\json_editor\node\placeholder;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;
use totara_notification\recipient\subject;
use totara_notification\external_helper;
use totara_program\testing\generator as program_generator;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/totara_notification_base.php');

/**
 * @group totara_notification
 */
class totara_certification_totara_notification_expiry_date_testcase extends totara_certification_totara_notification_base {
    use \totara_notification\testing\notification_log_test_trait;

    public function test_completed() {
        global $DB;

        $data = $this->setup_certifications();

        // Create a custom notification in event context.
        $event_context = extended_context::make_with_context(
            context_program::instance($data->program1->id)
        );
        $notification_generator = notification_generator::instance();
        $preference = $notification_generator->create_notification_preference(
            expiry_date_resolver::class,
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
                            placeholder::create_node_from_key_and_label('certification:full_name', 'Certification full name'),
                            placeholder::create_node_from_key_and_label('managers:last_name', 'All managers last name'),
                            placeholder::create_node_from_key_and_label(
                                'assignment:due_date_criteria',
                                'Assignment due date criteria'
                            ),
                            placeholder::create_node_from_key_and_label(
                                'assignment:due_date',
                                'Assignment due date'
                            ),
                        ]),
                    ])
                ),
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
                'forced_delivery_channels' => null,
            ]
        );

        // Remove the 'assigned' notifiable event queue record.
        $DB->delete_records('notifiable_event_queue');

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Directly push the event into the queue.
        $resolver = new expiry_date_resolver([
            'program_id' => $data->program1->id,
            'user_id' => $data->user1->id,
        ]);
        external_helper::create_notifiable_event_queue($resolver);

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
        self::assertStringContainsString('My certification1 full name', $message->fullmessage);
        self::assertStringContainsString('Manager1 last name, Manager2 last name', $message->fullmessage);
        self::assertStringContainsString('Due date criteria not defined', $message->fullmessage);
        self::assertStringContainsString(userdate($data->due_date->getTimestamp(), '%d/%m/%Y', 99, false), $message->fullmessage);
        self::assertEquals($data->user1->id, $message->userto->id);

        // Check the logs
        $delivery_channels = json_decode($message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'resolver_class_name' => expiry_date_resolver::class,
                'context_id' => $event_context->get_context()->id,
                'logs' => [
                    [
                        'preference_id' => $preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                    ],
                ],
                'event_name' => get_string('notification_log_expiry_date_resolver', 'totara_certification', [
                    'resolver_title' => expiry_date_resolver::get_notification_title(),
                    'user' => 'User1 first name User1 last name',
                    'cert' => 'My certification1 full name',
                ])
            ],
        ]);
    }

    public function test_get_scheduled_events(): void {
        $resolver_class_name = expiry_date_resolver::class;

        $data = $this->setup_certifications();
        $now = time();

        // No scheduled events expected.
        self::assert_scheduled_events($resolver_class_name, $now - DAYSECS * 100, $now + DAYSECS * 100, []);

        // Force the certification to be open for renewal with an expiry + 3 days out
        [$certif_completion_1, $prog_completion_1] = certif_load_completion($data->program1->id, $data->user1->id, MUST_EXIST);
        $certif_completion_1->timeexpires = $now + DAYSECS * 3;
        $certif_completion_1->baselinetimeexpires = $now + DAYSECS * 3;
        $prog_completion_1->timedue = $now + DAYSECS * 3;
        $certif_completion_1->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certif_completion_1->status = CERTIFSTATUS_COMPLETED;
        $certif_completion_1->timecompleted = $now - DAYSECS * 10;
        $certif_completion_1->timewindowopens = $now - DAYSECS * 5;
        $certif_completion_1->certifpath = CERTIFPATH_RECERT;

        certif_write_completion($certif_completion_1, $prog_completion_1);

        // Force the second program to be open for renewal with an expire + 6 days out
        program_generator::instance()->assign_program($data->program2->id, [$data->user1->id]);
        [$certif_completion_2, $prog_completion_2] = certif_load_completion($data->program2->id, $data->user1->id, MUST_EXIST);
        $certif_completion_2->timeexpires = $now + DAYSECS * 6;
        $certif_completion_2->baselinetimeexpires = $now + DAYSECS * 6;
        $prog_completion_2->timedue = $now + DAYSECS * 6;
        $certif_completion_2->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certif_completion_2->status = CERTIFSTATUS_COMPLETED;
        $certif_completion_2->timecompleted = $now - DAYSECS * 10;
        $certif_completion_2->timewindowopens = $now - DAYSECS * 5;
        $certif_completion_2->certifpath = CERTIFPATH_RECERT;

        certif_write_completion($certif_completion_2, $prog_completion_2);

        // Empty result for min_time after time due.
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 7, $now + DAYSECS * 9, []);
        // Empty result for max_time before time due.
        self::assert_scheduled_events($resolver_class_name, $now - DAYSECS * 3, $now + DAYSECS * 2, []);
        // Empty result for max_time = time due.
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 2, $now + DAYSECS * 3, []);
        // Result expected for min_time = time due.
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 3, $now + DAYSECS * 4, [
            ['program_id' => $data->program1->id, 'user_id' => $data->user1->id, 'time_completed' => $now + DAYSECS * 3],
        ]);
        // Result expected for min_time < time due.
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 2, $now + DAYSECS * 4, [
            ['program_id' => $data->program1->id, 'user_id' => $data->user1->id, 'time_completed' => $now + DAYSECS * 3],
        ]);

        // Include second program in time period.
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 2, $now + DAYSECS * 7, [
            ['program_id' => $data->program1->id, 'user_id' => $data->user1->id, 'time_completed' => $now + DAYSECS * 3],
            ['program_id' => $data->program2->id, 'user_id' => $data->user1->id, 'time_completed' => $now + DAYSECS * 6],
        ]);
        // Only second program.
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 5, $now + DAYSECS * 7, [
            ['program_id' => $data->program2->id, 'user_id' => $data->user1->id, 'time_completed' => $now + DAYSECS * 6],
        ]);
    }
}
