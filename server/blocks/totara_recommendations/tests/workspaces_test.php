<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @package block_totara_recommendations
 */

use block_totara_recommendations\repository\recommendations_repository;
use block_totara_recommendations\testing\recommendations_service_mock_trait;
use container_workspace\member\member;
use core_phpunit\testcase;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * @group block_totara_recommendations
 */
class block_totara_recommendations_workspaces_testcase extends testcase {
    use recommendations_service_mock_trait;

    /**
     * Simple data provider to toggle between multi-tenancy enabled/disabled with isolation or not
     *
     * @return array
     */
    public function multi_tenancy_data_provider(): array {
        return [
            // MT, Isolated, Legacy
            [1, 1, false],
            [1, 0, false],
            [0, 0, false],
            [1, 1, true],
            [1, 0, true],
            [0, 0, true],
        ];
    }

    /**
     * Simple true/false provider that we use to run the unit test twice without internal looping.
     *
     * @return int[][]|array
     */
    public function simple_toggle_data_provider(): array {
        return [
            // MT Enabled, Legacy MT
            [0, false],
            [1, false],
            [0, true],
            [1, true],
        ];
    }

    /**
     * Assert we recommend system-level workspaces to a regular non-tenant user with & without multi-tenancy
     * and isolation enabled. In all cases they should never see a tenant-based workspace.
     *
     * This covers both a system user inside a tenant-enabled site, and a regular user in a non-tenant site.
     *
     * @param int $multitenancy_enabled
     * @param int $isolated
     * @param bool $legacy Indicates we're testing the ml_recommender data source
     * @return void
     * @dataProvider multi_tenancy_data_provider
     */
    public function test_recommended_workspaces_no_tenancy(int $multitenancy_enabled, int $isolated, bool $legacy): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->disable_tenants();
        set_config('tenantsisolated', 0);

        if ($multitenancy_enabled) {
            $tenant_generator->enable_tenants();
            set_config('tenantsisolated', $isolated);
        }

        $this->toggle_legacy_service($legacy);

        $workspaces_generator = $generator->get_plugin_generator('container_workspace');
        /** @var \container_workspace\testing\generator $workspaces_generator */

        $this->setAdminUser();

        $workspace_a = $workspaces_generator->create_workspace('A Public');
        $workspace_b = $workspaces_generator->create_private_workspace('B Private');
        $workspace_c = $workspaces_generator->create_hidden_workspace('C Hidden');

        $user = $generator->create_user();

        // If we're running multi-tenancy, create a tenant & add an extra workspace to recommend
        if ($multitenancy_enabled) {
            [$workspace_d] = $this->create_tenant_user_and_workspace();
            $this->recommend([$workspace_d->get_id()], $user->id);
        }

        // recommend a, b & c to user
        $this->recommend([$workspace_a->get_id(), $workspace_b->get_id(), $workspace_c->get_id()], $user->id);
        $this->commit_recommendations($user->id);

        // Confirm that only A is visible (public)
        $recommended = recommendations_repository::get_recommended_workspaces(3, $user->id);
        self::assertCount(1, $recommended);
        $expected_ids = [$workspace_a->get_id()];
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        // Now enrol in the workspace and make sure it stops being recommended
        member::join_workspace($workspace_a, $user->id);

