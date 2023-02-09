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
 * @package totara_playlist
 */
defined('MOODLE_INTERNAL') || die();

use editor_weka\webapi\resolver\query\users_by_pattern;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_playlist\playlist;
use core\entity\user;

class totara_playlist_webapi_multi_tenancy_find_users_in_comment_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return \totara_playlist\testing\generator
     */
    private function get_playlist_generator(): \totara_playlist\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        return $playlist_generator;
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
     * @param int $number_of_users
     * @return stdClass[]
     */
    private function create_users(int $number_of_users = 2): array {
        $generator = $this->getDataGenerator();
        $users = [];

        for ($i = 0; $i < $number_of_users; $i++) {
            $users[] = $generator->create_user([
                'firstname' => uniqid('first_name_'),
                'lastname' => uniqid('last_name_')
            ]);
        }

        return $users;
    }

    /**
     * @return \totara_comment\testing\generator
     */
    private function get_comment_generator(): \totara_comment\testing\generator {
        $generator = $this->getDataGenerator();

        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        return $comment_generator;
    }

    /**
     * @return void
     */
    public function test_search_system_users_as_tenant_member_in_tenant_playlist(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);

        $this->setUser($user_one);
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist();

        $comment_generator = $this->get_comment_generator();
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        // Search for user two in this playlist comment.
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'pattern' => $user_two->firstname,
            'contextid' => $playlist->get_contextid(),
            'component' => $comment::get_component_name(),
            'area' => $comment->get_comment_area(),
            'instance_id' => $comment->get_id()
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
        self::assertEmpty($after_result);
    }

    /**
     * @return void
     */
    public function test_search_tenant_participant_as_tenant_member_in_tenant_playlist(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        $this->setUser($user_one);
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist();

        $comment_generator = $this->get_comment_generator();
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        // Search for user two in this playlist comment.
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'pattern' => $user_two->firstname,
            'contextid' => $playlist->get_contextid(),
            'component' => $comment::get_component_name(),
            'area' => $comment->get_comment_area(),
            'instance_id' => $comment->get_id()
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
    public function test_search_member_tenant_as_user_participant_in_tenant_playlist(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        $this->setUser($user_one);
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist();

        $comment_generator = $this->get_comment_generator();
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        // Search for user two in this playlist comment.
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'pattern' => $user_one->lastname,
            'contextid' => $playlist->get_contextid(),
            'component' => $comment::get_component_name(),
            'area' => $comment->get_comment_area(),
            'instance_id' => $comment->get_id()
        ];

        $this->setUser($user_two);
        $before_result = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($before_result);
        self::assertNotEmpty($before_result);
        self::assertCount(1, $before_result);

        $before_fetch_user = reset($before_result);
        self::assertInstanceOf(user::class, $before_fetch_user);
        self::assertEquals($user_one->id, $before_fetch_user->id);

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
    public function test_search_system_user_as_tenant_participant_in_tenant_playlist(): void {
        [$user_one, $user_two, $user_three] = $this->create_users(3);
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);
        $tenant_generator->set_user_participation($user_two->id, [$tenant->id]);

        $this->setUser($user_one);
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist();

        $comment_generator = $this->get_comment_generator();
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        // Search for user two in this playlist comment.
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'pattern' => $user_three->lastname,
            'contextid' => $playlist->get_contextid(),
            'component' => $comment::get_component_name(),
            'area' => $comment->get_comment_area(),
            'instance_id' => $comment->get_id()
        ];

        $this->setUser($user_two);
        $before_result = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($before_result);
        self::assertNotEmpty($before_result);
        self::assertCount(1, $before_result);

        $before_fetch_user = reset($before_result);
        self::assertInstanceOf(user::class, $before_fetch_user);
        self::assertEquals($user_three->id, $before_fetch_user->id);

        set_config('tenantsisolated', 1);
        $after_result = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($after_result);
        self::assertEmpty($after_result);
    }

    /**
     * @return void
     */
    public function test_search_member_tenant_as_system_user_in_system_playlist(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);

        $this->setUser($user_two);
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist();

        $comment_generator = $this->get_comment_generator();
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        // Search for user two in this playlist comment.
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'pattern' => $user_one->lastname,
            'contextid' => $playlist->get_contextid(),
            'component' => $comment::get_component_name(),
            'area' => $comment->get_comment_area(),
            'instance_id' => $comment->get_id()
        ];

        $before_result = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($before_result);
        self::assertNotEmpty($before_result);
        self::assertCount(1, $before_result);

        $before_fetch_user = reset($before_result);
        self::assertInstanceOf(user::class, $before_fetch_user);
        self::assertEquals($user_one->id, $before_fetch_user->id);

        set_config('tenantsisolated', 1);
        $after_result = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($after_result);
        self::assertEmpty($after_result);
    }

    /**
     * @return void
     */
    public function test_search_system_user_as_tenant_member_in_system_playlist(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);

        $this->setUser($user_two);
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist();

        $comment_generator = $this->get_comment_generator();
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        // Search for user two in this playlist comment.
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'pattern' => $user_two->lastname,
            'contextid' => $playlist->get_contextid(),
            'component' => $comment::get_component_name(),
            'area' => $comment->get_comment_area(),
            'instance_id' => $comment->get_id()
        ];

        $this->setUser($user_one);
        $result = $this->resolve_graphql_query($query_name, $parameters);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $fetch_user = reset($result);
        self::assertInstanceOf(user::class, $fetch_user);
        self::assertEquals($user_two->id, $fetch_user->id);
    }

    /**
     * @return void
     */
    public function test_search_system_user_as_tenant_member_in_system_playlist_with_isolation_on(): void {
        [$user_one, $user_two] = $this->create_users();
        $tenant_generator = $this->get_tenant_generator();

        $tenant = $tenant_generator->create_tenant();
        $tenant_generator->migrate_user_to_tenant($user_one->id, $tenant->id);

        $this->setUser($user_two);
        $playlist_generator = $this->get_playlist_generator();
        $playlist = $playlist_generator->create_public_playlist();

        $comment_generator = $this->get_comment_generator();
        $comment = $comment_generator->create_comment(
            $playlist->get_id(),
            playlist::get_resource_type(),
            playlist::COMMENT_AREA
        );

        // Search for user two in this playlist comment.
        $query_name = $this->get_graphql_name(users_by_pattern::class);
        $parameters = [
            'pattern' => $user_two->lastname,
            'contextid' => $playlist->get_contextid(),
            'component' => $comment::get_component_name(),
            'area' => $comment->get_comment_area(),
            'instance_id' => $comment->get_id()
        ];

        set_config('tenantsisolated', 1);
        $this->setUser($user_one);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "User with id '{$user_one->id}' cannot access context"
        );
        $this->resolve_graphql_query($query_name, $parameters);
    }
}