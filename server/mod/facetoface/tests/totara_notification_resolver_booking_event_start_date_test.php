<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\orm\query\builder;
use core_course\totara_notification\placeholder\activity as activity_placeholder;
use core_course\totara_notification\placeholder\course as course_placeholder;
use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user as user_placeholder;
use mod_facetoface\seminar;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\user_cancelled;
use mod_facetoface\signup_helper;
use mod_facetoface\testing\generator as facetoface_generator;
use mod_facetoface\totara_notification\placeholder\event as event_placeholder;
use mod_facetoface\totara_notification\placeholder\signup as signup_placeholder;
use mod_facetoface\totara_notification\resolver\booking_event_start_date;
use mod_facetoface\totara_notification\seminar_notification_helper;
use totara_core\extended_context;
use totara_job\job_assignment;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\json_editor\node\placeholder;
use totara_notification\recipient\subject;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;


/**
 * @group totara_notification
 */
class mod_facetoface_totara_notification_resolver_booking_event_start_date_testcase extends testcase {
    use \totara_notification\testing\notification_log_test_trait;

    private $user = null;
    private $course = null;
    private $seminar = null;
    private $signup = null;
    private $seminar_event = null;
    private $time_start = null;
    private $system_custom_preference = null;

    /**
     * @inheritDocs
     * @throws coding_exception
     */
    public function setUp(): void {
        // We always clear all related caches before testing with them, to prevent leaks between tests.
        user_placeholder::clear_instance_cache();
        event_placeholder::clear_instance_cache();
        course_placeholder::clear_instance_cache();
        activity_placeholder::clear_instance_cache();
        signup_placeholder::clear_instance_cache();

        // Delete built-in notifications.
        builder::table('notification_preference')->delete();

        $generator = self::getDataGenerator();

        $this->time_start = time() + 60; // Put it 1min out so it doesn't start before signup.

        // Create a base user.
        $this->user = $generator->create_user(['lastname' => 'User1 last name']);

        // Create a manager.
        $manager = $generator->create_user(['lastname' => 'Manager1 last name']);

        // Assign the manager to the user.

        /** @var job_assignment $manager1job */
        $manager1job = job_assignment::create(['userid' => $manager->id, 'idnumber' => 'job1']);
        job_assignment::create([
            'userid' => $this->user->id,
            'idnumber' => 'userjob1',
            'managerjaid' => $manager1job->id
        ]);

        // Create a seminar.
        $this->course = self::getDataGenerator()->create_course();
        $seminar_generator = facetoface_generator::instance();
        $this->seminar = $seminar_generator->create_instance([
            'course' => $this->course->id,
            'approvaltype' => seminar::APPROVAL_NONE // Make sure we don't have approvals getting in the way.

        ]);
        $this->seminar_event = $seminar_generator->add_session([
            'facetoface' => $this->seminar->id,
            'sessiondates' => [
                $this->time_start,
                $this->time_start + (DAYSECS * 3)
            ]
        ]);

        // Enrol the user in the course.
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id);

        // Create a user signup.
        $this->signup = signup::create($this->user->id, $this->seminar_event);
        if (signup_helper::can_signup($this->signup)) {
            signup_helper::signup($this->signup);
        } else {
            // Was having issues before putting timestart out by 60s.
            $reasons = signup_helper::get_failures($this->signup);
            $this->fail(implode(', ', $reasons));
        }

