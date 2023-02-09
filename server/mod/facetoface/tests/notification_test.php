<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

use core\entity\adhoc_task;
use core\orm\query\builder;
use core_phpunit\testcase;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\signup;
use mod_facetoface\signup_helper;
use mod_facetoface\signup_status;
use mod_facetoface\task\send_user_message_adhoc_task;
use mod_Facetoface\testing\generator;
use totara_job\job_assignment;
use mod_facetoface\signup\state\requested;

class mod_facetoface_notification_testcase extends testcase {
    /**
     * @var stdClass|null
     */
    private $user_one;

    /**
     * @var stdClass|null
     */
    private $user_two;

    /**
     * @return void
     */
    protected function setUp(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

        // Setup the global settings.
        set_config("facetoface_selectjobassignmentonsignupglobal", 1);
        set_config('facetoface_allow_legacy_notifications', 1);

        $generator = self::getDataGenerator();
        $this->user_one = $generator->create_user(["firstname" => "User", "lastname" => "One"]);
        $this->user_two = $generator->create_user(["firstname" => "User", "lastname" => "Two"]);
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->user_one = null;
        $this->user_two = null;
    }

    /**
     * This test is to make sure that we send the message to the temporary manager
     * when the permanent manager is not provided.
     *
     * @return void
     */
    public function test_send_message_to_temporary_manager(): void {
        self::setAdminUser();

        $generator = self::getDataGenerator();
        $course = $generator->create_course();

        // Enrol user one to the course.
        $generator->enrol_user($this->user_one->id, $course->id);

        // Adding user two as a temporary manager of user one.
        $temp_job_assignment = job_assignment::create_default($this->user_two->id);

        $job_assignment = job_assignment::create_default(
            $this->user_one->id,
            [
                'tempmanagerjaid' => $temp_job_assignment->id,
                'tempmanagerexpirydate' => time() + WEEKSECS
            ]
        );

        // Clear the user in session.
        self::setUser(null);

        $seminar_record = $generator->create_module(
            "facetoface",
            [
                "course" => $course->id,
                "approvaltype" => seminar::APPROVAL_MANAGER,
                "selectjobassignmentonsignup" => true
            ]
        );

        $seminar_generator = generator::instance();
        $session_id = $seminar_generator->add_session(["facetoface" => $seminar_record->id]);

        $seminar_event = new seminar_event($session_id);

        // Create sign-up for user one in seminar.
        $db = builder::get_db();
        self::assertFalse(
            $db->record_exists(
                signup::DBTABLE,
                [
                    "userid" => $this->user_one->id,
                    "sessionid" => $seminar_event->get_id()
                ]
            )
        );

        $sign_up = signup::create($this->user_one->id, $seminar_event, MDL_F2F_TEXT);
        $sign_up->set_jobassignmentid($job_assignment->id);

        // No adhoc task were queued for send manager message yet.
        self::assertEquals(
            0,
            $db->count_records(adhoc_task::TABLE, ["classname" => '\\' . send_user_message_adhoc_task::class])
        );

        // Check that the user one does not get the notification yet.
        self::assertFalse(
            $db->record_exists(
                "facetoface_notification_sent",
                [
                    "userid" => $this->user_one->id,
                    "sessionid" => $seminar_event->get_id()
                ]
            )
        );

        self::assertTrue(signup_helper::can_signup($sign_up));
        signup_helper::signup($sign_up);

        self::assertTrue(
            $db->record_exists(
                signup::DBTABLE,
                [
                    "userid" => $this->user_one->id,
                    "sessionid" => $seminar_event->get_id()
                ]
            )
        );

        self::assertEquals(requested::get_code(), $sign_up->get_state()::get_code());

        // User one should be signed up as requested.
        self::assertTrue(
            $db->record_exists(
                signup_status::DBTABLE,
                [
                    "signupid" => $sign_up->get_id(),
                    "statuscode" => requested::get_code()
                ]
            )
        );

        // Check that there are two sending message task queue.
        // No adhoc task were queued for send manager message yet.
        self::assertEquals(
            2,
            $db->count_records(adhoc_task::TABLE, ["classname" => '\\' . send_user_message_adhoc_task::class])
        );

        $message_sink = self::redirectMessages();
        self::assertEquals(0, $message_sink->count());
        self::assertEmpty($message_sink->get_messages());

        // Execute the adhoc tasks.
        self::executeAdhocTasks();

        // Check that the user one had received the notification.
        $notification_id = $db->get_field(
            "facetoface_notification_sent",
            "notificationid",
            [
                "userid" => $this->user_one->id,
                "sessionid" => $seminar_event->get_id()
            ]
        );

        self::assertNotNull($notification_id);

        $messages =  $message_sink->get_messages();
        self::assertNotEmpty($messages);
        self::assertCount(2, $messages);

        // First message is to the signup user.
        $first_message = reset($messages);
        self::assertIsObject($first_message);

        self::assertObjectHasAttribute("useridto", $first_message);
        self::assertEquals($this->user_one->id, $first_message->useridto);

        self::assertObjectHasAttribute("smallmessage", $first_message);
        self::assertObjectHasAttribute("fullmessage", $first_message);
        self::assertObjectHasAttribute("fullmessagehtml", $first_message);
        self::assertObjectHasAttribute("subject", $first_message);

        // Build the message to the user.
        $notification = $db->get_record("facetoface_notification", ["id" => $notification_id], "*", MUST_EXIST);
        $seminar_event_record = $seminar_event->to_record();
        $seminar_event_record->course = $course->id;
        $seminar_event_record->sessiondates = $seminar_event->get_sessions(true)->to_records(false);

        $subject =   facetoface_message_substitutions(
            format_string($notification->title),
            format_string($course->fullname),
            $seminar_record->name,
            $this->user_one,
            $seminar_event_record,
            $seminar_event->get_id(),
            $seminar_record->approvalrole
        );

        $body = facetoface_message_substitutions(
            format_text($notification->body, FORMAT_HTML),
            format_string($course->fullname),
            $seminar_record->name,
            $this->user_one,
            $seminar_event_record,
            $seminar_event->get_id(),
            $seminar_record->approvalrole
        );

        self::assertEquals(
            $subject,
            $first_message->subject
        );

        self::assertEquals(
            format_text_email($body, FORMAT_HTML),
            $first_message->fullmessage
        );

        $second_message = end($messages);
        self::assertIsObject($second_message);
        self::assertObjectHasAttribute("useridto", $second_message);
        self::assertEquals($this->user_two->id, $second_message->useridto);

        self::assertObjectHasAttribute("fullmessage", $second_message);
        self::assertObjectHasAttribute("fullmessagehtml", $second_message);
        self::assertObjectHasAttribute("smallmessage", $second_message);
        self::assertObjectHasAttribute("subject", $second_message);

        // Build manager message prefix
        self::assertEquals($subject, $second_message->subject);

        $manager_prefix = facetoface_message_substitutions(
            format_text($notification->managerprefix, FORMAT_HTML),
            format_string($course->fullname),
            $seminar_record->name,
            $this->user_one,
            $seminar_event_record,
            $seminar_event->get_id(),
            $seminar_record->approvalrole
        );

        self::assertEquals(
            format_text_email($manager_prefix, FORMAT_HTML) . format_text_email($body, FORMAT_HTML),
            $second_message->fullmessage
        );
    }

