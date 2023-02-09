<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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

use container_workspace\task\delete_workspace_task;
use container_workspace\exception\workspace_exception;
use container_workspace\workspace;
use container_workspace\discussion\discussion;

class container_workspace_delete_workspace_task_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_execute_task_with_no_user_id(): void {
        $task = new delete_workspace_task();
        $task->set_custom_data(['workspace_id' => 42]);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "Cannot execute the deletion task due to missing workspace's id or user's id"
        );

        $task->execute();
    }

    /**
     * @return void
     */
    public function test_execute_task_no_workspace_id(): void {
        $task = new delete_workspace_task();
        $task->set_userid(42);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "Cannot execute the deletion task due to missing workspace's id or user's id"
        );

        $task->execute();
    }

    /**
     * @return void
     */
    public function test_execute_task_by_user_without_permission(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Set the flag to be deleted so that we can execute the task
        $workspace->mark_to_be_deleted();

        $task = delete_workspace_task::from_workspace_id($workspace->get_id(), $user_two->id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/.*User cannot delete the user enrolment of someone else.*/");

        $task->execute();
    }

    /**
     * @return void
     */
    public function test_execute_task_for_non_to_be_deleted_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $task = delete_workspace_task::from_workspace_id($workspace->get_id(), $user_one->id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("The workspace was not set to be deleted");

        $task->execute();
    }

    /**
     * @return void
     */
    public function test_execute_task(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create two discussions and 2 comments in the workspace.
        $workspace_id = $workspace->get_id();

        $discussion_one = $workspace_generator->create_discussion($workspace_id);
        $discussion_two = $workspace_generator->create_discussion($workspace_id);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');

        // Create a comment for discussion one and a comment for discussion two.
        $comment_one = $comment_generator->create_comment(
            $discussion_one->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        $comment_two = $comment_generator->create_comment(
            $discussion_two->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        self::assertTrue(
            $DB->record_exists('workspace_discussion', ['id' => $discussion_one->get_id()])
        );

        self::assertTrue(
            $DB->record_exists('workspace_discussion', ['id' => $discussion_two->get_id()])
        );

        self::assertTrue(
            $DB->record_exists('totara_comment', ['id' => $comment_one->get_id()])
        );

        self::assertTrue(
            $DB->record_exists('totara_comment', ['id' => $comment_two->get_id()])
        );

        // Add a member to a workspace.
        $user_two = $generator->create_user();
        $user_two_member = $workspace_generator->add_member($workspace, $user_two->id, $user_one->id);

        self::assertTrue(
            $DB->record_exists('user_enrolments', ['id' => $user_two_member->get_id()])
        );

        // Flag the workspace so that we can trigger the deletion.
        $workspace->mark_to_be_deleted();

        $task = delete_workspace_task::from_workspace_id($workspace_id, $user_one->id);
        $task->execute();

        self::assertFalse(
            $DB->record_exists('workspace_discussion', ['id' => $discussion_one->get_id()])
        );

        self::assertFalse(
            $DB->record_exists('workspace_discussion', ['id' => $discussion_two->get_id()])
        );

        self::assertFalse(
            $DB->record_exists('totara_comment', ['id' => $comment_one->get_id()])
        );

        self::assertFalse(
            $DB->record_exists('totara_comment', ['id' => $comment_two->get_id()])
        );

        self::assertFalse(
            $DB->record_exists('user_enrolments', ['id' => $user_two_member->get_id()])
        );
    }
}