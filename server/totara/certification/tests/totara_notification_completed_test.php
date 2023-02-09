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
use core\orm\query\builder;
use totara_certification\totara_notification\resolver\completed as completed_resolver;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\json_editor\node\placeholder;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;
use totara_program\event\program_completed as program_completed_event;
use totara_program\testing\generator as program_generator;
use totara_notification\recipient\subject;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/totara_notification_base.php');

/**
 * @group totara_notification
 */
class totara_certification_totara_notification_completed_testcase extends totara_certification_totara_notification_base {
    use \totara_notification\testing\notification_log_test_trait;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        global $CFG;
        require_once($CFG->dirroot . '/totara/certification/lib.php');
    }

    public function test_resolver() {
        global $DB;

        $data = $this->setup_certifications();

        // Create a custom notification in event context.
        $event_context = extended_context::make_with_context(
            context_program::instance($data->program1->id)
        );
        $notification_generator = notification_generator::instance();
        $preference = $notification_generator->create_notification_preference(
            completed_resolver::class,
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

        $event = program_completed_event::create([
            'objectid' => $data->program1->id,
            'context' => context_program::instance($data->program1->id),
            'userid' => $data->user1->id,
            'other' => ['certifid' => $data->program1->certifid],
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
        self::assertStringContainsString('My certification1 full name', $message->fullmessage);
        self::assertStringContainsString('Manager1 last name, Manager2 last name', $message->fullmessage);
        self::assertStringContainsString('Due date criteria not defined', $message->fullmessage);
        self::assertStringContainsString(userdate($data->due_date->getTimestamp(), '%d/%m/%Y', 99, false), $message->fullmessage);
        self::assertEquals($data->user1->id, $message->userto->id);

        // Check the logs
        $delivery_channels = json_decode($message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'resolver_class_name' => completed_resolver::class,
                'context_id' => $event_context->get_context()->id,
                'logs' => [
                    [
                        'preference_id' => $preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                    ],
                ],
                'event_name' => get_string('notification_log_completed_resolver', 'totara_certification', [
                    'resolver_title' => completed_resolver::get_notification_title(),
                    'user' => 'User1 first name User1 last name',
                    'cert' => 'My certification1 full name',
                ])
            ],
        ]);
    }

    public function test_get_scheduled_events(): void {
        $resolver_class_name = completed_resolver::class;

        $data = $this->setup_certifications();

        $completed1 = time();

        // No scheduled events because nothing is completed.
        self::assert_scheduled_events($resolver_class_name, 0, $completed1 + 1, []);

        // Set certif1(program1) to complete - current time.
        self::set_certif_timecompleted($data->program1->id, $data->user1->id, $completed1);

        // Add completion for certif2(program2) - one hour ago.
        $program_generator = program_generator::instance();
        $program_generator->assign_program($data->program2->id, [$data->user1->id]);
        $completed2 = $completed1 - HOURSECS;
        self::set_certif_timecompleted($data->program2->id, $data->user1->id, $completed2);

        // Empty result for min_time after completion.
        self::assert_scheduled_events($resolver_class_name, $completed1 + 1, $completed1 + 2, []);
        // Empty result for max_time before completion.
        self::assert_scheduled_events($resolver_class_name, $completed1 - MINSECS, $completed1 - 1, []);
        // Empty result for max_time = completed time.
        self::assert_scheduled_events($resolver_class_name, $completed1 - MINSECS, $completed1, []);
        // Result expected for min_time = completed time.
        self::assert_scheduled_events($resolver_class_name, $completed1, $completed1 + 1, [
            ['program_id' => $data->program1->id, 'user_id' => $data->user1->id, 'time_completed' => $completed1],
        ]);
        // Result expected for min_time < completed time.
        self::assert_scheduled_events($resolver_class_name, $completed1 - 1, $completed1 + 1, [
            ['program_id' => $data->program1->id, 'user_id' => $data->user1->id, 'time_completed' => $completed1],
        ]);

        // Only program2 completion.
        self::assert_scheduled_events($resolver_class_name, $completed1 - DAYSECS, $completed1 - 1, [
            ['program_id' => $data->program2->id, 'user_id' => $data->user1->id, 'time_completed' => $completed2],
        ]);

        // Both completions included in time period.
        self::assert_scheduled_events($resolver_class_name, $completed1 - DAYSECS, $completed1 + 1, [
            ['program_id' => $data->program1->id, 'user_id' => $data->user1->id, 'time_completed' => $completed1],
            ['program_id' => $data->program2->id, 'user_id' => $data->user1->id, 'time_completed' => $completed2],
        ]);

        // Fake program1 to look like it's not a certification to make sure it's not picked up.
        builder::table('prog')->where('id', $data->program1->id)->update(['certifid' => 0]);
        self::assert_scheduled_events($resolver_class_name, $completed1 - DAYSECS, $completed1 + 1, [
            ['program_id' => $data->program2->id, 'user_id' => $data->user1->id, 'time_completed' => $completed2],
        ]);
    }

    /**
     * @param $program_id
     * @param $user_id
     * @param $time_completed
     */
    private static function set_certif_timecompleted($program_id, $user_id, $time_completed): void {
        [$certif_compl1, $prog_compl1] = certif_load_completion($program_id, $user_id);
        $prog_compl1->status = STATUS_PROGRAM_INCOMPLETE;
        $prog_compl1->timecompleted = 0;
        $prog_compl1->timedue = $time_completed + 200;
        $certif_compl1->status = CERTIFSTATUS_COMPLETED;
        $certif_compl1->timecompleted = $time_completed;
        $certif_compl1->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certif_compl1->certifpath = CERTIFPATH_RECERT;
        $certif_compl1->timecompleted = $time_completed;
        $certif_compl1->timewindowopens = $time_completed + 100;
        $certif_compl1->timeexpires = $time_completed + 200;
        $certif_compl1->baselinetimeexpires = $time_completed + 200;
        self::assertTrue(certif_write_completion($certif_compl1, $prog_compl1));
    }
}
