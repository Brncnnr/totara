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
 * @author David Curry <david.curry@totaralearning.com>
 * @package core_course
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\orm\query\builder;
use core_course\totara_notification\resolver\course_completed_resolver;
use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user as user_placeholder;
use totara_core\extended_context;
use totara_job\job_assignment;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;
use totara_notification\json_editor\node\placeholder;
use totara_notification\recipient\subject;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class core_course_totara_notification_course_completed_testcase extends testcase {
    use \totara_notification\testing\notification_log_test_trait;

    private $user = null;
    private $course1 = null;
    private $course2 = null;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        user_placeholder::clear_instance_cache();

        // Disable built-in notifications.
        builder::table('notification_preference')->update(['enabled' => 0]);

        $generator = self::getDataGenerator();
        $completiongen = $generator->get_plugin_generator('core_completion');

        // Create a base user.
        $this->user = $generator->create_user(['firstname' => 'User1 first name', 'lastname' => 'User1 last name']);

        // Create 2 managers for the user.
        $manager1 = $generator->create_user(['firstname' => 'Manager1 first name', 'lastname' => 'Manager1 last name']);
        $manager2 = $generator->create_user(['firstname' => 'Manager2 first name', 'lastname' => 'Manager2 last name']);

        // Assign the managers to the user.
        $manager1job = job_assignment::create(['userid' => $manager1->id, 'idnumber' => 'job1']);
        $manager2job = job_assignment::create(['userid' => $manager2->id, 'idnumber' => 'job2']);
        job_assignment::create([
            'userid' => $this->user->id,
            'idnumber' => 'userjob1',
            'managerjaid' => $manager1job->id
        ]);
        job_assignment::create([
            'userid' => $this->user->id,
            'idnumber' => 'userjob2',
            'managerjaid' => $manager2job->id
        ]);

        // Create two courses.
        $this->course1 = $generator->create_course(['fullname' => 'The first course']);
        $this->course2 = $generator->create_course(['fullname' => 'The second course']);

        // Set up completion for the courses.
        $completiongen->set_completion_criteria($this->course1, [COMPLETION_CRITERIA_TYPE_SELF => 1]);
        $completiongen->set_completion_criteria($this->course2, [COMPLETION_CRITERIA_TYPE_SELF => 1]);

        // Enrol the test user in the courses.
        $generator->enrol_user($this->user->id, $this->course1->id);
        $generator->enrol_user($this->user->id, $this->course2->id);
    }

    /**
     * @return void
     */
    public function tearDown(): void {
        user_placeholder::clear_instance_cache();

        $this->user = null;
        $this->course1 = null;
        $this->course2 = null;

        parent::tearDown();
    }

    public function test_custom_notification(): void {
        global $DB;

        // Create a custom notification in event context.
        $event_context = extended_context::make_with_context(
            context_course::instance($this->course1->id)
        );
        $notification_generator = notification_generator::instance();
        $preference = $notification_generator->create_notification_preference(
            course_completed_resolver::class,
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
                            placeholder::create_node_from_key_and_label('course:full_name', 'Course name'),
                            placeholder::create_node_from_key_and_label('course_completion:completion_date', 'Completion date'),
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

        // Trigger the course completion.
        $timecompleted = time();
        $ccompletion = new completion_completion(['course' => $this->course1->id, 'userid' => $this->user->id]);
        $ccompletion->mark_complete($timecompleted);

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
        // Only one notification was processed, because the other built-in notifs were disabled.
        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertEquals('Test notification subject', $message->subject);
        self::assertStringContainsString('Test notification body', $message->fullmessage);
        self::assertStringContainsString('User1 last name', $message->fullmessage);
        self::assertStringContainsString('The first course', $message->fullmessage);
        self::assertStringContainsString(userdate($timecompleted), $message->fullmessage);
        self::assertEquals($this->user->id, $message->userto->id);

        // Check the logs
        $delivery_channels = json_decode($message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'resolver_class_name' => course_completed_resolver::class,
                'context_id' => $event_context->get_context()->id,
                'logs' => [
                    [
                        'preference_id' => $preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                    ],
                ],
                'event_name' => get_string('notification_log_course_completed', 'core', [
                    'resolver_title' => course_completed_resolver::get_notification_title(),
                    'user' => 'User1 first name User1 last name',
                    'course' => 'The first course',
                    'activity' => 'C1choice',
                ])
            ],
        ]);
    }

    public function test_get_scheduled_events(): void {
        global $DB;

        $resolver_class_name = course_completed_resolver::class;

        // Remove the 'assigned' notifiable event queue record.
        $DB->delete_records('notifiable_event_queue');

        $timecomplete1 = time();
        $timecomplete2 = $timecomplete1 - HOURSECS;

        // No scheduled events because nothing is completed.
        self::assert_scheduled_events($resolver_class_name, 0, $timecomplete1 + 1, []);

        // Set course1 to complete - current time.
        $ccompletion = new completion_completion(['course' => $this->course1->id, 'userid' => $this->user->id]);
        $ccompletion->mark_complete($timecomplete1);

        // Set course2 to complete - an hour ago.
        $ccompletion = new completion_completion(['course' => $this->course2->id, 'userid' => $this->user->id]);
        $ccompletion->mark_complete($timecomplete2);

        // Empty result for min_time after completion.
        self::assert_scheduled_events($resolver_class_name, $timecomplete1 + 1, $timecomplete1 + 2, []);

        // Empty result for max_time before completion.
        self::assert_scheduled_events($resolver_class_name, $timecomplete1 - MINSECS, $timecomplete1 - 1, []);

        // Empty result for max_time = completed time.
        self::assert_scheduled_events($resolver_class_name, $timecomplete1 - MINSECS, $timecomplete1, []);

        // Result expected for min_time = completed time.
        self::assert_scheduled_events($resolver_class_name, $timecomplete1, $timecomplete1 + 1, [
            ['course_id' => $this->course1->id, 'user_id' => $this->user->id, 'time_completed' => $timecomplete1],
        ]);

        // Result expected for min_time < completed time.
        self::assert_scheduled_events($resolver_class_name, $timecomplete1 - 1, $timecomplete1 + 1, [
            ['course_id' => $this->course1->id, 'user_id' => $this->user->id, 'time_completed' => $timecomplete1],
        ]);

        // Only course2 completion.
        self::assert_scheduled_events($resolver_class_name, $timecomplete1 - DAYSECS, $timecomplete1 - 1, [
            ['course_id' => $this->course2->id, 'user_id' => $this->user->id, 'time_completed' => $timecomplete2],
        ]);

        // Both completions included in time period.
        self::assert_scheduled_events($resolver_class_name, $timecomplete1 - DAYSECS, $timecomplete1 + 1, [
            ['course_id' => $this->course1->id, 'user_id' => $this->user->id, 'time_completed' => $timecomplete1],
            ['course_id' => $this->course2->id, 'user_id' => $this->user->id, 'time_completed' => $timecomplete2],
        ]);
    }

    public function test_warnings(): void {
        $course_completion_disabled = self::getDataGenerator()->create_course(['enablecompletion' => 0]);
        $course_completion_enabled = self::getDataGenerator()->create_course(['enablecompletion' => 1]);

        $system_context = extended_context::make_system();
        $course_context_disabled = extended_context::make_with_context(
            context_course::instance($course_completion_disabled->id)
        );
        $course_context_enabled = extended_context::make_with_context(
            context_course::instance($course_completion_enabled->id)
        );
        $extended_course_context = extended_context::make_with_context(
            context_course::instance($course_completion_disabled->id),
            'test_component',
            'test_area',
            123
        );

        self::assertEmpty(course_completed_resolver::get_warnings($system_context));
        self::assertNotEmpty(course_completed_resolver::get_warnings($course_context_disabled));
        self::assertEmpty(course_completed_resolver::get_warnings($course_context_enabled));
        self::assertEmpty(course_completed_resolver::get_warnings($extended_course_context));
    }
}
