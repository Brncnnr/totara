<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
use totara_core\advanced_feature;
use totara_webapi\graphql;

/**
 * Test the endpoints for recommending workspaces by workspace or user from the ML service
 */
class ml_recommender_get_service_recommended_workspaces_testcase extends testcase {
    /**
     * Test workspaces are recommended by user id
     */
    public function test_recommended_workspaces_by_user_graphql() {
        $generator = $this->getDataGenerator();
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');

        $this->setAdminUser();

        $user = $generator->create_user();
        $this->setUser($user);

        // We're going to recommend for user 2
        $user2 = $generator->create_user();

        // Going to create a few workspaces, then recommend *some* of them
        $recommended = [];
        for ($i = 1; $i <= 10; $i++) {
            $workspace = $workspace_generator->create_workspace(
                'W' . $i,
                'Summary',
                null,
                $user->id
            );
            // Recommend it if it's > 5
            if ($i > 5) {
                $recommended[] = $workspace->get_id();
            }
        }
        $this->set_recommended_data($recommended);

        // Now we're going to ask for some recommended workspaces
        advanced_feature::enable('ml_recommender');
        $this->setUser($user2);
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
        $this->set_recommended_data([]);
        $this->setUser($user);
        $parameters = [
            'cursor' => null,
            'theme' => 'ventura',
        ];
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
     */
    public function test_recommended_workspaces_multi_tenancy() {
        $generator = $this->getDataGenerator();
        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        advanced_feature::enable('ml_recommender');
        advanced_feature::enable('container_workspace');

        // User 1 & 2 will belong to Tenant 1, User 3 & 4 will belong to Tenant 2
        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();

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

        // Recommend all workspaces to both user 2 & user 4
        $recommended = [];
        foreach ($workspace_ids as $workspace_id) {
            $recommended[] = $workspace_id;
        }

        // Check what user2 can only see workspaces for tenant 1 & only public & non-enrolled
        $this->set_recommended_data($recommended);
        $this->setUser($user2);
        $ec = execution_context::create('ajax', 'ml_recommender_get_recommended_user_workspaces');
        $parameters = [
            'cursor' => null,
            'theme' => 'ventura',
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);
        $this->assertIsArray($result->data['workspaces']);
        $workspaces = $result->data['workspaces'];

        $this->assertCount(1, $workspaces);
        $workspace = current($workspaces);
        $this->assertSame('Public Tenant 1', $workspace['name']);

        // Check what user4 can only see workspaces for tenant 2 & only public & non-enrolled
        $this->set_recommended_data($recommended);
        $this->setUser($user4);
        $ec = execution_context::create('ajax', 'ml_recommender_get_recommended_user_workspaces');
        $parameters = [
            'cursor' => null,
            'theme' => 'ventura',
        ];
        $result = graphql::execute_operation($ec, $parameters);
        $this->assertNotNull($result->data);
        $this->assertIsArray($result->data['workspaces']);
        $workspaces = $result->data['workspaces'];

        $this->assertCount(1, $workspaces);
        $workspace = current($workspaces);
        $this->assertSame('Public Tenant 2', $workspace['name']);
    }

    /**
     * Enable ML
     */
    protected function setUp(): void {
        global $CFG;
        $CFG->ml_service_key = 'abc';
        $CFG->ml_service_url = 'http://example.com:5000';
    }

    /**
     * Cleanup
     */
    protected function tearDown(): void {
        $this->set_recommended_data(null);
    }

    /**
     * @param array|null $data
     */
    protected function set_recommended_data(?array $data): void {
        $mock_helper = null;
        if (null !== $data) {
            $mock_helper = $this->createMock(recommendations::class);
            $mock_helper
                ->method('get_user_recommendations')
                ->willReturn($data);
        }

        $reflection = new ReflectionProperty(workspaces_loader::class, 'recommendations_helper');
        $reflection->setAccessible(true);
        $reflection->setValue($mock_helper);
        $reflection->setAccessible(false);
    }
}
