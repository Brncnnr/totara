<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 Totara Learning Solutions Ltd
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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use core\orm\query\builder;
use core_phpunit\testcase;
use mod_facetoface\event\booking_booked;
use mod_facetoface\notice_sender;
use mod_facetoface\room;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup_helper;
use mod_facetoface\signup_status;
use mod_facetoface\task\send_notifications_task;
use mod_facetoface\testing\generator as facetoface_generator;
use mod_facetoface\trainer_helper;
use totara_job\job_assignment;

/**
 * Test behaviour when switching legacy notifications on and off
 * We need to ensure that
 *   - no new legacy messages are queued once legacy notifications are switched off
 *   - all legacy messages that were queued before switching notification system are still sent out at the next cron run
 */
class mod_facetoface_notifications_allow_legacy_setting_testcase extends testcase {

    private $user = null;
    private $manager = null;
    private $course = null;
    private $seminar_event = null;
    private $signup = null;

    public function setUp(): void {
        parent::setUp();

        $generator = static::getDataGenerator();

        // We don't want setup resultin in queued notification - therefore redirecting events
        $sink = static::redirectEvents();

        // Create a base user.
        $this->user = $generator->create_user(['lastname' => 'User1 last name']);

        // Create a manager.
        $this->manager = $generator->create_user(['lastname' => 'Manager1 last name']);

        // Assign the manager to the user.
        /** @var job_assignment $manager1job */
        $manager1job = job_assignment::create(['userid' => $this->manager->id, 'idnumber' => 'job1']);
        job_assignment::create([
            'userid' => $this->user->id,
            'idnumber' => 'userjob1',
            'managerjaid' => $manager1job->id
        ]);

        // Create a course.
        $this->course = $generator->create_course(['fullname' => 'The first course']);

        // Enrol user in the course
        $generator->enrol_user($this->user->id, $this->course->id);

        // Create seminar
        $f2f_gen = $generator->get_plugin_generator('mod_facetoface');
        $seminar_event = $f2f_gen->create_session_for_course($this->course);
        $this->seminar_event = new seminar_event($seminar_event->get_id());

        $this->signup = signup::create($this->user->id, $this->seminar_event)->save();
        signup_helper::signup($this->signup);
        $sink->close();
    }

    public function tearDown(): void {
        $this->user = null;
        $this->manager = null;
        $this->course = null;
        $this->seminar_event = null;
        $this->signup = null;

        parent::tearDown();
    }

    /**
     * Some events use notice_sender::send, others notice_sender::send_event and others notice_sender::send_notice.
     * Both notice_sender::send and notice_sender::send_event eventually call notice_sender::send_notice.
     * So, testing with one notification type should cover all the others.
     * There are a few exceptions that needs to be tested separately. (see later test functions in this file)
     *
     * @dataProvider data_provider_allow_legacy
     * @param bool $allow_legacy
     */
    public function test_notice_sender_send_notice(bool $allow_legacy) {
        set_config('facetoface_allow_legacy_notifications', (int)$allow_legacy);

        $queued = builder::table('task_adhoc')
            ->where('component', 'mod_facetoface')
            ->count();
        static::assertSame(0, $queued);

        notice_sender::confirm_booking($this->signup, MDL_F2F_TEXT);

        $queued = builder::table('task_adhoc')
            ->where('component', 'mod_facetoface')
            ->count();

        if ($allow_legacy) {
            static::assertNotSame(0, $queued);
        } else {
            static::assertSame(0, $queued);
        }

    }

    /**
     * The following notifications don't make use of notice_sender::send_??
     *      - registration_closure
     *      - send_notification_registration_expired
     *      - send_notification_session_under_capacity
     *      - send_notification_virtual_meeting_creation_failure
     */

