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
use mod_facetoface\room;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\seminar;
use mod_facetoface\seminar_session_list;
use mod_facetoface\task\manage_virtualmeetings_adhoc_task;
use mod_facetoface\testing\generator as facetoface_generator;
use mod_facetoface\totara_notification\placeholder\event as event_placeholder;
use mod_facetoface\totara_notification\recipient\virtualmeeting_creators;
use mod_facetoface\totara_notification\resolver\virtual_meeting_creation_failed;
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
class mod_facetoface_totara_notification_resolver_virtual_meeting_creation_failed_testcase extends testcase {
    use \totara_notification\testing\notification_log_test_trait;

    private $course = null;
    private $seminar = null;
    private $seminarevent = null;
    private $system_custom_preference = null;

    /**
     * @return void
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

        // Create a course.
        $this->course = $generator->create_course(['fullname' => 'The first course']);

        // Create a seminar.
        $f2f_gen = facetoface_generator::instance();
        $f2f = $f2f_gen->create_instance(['course' => $this->course->id]);

        $this->seminar = new seminar($f2f->id);
        $this->seminar->set_waitlistautoclean(true);
        $this->seminar->save();

        $this->seminarevent = $f2f_gen->create_session_for_course($this->course);
        $this->seminarevent->set_facetoface($this->seminar->get_id());
        $this->seminarevent->set_allowoverbook(1); // Enable waitlist.
        $this->seminarevent->set_capacity(1);
        $this->seminarevent->save();

        // Create a custom notification in system context.
        $notification_generator = notification_generator::instance();
        $this->system_custom_preference = $notification_generator->create_notification_preference(
            virtual_meeting_creation_failed::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => virtualmeeting_creators::class,
                'recipients' => [virtualmeeting_creators::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Virtual meeting creation failure notification body'),
                        paragraph::create_json_node_with_content_nodes([
                            placeholder::create_node_from_key_and_label('recipient:first_name', 'Recipient last name'),
                            placeholder::create_node_from_key_and_label('course:full_name', 'Course name'),
                            placeholder::create_node_from_key_and_label('event:duration', 'Event duration'),
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
        $this->course = null;
        $this->seminar = null;
        $this->seminarevent = null;
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
    public function test_resolver_virtual_meeting_creation_failed(bool $site_allow_legacy, bool $use_legacy): void {
        global $DB;

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);
        // If the seminar name contains the string "fail" then virtual meeting room creation will fail!!!
        $this->seminar->set_name('name fail')
            ->set_legacy_notifications($use_legacy)
            ->save();


        // Create an admin user who creates a room - they will receive the notification.
        $room_creator = self::getDataGenerator()->create_user();
        $room = new room(facetoface_generator::instance()->add_virtualmeeting_room(
            ['name' => 'Test Room Name'],
            ['userid' => $room_creator->id, 'plugin' => 'poc_app']
        )->id);

        // Add the room to the session.
        $session =  $this->seminarevent->get_sessions()->get_first();
        $roomdate_vm = (new room_dates_virtualmeeting())
            ->set_roomid($room->get_id())
            ->set_sessionsdateid($session->get_id())
            ->set_virtualmeetingid(123)
            ->set_status(room_dates_virtualmeeting::STATUS_PENDING_UPDATE);
        $roomdate_vm->save();

        // Ensure the queues are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Trigger the event. The adhoc task fails because virtual meeting room creation fails,
        // because seminar name contains 'fail'.
        self::setAdminUser();
        $task = manage_virtualmeetings_adhoc_task::create_from_seminar_event_id(
            $this->seminarevent->get_id(),
            $room_creator->id
        );
        $task->execute();

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
            return;
        }

        // Ensure we have the one item in the queue.
        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Redirect messages.
        $sink = self::redirectMessages();

        $messages = $sink->get_messages();
        self::assertCount(0, $messages);

        // Run tasks.
        $task = new process_event_queue_task();
        $task->execute();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $messages = $sink->get_messages();
        self::assertCount(1, $messages);

        $message = reset($messages);

        self::assertEquals('Test notification subject', $message->subject);
        self::assertStringContainsString('Virtual meeting creation failure notification body', $message->fullmessage); // Body
        self::assertStringContainsString($room_creator->firstname, $message->fullmessage); // Recipient
        self::assertStringContainsString('course1', $message->fullmessage); // Course
        self::assertStringContainsString('1 min', $message->fullmessage); // Event
        self::assertStringContainsString('name fail', $message->fullmessage); // Seminar
        self::assertEquals($room_creator->id, $message->userto->id);

        // Check the logs
        $sessions = $this->seminarevent->get_sessions();
        $sessions->sort('timestart', seminar_session_list::SORT_DESC);
        $session_dates = $sessions->to_records(false);

        $event_time = empty($session_dates) ? '' : $session_dates[0]->timestart;

        $delivery_channels = json_decode($message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'resolver_class_name' => virtual_meeting_creation_failed::class,
                'context_id' => $this->seminar->get_context()->id,
                'logs' => [
                    [
                        'preference_id' => $this->system_custom_preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                    ],
                ],
                'event_name' => get_string('notification_log_virtual_meeting_creation_failed', 'mod_facetoface', [
                    'resolver_title' => virtual_meeting_creation_failed::get_notification_title(),
                    'course' => $this->course->fullname,
                    'activity' => 'name fail',
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
