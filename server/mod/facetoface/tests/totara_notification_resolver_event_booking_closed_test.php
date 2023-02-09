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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_facetoface
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\orm\query\builder;
use core_course\totara_notification\placeholder\activity as activity_placeholder;
use core_course\totara_notification\placeholder\course as course_placeholder;
use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user as user_placeholder;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session_list;
use mod_facetoface\testing\generator as facetoface_generator;
use mod_facetoface\totara_notification\placeholder\event as event_placeholder;
use mod_facetoface\totara_notification\recipient\notifiable_roles;
use mod_facetoface\totara_notification\resolver\event_booking_closed as event_booking_closed_resolver;
use mod_facetoface\totara_notification\seminar_notification_helper;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\json_editor\node\placeholder;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_facetoface_totara_notification_resolver_event_booking_closed_testcase extends testcase {
    use \totara_notification\testing\notification_log_test_trait;

    private $user = null;
    private $trainer = null;
    private $course = null;
    private $seminar = null;
    private $seminar_event = null;
    private $trainer_role = null;
    private $system_custom_preference = null;

    /**
     * @return void
     * @throws \coding_exception
     */
    protected function setUp(): void {
        parent::setUp();

        // Disable built-in notifications.
        builder::table('notification_preference')->delete();

        $generator = static::getDataGenerator();
        $f2f_gen = facetoface_generator::instance();

        // Create user.
        $this->user = $generator->create_user(['lastname' => "User lastname"]);
        $this->trainer = $generator->create_user(['lastname' => 'Trainer lastname']);

        // Create a course.
        $this->course = $generator->create_course(['fullname' => 'The test course']);

        // Enrol user
        $generator->enrol_user($this->user->id, $this->course->id);

        // Enrol the trainer
        $this->trainer_role = builder::table('role')
            ->select('id')
            ->where('shortname', 'teacher')
            ->one();

        $generator->enrol_user($this->trainer->id, $this->course->id, $this->trainer_role->id);

        // Create a seminar
        $f2f_data = [
            'course' => $this->course->id,
            'name' => 'Test seminar',
        ];

        $this->seminar = $f2f_gen->create_instance($f2f_data);
        $session_dates = [
            (object)[
                'sessiontimezone' => 'Pacific/Auckland',
                'timestart' => strtotime('+3 day 9am'),
                'timefinish' => strtotime('+3 day 3pm'),
            ],
        ];
        $session_data = (object)[
            'facetoface' => $this->seminar->id,
            'capacity' => 10,
            'sessiondates' => $session_dates,
        ];

        $session_id = $f2f_gen->add_session($session_data);
        $this->seminar_event = new seminar_event($session_id);
        $this->seminar_event
            ->set_registrationtimestart(time() - DAYSECS)
            ->set_registrationtimefinish(time() + DAYSECS)
            ->save();

        // Create a custom notification in system context.
        $notification_generator = notification_generator::instance();
        $this->system_custom_preference = $notification_generator->create_notification_preference(
            event_booking_closed_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => notifiable_roles::class,
                'recipients' => [notifiable_roles::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Event booking closed test notification body'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:last_name', 'Recipient last name'),
                            placeholder::create_node_from_key_and_label('course:full_name','Course full name'),
                            placeholder::create_node_from_key_and_label('activity:name', 'Seminar name'),
                            placeholder::create_node_from_key_and_label('event:duration','Event duration'),
                        ]),
                    ])
                ),
                'subject' => 'Test event booking closed notification subject',
                'subject_format' => FORMAT_PLAIN,
            ]
        );
    }

    /**
     * @return void
     */
    public function tearDown(): void {
        $this->user = null;
        $this->trainer = null;
        $this->course = null;
        $this->seminar = null;
        $this->seminar_event = null;
        $this->trainer_role = null;
        $this->system_custom_preference = null;

        parent::tearDown();
    }

    /**
     * @dataProvider data_provider_test_resolver
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     */
    public function test_resolver(bool $site_allow_legacy, bool $use_legacy) {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $this->seminar_event->get_seminar()->set_legacy_notifications($use_legacy)
            ->save();

        // Ensure all queues are empty.
        $DB->delete_records('notifiable_event_queue');
        static::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        static::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sink = static::redirectMessages();

        // Queue a notification
        $resolver = new event_booking_closed_resolver([
            'seminar_event_id' => $this->seminar_event->get_id(),
            'seminar_id' => $this->seminar->id,
            'module_id' => $this->seminar->cmid,
            'course_id' => $this->course->id,
        ]);

        seminar_notification_helper::create_seminar_notifiable_event_queue($this->seminar_event->get_seminar(), $resolver);

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
        } else {
            static::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
            static::assertEquals(0, $DB->count_records(notification_queue::TABLE));

            // Run task - no roles yet
            $task = new process_event_queue_task();
            $task->execute();

            static::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            static::assertEquals(0, $DB->count_records(notification_queue::TABLE));

            $messages = $sink->get_messages();
            static::assertEmpty($messages);

            // Event log should have been created
            self::verify_notification_logs([]);
            self::clear_notification_logs();
        }

        // Now add some notifiable_roles and repeat
        set_config('facetoface_session_rolesnotify', $this->trainer_role->id);

        seminar_notification_helper::create_seminar_notifiable_event_queue($this->seminar_event->get_seminar(), $resolver);

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
            $sink->close();
            return;
        }

        static::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        static::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Run task
        $task = new process_event_queue_task();
        $task->execute();

        static::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        static::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertEquals('Test event booking closed notification subject', $message->subject);
        self::assertStringContainsString('Event booking closed test notification body', $message->fullmessage);
        self::assertStringContainsString('Trainer lastname', $message->fullmessage); // Recipient
        self::assertStringContainsString('The test course', $message->fullmessage); // Course
        self::assertStringContainsString('Test seminar', $message->fullmessage); // Seminar
        self::assertStringContainsString('6 hours', $message->fullmessage); // Event
        self::assertEquals($this->trainer->id, $message->userto->id);

        // Check the logs
        $sessions = $this->seminar_event->get_sessions();
        $sessions->sort('timestart', seminar_session_list::SORT_DESC);
        $session_dates = $sessions->to_records(false);

        $event_time = empty($session_dates) ? '' : $session_dates[0]->timestart;

        $delivery_channels = json_decode($message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'resolver_class_name' => event_booking_closed_resolver::class,
                'context_id' => $this->seminar_event->get_seminar()->get_context()->id,
                'logs' => [
                    [
                        'preference_id' => $this->system_custom_preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                    ],
                ],
                'event_name' => get_string('notification_log_event_booking_closed', 'mod_facetoface', [
                    'resolver_title' => event_booking_closed_resolver::get_notification_title(),
                    'user' => 'Firstname1 User1 last name',
                    'course' => $this->course->fullname,
                    'activity' => 'Test seminar',
                    'date' => userdate($event_time),
                ])
            ],
        ]);
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
