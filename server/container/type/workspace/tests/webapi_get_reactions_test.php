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

class container_workspace_webapi_get_reactions_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_reactions_of_public_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create a discussion.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Log in as second user and perform persist operation.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $result = $this->execute_graphql_operation(
            'totara_reaction_get_likes',
            [
                'instanceid' => $discussion->get_id(),
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertEmpty($result->errors);
        self::assertNotEmpty($result->data);

        self::assertArrayHasKey('reactions', $result->data);
        self::assertArrayHasKey('count', $result->data);

        self::assertEmpty($result->data['reactions']);
        self::assertEquals(0, $result->data['count']);
    }

    /**
     * @return void
     */
    public function test_get_reactions_of_private_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Create a discussion.
        $discussion = $workspace_generator->create_discussion($workspace->get_id());

        // Log in as second user and perform persist operation.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $result = $this->execute_graphql_operation(
            'totara_reaction_get_likes',
            [
                'instanceid' => $discussion->get_id(),
                'component' => workspace::get_type(),
                'area' => discussion::AREA
            ]
        );

        self::assertEmpty($result->data);
        self::assertNotEmpty($result->errors);
    }
}