    /**
     * This test is to make sure that we send the message to both the temporary
     * manager and the permanent manager when the user sign up to a session.
     *
     * @return void
     */
    public function test_send_message_to_both_manager_and_temporary_manager(): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();
        $user_three = $generator->create_user();

        // Create the job assignment for user three as manager job assignment.
        $manager_job_assignment = job_assignment::create_default($user_three->id);
        $temporary_job_assignment = job_assignment::create_default($this->user_two->id);
        $job_assignment = job_assignment::create_default(
            $this->user_one->id,
            [
                "tempmanagerjaid" => $temporary_job_assignment->id,
                "tempmanagerexpirydate" => time() + WEEKSECS,
                "managerjaid" => $manager_job_assignment->id
            ]
        );

        // Clear the user in session.
        self::setUser(null);
        $course = $generator->create_course();
        $generator->enrol_user($this->user_one->id, $course->id);
        $seminar_record = $generator->create_module(
            "facetoface",
            [
                "course" => $course->id,
                "approvaltype" => seminar::APPROVAL_MANAGER,
                "selectjobassignmentonsignup" => true
            ]
        );

        $seminar_generator = generator::instance();
        $session_id = $seminar_generator->add_session(["facetoface" => $seminar_record->id]);

        $seminar_event = new seminar_event($session_id);

