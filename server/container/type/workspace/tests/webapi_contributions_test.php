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
use totara_engage\access\access;
use container_workspace\member\member;

class container_workspace_webapi_contributions_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_fetch_contributions_from_private_workspace_as_non_member(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Log in as first user and create the workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Log in as second user and check if the user is able to fetch the library
        // contributions or not.
        $this->setUser($user_two);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot fetch the workspace's library");

        $this->resolve_graphql_query(
            'container_workspace_contributions',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'adder',
                'filter' => [
                    'access' => access::get_code(access::PUBLIC),
                    'type' => 'totara_playlist'
                ],
                'footnotes' => []
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_contributions_of_a_course(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);
        $course = $generator->create_course();

        // Fetch the contributions with course's id to see whether the graphql is accepting it or not.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot find workspace');

        $this->resolve_graphql_query(
            'container_workspace_contributions',
            [
                'workspace_id' => $course->id,
                'area' => 'adder',
                'filter' => [
                    'access' => access::get_code(access::PUBLIC),
                    'type' => 'totara_playlist'
                ],
                'footnotes' => []
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_contributions_of_hidden_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();
        
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        // Log in as second user and check that second user is able to fetch the 
        // contributions or not.

        $this->setUser($user_two);
        
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Cannot fetch the workspace's library");
    
        $this->resolve_graphql_query(
            'container_workspace_contributions',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'adder',
                'filter' => [
                    'access' => access::get_code(access::PUBLIC),
                    'type' => 'totara_playlist'
                ],
                'footnotes' => []
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_contributions_of_public_workspace_by_non_member(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        // Log in as first user to create the public workspace.
        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Log in as user two to fetch the contributions.
        $this->setUser($user_two);

        $result = $this->resolve_graphql_query(
            'container_workspace_contributions',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'adder',
                'filter' => [
                    'access' => access::get_code(access::PUBLIC),
                    'type' => 'totara_playlist'
                ],
                'footnotes' => []
            ]
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('cursor', $result);
        self::assertArrayHasKey('cards', $result);

        self::assertEmpty($result['cards']);
    }

    /**
     * @return void
     */
    public function test_fetch_contributions_of_private_workspace_by_member(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        // Add user two to the workspace.
        member::added_to_workspace($workspace, $user_two->id, false);

        // Log in as user two.
        $this->setUser($user_two);
        $result = $this->resolve_graphql_query(
            'container_workspace_contributions',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'adder',
                'filter' => [
                    'access' => access::get_code(access::PUBLIC),
                    'type' => 'totara_playlist'
                ],
                'footnotes' => []
            ]
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('cursor', $result);
        self::assertArrayHasKey('cards', $result);

        self::assertEmpty($result['cards']);
    }

    /**
     * @return void
     */
    public function test_fetch_contributions_of_hidden_workspace_by_member(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        // Add user two to the workspace.
        member::added_to_workspace($workspace, $user_two->id, false);

        // Log in as user two.
        $this->setUser($user_two);
        $result = $this->resolve_graphql_query(
            'container_workspace_contributions',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'adder',
                'filter' => [
                    'access' => access::get_code(access::PUBLIC),
                    'type' => 'totara_playlist'
                ],
                'footnotes' => []
            ]
        );

        self::assertIsArray($result);
        self::assertArrayHasKey('cursor', $result);
        self::assertArrayHasKey('cards', $result);

        self::assertEmpty($result['cards']);
    }

    /**
     * @return void
     */
    public function test_fetch_contributions_with_invalid_area(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Contribution area is invalid");

        $this->resolve_graphql_query(
            'container_workspace_contributions',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'dummy_adder',
                'filter' => [
                    'access' => access::get_code(access::PUBLIC),
                    'type' => 'totara_playlist'
                ],
                'footnotes' => []
            ]
        );
    }

    /**
     * @return void
     */
    public function test_fetch_contributions_exclude_components(): void {
        $generator = self::getDataGenerator();

        $user_one = $generator->create_user();
        // Login as user_one.
        $this->setUser($user_one);
        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article_generator->create_public_article();
        $article_generator->create_public_article();

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $survey_generator->create_public_survey();
        $survey_generator->create_public_survey();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();
        $result = $this->resolve_graphql_query(
            'container_workspace_contributions',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'adder',
                'filter' => [
                ],
                'footnotes' => []
            ]
        );

        self::assertNotEmpty($result['cards']);
        self::assertEquals(4, count($result['cards']));

        // Disabled survey share capability.
        $role_user = \core\orm\query\builder::get_db()->get_field('role', 'id', ['shortname' => 'user'], MUST_EXIST);
        assign_capability('engage/survey:share', CAP_PREVENT, $role_user, context_user::instance($user_one->id)->id);

        $result = $this->resolve_graphql_query(
            'container_workspace_contributions',
            [
                'workspace_id' => $workspace->get_id(),
                'area' => 'adder',
                'filter' => [
                ],
                'footnotes' => []
            ]
        );

        self::assertNotEmpty($result['cards']);
        self::assertEquals(2, count($result['cards']));
    }
}