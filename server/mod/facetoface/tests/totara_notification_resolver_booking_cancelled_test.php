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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package mod_facetoface
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\orm\query\builder;
use core_course\totara_notification\placeholder\activity as activity_placeholder;
use core_course\totara_notification\placeholder\course as course_placeholder;
use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user as user_placeholder;
use mod_facetoface\exception\signup_exception;
use mod_facetoface\seminar;
use mod_facetoface\seminar_session;
use mod_facetoface\seminar_session_list;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\user_cancelled;
use mod_facetoface\signup_status;
use mod_facetoface\testing\generator as facetoface_generator;
use mod_facetoface\totara_notification\placeholder\event as event_placeholder;
use mod_facetoface\totara_notification\placeholder\signup as signup_placeholder;
use mod_facetoface\totara_notification\resolver\booking_cancelled;
use totara_core\extended_context;
use totara_job\job_assignment;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\json_editor\node\placeholder;
use totara_notification\recipient\subject;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;


defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_facetoface_totara_notification_resolver_booking_cancelled_testcase extends testcase {
    use \totara_notification\testing\notification_log_test_trait;

    private $user = null;
    private $course = null;
    private $seminar = null;
    private $seminarevent = null;
    private $system_custom_preference = null;

    /**
     * @return void
     * @throws coding_exception
     */
    protected function setUp(): void {
        parent::setUp();

        // We always clear all related caches before testing with them, to prevent leaks between tests.
        user_placeholder::clear_instance_cache();
        event_placeholder::clear_instance_cache();
        course_placeholder::clear_instance_cache();
        activity_placeholder::clear_instance_cache();
        signup_placeholder::clear_instance_cache();

        // Delete built-in notifications.
        builder::table('notification_preference')->delete();

        $generator = self::getDataGenerator();

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

        // Create a seminar.
        $f2f_gen = facetoface_generator::instance();
        $f2f = $f2f_gen->create_instance(['course' => $this->course->id]);

        $this->seminar = new seminar($f2f->id);
        $this->seminarevent = $f2f_gen->create_session_for_course($this->course);
        $this->seminarevent->set_facetoface($this->seminar->get_id())->save();

        $this->seminar->set_attendancetime(seminar::EVENT_ATTENDANCE_UNRESTRICTED)->save();
        $this->seminar->set_approvalrole(seminar::APPROVAL_SELF)->save();
        $this->seminar->set_approvaltype(seminar::APPROVAL_NONE)->save();

        // Create a custom notification in system context.
        $notification_generator = notification_generator::instance();
        $this->system_custom_preference = $notification_generator->create_notification_preference(
            booking_cancelled::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [subject::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('User booking test notification body'),
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
                'additional_criteria' => '{"ical":["include_ical_attachment"]}',
            ]
        );
    }

    /**
     * @return void
     */
    public function tearDown(): void {
        $this->user = null;
        $this->course = null;
        $this->seminar = null;
        $this->seminarevent = null;
        $this->system_custom_preference = null;

        // We always clear all related caches after testing with them, to prevent leaks between tests.
        user_placeholder::clear_instance_cache();
        event_placeholder::clear_instance_cache();
        course_placeholder::clear_instance_cache();
        activity_placeholder::clear_instance_cache();
        signup_placeholder::clear_instance_cache();

        parent::tearDown();
    }

    /**
     * @dataProvider data_provider_test_resolver
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     * @throws signup_exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_resolver_booking_cancelled(bool $site_allow_legacy, bool $use_legacy): void {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $this->seminar->set_legacy_notifications($use_legacy)
            ->save();

        // Book user.
        $signup = signup::create($this->user->id, $this->seminarevent)->save();
        signup_status::create($signup, new booked($signup))->save();
        $this->assertInstanceOf(booked::class, $signup->get_state());

        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Now cancel the booking.
        $signup->switch_state(user_cancelled::class);

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
            return;
        }

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
        self::assertStringContainsString('User booking test notification body', $message->fullmessage); // Body
        self::assertStringContainsString('User1 first name', $message->fullmessage); // Recipient
        self::assertStringContainsString('User1 last name', $message->fullmessage); // Subject
        self::assertStringContainsString('Manager1 last name', $message->fullmessage); // Manager
        self::assertStringContainsString('course1', $message->fullmessage); // Course
        self::assertStringContainsString('1 min', $message->fullmessage); // Event
        self::assertStringContainsString('Seminar 1', $message->fullmessage); // Seminar
        self::assertStringContainsString('$100', $message->fullmessage); // Signup
        self::assertEquals($this->user->id, $message->userto->id);

        // Check the logs
        $sessions = $this->seminarevent->get_sessions();
        $sessions->sort('timestart', seminar_session_list::SORT_DESC);
        $session_dates = $sessions->to_records(false);

        $event_time = empty($session_dates) ? '' : $session_dates[0]->timestart;

        $delivery_channels = json_decode($message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'resolver_class_name' => booking_cancelled::class,
                'context_id' => $this->seminar->get_context()->id,
                'logs' => [
                    [
                        'preference_id' => $this->system_custom_preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                    ],
                ],
                'event_name' => get_string('notification_log_booking_cancelled', 'mod_facetoface', [
                    'resolver_title' => booking_cancelled::get_notification_title(),
                    'user' => 'User1 first name User1 last name',
                    'course' => 'The first course',
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
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws signup_exception
     */
    public function test_resolver_booking_cancelled_with_ical_enable_and_one_session(bool $site_allow_legacy, bool $use_legacy): void {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $this->seminar->set_legacy_notifications($use_legacy)
            ->save();

        // Book user.
        $signup = signup::create($this->user->id, $this->seminarevent)->save();
        signup_status::create($signup, new booked($signup))->save();
        $this->assertInstanceOf(booked::class, $signup->get_state());

        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Now cancel the booking.
        $signup->switch_state(user_cancelled::class);

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
            return;
        }

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
        self::assertEquals($this->user->id, $message->userto->id);
        self::assertCount(1, $message->attachment_list); // One iCal attachment
        self::assertArrayHasKey('Session1.ics', $message->attachment_list); // iCal 1
    }

    /**
     * @dataProvider data_provider_test_resolver
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws signup_exception
     */
    public function test_resolver_booking_cancelled_with_ical_enable_and_multiple_sessions(bool $site_allow_legacy, bool $use_legacy): void {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $this->seminar->set_legacy_notifications($use_legacy)
            ->save();

        // Add new session.
        $sessions  = $this->seminarevent->get_sessions();
        $time = time() + 3600;
        $session = new seminar_session();
        $session->set_sessionid($this->seminarevent->get_id())
            ->set_timestart($time)
            ->set_timefinish($time + 7200)
            ->save();
        $sessions->add($session);

        // Book user.
        $signup = signup::create($this->user->id, $this->seminarevent)->save();
        signup_status::create($signup, new booked($signup))->save();
        $this->assertInstanceOf(booked::class, $signup->get_state());

        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Now cancel the booking.
        $signup->switch_state(user_cancelled::class);

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
            return;
        }

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
        self::assertEquals($this->user->id, $message->userto->id);
        self::assertCount(2, $message->attachment_list); // Two iCal attachments
        self::assertArrayHasKey('Session1.ics', $message->attachment_list); // iCal 1
        self::assertArrayHasKey('Session2.ics', $message->attachment_list); // iCal 2
    }

    /**
     * @dataProvider data_provider_test_resolver
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     * @throws signup_exception
     */
    public function test_resolver_booking_cancelled_with_ical_disabled(bool $site_allow_legacy, bool $use_legacy): void {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);

        // Reset exist custom notification in system context.
        $this->system_custom_preference->delete_custom();
        $notification_generator = notification_generator::instance();
        $notification_generator->create_notification_preference(
            booking_cancelled::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => subject::class,
                'recipients' => [subject::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'subject' => 'Test notification subject',
                'subject_format' => FORMAT_PLAIN,
                'additional_criteria' => '{"ical":[]}',
            ]
        );

        $this->seminar->set_legacy_notifications($use_legacy)
            ->save();

        // Book user.
        $signup = signup::create($this->user->id, $this->seminarevent)->save();
        signup_status::create($signup, new booked($signup))->save();
        $this->assertInstanceOf(booked::class, $signup->get_state());

        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Now cancel the booking.
        $signup->switch_state(user_cancelled::class);

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
            return;
        }

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
        self::assertEquals($this->user->id, $message->userto->id);
        self::assertCount(0, $message->attachment_list); // No iCal attachment
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
