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
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\testing\generator as facetoface_generator;
use mod_facetoface\totara_notification\placeholder\event as event_placeholder;
use mod_facetoface\totara_notification\recipient\notifiable_roles;
use mod_facetoface\totara_notification\resolver\event_under_minimum_bookings as event_under_minimum_bookings_resolver;
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
class mod_facetoface_totara_notification_resolver_event_under_minimum_bookings_testcase extends testcase {
    use \totara_notification\testing\notification_log_test_trait;

    private $users = [];
    private $course = null;
    private $seminars = [];
    private $seminar_events = [];
    private $teacher_role = null;

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

        // Create users.
        for ($i = 1; $i <= 5; $i++) {
            $this->users[$i] = $generator->create_user(['lastname' => "User {$i} lastname"]);
        }
        $this->users['teacher'] = $generator->create_user(['lastname' => 'Trainer lastname']);

        // Create a course.
        $this->course = $generator->create_course(['fullname' => 'The test course']);

        // Enrol users
        foreach ($this->users as $user) {
            $generator->enrol_user($user->id, $this->course->id);
        }

        // Enrol the trainer
        $this->teacher_role = builder::table('role')
            ->select('id')
            ->where('shortname', 'teacher')
            ->one();

        $generator->enrol_user($this->users['teacher']->id, $this->course->id, $this->teacher_role->id);

        // Create seminars
        $f2f_data = [
            'course' => $this->course->id,
            'name' => 'Multiple session dates',
        ];

        $this->seminars['multi_sessions'] = $f2f_gen->create_instance($f2f_data);
        $session_dates = [
            (object)[
                'sessiontimezone' => 'Pacific/Auckland',
                'timestart' => strtotime('+2 day 9am'),
                'timefinish' => strtotime('+2 day 3pm'),
            ],
            (object)[
                'sessiontimezone' => 'Pacific/Auckland',
                'timestart' => strtotime('+3 day 9am'),
                'timefinish' => strtotime('+3 day 3pm'),
            ],
        ];
        $session_data = (object)[
            'facetoface' => $this->seminars['multi_sessions']->id,
            'capacity' => 10,
            'sessiondates' => $session_dates,
            'mincapacity' => 2,
            'cutoff' => DAYSECS,
            'sendcapacityemail' => 1,
        ];

        $session_id = $f2f_gen->add_session($session_data);
        $this->seminar_events['multi_sessions'] = new seminar_event($session_id);

        $f2f_data = [
            'course' => $this->course->id,
            'name' => 'Single session',
        ];

        $this->seminars['single_session'] = $f2f_gen->create_instance($f2f_data);
        $session_dates = [
            (object)[
                'sessiontimezone' => 'Pacific/Auckland',
                'timestart' => strtotime('+3 day 9am'),
                'timefinish' => strtotime('+3 day 3pm'),
            ],
        ];
        $session_data = (object)[
            'facetoface' => $this->seminars['single_session']->id,
            'capacity' => 10,
            'sessiondates' => $session_dates,
            'mincapacity' => 2,
            'cutoff' => DAYSECS,
            'sendcapacityemail' => 1,
        ];

        $session_id = $f2f_gen->add_session($session_data);
        $this->seminar_events['single_session'] = new seminar_event($session_id);

        $f2f_data = [
            'course' => $this->course->id,
            'name' => 'No mincapacity',
        ];

        $this->seminars['no_mincapacity'] = $f2f_gen->create_instance($f2f_data);
        $session_dates = [
            (object)[
                'sessiontimezone' => 'Pacific/Auckland',
                'timestart' => strtotime('+2 day 9am'),
                'timefinish' => strtotime('+2 day 3pm'),
            ],
        ];
        $session_data = (object)[
            'facetoface' => $this->seminars['no_mincapacity']->id,
            'capacity' => 10,
            'sessiondates' => $session_dates,
            'mincapacity' => 0,
        ];

