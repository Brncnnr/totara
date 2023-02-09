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

use container_workspace\workspace;
use container_workspace\discussion\discussion;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_comment\exception\comment_exception;
use container_workspace\member\member;

class container_workspace_webapi_fetch_replies_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_replies_of_private_workspace_by_non_members(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Create a discussion, and comment for this discussion.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        // Log in as second user and fetch for replies of this comments.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(comment_exception::class);
        $this->expectExceptionMessage(get_string('error:accessdenied', 'totara_comment'));

        $this->resolve_graphql_query(
            'totara_comment_replies',
            ['commentid' => $comment->get_id()]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_replies_of_hidden_workspace_by_non_members(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        // Create a discussion, and comment for this discussion.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        // Log in as second user and fetch for replies of this comments.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(comment_exception::class);
        $this->expectExceptionMessage(get_string('error:accessdenied', 'totara_comment'));

        $this->resolve_graphql_query(
            'totara_comment_replies',
            ['commentid' => $comment->get_id()]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_replies_of_public_workspace_by_non_members(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create a discussion, and comment for this discussion.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        // Log in as second user and fetch for replies of this comments.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $replies = $this->resolve_graphql_query(
            'totara_comment_replies',
            ['commentid' => $comment->get_id()]
        );

        self::assertIsArray($replies);
        self::assertEmpty($replies);
    }

    /**
     * @return void
     */
    public function test_fetch_replies_of_private_workspace_by_members(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $user_two = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);

        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        $this->setUser($user_two);
        $replies = $this->resolve_graphql_query(
            'totara_comment_replies',
            ['commentid' => $comment->get_id()]
        );

        self::assertIsArray($replies);
        self::assertEmpty($replies);
    }
}