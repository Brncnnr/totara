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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package core_course
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\orm\query\builder;
use core_course\totara_notification\placeholder\course as course_placeholder;
use core_course\totara_notification\resolver\user_unenrolled_resolver;
use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user as user_placeholder;
use totara_core\extended_context;
use totara_job\job_assignment;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;
use totara_program\testing\generator as program_generator;
use totara_notification\json_editor\node\placeholder;
use totara_notification\recipient\subject;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class core_course_totara_notification_unenrolled_testcase extends testcase {
    use \totara_notification\testing\notification_log_test_trait;

    private $user = null;
    private $course = null;
    private $program = null;
    private $event_context = null;
    private $preference = null;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        // Make sure we're not testing with stale placeholder caches.
        user_placeholder::clear_instance_cache();
        course_placeholder::clear_instance_cache();

        // Disable built-in notifications.
        builder::table('notification_preference')->update(['enabled' => 0]);

        $generator = self::getDataGenerator();
        $programgen = program_generator::instance();

        // Create a base user.
        $this->user = $generator->create_user(['firstname' => 'User1 first name', 'lastname' => 'User1 last name']);

        // Create a manager.
        $manager = $generator->create_user(['firstname' => 'Manager1 first name', 'lastname' => 'Manager1 last name']);

        // Assign the manager to the user.
        /** @var job_assignment $manager1job */
        $manager1job = job_assignment::create(['userid' => $manager->id, 'idnumber' => 'job1']);
        job_assignment::create([
            'userid' => $this->user->id,
            'idnumber' => 'userjob1',
            'managerjaid' => $manager1job->id
        ]);

        // Create a course.
        $this->course = $generator->create_course(['fullname' => 'The first course']);

        // Create a program
        $this->program = $programgen->create_program(['fullname' => 'Program1 full name']);

        // Assign courses to the program.
        $coursesetdata = [
            [
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_CERT,
                'courses' => [$this->course],
            ],
        ];
        $programgen->legacy_add_coursesets_to_program($this->program, $coursesetdata);

        // Create a custom notification in event context.
        $this->event_context = extended_context::make_with_context(
            context_course::instance($this->course->id)
        );
        $notification_generator = notification_generator::instance();
        $this->preference = $notification_generator->create_notification_preference(
            user_unenrolled_resolver::class,
            $this->event_context,
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [subject::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('User unenrolled test notification body'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:last_name', 'Recipient last name'),
                            placeholder::create_node_from_key_and_label('subject:last_name', 'Subject last name'),
                            placeholder::create_node_from_key_and_label('managers:last_name', 'All managers last name'),
                            placeholder::create_node_from_key_and_label('course:full_name', 'Course name'),
                        ]),
                    ])
                ),
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
            ]
        );
    }

    /**
     * @return void
     */
    public function tearDown(): void {
        user_placeholder::clear_instance_cache();

        $this->user = null;
        $this->course = null;
        $this->program = null;
        $this->event_context = null;
        $this->preference = null;

        parent::tearDown();
    }

    public function test_resolver_single_course_enrolment(): void {
        global $DB;

        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Enrol the user1 in course1 then unenrol. Should result in 1 notification.
        $generator = self::getDataGenerator();
        $generator->enrol_user($this->user->id, $this->course->id, null, 'manual');
        $generator->unenrol_user($this->user->id, $this->course->id, 'manual');

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE, ['resolver_class_name' => user_unenrolled_resolver::class]));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Redirect messages.
        $sink = self::redirectMessages();

        // Run tasks.
        $task = new process_event_queue_task();
        $task->execute();

        // The notification should be sent directly without going into the notification_queue table.
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        // Only one notification was processed, because the other built-in notifs were disabled.
        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertEquals('Test notification subject', $message->subject);
        self::assertStringContainsString('User unenrolled test notification body', $message->fullmessage);
        self::assertStringContainsString('User1 last name', $message->fullmessage);
        self::assertStringContainsString('Manager1 last name', $message->fullmessage);
        self::assertStringContainsString('The first course', $message->fullmessage);
        self::assertEquals($this->user->id, $message->userto->id);

        // Check the logs
        $delivery_channels = json_decode($message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'resolver_class_name' => user_unenrolled_resolver::class,
                'context_id' => $this->event_context->get_context()->id,
                'logs' => [
                    [
                        'preference_id' => $this->preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                    ],
                ],
                'event_name' => get_string('notification_log_course_unenrolment', 'core', [
                    'resolver_title' => user_unenrolled_resolver::get_notification_title(),
                    'user' => 'User1 first name User1 last name',
                    'course' => 'The first course',
                ])
            ],
        ]);
    }

    public function test_resolver_multiple_course_enrolment(): void {
        global $DB;

        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Enrol the user1 in course1 as manual, self and program enrolment.
        $generator = self::getDataGenerator();
        $generator->enrol_user($this->user->id, $this->course->id, null, 'manual');
        $generator->enrol_user($this->user->id, $this->course->id, null, 'self');
        $generator->enrol_user($this->user->id, $this->course->id, null, 'totara_program');

        // Unenrol the manual enrolment.
        $generator->unenrol_user($this->user->id, $this->course->id, 'manual');

        // Ensure all are still empty, the user still enrolment into the course.
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE, ['resolver_class_name' => user_unenrolled_resolver::class]));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Unenrol the self enrolment.
        $generator->unenrol_user($this->user->id, $this->course->id, 'self');

        // Ensure all are still empty, the user still enrolment into the course.
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE, ['resolver_class_name' => user_unenrolled_resolver::class]));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Unenrol the program enrolment.
        $generator->unenrol_user($this->user->id, $this->course->id, 'totara_program');

        // The user no longer has enrolment into in the course.
        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE, ['resolver_class_name' => user_unenrolled_resolver::class]));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Redirect messages.
        $sink = self::redirectMessages();

        // Run tasks.
        $task = new process_event_queue_task();
        $task->execute();

        // The notification should be sent directly without going into the notification_queue table.
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        // Only one notification was processed, because the other built-in notifs were disabled.
        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertEquals('Test notification subject', $message->subject);
        self::assertStringContainsString('User unenrolled test notification body', $message->fullmessage);
        self::assertStringContainsString('User1 last name', $message->fullmessage);
        self::assertStringContainsString('Manager1 last name', $message->fullmessage);
        self::assertStringContainsString('The first course', $message->fullmessage);
        self::assertEquals($this->user->id, $message->userto->id);
    }
}