        $session_id = $f2f_gen->add_session($session_data);
        $this->seminar_events['no_mincapacity'] = new seminar_event($session_id);
    }

    /**
     * @return void
     */
    public function tearDown(): void {
        $this->users = [];
        $this->course = null;
        $this->seminars = [];
        $this->seminar_events = [];
        $this->teacher_role = null;

        parent::tearDown();
    }

    /**
     * @dataProvider data_provider_test_schedule
     * @param int $min_time
     * @param int $max_time
     * @param array $booked_users
     * @param array $expected_events
     */
    public function test_schedule(int $min_time, int $max_time, array $booked_users, array $expected_events): void {
        $resolver_class_name = event_under_minimum_bookings_resolver::class;

        $f2f_gen = facetoface_generator::instance();

        foreach ($booked_users as $event_key => $users) {
            foreach ($users as $user_key) {
                $f2f_gen->create_signup($this->users[$user_key], $this->seminar_events[$event_key]);
            }
        }

        $expected = [];
        foreach ($expected_events as $key => $start) {
            $expected[] = [
                'seminar_id' => $this->seminars[$key]->id,
                'seminar_event_id' => $this->seminar_events[$key]->get_id(),
                'module_id' => $this->seminars[$key]->cmid,
                'course_id' => $this->course->id,
                'time_start' => $start,
            ];
        }
        static::assert_scheduled_events($resolver_class_name, $min_time, $max_time, $expected);

        // Switch to legacy notifs - should prevent the event from being returned.
        set_config('facetoface_allow_legacy_notifications', 1);
        foreach ($this->seminars as $f2f) {
            self::assertEquals(1, $f2f->legacy_notifications); // Was set to legacy earlier, but site setting was off.
        }
        self::assert_scheduled_events($resolver_class_name, $min_time, $max_time, []);
    }

    /**
     * @dataProvider data_provider_test_resolver
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     */
    public function test_resolver(bool $site_allow_legacy, bool $use_legacy) {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);

        // Create a custom notification in system context.
        $notification_generator = notification_generator::instance();
        $system_custom_preference = $notification_generator->create_notification_preference(
            event_under_minimum_bookings_resolver::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => notifiable_roles::class,
                'recipients' => [notifiable_roles::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Event under minimum bookings test notification body'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:last_name', 'Recipient last name'),
                            placeholder::create_node_from_key_and_label('course:full_name','Course full name'),
                            placeholder::create_node_from_key_and_label('activity:name', 'Seminar name'),
                            placeholder::create_node_from_key_and_label('event:duration','Event duration'),
                        ]),
                    ])
                ),
                'subject' => 'Test event under minimum bookings notification subject',
                'subject_format' => FORMAT_PLAIN,
            ]
        );

        $this->seminar_events['multi_sessions']->get_seminar()->set_legacy_notifications($use_legacy)
            ->save();

        // Ensure all queues are empty.
        $DB->delete_records('notifiable_event_queue');
        static::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        static::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $sink = static::redirectMessages();

        // Queue a notification
        $session = $this->seminar_events['multi_sessions'];
        /** @var seminar $seminar */
        $seminar = $session->get_seminar();
        $event_data = [
            'seminar_event_id' => $session->get_id(),
            'seminar_id' => $seminar->get_id(),
            'module_id' => $this->seminars['multi_sessions']->cmid,
            'course_id' => $this->course->id,
            'time_start' => strtotime('+2 day 9am'),
        ];
        $resolver = new event_under_minimum_bookings_resolver($event_data);

        seminar_notification_helper::create_seminar_notifiable_event_queue(
            $seminar,
            $resolver
        );

        // Ensure the 2 events happen at different times
        self::waitForSecond();

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
        } else {
            static::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
            static::assertEquals(0, $DB->count_records(notification_queue::TABLE));

            // Needed for checking the logs
            $notification_time1 = $DB->get_field(notifiable_event_queue::TABLE, 'time_created', []);

            // Run task - no roles yet
            $task = new process_event_queue_task();
            $task->execute();

            static::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            static::assertEquals(0, $DB->count_records(notification_queue::TABLE));

            $messages = $sink->get_messages();
            static::assertEmpty($messages);
        }

        // Now add some notifiable_roles and repeat
        set_config('facetoface_session_rolesnotify', $this->teacher_role->id);

        seminar_notification_helper::create_seminar_notifiable_event_queue(
            $seminar,
            $resolver
        );

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
            $sink->close();
            return;
        }

        static::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        static::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Needed for checking the logs
        $notification_time2 = $DB->get_field(notifiable_event_queue::TABLE, 'time_created', []);

        // Run task
        $task = new process_event_queue_task();
        $task->execute();

        static::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        static::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertEquals('Test event under minimum bookings notification subject', $message->subject);
        self::assertStringContainsString('Event under minimum bookings test notification body', $message->fullmessage);
        self::assertStringContainsString('Trainer lastname', $message->fullmessage); // Recipient
        self::assertStringContainsString('The test course', $message->fullmessage); // Course
        self::assertStringContainsString('Multiple session dates', $message->fullmessage); // Seminar
        self::assertStringContainsString('1 day 6 hours', $message->fullmessage); // Event
        self::assertEquals($this->users['teacher']->id, $message->userto->id);

        // Check the logs
        $event_time = $event_data['time_start'];

        $delivery_channels = json_decode($message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'resolver_class_name' => event_under_minimum_bookings_resolver::class,
                'context_id' => $seminar->get_context()->id,
                'time_created' => $notification_time2,
                'logs' => [
                    [
                        'preference_id' => $system_custom_preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                    ],
                ],
                'event_name' => get_string('notification_log_event_under_minimum_bookings', 'mod_facetoface', [
                    'resolver_title' => event_under_minimum_bookings_resolver::get_notification_title(),
                    'course' => 'The test course',
                    'activity' => 'Multiple session dates',
                    'date' => userdate($event_time),
                ])
            ],
        ]);
    }

    public function data_provider_test_schedule(): array {
        return [
            [
                // Min after start
                'min_time' => strtotime('+2 days 10am'),
                'max_time' => strtotime('+2 days 4pm'),
                'booked_users' => [],
                'expected_events' => [],
            ],
            [
                // Max before start
                'min_time' => strtotime('+2 days 7am'),
                'max_time' => strtotime('+2 days 8am'),
                'booked_users' => [],
                'expected_events' => [],
            ],
            [
                // Max == start
                'min_time' => strtotime('+2 days 7am'),
                'max_time' => strtotime('+2 days 9am'),
                'booked_users' => [],
                'expected_events' => [],
            ],
            [
                // Max > start
                'min_time' => strtotime('+2 days 7am'),
                'max_time' => strtotime('+2 days 10am'),
                'booked_users' => [],
                'expected_events' => ['multi_sessions' => strtotime('+2 days 9am')]
            ],
            [
                // Min == start
                'min_time' => strtotime('+2 days 9am'),
                'max_time' => strtotime('+2 days 10am'),
                'booked_users' => [],
                'expected_events' => ['multi_sessions' => strtotime('+2 days 9am')]
            ],
            [
                // Some bookings
                'min_time' => strtotime('+2 days 7am'),
                'max_time' => strtotime('+3 days 10am'),
                'booked_users' => [
                    'single_session' => [1],
                ],
                'expected_events' => [
                    'multi_sessions' => strtotime('+2 days 9am'),
                    'single_session' => strtotime('+3 days 9am'),
                ],
            ],
            [
                // Min bookings reached
                'min_time' => strtotime('+2 days 7am'),
                'max_time' => strtotime('+3 days 10am'),
                'booked_users' => [
                    'multi_sessions' => [1, 2, 3],
                    'single_session' => [4, 5],
                ],
                'expected_events' => [],
            ],

        ];
    }

    public function data_provider_test_resolver(): array {
        return [
            [true, false],
            [true, true],
            [false, false],
            [false, true],
        ];
    }

    /**
     * @dataProvider data_provider_test_resolver
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     */
    public function test_get_scheduled_events(bool $site_allow_legacy, bool $use_legacy) {
        global $DB;

        // Setting to allow or not seminar legacy notifications.
        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);

        // Create data to test the event.
        $f2f_gen = facetoface_generator::instance();

        // Create seminar.
        $f2f_data = [
            'course' => $this->course->id,
            'name' => 'Seminar Now',
        ];

        $now = time();
        $seminar = $f2f_gen->create_instance($f2f_data);
        $session_dates = [
            (object)[
                'sessiontimezone' => 'Pacific/Auckland',
                'timestart' => $now,
                'timefinish' => $now + (MINSECS * 5),
            ]
        ];
        $session_data = (object)[
            'facetoface' => $seminar->id,
            'capacity' => 10,
            'sessiondates' => $session_dates,
            'mincapacity' => 2,
            'sendcapacityemail' => 1,
        ];

        $session_id = $f2f_gen->add_session($session_data);
        $seminar_event = new seminar_event($session_id);
        $seminar = $seminar_event->get_seminar();

        // Setting to allow legacy notifications for a particular seminar.
        $seminar->set_legacy_notifications($use_legacy)->save();

        // Start testing even under minimum booking CN notification.
        $resolver_class_name = event_under_minimum_bookings_resolver::class;

        // Remove the 'assigned' notifiable event queue record.
        $DB->delete_records('notifiable_event_queue');

        if ($site_allow_legacy && $use_legacy) {
            // No notification expected because CN is disabled for the seminar.
            self::assert_scheduled_events($resolver_class_name, 0, $now + 1, []);
        } else {
            self::assert_scheduled_events($resolver_class_name, 0, $now + 1, [
                [
                    "seminar_event_id" => $session_id,
                    "module_id" => $seminar->get_coursemodule()->id,
                    "course_id" => $this->course->id,
                    "seminar_id" => $seminar->get_id(),
                    "time_start" => $now
                ]
            ]);
        }
    }
}
