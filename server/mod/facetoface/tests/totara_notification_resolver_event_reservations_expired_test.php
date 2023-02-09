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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package core_course
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\orm\query\builder;
use core_course\totara_notification\placeholder\activity as activity_placeholder;
use core_course\totara_notification\placeholder\course as course_placeholder;
use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user as user_placeholder;
use mod_facetoface\reservations;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\seminar_session_list;
use mod_facetoface\testing\generator as facetoface_generator;
use mod_facetoface\totara_notification\placeholder\event as event_placeholder;
use mod_facetoface\totara_notification\resolver\event_reservations_expired;
use mod_facetoface\totara_notification\recipient\reservation_managers;
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
class mod_facetoface_totara_notification_resolver_event_reservations_expired_testcase extends testcase {
    use \totara_notification\testing\notification_log_test_trait;

    private $manager1 = null;
    private $manager2 = null;
    private $course = null;
    private $seminar = null;
    private $seminar_event = null;
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

        // Delete built-in notifications.
        builder::table('notification_preference')->delete();

        $generator = self::getDataGenerator();
        $facetoface_generator = facetoface_generator::instance();

        // Create a base user.
        $this->user = $generator->create_user(['lastname' => 'User1 last name']);

        // Create a course.
        $this->course = $generator->create_course(['fullname' => 'The first course']);

        // Create seminar.
        $seminar_record = $facetoface_generator->create_instance(['course' => $this->course->id]);
        $this->seminar = new seminar($seminar_record->id);

        // Create seminar event.
        $eventid = $facetoface_generator->add_session(
            [
                'facetoface' => $this->seminar->get_id(),
                'capacity' => 5,
                'sessiondates' => [
                    (object)[
                        'sessiontimezone' => '99',
                        'timestart' => time() + 2 * DAYSECS,
                        'timefinish' => time() + 3 * DAYSECS,
                        'roomids' => [],
                        'assetids' => [],
                        'facilitatorids' => [],
                    ],
                ],
            ],
        );

        // Load the object.
        $this->seminar_event = new seminar_event($eventid);

        // Create reservations.
        $this->manager1 = $generator->create_user();
        $this->manager2 = $generator->create_user();
        reservations::add($this->seminar_event, $this->manager1->id, 2, 0);
        reservations::add($this->seminar_event, $this->manager2->id, 0, 1);

        // Update the session to be in the past.
        $dates = [
            (object)[
                'id' => $this->seminar_event->get_sessions()->get_first()->get_id(),
                'sessiontimezone' => '99',
                'timestart' => time() - 3 * DAYSECS,
                'timefinish' => time() - 2 * DAYSECS,
                'roomids' => [],
                'assetids' => [],
                'facilitatorids' => [],
            ],
        ];
        seminar_event_helper::merge_sessions($this->seminar_event, $dates);

        // Create a custom notification in system context.
        $notification_generator = notification_generator::instance();
        $this->system_custom_preference = $notification_generator->create_notification_preference(
            event_reservations_expired::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => reservation_managers::class,
                'recipients' => [reservation_managers::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Test notification body'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:last_name', 'Recipient last name'),
                            placeholder::create_node_from_key_and_label('event:cost', 'Total cost'),
                            placeholder::create_node_from_key_and_label('course:full_name', 'Course full name'),
                            placeholder::create_node_from_key_and_label('activity:name', 'Seminar name'),
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

        $this->manager1 = null;
        $this->manager2 = null;
        $this->course = null;
        $this->seminar = null;
        $this->seminar_event = null;
        $this->system_custom_preference = null;

        // We always clear all related caches after testing with them, to prevent leaks between tests.
        user_placeholder::clear_instance_cache();
        event_placeholder::clear_instance_cache();
        course_placeholder::clear_instance_cache();
        activity_placeholder::clear_instance_cache();

        parent::tearDown();
    }

    /**
     * @dataProvider data_provider_test_resolver
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     */
    public function test_resolver(bool $site_allow_legacy, bool $use_legacy): void {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        $this->seminar->set_legacy_notifications($use_legacy)->save();

        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Now cancel the reservations. This function finds all expired reservations and removes them.
        reservations::remove_after_deadline(true);

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
        // Two notifications were processed, one for each reservation manager.
        self::assertCount(2, $messages);

        $recipients = [];
        $nchannels = 0;

        foreach ($messages as $message) {
            self::assertEquals('Test notification subject', $message->subject); // Subject
            self::assertStringContainsString('Test notification body', $message->fullmessage); // Body
            self::assertStringContainsString('$100', $message->fullmessage); // Event
            self::assertStringContainsString('The first course', $message->fullmessage); // Course
            self::assertStringContainsString('Seminar 1', $message->fullmessage); // Seminar
            switch ($message->userto->id) {
                case $this->manager1->id:
                    self::assertEquals($this->manager1->firstname, $message->userto->firstname);
                    break;
                case $this->manager2->id:
                    self::assertEquals($this->manager2->firstname, $message->userto->firstname);
                    break;
                default:
                    self::fail('Unrecognised recipient');
            }
            $recipients[] = $message->userto->id;

            $delivery_channels = json_decode($message->totara_notification_delivery_channels);
            $nchannels += count($delivery_channels);
        }
        self::assertEquals([$this->manager1->id, $this->manager2->id], $recipients);

        // Check the logs
        $sessions = $this->seminar_event->get_sessions();
        $sessions->sort('timestart', seminar_session_list::SORT_DESC);
        $session_dates = $sessions->to_records(false);
        $event_time = empty($session_dates) ? '' : $session_dates[0]->timestart;

        self::verify_notification_logs([
            [
                'resolver_class_name' => event_reservations_expired::class,
                'context_id' => $this->seminar->get_context()->id,
                'logs' => [
                    [
                        'preference_id' => $this->system_custom_preference->get_id(),
                        'recipients' => 2,
                        'channels' => $nchannels,
                    ],
                ],
                'event_name' => get_string('notification_log_event_reservations_expired', 'mod_facetoface', [
                    'resolver_title' => event_reservations_expired::get_notification_title(),
                    'course' => $this->course->fullname,
                    'activity' => 'Seminar 1',
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
