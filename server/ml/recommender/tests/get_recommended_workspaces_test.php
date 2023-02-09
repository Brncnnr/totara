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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_recommender
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\member\member;
use core\webapi\execution_context;
use core_phpunit\testcase;
use ml_recommender\loader\recommended_item\workspaces_loader;
use ml_recommender\recommendations;
use PHPUnit\Framework\MockObject\MockObject;
use totara_core\advanced_feature;
use totara_webapi\graphql;

/**
 * Test the endpoints for recommending workspaces by workspace or user
 */
class ml_recommender_get_recommended_workspaces_testcase extends testcase {
    /**
     * @var bool
     */
    private $is_legacy_service;

    /**
     * @var array
     */
    private $recommendations;

    /**
     * Simple true/false provider that we use to run the unit test twice without internal looping.
     *
     * @return int[][]|array
     */
    public function simple_toggle_data_provider(): array {
        return [
            [true],
            [false],
        ];
    }

    /**
     * Test workspaces are recommended by user id
     * @dataProvider simple_toggle_data_provider
     */
    public function test_recommended_workspaces_by_user_graphql(bool $legacy) {
        $generator = $this->getDataGenerator();
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $this->toggle_legacy_service($legacy);

        $this->setAdminUser();

        $user = $generator->create_user();
        $this->setUser($user);

        // We're going to recommend for user 2
        $user2 = $generator->create_user();

        // Going to create a few workspaces, then recommend *some* of them
        $item_ids = [];
        for ($i = 1; $i <= 10; $i++) {
            $workspace = $workspace_generator->create_workspace(
                'W' . $i,
                'Summary',
                null,
                $user->id
            );
            // Recommend it if it's > 5

            if ($i > 5) {
                $item_ids[] = $workspace->get_id();
            }
        }
        $this->recommend($item_ids, $user2->id);

        // Now we're going to ask for some recommended workspaces
        advanced_feature::enable('ml_recommender');
        $this->setUser($user2);
        $this->commit_recommendations($user2->id);
        $ec = execution_context::create('ajax', 'ml_recommender_get_recommended_user_workspaces');
        $parameters = [
            'cursor' => null,
            'theme' => 'ventura',
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);

        $cursor = $result->data['cursor'];
        $results = $result->data['workspaces'];

        $this->assertEquals(5, $cursor['total']);
        $this->assertCount(5, $results);

        // Quick check
        $expected = ['W5', 'W6', 'W7', 'W8', 'W9', 'W10'];
        foreach ($results as $result) {
            $this->assertTrue(in_array($result['name'], $expected));
        }

        // Now check for no results
        $this->setUser($user);
        $parameters = [
            'cursor' => null,
            'theme' => 'ventura',
        ];
        $this->commit_recommendations($user->id);
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);

        $cursor = $result->data['cursor'];
        $results = $result->data['workspaces'];

        $this->assertEquals(0, $cursor['total']);
        $this->assertCount(0, $results);

        // Test disabled feature
        advanced_feature::disable('ml_recommender');
        $ec = execution_context::create('ajax', 'ml_recommender_get_recommended_user_workspaces');
        $this->setUser($user2);
        $this->commit_recommendations($user2->id);
        $parameters = [
            'user_id' => $user2->id,
            'cursor' => null,
            'theme' => 'ventura',
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);

        $cursor = $result->data['cursor'];
        $results = $result->data['workspaces'];

