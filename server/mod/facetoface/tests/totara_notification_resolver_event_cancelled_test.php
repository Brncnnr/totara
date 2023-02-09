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
 * @author Gihan Hewaralalage <gihan.hewaralalage@totaralearning.com>
 * @package core_course
 */

use core\json_editor\helper\document_helper;
use core\json_editor\node\paragraph;
use core\orm\query\builder;
use core_course\totara_notification\placeholder\activity as activity_placeholder;
use core_course\totara_notification\placeholder\course as course_placeholder;
use mod_facetoface\totara_notification\placeholder\event as event_placeholder;
use core_phpunit\testcase;
use core_user\totara_notification\placeholder\user as user_placeholder;
use mod_facetoface\totara_notification\resolver\event_cancelled;
use totara_core\extended_context;
use totara_notification\entity\notifiable_event_queue;
use totara_notification\entity\notification_queue;
use totara_notification\task\process_event_queue_task;
use totara_notification\testing\generator as notification_generator;
use totara_notification\json_editor\node\placeholder;
use mod_facetoface\seminar;
use mod_facetoface\{signup};
use mod_facetoface\signup\state\{booked};
use mod_facetoface\{seminar_session_list, signup_status};
use mod_facetoface\event\{booking_booked};

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_facetoface_totara_notification_resolver_event_cancelled_testcase extends testcase {
    use \totara_notification\testing\notification_log_test_trait;

    private $user = null;
    private $course = null;
    private $seminar = null;
    private $seminarevent = null;
    private $system_custom_preference = null;

    /**
     * @return void
     * @throws \coding_exception
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

        // Create a base user.
        $this->user = $generator->create_user(['lastname' => 'User1 last name']);

        // Create a course.
        $this->course = $generator->create_course(['fullname' => 'The first course']);

        // Create a custom notification in system context.
        $notification_generator = notification_generator::instance();
        $this->system_custom_preference = $notification_generator->create_notification_preference(
            \mod_facetoface\totara_notification\resolver\event_cancelled::class,
            extended_context::make_system(),
            [
                'schedule_offset' => 0,
                'recipient' => \mod_facetoface\totara_notification\recipient\event_role::class,
                'recipients' => [\mod_facetoface\totara_notification\recipient\event_role::class],
                'body_format' => FORMAT_JSON_EDITOR,
                'body' => document_helper::json_encode_document(
                    document_helper::create_document_from_content_nodes([
                        paragraph::create_json_node_from_text('Event trainer cancellation notification body'),
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

        parent::tearDown();
    }

    /**
     * @dataProvider data_provider_test_resolver
     * @param bool $site_allow_legacy
     * @param bool $use_legacy
     */
    public function test_resolver_event_cancelled(bool $site_allow_legacy, bool $use_legacy): void {
        global $DB;

        $generator = self::getDataGenerator();

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);

        $f2f_gen = $generator->get_plugin_generator('mod_facetoface');
        $f2f = $f2f_gen->create_instance(['course' => $this->course->id]);

        $this->seminar = new seminar($f2f->id);
        $seminarevent = $f2f_gen->create_session_for_course($this->course);
        $seminarevent->set_facetoface($this->seminar->get_id())->save();

        $this->seminar->set_attendancetime(seminar::EVENT_ATTENDANCE_UNRESTRICTED)
            ->set_approvalrole(seminar::APPROVAL_ROLE)
            ->set_approvaltype(seminar::APPROVAL_ROLE)
            ->set_legacy_notifications($use_legacy)
            ->save();

        $role = $DB->get_record('role', array('shortname' => 'teacher'));
        $DB->set_field('facetoface', 'approvalrole', $role->id, ['id' => $this->seminar->get_id()]);
        $seminarevent = $this->seminar->get_events()->current();

        $sessions = $seminarevent->get_sessions();
        $sessions->sort('timestart', seminar_session_list::SORT_DESC);
        $session_dates = $sessions->to_records(false);
        $event_time = empty($session_dates) ? '' : $session_dates[0]->timestart;

        // Assign teacher1 to seminar event.
        $teacher1 = $this->getDataGenerator()->create_user(['lastname' => 'trainer last name']);
        $this->getDataGenerator()->enrol_user($teacher1->id, $this->seminar->get_course(), $role->id);
        $teachers[] = $teacher1->id;
        $form[$role->id] = $teachers;

        $helper = new \mod_facetoface\trainer_helper($seminarevent);
        foreach ($form as $roleid => $trainers) {
            $helper->add_trainers($roleid, $trainers);
        }
        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $signup = signup::create($this->user->id, $seminarevent)->save();
        signup_status::create($signup, new booked($signup))->save();

        // Now cancel the seminar event.
        $seminarevent->cancel();

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
            return;
        }

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE, ['resolver_class_name' => event_cancelled::class]));
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

        self::assertEquals('Test notification subject', $message->subject); // Subject
        self::assertStringContainsString('Event trainer cancellation notification body', $message->fullmessage); // Body
        self::assertStringContainsString('trainer last name', $message->fullmessage); // trainer
        self::assertStringContainsString('$100', $message->fullmessage); // Event
        self::assertStringContainsString('The first course', $message->fullmessage); // Course
        self::assertStringContainsString('Seminar 1', $message->fullmessage); // Seminar
        self::assertEquals($teacher1->id, $message->userto->id); // trainer
        self::assertEquals($teacher1->firstname, $message->userto->firstname); // trainer

        // Check the logs
        $delivery_channels = json_decode($message->totara_notification_delivery_channels);
        self::verify_notification_logs([
            [
                'resolver_class_name' => event_cancelled::class,
                'context_id' => $this->seminar->get_context()->id,
                'logs' => [
                    [
                        'preference_id' => $this->system_custom_preference->get_id(),
                        'recipients' => 1,
                        'channels' => count($delivery_channels),
                    ],
                ],
                'event_name' => get_string('notification_log_event_cancelled', 'mod_facetoface', [
                    'resolver_title' => event_cancelled::get_notification_title(),
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
     */
    public function test_resolver_event_cancelled_and_deleted(bool $site_allow_legacy, bool $use_legacy): void {
        global $DB;

        $generator = self::getDataGenerator();

        set_config('facetoface_allow_legacy_notifications', (int)$site_allow_legacy);

        $f2f_gen = $generator->get_plugin_generator('mod_facetoface');
        $f2f = $f2f_gen->create_instance(['course' => $this->course->id]);

        $this->seminar = new seminar($f2f->id);
        $seminarevent = $f2f_gen->create_session_for_course($this->course);
        $seminarevent->set_facetoface($this->seminar->get_id())->save();

        $this->seminar->set_attendancetime(seminar::EVENT_ATTENDANCE_UNRESTRICTED)
            ->set_approvalrole(seminar::APPROVAL_ROLE)
            ->set_approvaltype(seminar::APPROVAL_ROLE)
            ->set_legacy_notifications($use_legacy)
            ->save();

        $role = $DB->get_record('role', array('shortname' => 'teacher'));
        $DB->set_field('facetoface', 'approvalrole', $role->id, ['id' => $this->seminar->get_id()]);
        $seminarevent = $this->seminar->get_events()->current();

        // Assign teacher1 to seminar event.
        $teacher1 = $this->getDataGenerator()->create_user(['lastname' => 'trainer last name']);
        $this->getDataGenerator()->enrol_user($teacher1->id, $this->seminar->get_course(), $role->id);
        $teachers[] = $teacher1->id;
        $form[$role->id] = $teachers;

        $helper = new \mod_facetoface\trainer_helper($seminarevent);
        foreach ($form as $roleid => $trainers) {
            $helper->add_trainers($roleid, $trainers);
        }
        // Ensure all are empty.
        $DB->delete_records('notifiable_event_queue');
        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        $signup = signup::create($this->user->id, $seminarevent)->save();
        signup_status::create($signup, new booked($signup))->save();

        // Now cancel the seminar event.
        $seminarevent->cancel();

        if ($site_allow_legacy && $use_legacy) {
            self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE));
            self::assertEquals(0, $DB->count_records(notification_queue::TABLE));
            return;
        }

        self::assertEquals(1, $DB->count_records(notifiable_event_queue::TABLE, ['resolver_class_name' => event_cancelled::class]));
        self::assertEquals(0, $DB->count_records(notification_queue::TABLE));

        // Now delete the seminar event.
        $seminarevent->delete();

        self::assertEquals(0, $DB->count_records(notifiable_event_queue::TABLE, ['resolver_class_name' => event_cancelled::class]));
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