        // Create sign-up for user one in seminar.
        $db = builder::get_db();
        self::assertFalse(
            $db->record_exists(
                signup::DBTABLE,
                [
                    "userid" => $this->user_one->id,
                    "sessionid" => $seminar_event->get_id()
                ]
            )
        );

        // Check that the user one does not get the notification yet.
        self::assertFalse(
            $db->record_exists(
                "facetoface_notification_sent",
                [
                    "sessionid" => $seminar_event->get_id(),
                    "userid" => $this->user_one->id
                ]
            )
        );

        $sign_up = signup::create($this->user_one->id, $seminar_event, MDL_F2F_TEXT);
        $sign_up->set_jobassignmentid($job_assignment->id);

        // Check that there are zero adhoc tasks are queued prior to the sign up.
        self::assertEquals(
            0,
            $db->count_records(adhoc_task::TABLE, ["classname" => '\\' . send_user_message_adhoc_task::class])
        );

        self::assertTrue(signup_helper::can_signup($sign_up));
        signup_helper::signup($sign_up);

        self::assertEquals(
            3,
            $db->count_records(adhoc_task::TABLE, ["classname" => '\\' . send_user_message_adhoc_task::class])
        );

        $message_sink = self::redirectMessages();
        self::assertEquals(0, $message_sink->count());
        self::assertEmpty($message_sink->get_messages());

        // Execute the adhoc tasks.
        self::executeAdhocTasks();

        // Check that the user one had received the notification.
        $notification_id = $db->get_field(
            "facetoface_notification_sent",
            "notificationid",
            [
                "userid" => $this->user_one->id,
                "sessionid" => $seminar_event->get_id()
            ]
        );

        self::assertNotNull($notification_id);

        $messages =  $message_sink->get_messages();
        self::assertNotEmpty($messages);
        self::assertCount(3, $messages);

        // First message is to the signup user.
        $first_message = reset($messages);
        self::assertIsObject($first_message);

        self::assertObjectHasAttribute("useridto", $first_message);
        self::assertEquals($this->user_one->id, $first_message->useridto);

        self::assertObjectHasAttribute("smallmessage", $first_message);
        self::assertObjectHasAttribute("fullmessage", $first_message);
        self::assertObjectHasAttribute("fullmessagehtml", $first_message);
        self::assertObjectHasAttribute("subject", $first_message);

        // Build the message to the user.
        $notification = $db->get_record("facetoface_notification", ["id" => $notification_id], "*", MUST_EXIST);
        $seminar_event_record = $seminar_event->to_record();
        $seminar_event_record->course = $course->id;
        $seminar_event_record->sessiondates = $seminar_event->get_sessions(true)->to_records(false);

        $subject =   facetoface_message_substitutions(
            format_string($notification->title),
            format_string($course->fullname),
            $seminar_record->name,
            $this->user_one,
            $seminar_event_record,
            $seminar_event->get_id(),
            $seminar_record->approvalrole
        );

        $body = facetoface_message_substitutions(
            format_text($notification->body, FORMAT_HTML),
            format_string($course->fullname),
            $seminar_record->name,
            $this->user_one,
            $seminar_event_record,
            $seminar_event->get_id(),
            $seminar_record->approvalrole
        );

        self::assertEquals(
            $subject,
            $first_message->subject
        );

        self::assertEquals(
            format_text_email($body, FORMAT_HTML),
            $first_message->fullmessage
        );

        // Second message is for the permanent manager.
        $second_message = next($messages);
        self::assertIsObject($second_message);
        self::assertObjectHasAttribute("useridto", $second_message);
        self::assertEquals($user_three->id, $second_message->useridto);

