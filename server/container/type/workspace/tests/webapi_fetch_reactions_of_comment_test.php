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

use totara_reaction\exception\reaction_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\discussion\discussion;
use container_workspace\workspace;
use container_workspace\member\member;

class container_workspace_webapi_fetch_reactions_of_comment_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_reactions_of_comment_in_private_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Create discussion and comment.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        // Log in as second user and fetch for the reactions of comment in workspace.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(reaction_exception::class);
        $this->expectExceptionMessage(get_string('error:view', 'totara_reaction'));

        $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $comment->get_id(),
                'component' => $comment::get_component_name(),
                'area' => $comment->get_comment_area()
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_reactions_of_comment_in_hidden_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        // Create discussion and comment.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        // Log in as second user and fetch for the reactions of comment in workspace.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(reaction_exception::class);
        $this->expectExceptionMessage(get_string('error:view', 'totara_reaction'));

        $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $comment->get_id(),
                'component' => $comment::get_component_name(),
                'area' => $comment->get_comment_area()
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_reactions_of_comment_in_public_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $workspace = $workspace_generator->create_workspace();
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Create comment on the discusison.
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        // Log in as user two and fetch for the reactions.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $reactions = $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $comment->get_id(),
                'component' => $comment::get_component_name(),
                'area' => $comment->get_comment_area()
            ]
        );

        self::assertIsArray($reactions);
        self::assertEmpty($reactions);
    }

    /**
     * @return void
     */
    public function test_fetch_reactions_of_comment_in_private_workspace_as_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $workspace = $workspace_generator->create_workspace();
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Create comment on the discusison.
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment = $comment_generator->create_comment(
            $discussion->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        // Log in as user two and fetch for the reactions.
        $user_two = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);

        $this->setUser($user_two);

        $reactions = $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $comment->get_id(),
                'component' => $comment::get_component_name(),
                'area' => $comment->get_comment_area()
            ]
        );

        self::assertIsArray($reactions);
        self::assertEmpty($reactions);
    }
}