        $recommended = recommendations_repository::get_recommended_workspaces(3, $user->id);
        self::assertCount(0, $recommended);
    }

    /**
     * Assert that recommendations for workspaces respect the tenancy boundaries.
     * - System user (non-participant) should only see system workspaces.
     * - Tenant member should see tenant workspace & (if non-isolation) system workspaces.
     * - Tenant participants should see tenant workspaces (they are in) and system workspaces (regardless of isolation).
     * - System user with the tenant capability should see all workspaces, tenant & system (regardless of isolation).
     *
     * @param int $isolated
     * @param bool $legacy Indicates we're testing the ml_recommender data source
     * @return void
     * @dataProvider simple_toggle_data_provider
     */
    public function test_recommended_workspaces_with_tenants(int $isolated, bool $legacy): void {
        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        set_config('tenantsisolated', $isolated);

        $this->toggle_legacy_service($legacy);

        $workspaces_generator = $generator->get_plugin_generator('container_workspace');
        /** @var \container_workspace\testing\generator $workspaces_generator */

        $this->setAdminUser();

        // System workspaces
        $workspace_a = $workspaces_generator->create_workspace('A Public');
        $workspace_b = $workspaces_generator->create_private_workspace('B Private');
        $workspace_c = $workspaces_generator->create_hidden_workspace('C Hidden');

        // Create the tenant workspaces
        [$workspace_t_a, $tenant_a, $user_t_a] = $this->create_tenant_user_and_workspace();
        [$workspace_t_b] = $this->create_tenant_user_and_workspace();

        $workspace_ids = $this->pluck([$workspace_a, $workspace_b, $workspace_c, $workspace_t_a, $workspace_t_b], 'id');

        // Recommend all workspaces to User Tenant A (who is a member of Tenant A)
        $this->recommend($workspace_ids, $user_t_a->id);
        $this->commit_recommendations($user_t_a->id);
        $recommended = recommendations_repository::get_recommended_workspaces(10, $user_t_a->id);

        if ($isolated) {
            // If isolation is enabled, we should only see Tenant A workspaces
            self::assertCount(1, $recommended);
            $first = current($recommended);
            self::assertEquals($workspace_t_a->get_id(), $first->item_id);
        } else {
            // If isolation is disabled, we should see Tenant A workspaces & public system workspaces
            self::assertCount(2, $recommended);
            $expected_ids = $this->pluck([$workspace_a, $workspace_t_a], 'id');
            $actual_ids = $this->pluck($recommended, 'item_id');
            self::assertEqualsCanonicalizing($expected_ids, $actual_ids);
        }

        // Recommend all workspaces to User 4 who is a participant of Tenant A
        $user_4 = $generator->create_user();
        $tenant_generator->set_user_participation($user_4->id, [$tenant_a->id]);
        $this->recommend($workspace_ids, $user_4->id);

        $this->commit_recommendations($user_4->id);
        $recommended = recommendations_repository::get_recommended_workspaces(10, $user_4->id);

        // User 4 should see all system workspaces & all on Tenant A public workspaces
        self::assertCount(2, $recommended);
        $expected_ids = $this->pluck([$workspace_a, $workspace_t_a], 'id');
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        // Recommend all workspaces to User 5 who is a system user and not a participant
        $user_5 = $generator->create_user();
        $this->recommend($workspace_ids, $user_5->id);

        $this->commit_recommendations($user_5->id);
        $recommended = recommendations_repository::get_recommended_workspaces(10, $user_5->id);

        // User 5 should see only system workspaces
        self::assertCount(1, $recommended);
        $first = current($recommended);
        self::assertEquals($workspace_a->get_id(), $first->item_id);

        // Give user 5 the capability “totara/tenant:config”, which allows tenancy recommendations
        $role_id = $this->getDataGenerator()->create_role();
        $system_context = context_system::instance();
        assign_capability('totara/tenant:config', CAP_ALLOW, $role_id, $system_context);
        role_assign($role_id, $user_5->id, $system_context);

        $recommended = recommendations_repository::get_recommended_workspaces(10, $user_5->id);
        self::assertCount(3, $recommended);

        $expected_ids = $this->pluck([$workspace_a, $workspace_t_a, $workspace_t_b], 'id');
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);
    }

    /**
     * If we're running this test, make sure workspaces are enabled
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        advanced_feature::enable('ml_recommender');
        advanced_feature::enable('container_workspace');

        $this->start_mock_service('container_workspace');
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();
        $this->clean_mock_service();
    }

    /**
     * Creates a tenant, a workspace and a user.
     *
     * @return array
     */
    private function create_tenant_user_and_workspace(): array {
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');

        $workspaces_generator = $this->getDataGenerator()->get_plugin_generator('container_workspace');
        /** @var \container_workspace\testing\generator $workspaces_generator */

        $tenant = $tenant_generator->create_tenant();
        $workspace_owner = $this->getDataGenerator()->create_user();
        $tenant_generator->migrate_user_to_tenant($workspace_owner->id, $tenant->id);

        $tenant_user = $this->getDataGenerator()->create_user();
        $tenant_generator->migrate_user_to_tenant($tenant_user->id, $tenant->id);

        $category = $workspaces_generator->create_category(['tenant_id' => $tenant->id]);
        $workspace = $workspaces_generator->create_workspace(
            null,
            null,
            null,
            $workspace_owner->id,
            false,
            false,
            null,
            $category->id
        );

        return [$workspace, $tenant, $tenant_user];
    }

    /**
     * @param array $collection
     * @param string $column
     * @return array
     */
    private function pluck(array $collection, string $column): array {
        return array_map(function ($item) use ($column) {
            return $item->{$column};
        }, $collection);
    }
}
