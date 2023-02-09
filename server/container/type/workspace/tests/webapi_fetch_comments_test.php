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

use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\workspace;
use container_workspace\discussion\discussion;
use totara_comment\exception\comment_exception;
use container_workspace\member\member;

class container_workspace_webapi_fetch_comments_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_comments_of_private_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Create a discussion in this workspace.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Log in as second user and try to fetch the comments.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(comment_exception::class);
        $this->expectExceptionMessage("Comment access denied");

        $this->resolve_graphql_query(
            'totara_comment_comments',
            [
                'instanceid' => $discussion->get_id(),
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_comments_of_hidden_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        // Create a discussion in this workspace.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Log in as second user and try to fetch the comments.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(comment_exception::class);
        $this->expectExceptionMessage("Comment access denied");

        $this->resolve_graphql_query(
            'totara_comment_comments',
            [
                'instanceid' => $discussion->get_id(),
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_comments_of_public_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Log in as user two and fetch the comments.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $comments = $this->resolve_graphql_query(
            'totara_comment_comments',
            [
                'instanceid' => $discussion->get_id(),
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertIsArray($comments);
        self::assertEmpty($comments);
    }

    /**
     * @return void
     */
    public function test_fetch_comments_of_private_workspace_as_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Add user two to the workspace
        $user_two = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);

        $this->setUser($user_two);
        $comments = $this->resolve_graphql_query(
            'totara_comment_comments',
            [
                'instanceid' => $discussion->get_id(),
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertIsArray($comments);
        self::assertEmpty($comments);
    }
}