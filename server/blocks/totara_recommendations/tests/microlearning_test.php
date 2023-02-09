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
use core_phpunit\testcase;
use totara_core\advanced_feature;
use totara_engage\timeview\time_view;

defined('MOODLE_INTERNAL') || die();

/**
 * @group block_totara_recommendations
 */
class block_totara_recommendations_microlearning_testcase extends testcase {
    use recommendations_service_mock_trait;

    /**
     * Simple data helper to run the sets of tests with & without legacy recommendations.
     *
     * @return array
     */
    public function simple_toggle_data_provider(): array {
        return [[false], [true]];
    }

    /**
     * Assert that only public & time-to-view <= 5 minute resources can be recommended.
     *
     * @param bool $legacy Indicates we're testing the ml_recommender data source
     * @return void
     * @dataProvider simple_toggle_data_provider
     */
    public function test_recommended_resources_multi_tenancy(bool $legacy): void {
        if (!class_exists('\totara_engage\timeview\time_view')) {
            $this->markTestSkipped('Engage was not available');
        }

        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $this->toggle_legacy_service($legacy);

        /** @var \engage_article\testing\generator $resources_generator */
        $resources_generator = $generator->get_plugin_generator('engage_article');

        $this->setAdminUser();

        $resource_a = $resources_generator->create_public_article();
        $resource_b = $resources_generator->create_public_article(['timeview' => time_view::FIVE_TO_TEN]);
        $resource_c = $resources_generator->create_article();

        // Create Tenant resources
        $tenant_t1 = $tenant_generator->create_tenant();
        $user_o_t1 = $generator->create_user();
        $tenant_generator->migrate_user_to_tenant($user_o_t1->id, $tenant_t1->id);

        $resource_t1_a = $resources_generator->create_public_article(['userid' => $user_o_t1->id]);
        $resource_t1_b = $resources_generator->create_public_article([
            'userid' => $user_o_t1->id,
            'timeview' => time_view::FIVE_TO_TEN
        ]);
        $resource_t1_c = $resources_generator->create_article(['userid' => $user_o_t1->id]);
        unset($user_o_t1);

        $tenant_t2 = $tenant_generator->create_tenant();
        $user_o_t2 = $generator->create_user();
        $tenant_generator->migrate_user_to_tenant($user_o_t2->id, $tenant_t2->id);

        $resource_t2_a = $resources_generator->create_public_article(['userid' => $user_o_t2->id]);
        $resource_t2_b = $resources_generator->create_public_article([
            'userid' => $user_o_t2->id,
            'timeview' => time_view::FIVE_TO_TEN
        ]);
        $resource_t2_c = $resources_generator->create_article(['userid' => $user_o_t2->id]);
        unset($user_o_t2);

        // Create users to recommend against
        $user_t1 = $generator->create_user();
        $user_t2 = $generator->create_user();
        $user_p_t1 = $generator->create_user();
        $user_sys = $generator->create_user();

        $tenant_generator->migrate_user_to_tenant($user_t1->id, $tenant_t1->id);
        $tenant_generator->migrate_user_to_tenant($user_t2->id, $tenant_t2->id);
        $tenant_generator->set_user_participation($user_p_t1->id, [$tenant_t1->id]);

        // Recommend everything to everyone
        $users = [$user_t1, $user_t2, $user_p_t1, $user_sys];
        $resource_ids = $this->pluck([
            $resource_a,
            $resource_b,
            $resource_c,
            $resource_t1_a,
            $resource_t1_b,
            $resource_t1_c,
            $resource_t2_a,
            $resource_t2_b,
            $resource_t2_c,
        ], 'get_id');
        foreach ($users as $user) {
            $this->recommend($resource_ids, $user->id);
        }

        // With isolation disabled
        set_config('tenantsisolated', 0);

        // Tenant Member 1 should see both tenant & system resources
        $this->commit_recommendations($user_t1->id);
        $recommended = recommendations_repository::get_recommended_micro_learning(10, $user_t1->id);
        self::assertCount(2, $recommended);
        $expected_ids = $this->pluck([$resource_a, $resource_t1_a], 'get_id');
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        // Participant 1 should see all valid resources
        $this->commit_recommendations($user_p_t1->id);
        $recommended = recommendations_repository::get_recommended_micro_learning(10, $user_p_t1->id);
        self::assertCount(3, $recommended);
        $expected_ids = $this->pluck([$resource_a, $resource_t1_a, $resource_t2_a], 'get_id');
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        // System user should see the same
        $this->commit_recommendations($user_sys->id);
        $recommended = recommendations_repository::get_recommended_micro_learning(10, $user_sys->id);
        self::assertCount(3, $recommended);
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        // With isolation enabled
        set_config('tenantsisolated', 1);

        // Tenant Member 1 should only see tenant resources.
        $this->commit_recommendations($user_t1->id);
        $recommended = recommendations_repository::get_recommended_micro_learning(10, $user_t1->id);
        self::assertCount(1, $recommended);
        $expected_ids = $this->pluck([$resource_t1_a], 'get_id');
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        // Participant 1 should see only system & tenant 1 resources
        $this->commit_recommendations($user_p_t1->id);
        $recommended = recommendations_repository::get_recommended_micro_learning(10, $user_p_t1->id);
        self::assertCount(2, $recommended);
        $expected_ids = $this->pluck([$resource_a, $resource_t1_a], 'get_id');
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        // System user should see only system resources
        $this->commit_recommendations($user_sys->id);
        $recommended = recommendations_repository::get_recommended_micro_learning(10, $user_sys->id);
        self::assertCount(1, $recommended);
        $expected_ids = $this->pluck([$resource_a], 'get_id');
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);
    }

    /**
     * Assert that with multi-tenancy the following rules are applied:
     *
     * If isolation is disabled, tenant members can see their own + system resources (public + time to view <= 5).
     * System users can see all resource (public + time to view <= 5).
     *
     * If isolation is enabled, tenant members can see their own resources (public + time to view <= 5).
     * Tenant participants can see system resources + any tenant they're a part of (public + time to view <= 5).
     * System users (no tenancy) can only see system resources (public + time to view <= 5).
     *
     * @param bool $legacy
     * @return void
     * @dataProvider simple_toggle_data_provider
     */
    public function test_recommended_resources_no_tenancy(bool $legacy): void {
        if (!class_exists('\totara_engage\timeview\time_view')) {
            $this->markTestSkipped('Engage was not available');
        }

        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->disable_tenants();

        $this->toggle_legacy_service($legacy);

        /** @var \engage_article\testing\generator $resources_generator */
        $resources_generator = $generator->get_plugin_generator('engage_article');

        $this->setAdminUser();

        $resource_a = $resources_generator->create_public_article();
        $resource_b = $resources_generator->create_public_article(['timeview' => time_view::FIVE_TO_TEN]);
        $resource_c = $resources_generator->create_article();

        $user = $generator->create_user();
        $this->recommend([$resource_a->get_id(), $resource_b->get_id(), $resource_c->get_id()], $user->id);
        $this->commit_recommendations($user->id);

        // Confirm we only see the public < 5 minute time to read article
        $recommended = recommendations_repository::get_recommended_micro_learning(10, $user->id);
        self::assertCount(1, $recommended);
        $recommended_ids = $this->pluck($recommended, 'item_id');
        $expected_ids = [$resource_a->get_id()];
        self::assertEqualsCanonicalizing($expected_ids, $recommended_ids);
    }

    /**
     * If we're running this test, make sure resources are enabled
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        advanced_feature::enable('ml_recommender');
        advanced_feature::enable('engage_resources');

        $this->start_mock_service('engage_article');
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();

        $this->clean_mock_service();
    }

    /**
     * @param array $collection
     * @param string $column
     * @return array
     */
    private function pluck(array $collection, string $column): array {
        $is_method = null;
        return array_map(function ($item) use ($column, &$is_method) {
            if ($is_method === null) {
                $is_method = method_exists($item, $column);
            }
            return $is_method ? call_user_func([$item, $column]) : $item->{$column};
        }, $collection);
    }
}
