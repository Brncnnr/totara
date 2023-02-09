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

use container_workspace\exception\workspace_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_engage\access\access;
use container_workspace\workspace;
use container_workspace\totara_engage\share\recipient\library;

class container_workspace_check_share_access_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_check_change_to_public(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article(['access' => access::PRIVATE]);

        $result = $this->resolve_graphql_query(
            'container_workspace_check_share_access',
            [
                'items' => [
                    [
                        'itemid' => $article->get_id(),
                        'component' => $article::get_resource_type()
                    ]
                ],
                'workspace' => [
                    'instanceid' => $workspace->get_id(),
                    'component' => workspace::get_type(),
                    'area' => library::AREA
                ]
            ]
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('warning', $result);
        $this->assertArrayHasKey('message', $result);

        $this->assertTrue($result['warning']);
        $this->assertEquals(get_string('warning_change_to_public', 'container_workspace'), $result['message']);
    }

    /**
     * @return void
     */
    public function test_check_change_to_restricted(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article(['access' => access::PRIVATE]);

        $result = $this->resolve_graphql_query(
            'container_workspace_check_share_access',
            [
                'items' => [
                    [
                        'itemid' => $article->get_id(),
                        'component' => $article::get_resource_type()
                    ]
                ],
                'workspace' => [
                    'instanceid' => $workspace->get_id(),
                    'component' => workspace::get_type(),
                    'area' => library::AREA
                ]
            ]
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('warning', $result);
        $this->assertArrayHasKey('message', $result);

        $this->assertTrue($result['warning']);
        $this->assertEquals(get_string('warning_change_to_restricted', 'container_workspace'), $result['message']);
    }

    /**
     * @return void
     */
    public function test_check_result_not_have_to_change_to_public(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article(['access' => access::PUBLIC]);

        $result = $this->resolve_graphql_query(
            'container_workspace_check_share_access',
            [
                'items' => [
                    [
                        'itemid' => $article->get_id(),
                        'component' => $article::get_resource_type()
                    ]
                ],
                'workspace' => [
                    'instanceid' => $workspace->get_id(),
                    'component' => workspace::get_type(),
                    'area' => library::AREA
                ]
            ]
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('warning', $result);
        $this->assertArrayHasKey('message', $result);

        $this->assertFalse($result['warning']);
        $this->assertEmpty($result['message']);
    }

    /**
     * @return void
     */
    public function test_check_result_not_have_to_change_to_restricted(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article(['access' => access::PUBLIC]);

        $result = $this->resolve_graphql_query(
            'container_workspace_check_share_access',
            [
                'items' => [
                    [
                        'itemid' => $article->get_id(),
                        'component' => $article::get_resource_type()
                    ]
                ],
                'workspace' => [
                    'instanceid' => $workspace->get_id(),
                    'component' => workspace::get_type(),
                    'area' => library::AREA
                ]
            ]
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('warning', $result);
        $this->assertArrayHasKey('message', $result);

        $this->assertFalse($result['warning']);
        $this->assertEmpty($result['message']);
    }

    /**
     * @return void
     */
    public function test_access_not_other_private_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_private_workspace();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article(['access' => access::PUBLIC]);

        $this->setUser($user_two);
        $this->expectException(workspace_exception::class);
        $this->expectExceptionMessage("You don't have permission to view this page.");
        $this->resolve_graphql_query(
            'container_workspace_check_share_access',
            [
                'items' => [
                    [
                        'itemid' => $article->get_id(),
                        'component' => $article::get_resource_type()
                    ]
                ],
                'workspace' => [
                    'instanceid' => $workspace->get_id(),
                    'component' => workspace::get_type(),
                    'area' => library::AREA
                ]
            ]
        );
    }

    /**
     * @return void
     */
    public function test_access_not_other_hidden_workspace(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        $this->setUser($user_one);
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_hidden_workspace();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article = $article_generator->create_article(['access' => access::PUBLIC]);

        $this->setUser($user_two);
        $this->expectException(workspace_exception::class);
        $this->expectExceptionMessage("You don't have permission to view this page.");
        $this->resolve_graphql_query(
            'container_workspace_check_share_access',
            [
                'items' => [
                    [
                        'itemid' => $article->get_id(),
                        'component' => $article::get_resource_type()
                    ]
                ],
                'workspace' => [
                    'instanceid' => $workspace->get_id(),
                    'component' => workspace::get_type(),
                    'area' => library::AREA
                ]
            ]
        );
    }
}