        // Create a custom notification in system context.
        $this->notification_generator = notification_generator::instance();
        $this->system_custom_preference = $this->notification_generator->create_notification_preference(
            booking_event_start_date::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [subject::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Test notification body'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:first_name', 'Recipient last name'),
                            placeholder::create_node_from_key_and_label('subject:last_name', 'Subject last name'),
                            placeholder::create_node_from_key_and_label('managers:last_name', 'All managers last name'),
                            placeholder::create_node_from_key_and_label('course:full_name', 'Course name'),
                            placeholder::create_node_from_key_and_label('event:duration', 'Event duration'),
                            placeholder::create_node_from_key_and_label('activity:name', 'Seminar name'),
                            placeholder::create_node_from_key_and_label('signup:cost', 'Personal cost'),
                        ]),
                    ])
                ),
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"recipients":["status_booked"]}',
            ]
        );
    }

    /**
     * @inheritDocs
     */
    public function tearDown(): void {
        // We always clear all related caches after testing as well, to prevent leaks between tests.
        user_placeholder::clear_instance_cache();
        event_placeholder::clear_instance_cache();
        course_placeholder::clear_instance_cache();
        activity_placeholder::clear_instance_cache();
        signup_placeholder::clear_instance_cache();

        $this->user = null;
        $this->course = null;
        $this->seminar = null;
        $this->signup = null;
        $this->seminar_event = null;
        $this->time_start = null;
        $this->system_custom_preference = null;
    }

    /**
     * @return void
     * @throws \mod_facetoface\exception\signup_exception
     */
    public function test_schedule() {
        $now = time();
        $resolver_class_name = booking_event_start_date::class;

        // First make sure the scheduler doesn't find
        self::assert_scheduled_events($resolver_class_name, $now + DAYSECS, $now + DAYSECS * 2, []);
        self::assert_scheduled_events($resolver_class_name, $now - DAYSECS, $now - DAYSECS * 2, []);

        $f2f = new seminar($this->seminar->id);
        $expected = [
            'id' => $this->signup->get_id(),
            'seminar_id' => $f2f->get_id(),
            'seminar_event_id' => $this->seminar_event,
            'user_id' => $this->user->id,
            "status_code" => booked::get_code(),
            'module_id' => $f2f->get_coursemodule()->id,
            'course_id' => $this->course->id,
            'timestart' => $this->time_start
        ];
        self::assert_scheduled_events($resolver_class_name, $now - DAYSECS, $now + DAYSECS, [$expected]);

        // Switch to legacy notifs - should prevent the event from being returned.
        set_config('facetoface_allow_legacy_notifications', 1);
        self::assertEquals(1, $f2f->get_legacy_notifications()); // Was set to legacy earlier, but site setting was off.
        self::assert_scheduled_events($resolver_class_name, $now - DAYSECS, $now + DAYSECS, []);
    }

    /**
     * @dataProvider data_provider_test_resolver
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     */
    public function test_resolver(bool $site_allow_legacy, bool $use_legacy) {
        global $DB;

        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Redirect messages.
        $sink = self::redirectMessages();

        // Run tasks.
        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $f2f = new seminar($this->seminar->id);
        $f2f->set_legacy_notifications($use_legacy)
            ->save();

        $resolver = new booking_event_start_date([
            'id' => $this->signup->get_id(),
            'seminar_event_id' => $this->seminar_event,
            'seminar_id' => $this->seminar->id,
            'user_id' => $this->user->id,
            "status_code" => booked::get_code(),
            'module_id' => $f2f->get_coursemodule()->id,
            'course_id' => $this->course->id,
            'timestart' => $this->time_start
        ]);

        seminar_notification_helper::create_seminar_notifiable_event_queue($f2f, $resolver);

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
            $sink->close();
            return;
        }

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Run tasks.
        $task = new process_event_queue_task();
        $task->execute();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);

        $message = reset($messages);

        self::assertEquals('Test notification subject', $message->subject);
        self::assertStringContainsString('Test notification body', $message->fullmessage); // Body
        self::assertStringContainsString('Firstname1', $message->fullmessage); // Recipient
        self::assertStringContainsString('User1 last name', $message->fullmessage); // Subject
        self::assertStringContainsString('Manager1 last name', $message->fullmessage); // Manager
        self::assertStringContainsString($this->course->fullname, $message->fullmessage); // Course
        self::assertStringContainsString('5 days', $message->fullmessage); // Event
        self::assertStringContainsString('Seminar 1', $message->fullmessage); // Seminar
        self::assertStringContainsString('$100', $message->fullmessage); // Signup
        self::assertEquals($this->user->id, $message->userto->id);

        // Check the logs
        $event_time = $this->time_start;

        $delivery_channels = json_decode($message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'resolver_class_name' => booking_event_start_date::class,
                'context_id' => $f2f->get_context()->id,
                'logs' => [
                    [
                        'preference_id' => $this->system_custom_preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                    ],
                ],
                'event_name' => get_string('notification_log_booking_event_start_date', 'mod_facetoface', [
                    'resolver_title' => booking_event_start_date::get_notification_title(),
                    'user' => 'Firstname1 User1 last name',
                    'course' => $this->course->fullname,
                    'activity' => 'Seminar 1',
                    'date' => userdate($event_time),
                ])
            ],
        ]);
    }

    /**
     * @dataProvider data_provider_test_resolver
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     */
    public function test_resolver_with_attendance_status_user_cancelled(bool $site_allow_legacy, bool $use_legacy) {
        global $DB;

        // Reset exist custom notification in system context.
        $this->system_custom_preference->delete_custom();
        $this->system_custom_preference = $this->notification_generator->create_notification_preference(
            booking_event_start_date::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [subject::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Test notification body'),
                    ])
                ),
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"recipients":["status_user_cancelled"]}',
            ]
        );

        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Redirect messages.
        $sink = self::redirectMessages();

        // Run tasks.
        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $f2f = new seminar($this->seminar->id);
        $f2f->set_legacy_notifications($use_legacy)
            ->save();

        $resolver = new booking_event_start_date([
            'id' => $this->signup->get_id(),
            'seminar_event_id' => $this->seminar_event,
            'seminar_id' => $this->seminar->id,
            'user_id' => $this->user->id,
            "status_code" => user_cancelled::get_code(),
            'module_id' => $f2f->get_coursemodule()->id,
            'course_id' => $this->course->id,
            'timestart' => $this->time_start
        ]);

        seminar_notification_helper::create_seminar_notifiable_event_queue($f2f, $resolver);

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
            $sink->close();
            return;
        }

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Run tasks.
        $task = new process_event_queue_task();
        $task->execute();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);

        $message = reset($messages);

        self::assertEquals('Test notification subject', $message->subject);
        self::assertEquals($this->user->id, $message->userto->id);
    }

    /**
     * @dataProvider data_provider_test_resolver
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     */
    public function test_resolver_with_multiple_attendance_status(bool $site_allow_legacy, bool $use_legacy) {
        global $DB;

        // Reset exist custom notification in system context.
        $this->system_custom_preference->delete_custom();
        $this->system_custom_preference = $this->notification_generator->create_notification_preference(
            booking_event_start_date::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [subject::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Test notification body'),
                    ])
                ),
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"recipients":["status_user_cancelled", "status_booked"]}',
            ]
        );

        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Redirect messages.
        $sink = self::redirectMessages();

        // Run tasks.
        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $f2f = new seminar($this->seminar->id);
        $f2f->set_legacy_notifications($use_legacy)
            ->save();

        $resolver = new booking_event_start_date([
            'id' => $this->signup->get_id(),
            'seminar_event_id' => $this->seminar_event,
            'seminar_id' => $this->seminar->id,
            'user_id' => $this->user->id,
            "status_code" => user_cancelled::get_code(),
            'module_id' => $f2f->get_coursemodule()->id,
            'course_id' => $this->course->id,
            'timestart' => $this->time_start
        ]);

        seminar_notification_helper::create_seminar_notifiable_event_queue($f2f, $resolver);

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
            $sink->close();
            return;
        }

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Run tasks.
        $task = new process_event_queue_task();
        $task->execute();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);

        $message = reset($messages);

        self::assertEquals('Test notification subject', $message->subject);
        self::assertEquals($this->user->id, $message->userto->id);
    }

    /**
     * @dataProvider data_provider_test_resolver
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     */
    public function test_resolver_with_no_attendance_status(bool $site_allow_legacy, bool $use_legacy) {
        global $DB;

        // Reset exist custom notification in system context.
        $this->system_custom_preference->delete_custom();
        $this->system_custom_preference = $this->notification_generator->create_notification_preference(
            booking_event_start_date::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [subject::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Test notification body'),
                    ])
                ),
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"recipients":[]}',
            ]
        );

        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Redirect messages.
        $sink = self::redirectMessages();

        // Run tasks.
        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $f2f = new seminar($this->seminar->id);
        $f2f->set_legacy_notifications($use_legacy)
            ->save();

        $resolver = new booking_event_start_date([
            'id' => $this->signup->get_id(),
            'seminar_event_id' => $this->seminar_event,
            'seminar_id' => $this->seminar->id,
            'user_id' => $this->user->id,
            "status_code" => user_cancelled::get_code(),
            'module_id' => $f2f->get_coursemodule()->id,
            'course_id' => $this->course->id,
            'timestart' => $this->time_start
        ]);

        seminar_notification_helper::create_seminar_notifiable_event_queue($f2f, $resolver);

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
            $sink->close();
            return;
        }

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Run tasks.
        $task = new process_event_queue_task();
        $task->execute();

        // No email to send.
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
    }

    public function data_provider_test_resolver(): array {
        return [
            [true, false],
            [true, true],
            [false, false],
            [false, true],
        ];
    }
}
