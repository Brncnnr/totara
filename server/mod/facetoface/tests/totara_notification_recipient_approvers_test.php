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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_facetoface
 * @category totara_notification
 */

use core_phpunit\testcase;
use mod_facetoface\signup;
use mod_facetoface\seminar;
use mod_facetoface\testing\generator as facetoface_generator;
use mod_facetoface\totara_notification\recipient\approvers as recipient_group;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_notification
 */
class mod_facetoface_totara_notification_recipient_approvers_testcase extends testcase {

    /**
     * Test that the function requires a seminar_event_id
     */
    public function test_missing_event(): void {
        // Set up user.
        $user = self::getDataGenerator()->create_user();

        // Run the test.
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Missing seminar_event_id');
        recipient_group::get_user_ids([
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test that the function requires a user_id
     */
    public function test_missing_user(): void {
        // Set up seminar event.
        $course = self::getDataGenerator()->create_course();
        $seminar_generator = facetoface_generator::instance();
        $seminar = $seminar_generator->create_instance(['course' => $course->id]);
        $seminar_event_id = $seminar_generator->add_session(['facetoface' => $seminar->id]);

        // Run the test.
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Missing user_id');
        recipient_group::get_user_ids([
            'seminar_event_id' => $seminar_event_id,
        ]);
    }

    /**
     * Test that no users are returned when seminar approval is set to "None"
     */
    public function test_no_approval_required(): void {
        // Set up user.
        $user = self::getDataGenerator()->create_user();

        // Set up seminar event with no approval required.
        $course = self::getDataGenerator()->create_course();
        $seminar_generator = facetoface_generator::instance();
        $seminar = $seminar_generator->create_instance([
            'course' => $course->id,
            'approvaltype' => seminar::APPROVAL_NONE,
        ]);
        $seminar_event_id = $seminar_generator->add_session(['facetoface' => $seminar->id]);

        // Sign up the user to the seminar event.
        $signup = signup::create($user->id, $seminar_event_id); // Creates object but not DB record.
        $signup->save();

        // Run the test.
        $user_ids = recipient_group::get_user_ids([
            'seminar_event_id' => $seminar_event_id,
            'user_id' => $user->id,
        ]);

        // Check the result.
        self::assertEmpty($user_ids);
    }

    /**
     * Test that no users are returned when seminar approval is set to "Self"
     */
    public function test_self_approval(): void {
        // Set up user.
        $user = self::getDataGenerator()->create_user();

        // Set up seminar event with self approval.
        $course = self::getDataGenerator()->create_course();
        $seminar_generator = facetoface_generator::instance();
        $seminar = $seminar_generator->create_instance([
            'course' => $course->id,
            'approvaltype' => seminar::APPROVAL_SELF,
        ]);
        $seminar_event_id = $seminar_generator->add_session(['facetoface' => $seminar->id]);

        // Sign up the user to the seminar event.
        $signup = signup::create($user->id, $seminar_event_id); // Creates object but not DB record.
        $signup->save();

        // Run the test.
        $user_ids = recipient_group::get_user_ids([
            'seminar_event_id' => $seminar_event_id,
            'user_id' => $user->id,
        ]);

        // Check the result.
        self::assertEmpty($user_ids);
    }

    /**
     * Test that all the participant's managers are returned when seminar approval is set to "Manager"
     */
    public function test_manager_approval_without_job_selected(): void {
        // Set up user.
        $user = self::getDataGenerator()->create_user();

        // Set up the user's managers.
        $manager1 = self::getDataGenerator()->create_user();
        $manager1_ja = job_assignment::create_default($manager1->id);
        job_assignment::create_default(
            $user->id,
            ['managerjaid' => $manager1_ja->id]
        );
        $manager2 = self::getDataGenerator()->create_user();
        $manager2_ja = job_assignment::create_default($manager2->id);
        job_assignment::create_default(
            $user->id,
            ['managerjaid' => $manager2_ja->id]
        );

        // Set up seminar event with manager approval.
        $course = self::getDataGenerator()->create_course();
        $seminar_generator = facetoface_generator::instance();
        $seminar = $seminar_generator->create_instance([
            'course' => $course->id,
            'approvaltype' => seminar::APPROVAL_MANAGER,
        ]);
        $seminar_event_id = $seminar_generator->add_session(['facetoface' => $seminar->id]);

        // Sign up the user to the seminar event.
        $signup = signup::create($user->id, $seminar_event_id); // Creates object but not DB record.
        $signup->save();

        // Run the test.
        $user_ids = recipient_group::get_user_ids([
            'seminar_event_id' => $seminar_event_id,
            'user_id' => $user->id,
        ]);

        // Check the result.
        self::assertEqualsCanonicalizing([$manager1->id, $manager2->id], $user_ids);
    }

    /**
     * Test that only the participant's selected manager is returned when seminar approval is set to "Manager"
     */
    public function test_manager_approval_with_job_selected(): void {
        // Set up user.
        $user = self::getDataGenerator()->create_user();

        // Set up the user's managers.
        $manager1 = self::getDataGenerator()->create_user();
        $manager1_ja = job_assignment::create_default($manager1->id);
        $ja1 = job_assignment::create_default(
            $user->id,
            ['managerjaid' => $manager1_ja->id]
        );
        $manager2 = self::getDataGenerator()->create_user();
        $manager2_ja = job_assignment::create_default($manager2->id);
        $ja2 = job_assignment::create_default(
            $user->id,
            ['managerjaid' => $manager2_ja->id]
        );

        // Set up seminar event with manager approval.
        $course = self::getDataGenerator()->create_course();
        $seminar_generator = facetoface_generator::instance();
        $seminar = $seminar_generator->create_instance([
            'course' => $course->id,
            'approvaltype' => seminar::APPROVAL_MANAGER,
            'selectjobassignmentonsignup' => 1,
        ]);
        set_config('facetoface_selectjobassignmentonsignupglobal', 1);
        $seminar_event_id = $seminar_generator->add_session(['facetoface' => $seminar->id]);

        // Sign up the user to the seminar event and specify their job assignment for approval.
        $signup = signup::create($user->id, $seminar_event_id); // Creates object but not DB record.
        $signup->set_jobassignmentid($ja1->id);
        $signup->save();

        // Run the test.
        $user_ids = recipient_group::get_user_ids([
            'seminar_event_id' => $seminar_event_id,
            'user_id' => $user->id,
        ]);

        // Check the result.
        self::assertEqualsCanonicalizing([$manager1->id], $user_ids);

        // Double-check by changing their signup to use the other job assignment for approval.
        $signup->set_jobassignmentid($ja2->id);
        $signup->save();

        // Run the test.
        $user_ids = recipient_group::get_user_ids([
            'seminar_event_id' => $seminar_event_id,
            'user_id' => $user->id,
        ]);

        // Check the result.
        self::assertEqualsCanonicalizing([$manager2->id], $user_ids);
    }

    /**
     * Test that only the participant's selected manager is returned when seminar approval is set to "Admin"
     */
    public function test_admin_approval(): void {
        // Set up user.
        $user = self::getDataGenerator()->create_user();

        // Set up the user's managers.
        $manager = self::getDataGenerator()->create_user();
        $manager_ja = job_assignment::create_default($manager->id);
        job_assignment::create_default(
            $user->id,
            ['managerjaid' => $manager_ja->id]
        );

        // Set up another user to be an approver.
        $approver = self::getDataGenerator()->create_user();

        // Set global approver.
        $site_admin_id = 2; // Known constant across all tests.
        set_config('facetoface_adminapprovers', $site_admin_id);

        // Set up seminar event with admin approval.
        $course = self::getDataGenerator()->create_course();
        $seminar_generator = facetoface_generator::instance();
        $seminar = $seminar_generator->create_instance([
            'course' => $course->id,
            'approvaltype' => seminar::APPROVAL_ADMIN,
            'approvaladmins' => $approver->id,
        ]);
        $seminar_event_id = $seminar_generator->add_session(['facetoface' => $seminar->id]);

        // Sign up the user to the seminar event.
        $signup = signup::create($user->id, $seminar_event_id); // Creates object but not DB record.
        $signup->save();

        // Run the test.
        $user_ids = recipient_group::get_user_ids([
            'seminar_event_id' => $seminar_event_id,
            'user_id' => $user->id,
        ]);

        // Check the result.
        $expected_approvers = [
            $approver->id,
            $site_admin_id,
            $manager->id,
        ];
        self::assertEqualsCanonicalizing($expected_approvers, $user_ids);
    }

    /**
     * Test that only the role users are returned when seminar approval is set to one of the "Event role" roles.
     */
    public function test_role_approval(): void {
        global $DB;

        // Set up users.
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();

        // Set up course.
        $course = self::getDataGenerator()->create_course();

        // Set up two users to be editing trainers.
        $editing_trainer_role = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $editing_trainer1 = self::getDataGenerator()->create_user();
        $editing_trainer2 = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($editing_trainer1->id, $course->id, $editing_trainer_role->id);
        self::getDataGenerator()->enrol_user($editing_trainer2->id, $course->id, $editing_trainer_role->id);

        // Set up a user to be a trainer.
        $trainer = self::getDataGenerator()->create_user();
        $trainer_role = $DB->get_record('role', ['shortname' => 'teacher']);
        self::getDataGenerator()->enrol_user($trainer->id, $course->id, $trainer_role->id);

        // Set up a seminar event with "Trainer" approval.
        $seminar_generator = facetoface_generator::instance();
        $seminar_record = $seminar_generator->create_instance([
            'course' => $course->id,
            'approvaltype' => seminar::APPROVAL_ROLE,
            'approvalrole' => $trainer_role->id,
        ]);
        $seminar_event_id = $seminar_generator->add_session(['facetoface' => $seminar_record->id]);

        // Add session role users to the event. Only one of the editing trainers is added.
        $DB->insert_record('facetoface_session_roles', [
            'sessionid' => $seminar_event_id,
            'roleid' => $editing_trainer_role->id,
            'userid' => $editing_trainer1->id,
        ]);
        $DB->insert_record('facetoface_session_roles', [
            'sessionid' => $seminar_event_id,
            'roleid' => $trainer_role->id,
            'userid' => $trainer->id,
        ]);

        // Sign up the user to the seminar event.
        $signup = signup::create($user1->id, $seminar_event_id); // Creates object but not DB record.
        $signup->save();

        // Run the test.
        $user_ids = recipient_group::get_user_ids([
            'seminar_event_id' => $seminar_event_id,
            'user_id' => $user1->id,
        ]);

        // Check the result.
        self::assertEqualsCanonicalizing([$trainer->id], $user_ids);

        // Double-check by changing the seminar to have editing trainer as approver.
        $seminar = new seminar($seminar_record->id);
        $seminar->set_approvalrole($editing_trainer_role->id);
        $seminar->save();

        // Sign up the user to the seminar event.
        $signup = signup::create($user2->id, $seminar_event_id); // Creates object but not DB record.
        $signup->save();

        // Run the test.
        $user_ids = recipient_group::get_user_ids([
            'seminar_event_id' => $seminar_event_id,
            'user_id' => $user2->id,
        ]);

        // Check the result.
        self::assertEqualsCanonicalizing([$editing_trainer1->id], $user_ids);
    }
}