    /**
     * @dataProvider data_provider_allow_legacy
     * @param bool $allow_legacy
     */
    public function test_notice_sender_registration_closure(bool $allow_legacy) {
        set_config('facetoface_allow_legacy_notifications', (int)$allow_legacy);

        $this->seminar_event->get_seminar()->set_approvaltype(seminar::APPROVAL_ADMIN)->save();

        $user2 = static::getDataGenerator()->create_user(['lastname' => 'User2 last name']);
        $signup2 = signup::create($user2->id, $this->seminar_event)->save();

        $queued = builder::table('task_adhoc')
            ->where('component', 'mod_facetoface')
            ->count();
        static::assertSame(0, $queued);

        notice_sender::registration_closure($this->seminar_event, $user2->id);

        $queued = builder::table('task_adhoc')
            ->where('component', 'mod_facetoface')
            ->count();

        if ($allow_legacy) {
            static::assertNotSame(0, $queued);
        } else {
            static::assertSame(0, $queued);
        }
    }

    /**
     * @dataProvider data_provider_allow_legacy
     * @param bool $allow_legacy
     */
    public function test_send_notification_registration_expired(bool $allow_legacy) {
        set_config('facetoface_allow_legacy_notifications', (int)$allow_legacy);

        // Assign a teacher and specify it as an event and notification role
        $role = builder::table('role')
            ->select('id')
            ->where('shortname', 'teacher')
            ->one();

        set_config('facetoface_session_roles', $role->id);
        set_config('facetoface_session_rolesnotify', $role->id);
        $user2 = static::getDataGenerator()->create_user(['lastname' => 'Trainer last name']);
        static::getDataGenerator()->enrol_user($user2->id, $this->course->id, $role->id);

        $helper = new trainer_helper($this->seminar_event);
        $helper->add_trainers($role->id, [$user2->id]);

        $sessions = $this->seminar_event->get_sessions();
        static::assertNotEmpty($sessions);
        $session = $sessions->get_first();
        $record = builder::table('facetoface_sessions')
            ->where('id', $session->get_sessionid())
            ->one();

        // Enrolling the trainer would have queued legacy events if allowed. We just need to confirm more are queued
        $queued_before = builder::table('task_adhoc')
            ->where('component', 'mod_facetoface')
            ->count();

        $notification = new \facetoface_notification((array)$record, false);
        $notification->send_notification_registration_expired($record);

        $queued_after = builder::table('task_adhoc')
            ->where('component', 'mod_facetoface')
            ->count();

        if ($allow_legacy) {
            static::assertGreaterThan($queued_before, $queued_after);
        } else {
            static::assertSame(0, $queued_before);
            static::assertSame(0, $queued_after);
        }
    }

    /**
     * @dataProvider data_provider_allow_legacy
     * @param bool $allow_legacy
     */
    public function test_send_under_capacity(bool $allow_legacy) {
        set_config('facetoface_allow_legacy_notifications', (int)$allow_legacy);

        // Assign a teacher and specify it as an event and notification role
        $role = builder::table('role')
            ->select('id')
            ->where('shortname', 'teacher')
            ->one();

        set_config('facetoface_session_roles', $role->id);
        set_config('facetoface_session_rolesnotify', $role->id);
        $user2 = static::getDataGenerator()->create_user(['lastname' => 'Trainer last name']);
        static::getDataGenerator()->enrol_user($user2->id, $this->course->id, $role->id);

        $helper = new trainer_helper($this->seminar_event);
        $helper->add_trainers($role->id, [$user2->id]);

        $this->seminar_event->set_mincapacity('2')
            ->set_cutoff(DAYSECS)
            ->save();

        $sessions = $this->seminar_event->get_sessions();
        static::assertNotEmpty($sessions);
        $session = $sessions->get_first();
        $record = builder::table('facetoface_sessions')
            ->where('id', $session->get_sessionid())
            ->one();

        // Enrolling the trainer would have queued legacy events if allowed. We just need to confirm more are queued
        $queued_before = builder::table('task_adhoc')
            ->where('component', 'mod_facetoface')
            ->count();

        $notification = new \facetoface_notification((array)$record, false);
        $notification->send_notification_session_under_capacity($record);

        $queued_after = builder::table('task_adhoc')
            ->where('component', 'mod_facetoface')
            ->count();

        if ($allow_legacy) {
            static::assertGreaterThan($queued_before, $queued_after);
        } else {
            static::assertSame(0, $queued_before);
            static::assertSame(0, $queued_after);
        }
    }

