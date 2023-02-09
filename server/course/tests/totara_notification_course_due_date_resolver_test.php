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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package core_course
 */

use container_course\course as course_container;
use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core_course\totara_notification\resolver\course_due_date_resolver;
use core_phpunit\testcase;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\manager\scheduled_event_manager;
use totara_notification\recipient\subject;
use totara_notification\testing\generator as notification_generator;
use totara_notification\json_editor\node\placeholder;

global $CFG;
require_once($CFG->libdir . '/dml/array_recordset.php');

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class core_course_totara_notification_course_due_date_resolver_testcase extends testcase {
    use \totara_notification\testing\notification_log_test_trait;

    private function setup_data() {
        global $DB;

        $generator = self::getDataGenerator();

        $now = time();

        // Create base users.
        $users = [
            1 => $generator->create_user(['firstname' => 'User1 first name', 'lastname' => 'User1 last name']),
            2 => $generator->create_user(['firstname' => 'User2 first name', 'lastname' => 'User2 last name']),
            3 => $generator->create_user(['firstname' => 'User3 first name', 'lastname' => 'User3 last name']),
        ];

        // Create courses.
        $courses = [
            1 => $generator->create_course([
                'fullname' => 'Test course with completion',
                'enablecompletion' => COMPLETION_ENABLED,
            ]),
            2 => $generator->create_course([
                'fullname' => 'Test course with no completion',
                'enablecompletion' => COMPLETION_DISABLED,
            ]),
        ];

        // Directly create course_completion records with specific status and due date values
        $to_insert = [
            [
                'course' => $courses[1]->id,
                'userid' => $users[1]->id,
                'timeenrolled' => $now,
                'timestarted' => $now,
                'reaggregate' => 0,
                'status' => COMPLETION_STATUS_INPROGRESS,
                'duedate' => $now + DAYSECS * 3,
            ],
            [
                'course' => $courses[1]->id,
                'userid' => $users[2]->id,
                'timeenrolled' => $now,
                'timestarted' => $now,
                'reaggregate' => 0,
                'status' => COMPLETION_STATUS_NOTYETSTARTED,
                'duedate' => $now + DAYSECS * 6,
            ],
            [
                'course' => $courses[1]->id,
                'userid' => $users[3]->id,
                'timeenrolled' => $now,
                'timestarted' => $now,
                'reaggregate' => 0,
                'status' => COMPLETION_STATUS_COMPLETE,
                'duedate' => $now + DAYSECS * 6,
            ],
            [
                'course' => $courses[2]->id,
                'userid' => $users[1]->id,
                'timeenrolled' => $now,
                'timestarted' => $now,
                'reaggregate' => 0,
                'status' => COMPLETION_STATUS_INPROGRESS,
                'duedate' => $now + DAYSECS * 3,
            ],
            [
                'course' => $courses[2]->id,
                'userid' => $users[2]->id,
                'timeenrolled' => $now,
                'timestarted' => $now,
                'reaggregate' => 0,
                'status' => COMPLETION_STATUS_NOTYETSTARTED,
                'duedate' => $now + DAYSECS * 6,
            ],
            [
                'course' => $courses[2]->id,
                'userid' => $users[3]->id,
                'timeenrolled' => $now,
                'timestarted' => $now,
                'reaggregate' => 0,
                'status' => COMPLETION_STATUS_COMPLETEVIARPL,
                'duedate' => $now + DAYSECS * 6,
            ],
        ];

        $DB->insert_records('course_completions', $to_insert);

        return (object)[
            'now' => $now,
            'users' => $users,
            'courses' => $courses,
        ];
    }

    public function test_resolver(): void {
        global $CFG;

        $data = self::setup_data();

        $resolver_class_name = course_due_date_resolver::class;
        $now = $data->now;

        // Empty result for min_time after time due.
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 7, $now + DAYSECS * 9, []);
        // Empty result for max_time before time due.
        self::assert_scheduled_events($resolver_class_name, $now - DAYSECS * 3, $now + DAYSECS * 2, []);
        // Empty result for max_time = time due.
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 2, $now + DAYSECS * 3, []);

        // Result expected for min_time = time due.
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 3, $now + DAYSECS * 4, [
            ['course_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'duedate' => $now + DAYSECS * 3],
        ]);
        // Result expected for min_time < time due.
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 2, $now + DAYSECS * 4, [
            ['course_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'duedate' => $now + DAYSECS * 3],
        ]);

        // Include both due dates
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 2, $now + DAYSECS * 7, [
            ['course_id' => $data->courses[1]->id, 'user_id' => $data->users[1]->id, 'time_due' => $now + DAYSECS * 3],
            ['course_id' => $data->courses[1]->id, 'user_id' => $data->users[2]->id, 'time_due' => $now + DAYSECS * 6],
        ]);

        // Only second due date
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 5, $now + DAYSECS * 7, [
            ['course_id' => $data->courses[1]->id, 'user_id' => $data->users[2]->id, 'time_due' => $now + DAYSECS * 6],
        ]);

        // Now disable completion on site level and re-evaluate the last 3 tests
        $CFG->enablecompletion = 0;
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 2, $now + DAYSECS * 4, []);
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 2, $now + DAYSECS * 7, []);
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS * 5, $now + DAYSECS * 7, []);
    }

    public function test_warnings(): void {
        $course_completion_disabled = self::getDataGenerator()->create_course(['enablecompletion' => 0]);
        $course_completion_enabled = self::getDataGenerator()->create_course(['enablecompletion' => 1]);
        $course_completion_enabled_with_duedate = self::getDataGenerator()->create_course([
            'enablecompletion' => 1,
            'duedate_op' => course_container::DUEDATEOPERATOR_FIXED,
            'duedate' => strtotime('+7 days'),
        ]);

        $system_context = extended_context::make_system();
        $course_context_disabled = extended_context::make_with_context(
            context_course::instance($course_completion_disabled->id)
        );
        $course_context_enabled = extended_context::make_with_context(
            context_course::instance($course_completion_enabled->id)
        );
        $course_context_enabled_with_duedate = extended_context::make_with_context(
            context_course::instance($course_completion_enabled_with_duedate->id)
        );
        $extended_course_context = extended_context::make_with_context(
            context_course::instance($course_completion_disabled->id),
            'test_component',
            'test_area',
            123
        );

        self::assertEmpty(course_due_date_resolver::get_warnings($system_context));
        self::assertNotEmpty(course_due_date_resolver::get_warnings($course_context_disabled));
        self::assertNotEmpty(course_due_date_resolver::get_warnings($course_context_enabled));
        self::assertEmpty(course_due_date_resolver::get_warnings($course_context_enabled_with_duedate));
        self::assertEmpty(course_due_date_resolver::get_warnings($extended_course_context));
    }

    public function test_custom_notification(): void {
        global $DB;

        $data = self::setup_data();

        // Create a custom notification in event context.
        $event_context = extended_context::make_with_context(
            context_course::instance($data->courses[1]->id)
        );
        $notification_generator = notification_generator::instance();
        $preference = $notification_generator->create_notification_preference(
            course_due_date_resolver::class,
            $event_context,
            [
                'schedule_offset' => -1 * DAYSECS * 3,
                'recipient' => subject::class,
                'recipients' => [subject::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Test notification body'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:last_name', 'Recipient last name'),
                            placeholder::create_node_from_key_and_label('course:full_name', 'Course name'),
                            placeholder::create_node_from_key_and_label('course_completion:due_date', 'Due date'),
                        ]),
                    ])
                ),
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        // Remove the 'assigned' notifiable event queue record.
        $DB->delete_records('notifiable_event_queue');

        // Redirect messages.
        $sink = self::redirectMessages();

        // Run task
        $manager = new scheduled_event_manager();
        $manager->execute($data->now + 1, $data->now - DAYSECS * 2);

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        // Only one notification was processed, because the other built-in notifs were disabled.
        self::assertCount(1, $messages);
        $message = reset($messages);

        $due_date_time_format = get_string("strftimedatefulllong", "langconfig");

        self::assertEquals('Test notification subject', $message->subject);
        self::assertStringContainsString('Test notification body', $message->fullmessage);
        self::assertStringContainsString('User1 last name', $message->fullmessage);
        self::assertStringContainsString('Test course with completion', $message->fullmessage);
        self::assertStringContainsString(userdate($data->now + DAYSECS * 3, $due_date_time_format, 99, false), $message->fullmessage);
        self::assertEquals($data->users[1]->id, $message->userto->id);

        // Check the logs
        $delivery_channels = json_decode($message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'resolver_class_name' => course_due_date_resolver::class,
                'context_id' => $event_context->get_context()->id,
                'logs' => [
                    [
                        'preference_id' => $preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                    ],
                ],
                'event_name' => get_string('notification_log_course_due_date', 'core', [
                    'resolver_title' => course_due_date_resolver::get_notification_title(),
                    'user' => 'User1 first name User1 last name',
                    'course' => 'Test course with completion',
                    'activity' => 'C1choice',
                ])
            ],
        ]);
    }
}
