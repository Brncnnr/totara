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

use container_workspace\member\member;
use container_workspace\workspace;
use container_workspace\discussion\discussion;
use container_workspace\discussion\discussion_helper;
use container_workspace\exception\discussion_exception;

class container_workspace_delete_discussion_testcase extends advanced_testcase {
    /**
     * Given a discussion with more than one comments, expecting that the deletion
     * of the discussion will also purge the comments as well.
     *
     * @return void
     */
    public function test_delete_discussion_that_has_comments(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace('Workspace 101', null, null, $user_one->id);

        $workspace_id = $workspace->get_id();

        // Create the first discussion
        $discussion = $workspace_generator->create_discussion($workspace_id);
        $discussion_id = $discussion->get_id();

        member::join_workspace($workspace, $user_two->id);
        $this->setUser($user_two);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');

        // Start creating 10 comments
        for ($i = 0; $i < 5; $i++) {
            $comment_generator->create_comment(
                $discussion_id,
                workspace::get_type(),
                discussion::AREA,
                null,
                null,
                $user_two->id
            );
        }

        // Now delete the discussion.
        discussion_helper::delete_discussion($discussion, $user_one->id);

        $this->assertFalse($DB->record_exists('workspace_discussion', ['id' => $discussion_id]));
        $this->assertFalse(
            $DB->record_exists(
                'totara_comment',
                [
                    'instanceid' => $discussion_id,
                    'component' => workspace::get_type(),
                    'area' => discussion::AREA
                ]
            )
        );
    }

    /**
     * Given scenario where other member user is deleting the discussion of other user.
     * Which expecting that the code should throw exception when this is happening.
     *
     * @return void
     */
    public function test_delete_discussion_by_other_user(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace('This is workspace', null, null, $user_one->id);

        member::join_workspace($workspace, $user_two->id);
        member::join_workspace($workspace, $user_three->id);

        // Set user two into the session, so that we can create the discussion that is crafted by the user two.
        $this->setUser($user_two);

        $discussion = $workspace_generator->create_discussion($workspace->get_id());
        $discussion_id = $discussion->get_id();

        // Set user three into the session, so that we test deleting the discussion that was creafted by user two.
        $this->setUser($user_three);

        try {
            discussion_helper::delete_discussion($discussion, $user_three->id);
            $this->fail("The code to delete discussion did not throw any exception");
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(discussion_exception::class, $e);
        }

        $this->assertTrue($DB->record_exists('workspace_discussion', ['id' => $discussion_id]));

        $this->setUser($user_two);
        discussion_helper::delete_discussion($discussion);

        $this->assertFalse($DB->record_exists('workspace_discussion', ['id' => $discussion_id]));
    }

    /**
     * Given a discussion with more than one comments, expecting that the deletion
     * of the discussion will also purge the comments as well if it was soft-deleted.
     *
     * @return void
     */
    public function test_soft_delete_discussion_that_has_comments(): void {
        global $DB;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace('Workspace 101', null, null, $user_one->id);

        $workspace_id = $workspace->get_id();

        // Create the first discussion
        $discussion = $workspace_generator->create_discussion($workspace_id);
        $discussion_id = $discussion->get_id();

        member::join_workspace($workspace, $user_two->id);
        $this->setUser($user_two);

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');

        // Start creating 10 comments
        for ($i = 0; $i < 5; $i++) {
            $comment_generator->create_comment(
                $discussion_id,
                workspace::get_type(),
                discussion::AREA,
                null,
                null,
                $user_two->id
            );
        }

        // Now delete the discussion.
        discussion_helper::soft_delete_discussion($discussion, $user_one->id, discussion::REASON_DELETED_REPORTED);

        $this->assertTrue($DB->record_exists('workspace_discussion', ['id' => $discussion_id]));
        $this->assertFalse(
            $DB->record_exists(
                'totara_comment',
                [
                    'instanceid' => $discussion_id,
                    'component' => workspace::get_type(),
                    'area' => discussion::AREA
                ]
            )
        );
    }
}