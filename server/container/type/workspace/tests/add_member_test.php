<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\member\member;
use container_workspace\workspace;
use core\entity\user_enrolment;
use core_user\profile\user_field_resolver;
use totara_notification\task\process_event_queue_task;
use totara_notification\task\process_notification_queue_task;

class container_workspace_add_member_testcase extends advanced_testcase {

    protected $expected_role;

    protected function tearDown(): void {
        parent::tearDown();
        $this->expected_role = null;
    }

    /**
     * @return void
     */
    public function test_add_member_results_in_correct_(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Login as first user and start creating the workspace.
        $this->setUser($user_one);
        $workspace = $workspace_generator->create_workspace();

        // Join via self enrolment
        $this->setUser($user_two);
        $member = member::join_workspace($workspace, $user_two->id);

        /** @var user_enrolment $user_enrolment */
        $user_enrolment = user_enrolment::repository()->find($member->get_id());
        $this->assertNotNull($user_enrolment);

        $this->assertEquals('self', $user_enrolment->enrol_instance->enrol);

        $this->setUser($user_one);

        $member = member::added_to_workspace($workspace, $user_two->id);
        $this->assertEquals($user_two->id, $member->get_user_id());

        /** @var user_enrolment $user_enrolment */
        $user_enrolment = user_enrolment::repository()->find($member->get_id());
        $this->assertNotNull($user_enrolment);
        $this->assertEquals(ENROL_USER_ACTIVE, $user_enrolment->status);

        // Method should now be manual
        $this->assertEquals('manual', $user_enrolment->enrol_instance->enrol);

        // User should only be enrolled once
        $this->assertEquals(
            1,
            user_enrolment::repository()->where('userid', $user_two->id)->count()
        );

        // Now suspend the enrolment
        $user_enrolment->status = ENROL_USER_SUSPENDED;
        $user_enrolment->save();

        // And enrolling the user again should reactivate the enrollment

        $member = member::added_to_workspace($workspace, $user_two->id);
        $this->assertEquals($user_two->id, $member->get_user_id());

        /** @var user_enrolment $user_enrolment */
        $user_enrolment = user_enrolment::repository()->find($member->get_id());
        $this->assertNotNull($user_enrolment);
        $this->assertEquals(ENROL_USER_ACTIVE, $user_enrolment->status);

        $this->assert_has_role_assignment($user_two->id, $workspace);
    }

    /**
     * Test workspace owner can view a member's profile url
     *
     * @return void
     */
    public function test_workspace_owner_can_view_member_profile_url(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/enrollib.php");

        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Login as first user and start creating the workspace.
        $this->setUser($user_one);
        $workspace = $workspace_generator->create_workspace();

        // adds user two
        member::added_to_workspace($workspace, $user_two->id);

        // user three joins via self enrolment
        $this->setUser($user_three);
        member::join_workspace($workspace, $user_three->id);

        // As user one(workspace owner)
        $this->setUser($user_one);

        // Get user two's profile url
        $user_two_profile_url = user_field_resolver::from_record($user_two, $workspace->id)
            ->get_field_value('profileurl');
        $this->assertNotNull($user_two_profile_url);

        // Get user three's profile url
        $user_three_profile_url = user_field_resolver::from_record($user_three, $workspace->id)
            ->get_field_value('profileurl');
        $this->assertNotNull($user_three_profile_url);
    }

    /**
     * @return void
     */
    public function test_add_member_to_public_workspace(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Login as first user and start creating the workspace.
        $this->setUser($user_one);
        $workspace = $workspace_generator->create_workspace();

        $member = member::added_to_workspace($workspace, $user_two->id);
        $this->assertEquals($user_two->id, $member->get_user_id());
    }

    /**
     * @return void
     */
    public function test_add_member_to_private_workspace(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Login as first user and start creating the workspace.
        $this->setUser($user_one);
        $workspace = $workspace_generator->create_private_workspace();

        $member = member::added_to_workspace($workspace, $user_two->id);
        $this->assertEquals($user_two->id, $member->get_user_id());
    }

    /**
     * @return void
     */
    public function test_add_member_to_hidden_workspace(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        // Login as first user and start creating the workspace.
        $this->setUser($user_one);
        $workspace = $workspace_generator->create_hidden_workspace();

        $member = member::added_to_workspace($workspace, $user_two->id);
        $this->assertEquals($user_two->id, $member->get_user_id());
    }

    /**
     * @return void
     */
    public function test_add_member_do_not_trigger_adhoc_tasks(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);

        $workspace = $workspace_generator->create_workspace();

