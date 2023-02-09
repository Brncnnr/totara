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

use container_workspace\discussion\discussion;
use container_workspace\workspace;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_reaction\exception\reaction_exception;
use container_workspace\member\member;

class container_workspace_webapi_fetch_reactions_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_reactions_of_discussion_in_private_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Log in as second user to fetch the reactions.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(reaction_exception::class);
        $this->expectExceptionMessage(get_string('error:view', 'totara_reaction'));

        $this->resolve_graphql_query(
            'totara_reaction_reactions',
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
    public function test_fetch_reactions_of_discussion_in_hidden_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Log in as user two and fetch the reactions.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(reaction_exception::class);
        $this->expectExceptionMessage(get_string('error:view', 'totara_reaction'));

        $this->resolve_graphql_query(
            'totara_reaction_reactions',
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
    public function test_fetch_reactions_of_discussion_in_public_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Log in as user two and fetch the reaction.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $reactions = $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $discussion->get_id(),
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertIsArray($reactions);
        self::assertEmpty($reactions);
    }

    /**
     * @return void
     */
    public function test_fetch_reactions_of_discussion_in_private_workspace_by_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Create user two and add user to the workspace.
        $user_two = $generator->create_user();
        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);

        // Log in as user two and fetch for the reactions of discussion.
        $this->setUser($user_two);
        $reactions = $this->resolve_graphql_query(
            'totara_reaction_reactions',
            [
                'instanceid' => $discussion->get_id(),
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertIsArray($reactions);
        self::assertEmpty($reactions);
    }
}