        $this->assertNull($cursor);
        $this->assertEmpty($results);
    }

    /**
     * Validate that we can only be recommended workspaces that belong to our tenancy
     * and are public & non-enrolled.
     *
     * @param bool $legacy
     * @return void
     * @dataProvider simple_toggle_data_provider
     */
    public function test_recommended_workspaces_multi_tenancy(bool $legacy): void {
        $generator = $this->getDataGenerator();
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        set_config('tenantsisolated', 0);
        advanced_feature::enable('ml_recommender');
        advanced_feature::enable('container_workspace');

        $this->toggle_legacy_service($legacy);

        // User 1 & 2 will belong to Tenant 1, User 3 & 4 will belong to Tenant 2, User 5 will be a system user.
        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user(); // System User

        $tenant_generator->migrate_user_to_tenant($user1->id, $tenant1->id);
        $user1->tenantid = $tenant1->id;

        $tenant_generator->migrate_user_to_tenant($user2->id, $tenant1->id);
        $user2->tenantid = $tenant1->id;

        $tenant_generator->migrate_user_to_tenant($user3->id, $tenant2->id);
        $user3->tenantid = $tenant2->id;

        $tenant_generator->migrate_user_to_tenant($user4->id, $tenant2->id);
        $user4->tenantid = $tenant2->id;

        $workspace_ids = [];

        // Create some workspaces owned by user1 & user3
        // Then we can check that only the correct workspaces are being returned
        $create_methods = [
            'create_workspace' => 'Public',
            'create_private_workspace' => 'Private',
            'create_hidden_workspace' => 'Hidden'
        ];
        foreach ([1 => $user1, 2 => $user3] as $tenant => $user) {
            foreach ($create_methods as $method => $title) {
                $this->setUser($user);
                $workspace = $workspace_generator->$method(
                    "{$title} Tenant {$tenant}",
                    null,
                    null,
                    $user->id
                );
                $workspace_ids[] = $workspace->get_id();

                // And the joinable version
                $workspace = $workspace_generator->$method(
                    "{$title} Tenant {$tenant} Joined",
                    null,
                    null,
                    $user->id
                );
                $workspace_ids[] = $workspace->get_id();
                // Join the workspace
                member::added_to_workspace(
                    $workspace,
                    $tenant === 1 ? $user2->id : $user4->id,
                    false,
                    $user->id
                );
            }
        }

        // Create a system-level workspace
        $this->setAdminUser();
        $system_workspace = $workspace_generator->create_workspace('System');
        $workspace_ids[] = $system_workspace->get_id();

        // Recommend all workspaces to both user 2, 4 & 5
        $this->recommend($workspace_ids, $user2->id);
        $this->recommend($workspace_ids, $user4->id);
        $this->recommend($workspace_ids, $user5->id);

        // Assert that user 2 & 4 only sees their tenant workspaces (plus system workspaces),
        // and that user 5 cannot see any tenant workspaces.
        $this->setUser($user2);
        $this->commit_recommendations($user2->id);
        $this->assert_workspaces(['Public Tenant 1', 'System']);
        $this->setUser($user4);
        $this->commit_recommendations($user4->id);
        $this->assert_workspaces(['Public Tenant 2', 'System']);
        $this->setUser($user5);
        $this->commit_recommendations($user5->id);
        $this->assert_workspaces(['System']);

        // With isolation enabled, repeat the tests but confirm the system workspace is no longer visible for
        // users 2 and 4.
        set_config('tenantsisolated', 1);
        $this->setUser($user2);
        $this->commit_recommendations($user2->id);
        $this->assert_workspaces(['Public Tenant 1']);
        $this->setUser($user4);
        $this->commit_recommendations($user4->id);
        $this->assert_workspaces(['Public Tenant 2']);
        $this->setUser($user5);
        $this->commit_recommendations($user5->id);
        $this->assert_workspaces(['System']);

        // Now with tenant isolation disabled, give user 5 the ability to see tenants
        set_config('tenantsisolated', 0);
        $role_id = $this->getDataGenerator()->create_role();
        $system_context = context_system::instance();
        assign_capability('totara/tenant:config', CAP_ALLOW, $role_id, $system_context);
        role_assign($role_id, $user5->id, $system_context);

        // User 5 should see all, regardless of isolation or not
        $this->setUser($user5);
        $this->commit_recommendations($user5->id);
        $this->assert_workspaces([
            'System',
            'Public Tenant 1',
            'Public Tenant 2',
            'Public Tenant 1 Joined',
            'Public Tenant 2 Joined'
        ]);

        set_config('tenantsisolated', 1);
        $this->assert_workspaces([
            'System',
            'Public Tenant 1',
            'Public Tenant 2',
            'Public Tenant 1 Joined',
            'Public Tenant 2 Joined'
        ]);
    }

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        $this->recommendations = [];
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();

        $this->is_legacy_service = null;
        $this->recommendations = null;

        // Reset the mock reflection
        $reflection = new ReflectionProperty(workspaces_loader::class, 'recommendations_helper');
        $reflection->setAccessible(true);
        $reflection->setValue(null);
        $reflection->setAccessible(false);
    }

    /**
     * Assert that the active user is can see the named workspaces.
     *
     * @param array $expected_names
     * @return void
     */
    private function assert_workspaces(array $expected_names): void {
        $expected_count = count($expected_names);

        $ec = execution_context::create('ajax', 'ml_recommender_get_recommended_user_workspaces');
        $parameters = [
            'cursor' => null,
            'theme' => 'ventura',
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);
        $this->assertIsArray($result->data['workspaces']);
        $workspaces = $result->data['workspaces'];

        $this->assertCount($expected_count, $workspaces);

        $names = array_map(function ($workspace) {
            return $workspace['name'];
        }, $workspaces);
        self::assertEqualsCanonicalizing($expected_names, $names);
    }

    /**
     * Switch between recommending from the mock service or the legacy tables.
     *
     * @param bool $legacy
     * @return void
     */
    private function toggle_legacy_service(bool $legacy): void {
        set_config('ml_service_url', $legacy ? null : 'http://localhost:5000');
        set_config('ml_service_key', $legacy ? '' : 'testing');
        $this->is_legacy_service = $legacy;
    }

    /**
     * @param array $item_ids
     * @param int $user_id
     * @return void
     */
    private function recommend(array $item_ids, int $user_id): void {
        $this->recommendations[$user_id] = $item_ids;
    }

    /**
     * Must be called before querying recommendations for any one user, this will insert their specific records.
     *
     * @param int $user_id
     * @return void
     */
    private function commit_recommendations(int $user_id): void {
        global $DB;
        $recommendations = $this->recommendations[$user_id] ?? [];

        if ($this->is_legacy_service) {
            // We only want to insert these records once
            $this->recommendations[$user_id] = null;
            foreach ($recommendations as $item_id) {
                $DB->insert_record('ml_recommender_users', [
                    'user_id' => $user_id,
                    'unique_id' => "container_workspace{$item_id}_user{$user_id}",
                    'item_id' => $item_id,
                    'component' => 'container_workspace',
                    'time_created' => time(),
                    'score' => 1,
                    'seen' => 0
                ]);
            }
        } else {
            $mock_helper = null;
            if (null !== $this->recommendations) {
                /** @var MockObject $mock_helper */
                $mock_helper = $this->createMock(recommendations::class);
                $mock_helper
                    ->method('get_user_recommendations')
                    ->willReturn($recommendations ?? []);
            }
            $reflection = new ReflectionProperty(workspaces_loader::class, 'recommendations_helper');
            $reflection->setAccessible(true);
            $reflection->setValue($mock_helper);
            $reflection->setAccessible(false);
        }
    }
}