        // Make sure that we clear out the adhoc tasks first.
        $this->executeAdhocTasks();

        $message_sink = $this->redirectMessages();
        $member = member::added_to_workspace($workspace, $user_two->id, false);

        $this->executeAdhocTasks();
        $messages = $message_sink->get_messages();

        $this->assertEmpty($messages);
        $this->assertNotEmpty($member->get_id());
        $this->assertTrue(
            $DB->record_exists(user_enrolment::TABLE, ['id' => $member->get_id()])
        );
    }

    /**
     * @return void
     */
    public function test_add_members_in_bulk(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();
        $user_four = $generator->create_user();
        $user_five = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $this->setUser($user_one);

        $workspace1 = $workspace_generator->create_workspace();
        $workspace2 = $workspace_generator->create_workspace();

        // Make sure that we clear out the adhoc tasks first.
        $this->executeAdhocTasks();

        $users_to_add = [$user_two->id, $user_three->id, $user_four->id, $user_five->id];

        $message_sink = $this->redirectMessages();
        $member_ids = member::added_to_workspace_in_bulk($workspace1, $users_to_add, false);

        $this->assertEquals(count($users_to_add), count($member_ids));

        $this->executeAdhocTasks();

        $this->assertEmpty($message_sink->get_messages());

        $this->assert_has_role_assignment($user_two->id, $workspace1);
        $this->assert_has_role_assignment($user_three->id, $workspace1);
        $this->assert_has_role_assignment($user_four->id, $workspace1);
        $this->assert_has_role_assignment($user_five->id, $workspace1);

        $members = member::added_to_workspace_in_bulk($workspace2, $users_to_add, false);

        $this->assertEquals(count($users_to_add), count($members));

        $this->executeAdhocTasks();

        foreach ($members as $member) {
            $user_enrolment = user_enrolment::repository()->find($member->get_id());
            $this->assertNotNull($user_enrolment);
            $this->assertEquals(ENROL_USER_ACTIVE, $user_enrolment->status);

            // Method should now be manual
            $this->assertEquals('manual', $user_enrolment->enrol_instance->enrol);
        }
        $this->assertDebuggingCalledCount(4);
    }

    /**
     * @return void
     */
    public function test_add_member_to_hidden_workspace_with_exception(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $this->setUser($user_one);
        $workspace = $workspace_generator->create_hidden_workspace();

        $this->setUser($user_two);
        $this->expectException("moodle_exception");
        $this->expectExceptionMessage("Cannot manual add user to workspace");
        member::added_to_workspace($workspace, $user_three->id);
    }

    /**
     * @return void
     */
    public function test_add_member_to_private_workspace_with_exception(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $this->setUser($user_one);
        $workspace = $workspace_generator->create_private_workspace();

        $this->setUser($user_two);
        $this->expectException("moodle_exception");
        $this->expectExceptionMessage("Cannot manual add user to workspace");
        member::added_to_workspace($workspace, $user_three->id);
    }

    /**
     * @return void
     */
    public function test_add_member_trigger_notification_flag(): void {
        \container_workspace\totara_notification\workspace_muter::reset();
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $this->setUser($user_one);
        $workspace = $workspace_generator->create_workspace();

        $message_sink = $this->redirectMessages();
        $message_sink->clear();
        member::added_to_workspace($workspace, $user_two->id, false);

        // Execute the queue
        $task = new process_event_queue_task();
        $task->execute();
        $task = new process_notification_queue_task();
        $task->execute();

        $messages = $message_sink->get_messages();
        self::assertEmpty($messages);

        $user_three = $generator->create_user();
        $message_sink->clear();
        member::added_to_workspace($workspace, $user_three->id, true);

        // Execute the queue
        $task = new process_event_queue_task();
        $task->execute();
        $task = new process_notification_queue_task();
        $task->execute();

        $messages = $message_sink->get_messages();
        self::assertCount(1, $messages);

        $message_sink->close();
    }

    /**
     * Assert that the user has the role assignment expected after enrolment.
     *
     * @param int $user_id
     * @param workspace $workspace
     */
    private function assert_has_role_assignment(int $user_id, workspace $workspace): void {
        global $DB;

        if (!isset($this->expected_role)) {
            $roles = get_archetype_roles('student');
            $this->expected_role = reset($roles);
        }

        $this->assertTrue(
            $DB->record_exists(
                'role_assignments',
                [
                    'userid' => $user_id,
                    'roleid' => $this->expected_role->id,
                    'contextid' => $workspace->get_context()->id
                ]
            )
        );
    }

}