    /**
     * @dataProvider data_provider_allow_legacy
     * @param bool $allow_legacy
     */
    public function test_send_notification_virtual_meeting_creation_failure(bool $allow_legacy) {
        set_config('facetoface_allow_legacy_notifications', (int)$allow_legacy);

        // Create an admin user who creates a room - they will receive the notification.
        $room_creator = static::getDataGenerator()->create_user();
        $room = new room(facetoface_generator::instance()->add_virtualmeeting_room(
            ['name' => 'Test Room Name'],
            ['userid' => $room_creator->id, 'plugin' => 'poc_app']
        )->id);

        // Add the room to the session.
        $session =  $this->seminar_event->get_sessions()->get_first();
        $roomdate_vm = (new room_dates_virtualmeeting())
            ->set_roomid($room->get_id())
            ->set_sessionsdateid($session->get_id())
            ->set_virtualmeetingid(123)
            ->set_status(room_dates_virtualmeeting::STATUS_PENDING_UPDATE);
        $roomdate_vm->save();

        // Enrolling the trainer would have queued legacy events if allowed. We just need to confirm more are queued
        $queued_before = builder::table('task_adhoc')
            ->where('component', 'mod_facetoface')
            ->count();

        $sessiondata = ['facetoface' => $this->seminar_event->get_facetoface()];
        $notification = new facetoface_notification($sessiondata, false);
        $notification->send_notification_virtual_meeting_creation_failure($this->seminar_event);

        $queued_after = builder::table('task_adhoc')
            ->where('component', 'mod_facetoface')
            ->count();

        if ($allow_legacy) {
            static::assertGreaterThan($queued_before, $queued_after);
        } else {
            static::assertSame(0, $queued_before);
            static::assertSame(0, $queued_after);
        }
    }

    public function data_provider_allow_legacy(): array {
        return [
            [true],
            [false],
        ];
    }

    /**
     * Testing handling of legacy notifications when system is switched after event is queued but before cron is run
     */
    public function test_legacy_message_previously_queued_send_after_switch() {
        // Allow legacy and queue a booking confirmation
        set_config('facetoface_allow_legacy_notifications', 1);

        $generator = static::getDataGenerator();
        $user2 = $generator->create_user(['lastname' => 'User2 last name']);
        $generator->enrol_user($user2->id, $this->course->id);
        $user3 = $generator->create_user(['lastname' => 'User3 last name']);
        $generator->enrol_user($user3->id, $this->course->id);

        // Ensure all queues are empty
        $queued = builder::table('task_adhoc')
            ->where('component', 'mod_facetoface')
            ->count();
        static::assertSame(0, $queued);

        $signup2 = signup::create($user2->id, $this->seminar_event)->save();
        signup_status::create($signup2, new booked($signup2))->save();
        $cm = $signup2->get_seminar_event()->get_seminar()->get_coursemodule();
        $context = \context_module::instance($cm->id);
        $event = booking_booked::create_from_signup($signup2, $context);
        $event->trigger();

        $queued_legacy = builder::table('task_adhoc')
            ->where('component', 'mod_facetoface')
            ->count();

        static::assertSame(1, $queued_legacy);

        // Now BEFORE we run cron tasks
        //   - disable legacy notifications,
        //   - add another event (should use cn notifications)
        // Expecting the legacy queued emails to still be sent when cron is run

        set_config('facetoface_allow_legacy_notifications', 0);

        $signup3 = signup::create($user3->id, $this->seminar_event)->save();
        signup_status::create($signup3, new booked($signup3))->save();
        $cm = $signup3->get_seminar_event()->get_seminar()->get_coursemodule();
        $context = \context_module::instance($cm->id);
        $event = booking_booked::create_from_signup($signup3, $context);
        $event->trigger();

        $new_queued_legacy = builder::table('task_adhoc')
            ->where('component', 'mod_facetoface')
            ->count();

        static::assertSame($queued_legacy, $new_queued_legacy);

        // Verify legacy messages
        $sink = static::redirectMessages();
        $legacy_task = new send_notifications_task();
        $legacy_task->testing = true;
        $legacy_task->execute();
        static::executeAdhocTasks();
        $messages = $sink->get_messages();
        $sink->clear();

        static::assertSame($new_queued_legacy, count($messages));
        $message = reset($messages);
        static::assertEquals($user2->id, $message->useridto);
    }
}
