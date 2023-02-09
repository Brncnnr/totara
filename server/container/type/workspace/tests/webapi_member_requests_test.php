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
use container_workspace\member\member;

class container_workspace_webapi_member_requests_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_member_requests_as_non_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Log in as user one and create the workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Log in as second user and run the query.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot fetch the workspace member requests");

        $this->resolve_graphql_query(
            'container_workspace_member_requests',
            ['workspace_id' => $workspace->get_id()]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_member_requests_as_workspace_owner(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Log in as user one and create the workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        $result = $this->resolve_graphql_query(
            'container_workspace_member_requests',
            ['workspace_id' => $workspace->get_id()]
        );

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_fetch_member_requests_as_workspace_member(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        // Log in as user one and create the workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Log in as second user and run the query.
        $user_two = $generator->create_user();

        // Add user two to the workspace.
        member::added_to_workspace($workspace, $user_two->id, false, $user_one->id);

        $this->setUser($user_two);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot fetch the workspace member requests");

        $this->resolve_graphql_query(
            'container_workspace_member_requests',
            ['workspace_id' => $workspace->get_id()]
        );
    }
}