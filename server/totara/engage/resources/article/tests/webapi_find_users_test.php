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
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\phpunit\webapi_phpunit_helper;
use editor_weka\webapi\resolver\query\users_by_pattern;
use engage_article\totara_engage\resource\article;
use core\entity\user;

class engage_article_webapi_find_users_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return \engage_article\testing\generator
     */
    private function get_article_generator(): \engage_article\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        return $article_generator;
    }

    /**
     * @param int $number_of_users
     * @return stdClass[]
     */
    private function create_users(int $number_of_users = 2): array {
        $generator = $this->getDataGenerator();
        $users = [];

        for ($i = 0; $i < $number_of_users; $i++) {
            $users[] = $generator->create_user([
                'firstname' => uniqid(),
                'lastname' => uniqid()
            ]);
        }

        return $users;
    }

    /**
     * @return \totara_tenant\testing\generator
     */
    private function get_tenant_generator(): \totara_tenant\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        return $tenant_generator;
    }

    /**
     * @return void
     */
    public function test_find_system_level_users_as_tenant_member_in_tenant_article(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        // Log in as second user and create an article.
        $this->setUser($user_two);

        $article_generator = $this->get_article_generator();
        $article = $article_generator->create_public_article();

        // Check that if user two is able to fetch user one.
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'pattern' => $user_one->lastname,
            'contextid' => $article->get_context_id(),
            'component' => article::get_resource_type(),
            'area' => article::CONTENT_AREA
        ];

        $before_result = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($before_result);
        self::assertNotEmpty($before_result);
        self::assertCount(1, $before_result);

        $fetched_user = reset($before_result);
        self::assertEquals($user_one->id, $fetched_user->id);

        set_config('tenantsisolated', 1);

        $after_result = $this->resolve_graphql_query($query_name, $parameters);
        self::assertIsArray($after_result);
        self::assertEmpty($after_result);
    }

    /**
     * @return void
     */
    public function test_find_participant_user_as_tenant_member_in_tenant_article(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);
        $tenant_generator->set_user_participation($user_one->id, [$tenant->id]);

        // Log in as user two - tenant user to create an article.
        $this->setUser($user_two);
        $article_generator = $this->get_article_generator();
        $article = $article_generator->create_public_article();

        // Start searching for user one within article context.
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'pattern' => $user_one->firstname,
            'contextid' => $article->get_context_id(),
            'component' => article::get_resource_type(),
            'area' => article::CONTENT_AREA
        ];

        $before_result = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($before_result);
        self::assertNotEmpty($before_result);
        self::assertCount(1, $before_result);

        $before_fetch_user = reset($before_result);
        self::assertInstanceOf(user::class, $before_fetch_user);
        self::assertEquals($user_one->id, $before_fetch_user->id);

        // Set isolation mode on.
        set_config('tenantsisolated', 1);
        $after_result = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($after_result);
        self::assertNotEmpty($after_result);
        self::assertCount(1, $after_result);

        $after_fetch_user = reset($after_result);
        self::assertInstanceOf(user::class, $after_fetch_user);
        self::assertEquals($user_one->id, $after_fetch_user->id);
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_as_partipant_in_tenant_article(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);
        $tenant_generator->set_user_participation($user_one->id, [$tenant->id]);

        $this->setUser($user_two);
        $article_generator = $this->get_article_generator();
        $article = $article_generator->create_public_article();

        // Log in as user one and check if user one is able to search for user two.
        $this->setUser($user_one);

        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'pattern' => $user_two->firstname,
            'contextid' => $article->get_context_id(),
            'component' => article::get_resource_type(),
            'area' => article::COMMENT_AREA
        ];

        $before_result = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($before_result);
        self::assertNotEmpty($before_result);
        self::assertCount(1, $before_result);

        $before_fetch_user = reset($before_result);
        self::assertInstanceOf(user::class, $before_fetch_user);
        self::assertEquals($user_two->id, $before_fetch_user->id);

        set_config('tenantsisolated', 1);
        $after_result = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($after_result);
        self::assertNotEmpty($after_result);
        self::assertCount(1, $after_result);

        $after_fetch_user = reset($after_result);
        self::assertInstanceOf(user::class, $after_fetch_user);
        self::assertEquals($user_two->id, $after_fetch_user->id);
    }

    /**
     * @return void
     */
    public function test_find_different_tenant_user_as_tenant_member_in_tenant_article(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Log in as user one to create article.
        $this->setUser($user_one);
        $article_generator = $this->get_article_generator();
        $article = $article_generator->create_public_article();

        // Search for user two.
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $before_result = $this->resolve_graphql_query(
            $query_name,
            [
                'pattern' => $user_two->firstname,
                'contextid' => $article->get_context_id(),
                'component' => article::get_resource_type(),
                'area' => article::COMMENT_AREA
            ]
        );

        self::assertIsArray($before_result);
        self::assertEmpty($before_result);

        set_config('tenantsisolated', 1);
        $after_result = $this->resolve_graphql_query(
            $query_name,
            [
                'pattern' => $user_two->firstname,
                'contextid' => $article->get_context_id(),
                'component' => article::get_resource_type(),
                'area' => article::COMMENT_AREA
            ]
        );

        self::assertIsArray($after_result);
        self::assertEmpty($after_result);
    }

    /**
     * @return void
     */
    public function test_find_tenant_one_user_as_tenant_two_user_in_tenant_one_article(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant_one->id);
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant_two->id);

        // Log in as user one to create an article.
        $this->setUser($user_one);
        $article_generator = $this->get_article_generator();
        $article = $article_generator->create_public_article();

        // Log in as user two and search for user one in this article context.
        $this->setUser($user_two);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "User with id '{$user_two->id}' cannot access context"
        );

        $this->resolve_graphql_query(
            $this->get_graphql_name(users_by_pattern::class),
            [
                'pattern' => $user_one->firstname,
                'contextid' => $article->get_context_id(),
                'component' => article::get_resource_type(),
                'area' => article::COMMENT_AREA
            ]
        );
    }

    /**
     * @return void
     */
    public function test_find_tenant_member_user_as_system_user_in_system_article(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_two->id, $tenant->id);

        // Log in as user one to create an article.
        $this->setUser($user_one);
        $article_generator = $this->get_article_generator();
        $article = $article_generator->create_public_article();

        // Search for user two in this article context.
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'pattern' => $user_two->firstname,
            'contextid' => $article->get_context_id(),
            'component' => article::get_resource_type(),
            'area' => article::CONTENT_AREA
        ];

        $before_result = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($before_result);
        self::assertNotEmpty($before_result);
        self::assertCount(1, $before_result);

        $fetched_user = reset($before_result);
        self::assertEquals($user_two->id, $fetched_user->id);

        set_config('tenantsisolated', 1);
        $after_result = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($after_result);
        self::assertEmpty($after_result);
    }
}