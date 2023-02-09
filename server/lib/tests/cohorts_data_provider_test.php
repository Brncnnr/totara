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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package core_cohort
 * @category test
 */

use core\collection;
use core\data_providers\cohorts;
use core\entity\cohort as cohort_entity;
use core\orm\query\builder;
use core\pagination\cursor;

// Needed for the cohort type enums.
global $CFG;
require_once($CFG->dirroot.'/totara/cohort/lib.php');

/**
 * @coversDefaultClass \core\data_providers\cohorts
 *
 * @group core_cohort
 */
class core_cohort_data_provider_testcase extends advanced_testcase {
    /**
     * @covers ::fetch_paginated
     */
    public function test_default_params(): void {
        $no_of_cohorts = 21;
        [$test_cohorts, $sys_context, ] = $this->create_cohorts($no_of_cohorts);

        $cohorts = new cohorts($sys_context);
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts->fetch_paginated();

        $this->assertEquals(7, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        // With every third cohort being in the system context we should get only 7
        $this->assertCount(7, $items, 'wrong current page count');

        $expected_cohorts = $test_cohorts->filter('contextid', $sys_context->id);

        $this->assertEqualsCanonicalizing(
            $expected_cohorts->pluck('id'),
            array_column($items, 'id'),
            'wrong retrievals'
        );
    }

    /**
     * @covers ::fetch_paginated
     */
    public function test_default_params_specific_cat_context(): void {
        [$test_cohorts, $system_context, $cat_ctx_1, $cat_ctx_2] = $this->create_cohorts(cohorts::DEFAULT_PAGE_SIZE);

        $expected = $test_cohorts
            ->filter(
                function (cohort_entity $entity) use ($cat_ctx_1, $system_context): bool {
                    return $entity->contextid == $cat_ctx_1->id || $entity->contextid == $system_context->id;
                }
            )
            ->pluck('name');

        $cohorts = new cohorts($cat_ctx_1);
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts->fetch_paginated();

        $this->assertEquals(count($expected), $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $this->assertCount(count($expected), $items, 'wrong current page count');
        $this->assertEqualsCanonicalizing($expected, array_column($items, 'name'), 'wrong retrievals');
    }

    /**
     * @covers ::set_filters
     * @covers ::fetch_paginated
     */
    public function test_filters(): void {
        [$test_cohorts, $system_context] = $this->create_cohorts();

        $first_cohort = $test_cohorts
            ->filter('contextid', $system_context->id)
            ->first();

        $last_cohort = $test_cohorts
            ->filter('contextid', $system_context->id)
            ->last();

        // Filter by single id value.
        $id = (int) $first_cohort->id;

        $cohorts = new cohorts();

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts
            ->set_filters(['ids' => $id])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');
        $this->assertEquals($id, $items[0]->id, 'wrong item retrieved');

        // Filter by multiple id value.
        $ids = [ $id, (int) $last_cohort->id ];

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts
            ->set_filters(['ids' => $ids])
            ->fetch_paginated();

        $this->assertEquals(count($ids), $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(count($ids), $items, 'wrong item count');

        $this->assertEqualsCanonicalizing($ids, array_column($items, 'id'), 'wrong items retrieved');

        // Filter by name.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts
            ->set_filters(['name' => 'cohort'])
            ->fetch_paginated();

        $expected_cohorts = $test_cohorts->filter('contextid', $system_context->id);

        $this->assertEqualsCanonicalizing(
            $expected_cohorts->pluck('id'),
            array_column($items, 'id'),
            'wrong retrievals'
        );

        $this->assertEquals(count($expected_cohorts), $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(count($expected_cohorts), $items, 'wrong item count');

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts
            ->set_filters(['name' => $first_cohort->name])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');
        $this->assertEqualsCanonicalizing([$first_cohort->id], array_column($items, 'id'));

        // Filter combination.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts
            ->set_filters([
                'ids' => $id,
                'name' => 'cohort'
            ])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts
            ->set_filters([
                'ids' => $ids,
                'name' => $first_cohort->name
            ])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');
        $this->assertEqualsCanonicalizing([$first_cohort->id], array_column($items, 'id'));

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts
            ->set_filters([
                'ids' => $ids,
                'name' => $last_cohort->name
            ])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');
        $this->assertEqualsCanonicalizing([$last_cohort->id], array_column($items, 'id'));

        // Filter no result combination.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts
            ->set_filters(['ids' => $id, 'name' => $last_cohort->name])
            ->fetch_paginated();

        $this->assertEquals(0, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(0, $items, 'wrong item count');

        // Unknown filter.
        $key = 'unknown';
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/$key/");
        $cohorts
            ->set_filters([$key => '#00'])
            ->fetch_paginated();
    }

    /**
     * @covers ::set_filters
     * @covers ::set_page_size
     * @covers ::fetch_paginated
     */
    public function test_filters_empty_values(): void {
        $no_of_cohorts = 5;
        [$test_cohorts, $system_context] = $this->create_cohorts($no_of_cohorts);

        $cohorts = new cohorts();
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts
            ->set_page_size($no_of_cohorts)
            ->set_filters([
                'ids' => [],    // This is a valid filter to filter everything out.
                'name' => '  ', // This filter is ignored.
                'type' => null  // As is this.
            ])
            ->fetch_paginated();

        $this->assertEquals(0, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(0, $items, 'wrong item count');

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts
            ->set_page_size($no_of_cohorts)
            ->set_filters([
                'ids' => null,  // Now this filter will be ignored.
                'name' => null,
                'type' => null
            ])
            ->fetch_paginated();

        // Only system context, so less than the number passed as limit
        $this->assertEquals(2, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(2, $items, 'wrong item count');
        // Only system context cohorts should be there
        foreach ($items as $item) {
            $this->assertEquals($system_context->id, $item->contextid);
        }

        $inactive_cohort_count = $test_cohorts
            ->filter('contextid', $system_context->id)
            ->filter('active', false)
            ->count();

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts
            ->set_page_size($no_of_cohorts)
            ->set_filters([
                'ids' => null,
                'name' => '  ',
                'type' => null,
                'active' => false // Although "empty", it is a valid filter.
            ])
            ->fetch_paginated();

        $this->assertEquals($inactive_cohort_count, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount($inactive_cohort_count, $items, 'wrong item count');
    }

    /**
     * @covers ::set_order
     * @covers ::set_page_size
     * @covers ::fetch_paginated
     */
    public function test_sorted_pagination(): void {
        $no_of_cohorts = 21;
        [$test_cohorts, $system_context] = $this->create_cohorts($no_of_cohorts);

        $order_direction = 'desc';
        $cohort_ids = $test_cohorts
            ->filter('contextid', $system_context->id)
            ->sort('id', $order_direction)
            ->pluck('id');

        $page_size = 3;
        $cohorts = (new cohorts())
            ->set_page_size($page_size)
            ->set_order('id', $order_direction);

        // 1st round.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts->fetch_paginated();

        $this->assertEquals(7, $total, 'wrong total count');
        $this->assertCount($page_size, $items, 'wrong current page count');
        $this->assertNotEmpty($enc_cursor, 'empty cursor');

        $retrieved = array_column($items, 'id');

        // 2nd round.
        $cursor = cursor::decode($enc_cursor);
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts->fetch_paginated($cursor);

        $this->assertEquals(7, $total, 'wrong total count');
        $this->assertCount(3, $items, 'wrong current page count');
        $this->assertNotEmpty($enc_cursor, 'empty cursor');

        $retrieved = array_merge($retrieved, array_column($items, 'id'));

        // 3rd round.
        $cursor = cursor::decode($enc_cursor);
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts->fetch_paginated($cursor);

        $this->assertEquals(7, $total, 'wrong total count');
        $this->assertCount(1, $items, 'wrong current page count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $retrieved = array_merge($retrieved, array_column($items, 'id'));

        // See if items were retrieved in the correct order.
        $this->assertEquals($cohort_ids, $retrieved, 'retrieved in wrong order');
    }

    /**
     * Generates test cohorts. Note: 1/3 of these will be in the system context
     * 1/3 in one course category and 1/3 in another course category.
     *
     * @param int $count no of cohorts to generate.
     *
     * @return array [collection of generated cohort entities, system context,
     *         category context#1, category context#2] tuple.
     */
    private function create_cohorts(int $count = 10): array {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $cat_context_1 = context_coursecat::instance($generator->create_category()->id);
        $cat_context_2 = context_coursecat::instance($generator->create_category()->id);

        $system_context = context_system::instance();

        $generator = $this->getDataGenerator();

        $cohorts = collection::new([]);
        for ($i = 0; $i < $count; $i++) {
            $base = sprintf('Test cohort #%02d', $i);
            $active = $i % 3 === 0 ? false : true;
            $type = $i % 4 === 0 ? cohort::TYPE_DYNAMIC : cohort::TYPE_STATIC;

            $context_id = null;
            switch ($i % 3) {
                case 0:
                    $context_id = $system_context->id;
                    break;

                case 1:
                    $context_id = $cat_context_1->id;
                    break;

                case 2:
                    $context_id = $cat_context_2->id;
                    break;
            }

            $cohort = $generator->create_cohort([
                'active' => $active,
                'component' => '',
                'contextid' => $context_id,
                'description' => "$base description",
                'descriptionformat' => FORMAT_MOODLE,
                'idnumber' => "$base idnumber",
                'name' => $base,
                'cohorttype' => $type,
                'visible' => true
            ]);

            $cohorts->append(new cohort_entity($cohort));
        }

        return [$cohorts, $system_context, $cat_context_1, $cat_context_2];
    }

    public function test_multi_tenancy() {
        $data = $this->setup_multi_tenancy_env();

        $system_context = context_system::instance();

        $generator = $this->getDataGenerator();
        // User in tenant 1
        $user11 = $generator->create_user([
            'tenantid' => $data->tenant1->id
        ]);
        // User in tenant 2
        $user21 = $generator->create_user([
            'tenantid' => $data->tenant2->id
        ]);

        // By default the users shouldn't see any cohorts
        $this->setUser($user11);

        $items = $this->load_by_context_id();
        $this->assertEmpty($items);

        $this->setUser($user21);

        $items = $this->load_by_context_id();
        $this->assertEmpty($items);

        $user_role = builder::table('role')->where('shortname', 'user')->one();

        assign_capability('moodle/cohort:view', CAP_ALLOW, $user_role->id, $system_context->id);

        $this->setUser($user11);

        // Now with the correct capability in the system context this users should
        // be able to see the system audiences
        $items = $this->load_by_context_id();
        $this->assert_result_contains_audiences($items, $data->aud1->id, $data->aud2->id);

        // Let's now assume the user has the capability to see only some children
        unassign_capability('moodle/cohort:view', $user_role->id, $system_context->id);

        // Give the user capability to view audiences of a sub category only
        assign_capability('moodle/cohort:view', CAP_ALLOW, $user_role->id, $data->tree1->cat11->get_context()->id);

        // Without the contextid parameter we only query the system context
        $items = $this->load_by_context_id();
        $this->assertEmpty($items);

        // Now we should only get those in that category
        $items = $this->load_by_context_id($data->tree1->cat11->get_context()->id);
        $this->assert_result_contains_audiences($items, $data->tree1->aud111->id, $data->tree1->aud112->id);

        // If we go further down the tree we should get more results
        $items = $this->load_by_context_id($data->tree1->cat112->get_context()->id);
        $this->assert_result_contains_audiences(
            $items,
            $data->tree1->aud1121->id,
            $data->tree1->aud1122->id,
            $data->tree1->aud111->id,
            $data->tree1->aud112->id
        );

        // Let's prohibit a lower level
        assign_capability('moodle/cohort:view', CAP_PROHIBIT, $user_role->id, $data->tree1->cat112->get_context()->id);

        // The lower level audiences should not be there
        $items = $this->load_by_context_id($data->tree1->cat112->get_context()->id);
        $this->assert_result_contains_audiences(
            $items,
            $data->tree1->aud111->id,
            $data->tree1->aud112->id
        );

        // Let's check whether we can see the whole tenant tree
        assign_capability(
            'moodle/cohort:view',
            CAP_ALLOW,
            $user_role->id,
            context_coursecat::instance($data->tenant2->categoryid)->id
        );

        $this->setUser($user21);

        // first check whether nothing comes back from the other tenant
        $items = $this->load_by_context_id($data->tree1->cat112->get_context()->id);
        $this->assertEmpty($items);

        // Now we should get all cohorts up from the lowest level (excluding the system ones)
        $items = $this->load_by_context_id($data->tree2->cat222->get_context()->id);
        $this->assert_result_contains_audiences(
            $items,
            $data->tree2->aud2221->id,
            $data->tree2->aud2222->id,
            $data->tree2->aud221->id,
            $data->tree2->aud222->id,
            $data->tree2->aud21->id,
            $data->tree2->aud22->id,
            $data->tenant2->cohortid
        );

        assign_capability('moodle/cohort:view', CAP_ALLOW, $user_role->id, $system_context->id);

        // Now we should get all cohorts up from the lowest level (excluding the system ones)
        $items = $this->load_by_context_id($data->tree2->cat222->get_context()->id);
        $this->assert_result_contains_audiences(
            $items,
            $data->tree2->aud2221->id,
            $data->tree2->aud2222->id,
            $data->tree2->aud221->id,
            $data->tree2->aud222->id,
            $data->tree2->aud21->id,
            $data->tree2->aud22->id,
            $data->tenant2->cohortid,
            $data->aud1->id,
            $data->aud2->id
        );

        // Now let's try with isolation on, then the user should not see the audiences on the system level
        set_config('tenantsisolated', 1);

        $items = $this->load_by_context_id($data->tree2->cat222->get_context()->id);
        $this->assert_result_contains_audiences(
            $items,
            $data->tree2->aud2221->id,
            $data->tree2->aud2222->id,
            $data->tree2->aud221->id,
            $data->tree2->aud222->id,
            $data->tree2->aud21->id,
            $data->tree2->aud22->id,
            $data->tenant2->cohortid
        );
    }

    /**
     * Test system audiences are excluded when tenant_scope is true and multi-tenancy is on.
     */
    public function test_system_audiences_are_excluded_when_tenant_scope_is_true_and_multi_tenancy_is_on(): void {
        $data = $this->setup_multi_tenancy_env();

        // Turn on multi-tenancy
        set_config('tenantsenabled', 1);
        $this->setAdminUser();
        $system_cohorts = $this->load_by_context_id();

        $tenant1_category_context = context_coursecat::instance($data->tenant1->categoryid);
        $tenant_cohorts = $this->load_by_context_id($tenant1_category_context->id, true);
        $tenant_cohorts_ids = array_map(
            function ($cohort) {
                return $cohort->id;
            },
            $tenant_cohorts
        );

        foreach ($system_cohorts as $system_cohort) {
            $this->assertFalse(in_array($system_cohort->id, $tenant_cohorts_ids));
        }
    }

    /**
     * Test system audiences are included when tenant_scope is true and multi-tenancy is off.
     */
    public function test_system_audiences_are_included_when_tenant_scope_is_true_and_multi_tenancy_is_off(): void {
        $data = $this->setup_multi_tenancy_env();

        // Turn off multi-tenancy
        set_config('tenantsenabled', 0);
        $this->setAdminUser();
        $system_cohorts = $this->load_by_context_id();

        $tenant1_category_context = context_coursecat::instance($data->tenant1->categoryid);
        $tenant_cohorts = $this->load_by_context_id($tenant1_category_context->id, true);
        $tenant_cohorts_ids = array_map(
            function ($cohort) {
                return $cohort->id;
            },
            $tenant_cohorts
        );

        foreach ($system_cohorts as $system_cohort) {
            $this->assertTrue(in_array($system_cohort->id, $tenant_cohorts_ids));
        }
    }

    /**
     * Test system audiences are included when tenant_scope is false and multi-tenancy is on.
     */
    public function test_system_audiences_are_included_when_tenant_scope_is_false_and_multi_tenancy_is_on(): void {
        $data = $this->setup_multi_tenancy_env();

        // Turn on multi-tenancy
        set_config('tenantsenabled', 1);
        $this->setAdminUser();
        $system_cohorts = $this->load_by_context_id();

        $tenant1_category_context = context_coursecat::instance($data->tenant1->categoryid);
        $tenant_cohorts = $this->load_by_context_id($tenant1_category_context->id, false);
        $tenant_cohorts_ids = array_map(
            function ($cohort) {
                return $cohort->id;
            },
            $tenant_cohorts
        );

        foreach ($system_cohorts as $system_cohort) {
            $this->assertTrue(in_array($system_cohort->id, $tenant_cohorts_ids));
        }
    }

    /**
     * Test system audiences are included when tenant_scope is false and multi-tenancy is off.
     */
    public function test_system_audiences_are_included_when_tenant_scope_is_false_and_multi_tenancy_is_off(): void {
        $data = $this->setup_multi_tenancy_env();

        // Turn off multi-tenancy
        set_config('tenantsenabled', 0);
        $this->setAdminUser();
        $system_cohorts = $this->load_by_context_id();

        $tenant1_category_context = context_coursecat::instance($data->tenant1->categoryid);
        $tenant_cohorts = $this->load_by_context_id($tenant1_category_context->id, false);
        $tenant_cohorts_ids = array_map(
            function ($cohort) {
                return $cohort->id;
            },
            $tenant_cohorts
        );

        foreach ($system_cohorts as $system_cohort) {
            $this->assertTrue(in_array($system_cohort->id, $tenant_cohorts_ids));
        }
    }

    /**
     * Test system audiences are excluded and sub-category cohorts are included to the tenant
     * when tenant_scope is true and multi-tenancy is on.
     */
    public function test_sub_category_audiences_are_included_when_tenant_scope_is_true_and_multi_tenancy_is_on(): void {
        $data = $this->setup_multi_tenancy_env();

        // Turn on multi-tenancy
        set_config('tenantsenabled', 1);
        $this->setAdminUser();
        $system_cohorts = $this->load_by_context_id();

        $tenant1_sub_category_context = context_coursecat::instance($data->tree1->cat11->id);
        $tenant_cohorts = $this->load_by_context_id($tenant1_sub_category_context->id, true);
        $tenant_cohorts_ids = array_map(
            function ($cohort) {
                return (int)$cohort->id;
            },
            $tenant_cohorts
        );

        // Check system cohorts are excluded.
        foreach ($system_cohorts as $system_cohort) {
            $this->assertNotContains((int)$system_cohort->id, $tenant_cohorts_ids);
        }

        // Check sub-category cohorts included.
        $this->assertEqualsCanonicalizing(
            [
                (int)$data->tenant1->cohortid,
                (int)$data->tree1->aud11->id,
                (int)$data->tree1->aud12->id,
                (int)$data->tree1->aud111->id,
                (int)$data->tree1->aud112->id,
            ],
            $tenant_cohorts_ids
        );
    }

    private function load_by_context_id(?int $context_id = null, ?bool $tenant_scope = false): array {
        $context = $context_id ? context::instance_by_id($context_id) : null;

        $cohorts = new cohorts($context, $tenant_scope);

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $cohorts->fetch_paginated();

        return $items;
    }

    private function assert_result_contains_audiences(array $items, ...$audiences) {
        $this->assertCount(count($audiences), $items);
        $this->assertEqualsCanonicalizing($audiences, array_column($items, 'id'));
    }

    private function setup_multi_tenancy_env() {
        $generator = $this->getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();

        $data = new class {
            public $tenant1, $tenant2;
            public $tree1, $tree2;
            public $aud1, $aud2;
        };

        $data->tenant1 = $tenant_generator->create_tenant();
        $data->tenant2 = $tenant_generator->create_tenant();

        $tenant1_category = builder::table('course_categories')->find($data->tenant1->categoryid);
        $tenant1_category_context = context_coursecat::instance($data->tenant1->categoryid);
        $tenant2_category = builder::table('course_categories')->find($data->tenant2->categoryid);
        $tenant2_category_context = context_coursecat::instance($data->tenant2->categoryid);

        $data->aud1 = $generator->create_cohort([
            'contextid' => context_system::instance()->id,
            'name' => 'aud1'
        ]);

        $data->aud2 = $generator->create_cohort([
            'contextid' => context_system::instance()->id,
            'name' => 'aud2'
        ]);

        // Build category tree
        $tree1 = new class {
            public $cat11, $cat12;
            public $cat111, $cat112;
            public $cat121, $cat122;
            public $aud11, $aud12;
            public $aud111, $aud112;
            public $aud1111, $aud1112;
            public $aud1121, $aud1122;
            public $aud121, $aud122;
            public $aud1211, $aud1212;
            public $aud1221, $aud1222;
        };

        /*
        Tenant1
            -> aud11
            -> aud12

            -> cat11
                -> aud111
                -> aud112

                -> cat111
                    -> aud1111
                    -> aud1112

                -> cat112
                    -> aud1121
                    -> aud1122

            -> cat12
                -> aud121
                -> aud122

                -> cat121
                    -> aud1211
                    -> aud1212

                -> cat122
                    -> aud1221
                    -> aud1222
         */

        $tree1->cat11 = $generator->create_category([
            'parent' => $tenant1_category->id,
            'name' => 'cat11'
        ]);

        $tree1->cat111 = $generator->create_category([
            'parent' => $tree1->cat11->id,
            'name' => 'cat111'
        ]);

        $tree1->cat112 = $generator->create_category([
            'parent' => $tree1->cat11->id,
            'name' => 'cat112'
        ]);

        $tree1->cat12 = $generator->create_category([
            'parent' => $tenant1_category->id,
            'name' => 'cat12'
        ]);

        $tree1->cat121 = $generator->create_category([
            'parent' => $tree1->cat12->id,
            'name' => 'cat121'
        ]);

        $tree1->cat122 = $generator->create_category([
            'parent' => $tree1->cat12->id,
            'name' => 'cat122'
        ]);

        $tree1->aud11 = $generator->create_cohort([
            'contextid' => $tenant1_category_context->id,
            'name' => 'aud11'
        ]);

        $tree1->aud12 = $generator->create_cohort([
            'contextid' => $tenant1_category_context->id,
            'name' => 'aud12'
        ]);

        $tree1->aud111 = $generator->create_cohort([
            'contextid' => $tree1->cat11->get_context()->id,
            'name' => 'aud111'
        ]);

        $tree1->aud112 = $generator->create_cohort([
            'contextid' => $tree1->cat11->get_context()->id,
            'name' => 'aud112'
        ]);

        $tree1->aud121 = $generator->create_cohort([
            'contextid' => $tree1->cat12->get_context()->id,
            'name' => 'aud121'
        ]);

        $tree1->aud122 = $generator->create_cohort([
            'contextid' => $tree1->cat12->get_context()->id,
            'name' => 'aud122'
        ]);

        $tree1->aud1111 = $generator->create_cohort([
            'contextid' => $tree1->cat111->get_context()->id,
            'name' => 'aud1111'
        ]);

        $tree1->aud1112 = $generator->create_cohort([
            'contextid' => $tree1->cat111->get_context()->id,
            'name' => 'aud1112'
        ]);

        $tree1->aud1121 = $generator->create_cohort([
            'contextid' => $tree1->cat112->get_context()->id,
            'name' => 'aud1121'
        ]);

        $tree1->aud1122 = $generator->create_cohort([
            'contextid' => $tree1->cat112->get_context()->id,
            'name' => 'aud1122'
        ]);

        $tree1->aud1211 = $generator->create_cohort([
            'contextid' => $tree1->cat121->get_context()->id,
            'name' => 'aud1211'
        ]);

        $tree1->aud1212 = $generator->create_cohort([
            'contextid' => $tree1->cat121->get_context()->id,
            'name' => 'aud1212'
        ]);

        $tree1->aud1221 = $generator->create_cohort([
            'contextid' => $tree1->cat122->get_context()->id,
            'name' => 'aud1221'
        ]);

        $tree1->aud1222 = $generator->create_cohort([
            'contextid' => $tree1->cat122->get_context()->id,
            'name' => 'aud1222'
        ]);

        $tree2 = new class {
            public $ca211, $cat22;
            public $cat211, $cat212;
            public $cat221, $cat222;
            public $aud21, $aud22;
            public $aud211, $aud212;
            public $aud2111, $aud2112;
            public $aud2121, $aud2122;
            public $aud221, $aud222;
            public $aud2211, $aud2212;
            public $aud2221, $aud2222;
        };

        /*
        Tenant2
            -> aud21
            -> aud22

            -> cat21
                -> aud211
                -> aud212

                -> cat211
                    -> aud2111
                    -> aud2112

                -> cat212
                    -> aud2121
                    -> aud2122

            -> cat22
                -> aud221
                -> aud222

                -> cat221
                    -> aud2211
                    -> aud2212

                -> cat222
                    -> aud2221
                    -> aud2222
         */

        $tree2->cat21 = $generator->create_category([
            'parent' => $tenant2_category->id,
            'name' => 'cat21'
        ]);

        $tree2->cat211 = $generator->create_category([
            'parent' => $tree2->cat21->id,
            'name' => 'cat211'
        ]);

        $tree2->cat212 = $generator->create_category([
            'parent' => $tree2->cat21->id,
            'name' => 'cat212'
        ]);

        $tree2->cat22 = $generator->create_category([
            'parent' => $tenant2_category->id,
            'name' => 'cat22'
        ]);

        $tree2->cat221 = $generator->create_category([
            'parent' => $tree2->cat22->id,
            'name' => 'cat221'
        ]);

        $tree2->cat222 = $generator->create_category([
            'parent' => $tree2->cat22->id,
            'name' => 'cat222'
        ]);

        $tree2->aud21 = $generator->create_cohort([
            'contextid' => $tenant2_category_context->id,
            'name' => 'aud21'
        ]);

        $tree2->aud22 = $generator->create_cohort([
            'contextid' => $tenant2_category_context->id,
            'name' => 'aud22'
        ]);

        $tree2->aud211 = $generator->create_cohort([
            'contextid' => $tree2->cat21->get_context()->id,
            'name' => 'aud211'
        ]);

        $tree2->aud212 = $generator->create_cohort([
            'contextid' => $tree2->cat21->get_context()->id,
            'name' => 'aud212'
        ]);

        $tree2->aud221 = $generator->create_cohort([
            'contextid' => $tree2->cat22->get_context()->id,
            'name' => 'aud221'
        ]);

        $tree2->aud222 = $generator->create_cohort([
            'contextid' => $tree2->cat22->get_context()->id,
            'name' => 'aud222'
        ]);

        $tree2->aud2111 = $generator->create_cohort([
            'contextid' => $tree2->cat211->get_context()->id,
            'name' => 'aud2111'
        ]);

        $tree2->aud2112 = $generator->create_cohort([
            'contextid' => $tree2->cat211->get_context()->id,
            'name' => 'aud2112'
        ]);

        $tree2->aud2121 = $generator->create_cohort([
            'contextid' => $tree2->cat212->get_context()->id,
            'name' => 'aud2121'
        ]);

        $tree2->aud2122 = $generator->create_cohort([
            'contextid' => $tree2->cat212->get_context()->id,
            'name' => 'aud2122'
        ]);

        $tree2->aud2211 = $generator->create_cohort([
            'contextid' => $tree2->cat221->get_context()->id,
            'name' => 'aud2211'
        ]);

        $tree2->aud2212 = $generator->create_cohort([
            'contextid' => $tree2->cat221->get_context()->id,
            'name' => 'aud2212'
        ]);

        $tree2->aud2221 = $generator->create_cohort([
            'contextid' => $tree2->cat222->get_context()->id,
            'name' => 'aud2221'
        ]);

        $tree2->aud2222 = $generator->create_cohort([
            'contextid' => $tree2->cat222->get_context()->id,
            'name' => 'aud2222'
        ]);

        $data->tree1 = $tree1;
        $data->tree2 = $tree2;

        return $data;
    }

}