        self::assertObjectHasAttribute("fullmessage", $second_message);
        self::assertObjectHasAttribute("fullmessagehtml", $second_message);
        self::assertObjectHasAttribute("smallmessage", $second_message);
        self::assertObjectHasAttribute("subject", $second_message);

        // Build manager message prefix
        self::assertEquals($subject, $second_message->subject);

        $manager_prefix = facetoface_message_substitutions(
            format_text($notification->managerprefix, FORMAT_HTML),
            format_string($course->fullname),
            $seminar_record->name,
            $this->user_one,
            $seminar_event_record,
            $seminar_event->get_id(),
            $seminar_record->approvalrole
        );

        self::assertEquals(
            format_text_email($manager_prefix, FORMAT_HTML) . format_text_email($body, FORMAT_HTML),
            $second_message->fullmessage
        );

        // Last message is for the temporary manager
        $third_message = end($messages);
        self::assertIsObject($third_message);
        self::assertObjectHasAttribute("useridto", $third_message);
        self::assertEquals($this->user_two->id, $third_message->useridto);

        self::assertObjectHasAttribute("fullmessage", $third_message);
        self::assertObjectHasAttribute("fullmessagehtml", $third_message);
        self::assertObjectHasAttribute("smallmessage", $third_message);
        self::assertObjectHasAttribute("subject", $third_message);

        // Build manager message prefix
        self::assertEquals($subject, $third_message->subject);

        $manager_prefix = facetoface_message_substitutions(
            format_text($notification->managerprefix, FORMAT_HTML),
            format_string($course->fullname),
            $seminar_record->name,
            $this->user_one,
            $seminar_event_record,
            $seminar_event->get_id(),
            $seminar_record->approvalrole
        );

        self::assertEquals(
            format_text_email($manager_prefix, FORMAT_HTML) . format_text_email($body, FORMAT_HTML),
            $third_message->fullmessage
        );
    }

    public function test_message_substitutions_session_customfields() {
        self::setAdminUser();

        $generator = self::getDataGenerator();
        $course = $generator->create_course();
        $seminar_generator = $generator->get_plugin_generator('mod_facetoface');

        $customfield_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        $customfields = $customfield_generator->create_multiselect('facetoface_session', ['event_multi' => ['opt1' => 'Option 1', 'opt2' => 'Option 2', 'opt3' => 'Option 3']]);
        $customfield = reset($customfields);

        // Create 2 sessions 1 with CF data and one without
        $seminar = $seminar_generator->create_instance([
            'name' => 'Test Facetoface',
            'course' => $course->id
        ]);

        $session_id = $seminar_generator->add_session(['facetoface' => $seminar->id, 'sessiondates' => []]);

        $seminar_event = new seminar_event($session_id);
        $seminar_event_record = $seminar_event->to_record();
        $seminar_event_record->course = $course->id;
        $seminar_event_record->sessiondates = $seminar_event->get_sessions(true)->to_records(false);

        // No need for a full notification, just some text to test substitutions
        $notification_text = "This is some custom notification. [session:event_multi]";

        $substituted_text = facetoface_message_substitutions(
            format_text($notification_text, FORMAT_HTML),
            format_string($course->fullname),
            $seminar->name,
            $this->user_one,
            $seminar_event_record,
            $seminar_event_record->id
        );

        self::assertStringNotContainsString('[session:event_muilti]', $substituted_text);

        // Set data for session custom field and try substitutions again
        $customfield_generator->set_multiselect($seminar_event_record, $customfield, array('Option 2'), 'facetofacesession', 'facetoface_session');

        $substituted_text_2 = facetoface_message_substitutions(
            format_text($notification_text, FORMAT_HTML),
            format_string($course->fullname),
            $seminar->name,
            $this->user_one,
            $seminar_event_record,
            $seminar_event_record->id
        );

        self::assertStringNotContainsString('[session:event_muilti]', $substituted_text_2);
        self::assertStringContainsString('Option 2', $substituted_text_2);
    }
}