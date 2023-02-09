<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 * @subpackage test
 */

use core_phpunit\testcase;
use totara_competency\entity\assignment;
use totara_competency\expand_task;
use totara_competency\user_groups;
use totara_competency\data_providers\user_assignable_competencies as data_provider;
use totara_competency\models\self_assignable_competency;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_repository;
use totara_job\job_assignment;

defined('MOODLE_INTERNAL') || die();


/**
 * Tests the data provider
 *
 * @group totara_competency
 */
class totara_competency_user_assignable_competencies_data_provider_testcase extends testcase {

    public function test_returns_empty_result() {
        $generator = $this->getDataGenerator();

        $user1 = $generator->create_user();

        $this->setUser($user1);

        $filters = [];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertIsArray($result['items']);
        $this->assertCount(0, $result['items']);
        $this->assertEquals(0, $result['total']);
    }

    public function test_returns_the_default_page_size_when_no_cursor_is_supplied() {
        $generator = $this->getDataGenerator();

        $user1 = $generator->create_user();
        $this->setUser($user1);
        $fw = $this->generator()->hierarchy_generator()->create_comp_frame([]);

        $self_assignable_count = data_provider::DEFAULT_PAGE_SIZE + 5;

        for ($i = 1; $i <= $self_assignable_count; $i++) {
            $competency = $this->generator()->create_competency(null, $fw->id, [
                'shortname' => 'c-chef--' . $i,
                'fullname' => 'Chef proficiency ' . $i,
                'description' => 'Bossing around ' . $i,
                'idnumber' => 'cook-chef-c--' . $i,
            ]);

            $this->activate_self_assignable($competency->id);
        }

        $filters = [];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertIsArray($result['items']);
        $this->assertCount(data_provider::DEFAULT_PAGE_SIZE, $result['items']);
        $this->assertEquals($self_assignable_count, $result['total']);
    }

    /**
     * @dataProvider empty_filter_provider
     * @param array $empty_filter
     * @param $expected_normalized_filter
     */
    public function test_empty_filters_are_normalized(array $empty_filter, $expected_normalized_filter): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @type $repository_spy competency_repository|MockObject */
        $repository_spy = $this->getMockBuilder(competency_repository::class)
            ->setConstructorArgs([competency::class])
            ->setMethods(['set_filters'])
            ->getMock();

        // The repository should receive normalized filters.
        $repository_spy->expects(static::once())
            ->method('set_filters')
            ->with($expected_normalized_filter)
            ->willReturnSelf();

        data_provider::for($user->id)
            ->set_competency_repository($repository_spy)
            ->set_filters($empty_filter)
            ->set_order(null, null)
            ->fetch_paginated();
    }

    public function empty_filter_provider(): array {
        $default_no_options_filter = ['visible' => true];

        return [
            'Empty array' => [[], $default_no_options_filter],
            'Empty array options' => [['type' => [], 'assignment_status' => []], $default_no_options_filter],
            'Null options' => [['type' => null, 'assignment_status' => null], $default_no_options_filter],
            'Present options' => [
                ['type' => ['type 1']],
                ['visible' => true, 'type' => ['type 1']]
            ],
        ];
    }

    public function test_only_competencies_with_self_assign_setting_are_returned() {
        $generator = $this->getDataGenerator();

        $fw = $this->generator()->hierarchy_generator()->create_comp_frame([]);

        $comp1 = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
        ]);

        $comp2 = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
        ]);

        $user1 = $generator->create_user();

        $this->setUser($user1);

        $filters = [];
        $order_by = null;
        $order_dir = null;

        // No competency has self assignment activated yet
        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertCount(0, $result['items']);
        $this->assertEquals(0, $result['total']);

        // Activate self assignment for the first competency
        $this->activate_self_assignable($comp1->id);

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertCount(1, $result['items']);
        $this->assertEquals(1, $result['total']);
        $competency = $result['items'][0];
        $this->assertEquals($comp1->id, $competency->get_id());

        // Activate self assignment for the second competency and check that it's in the result
        $this->activate_self_assignable($comp2->id);

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertCount(2, $result['items']);
        $this->assertEquals(2, $result['total']);
        $expected_comp_ids = [$comp1->id, $comp2->id];
        $actual_comp_is = $this->get_fieldset_from_result('id', $result);
        $this->assertEqualsCanonicalizing($expected_comp_ids, $actual_comp_is);

        // Finally verify that the self assignment availability does not affect other users
        $user2 = $generator->create_user();

        $this->setUser($user1);

        // User is now managing another user and can assign competencies for them
        $manager_job = job_assignment::create(['userid' => $user1->id, 'idnumber' => 1]);
        job_assignment::create(['userid' => $user2->id, 'idnumber' => 2, 'managerjaid' => $manager_job->id]);

        $result = data_provider::for($user2->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertCount(0, $result['items']);
        $this->assertEquals(0, $result['total']);
    }

    public function test_only_competencies_with_other_assign_setting_are_returned() {
        $generator = $this->getDataGenerator();

        $fw = $this->generator()->hierarchy_generator()->create_comp_frame([]);

        $comp1 = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'comp1',
            'fullname' => 'Competency 1',
            'description' => 'Competency 1 description',
            'idnumber' => 'comp1',
        ]);

        $comp2 = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'comp2',
            'fullname' => 'Competency 2',
            'description' => 'Competency 2 description',
            'idnumber' => 'comp2',
        ]);

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // User is now managing another user and can assign competencies for them
        $manager_job = job_assignment::create(['userid' => $user1->id, 'idnumber' => 1]);
        job_assignment::create(['userid' => $user2->id, 'idnumber' => 2, 'managerjaid' => $manager_job->id]);

        $this->setUser($user1);

        $filters = [];
        $order_by = null;
        $order_dir = null;

        // No competency has other assignment activated yet
        $result = data_provider::for($user2->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertCount(0, $result['items']);
        $this->assertEquals(0, $result['total']);

        // Activate other assignment for the first competency
        $this->activate_other_assignable($comp1->id);

        $result = data_provider::for($user2->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertCount(1, $result['items']);
        $this->assertEquals(1, $result['total']);
        $competency = $result['items'][0];
        $this->assertEquals($comp1->id, $competency->get_id());

        // Activate self assignment for the second competency and check that it's in the result
        $this->activate_other_assignable($comp2->id);

        $result = data_provider::for($user2->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertCount(2, $result['items']);
        $this->assertEquals(2, $result['total']);
        $expected_comp_ids = [$comp1->id, $comp2->id];
        $actual_comp_is = $this->get_fieldset_from_result('id', $result);
        $this->assertEqualsCanonicalizing($expected_comp_ids, $actual_comp_is);

        // Now make sure that the other assignment does not affect self assignment
        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertCount(0, $result['items']);
        $this->assertEquals(0, $result['total']);
    }

    public function test_competencies_able_assign_by_self_are_loaded() {
        $user1 = $this->getDataGenerator()->create_user();

        ['comps' => $comps] = $this->generate_competencies($user1->id);

        foreach ($comps as $comp) {
            $this->activate_other_assignable($comp->id);
        }

        $this->setUser($user1);

        $filters = [];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertCount(10, $result['items']);
        $this->assertEquals(10, $result['total']);
        $this->assertEqualsCanonicalizing([
            'Accounting',
            'Baking skill-set',
            // This one is not in the result as it's already self-assigned
            // 'Chef proficiency',
            'Coding',
            'Cooking',
            'Designing interiors',
            'Hacking',
            'Leading',
            'Planning',
            'Talking',
            'Typing',
        ], $this->get_fieldset_from_result('display_name', $result));
    }

    public function test_competencies_able_to_assign_by_others_are_loaded() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // User is now managing another user and can assign competencies for them
        $manager_job = job_assignment::create(['userid' => $user2->id, 'idnumber' => 1]);
        job_assignment::create(['userid' => $user1->id, 'idnumber' => 2, 'managerjaid' => $manager_job->id]);

        ['comps' => $comps] = $this->generate_competencies($user1->id);

        foreach ($comps as $comp) {
            $this->activate_other_assignable($comp->id);
        }

        $this->setUser($user2);

        $filters = [];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertCount(10, $result['items']);
        $this->assertEquals(10, $result['total']);
        $this->assertEqualsCanonicalizing([
            'Accounting',
            // This one is not in the result because it's already assigned via others
            // 'Baking skill-set',
            'Chef proficiency',
            'Coding',
            'Cooking',
            'Designing interiors',
            'Hacking',
            'Leading',
            'Planning',
            'Talking',
            'Typing',
        ], $this->get_fieldset_from_result('display_name', $result));
    }

    public function test_result_gets_ordered_by_name_by_default() {
        $this->generate_competencies();

        $user1 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        $filters = [];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);

        $expected_ids = competency::repository()
            ->order_by('fullname')
            ->order_by('id')
            ->where('visible', true)
            ->get()
            ->pluck('id');

        $actual_ids = $this->get_fieldset_from_result('id', $result);

        $this->assertEquals($expected_ids, $actual_ids);
    }

    public function test_result_gets_ordered_by_fullname() {
        $this->generate_competencies();

        $user1 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        // Leave out asc to test default order_dir
        $filters = [];
        $order_by = 'fullname';
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);

        $expected_ids = competency::repository()
            ->order_by('fullname', 'asc')
            ->where('visible', true)
            ->get()
            ->pluck('id');

        $actual_ids = $this->get_fieldset_from_result('id', $result);
        $this->assertEquals($expected_ids, $actual_ids);

        // add ascending order_dir
        $filters = [];
        $order_by = 'fullname';
        $order_dir = 'asc';

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);

        $expected_ids = competency::repository()
            ->order_by('fullname', 'asc')
            ->where('visible', true)
            ->get()
            ->pluck('id');

        $actual_ids = $this->get_fieldset_from_result('id', $result);
        $this->assertEquals($expected_ids, $actual_ids);

        // descending order_dir
        $filters = [];
        $order_by = 'fullname';
        $order_dir = 'desc';

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);

        $expected_ids = competency::repository()
            ->order_by('fullname', 'desc')
            ->where('visible', true)
            ->get()
            ->pluck('id');

        $actual_ids = $this->get_fieldset_from_result('id', $result);
        $this->assertEquals($expected_ids, $actual_ids);
    }

    public function test_can_be_filtered_by_text() {
        $this->generate_competencies();

        $user1 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        $filters = ['text' => 'des'];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Designing interiors',
        ], $this->get_fieldset_from_result('display_name', $result));

        // Searching by description
        $filters = ['text' => 'cook'];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Baking skill-set',
            'Chef proficiency',
            'Cooking',
        ], $this->get_fieldset_from_result('display_name', $result));
    }

    public function test_can_be_filtered_by_framework() {
        [, $fws] = array_values($this->generate_competencies());

        $user1 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        $filters = ['framework' => $fws[1]->id];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Baking skill-set',
            'Chef proficiency',
            'Coding',
            'Hacking',
            'Leading',
            'Planning',
            'Talking',
        ], $this->get_fieldset_from_result('display_name', $result));
    }

    public function test_can_be_filtered_by_path() {
        ['comps' => $comp] = $this->generate_competencies();

        $user1 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        $filters = ['path' => $comp[0]->id];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Coding',
            'Cooking',
            'Designing interiors',
            'Hacking',
            'Leading',
            'Planning',
            'Talking',
            'Typing',
        ], $this->get_fieldset_from_result('display_name', $result));
    }

    public function test_can_be_filtered_by_parent() {
        [$comp] = array_values($this->generate_competencies());

        $user1 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        $filters = ['parent' => $comp[0]->id];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Coding',
            'Designing interiors',
            'Hacking',
            'Leading',
            'Planning',
            'Talking',
            'Typing',
        ], $this->get_fieldset_from_result('display_name', $result));
    }

    public function test_can_be_filtered_by_status() {
        $user1 = $this->getDataGenerator()->create_user();

        ['fws' => $fws] = $this->generate_competencies($user1->id);

        $this->setUser($user1);

        // Filter by assigned only
        $filters = ['assignment_status' => 1];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Accounting',
            'Baking skill-set',
            // This competency is self assigned so should not be in the result
            // 'Chef proficiency',
            'Coding',
            'Cooking',
            'Designing interiors',
            'Hacking',
            'Talking',
        ], $this->get_fieldset_from_result('display_name', $result));

        // Filter by unassigned only
        $filters = [
            'assignment_status' => 0,
            'framework' => $fws[1]->id,
        ];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Leading',
            'Planning'
        ], $this->get_fieldset_from_result('display_name', $result));

        // Filter by (any) assigned and unassigned
        $filters = ['assignment_status' => null];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Accounting',
            'Baking skill-set',
            // This competency is self assigned so should not be in the result
            // 'Chef proficiency',
            'Coding',
            'Cooking',
            'Designing interiors',
            'Hacking',
            'Leading',
            'Planning',
            'Talking',
            'Typing',
        ], $this->get_fieldset_from_result('display_name', $result));
    }

    public function test_can_be_filtered_by_assignment_type_but_returns_empty_result() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Only create assignments for user 1
        $this->generate_competencies($user1->id);

        $this->setUser($user2);

        $filters = [];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user2->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertCount(11, $result['items']);
        $this->assertEquals(11, $result['total']);
        $this->assertEqualsCanonicalizing([
            'Accounting',
            'Baking skill-set',
            'Chef proficiency',
            'Coding',
            'Cooking',
            'Designing interiors',
            'Hacking',
            'Leading',
            'Planning',
            'Talking',
            'Typing',
        ], $this->get_fieldset_from_result('display_name', $result));

        $filters = ['assignment_type' => [ user_groups::POSITION ]];

        $result = data_provider::for($user2->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertCount(0, $result['items']);
        $this->assertEquals(0, $result['total']);
    }

    public function test_can_be_filtered_by_assignment_type() {
        $user1 = $this->getDataGenerator()->create_user();

        $this->generate_competencies($user1->id);

        $this->setUser($user1);

        // Has position assignment
        $filters = ['assignment_type' => [ user_groups::POSITION ]];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Talking',
        ], $this->get_fieldset_from_result('display_name', $result));

        // Has organisation assignment
        $filters = ['assignment_type' => [ user_groups::ORGANISATION ]];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Coding',
        ], $this->get_fieldset_from_result('display_name', $result));

        // Has cohort assignment
        $filters = ['assignment_type' => [ user_groups::COHORT ]];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Hacking',
        ], $this->get_fieldset_from_result('display_name', $result));

        // Has position and organisation assignment
        $filters = [
            'assignment_type' => [
                user_groups::POSITION,
                user_groups::ORGANISATION
            ]
        ];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Coding',
            'Talking'
        ], $this->get_fieldset_from_result('display_name', $result));

        // Has self assignment
        $filters = ['assignment_type' => [assignment::TYPE_SELF]];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertEmpty($result['items']);

        // Has other assignment
        $filters = ['assignment_type' => [assignment::TYPE_OTHER]];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Baking skill-set',
        ], $this->get_fieldset_from_result('display_name', $result));

        // Has system assignment
        $filters = ['assignment_type' => [assignment::TYPE_SYSTEM]];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Cooking',
        ], $this->get_fieldset_from_result('display_name', $result));

        // Has admin assignment
        $filters = ['assignment_type' => [assignment::TYPE_ADMIN]];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Accounting',
            'Designing interiors',
        ], $this->get_fieldset_from_result('display_name', $result));

        // Has system, position and organisation assignment
        $filters = [
            'assignment_type' => [
                user_groups::ORGANISATION,
                user_groups::POSITION,
                assignment::TYPE_SYSTEM
            ]
        ];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Coding',
            'Cooking',
            'Talking',
        ], $this->get_fieldset_from_result('display_name', $result));

        // Has admin, system and position assignment
        $filters = [
            'assignment_type' => [
                user_groups::POSITION,
                assignment::TYPE_SYSTEM,
                assignment::TYPE_ADMIN
            ]
        ];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Accounting',
            'Cooking',
            'Designing interiors',
            'Talking',
        ], $this->get_fieldset_from_result('display_name', $result));

        // Has admin, system, position and organisation assignment
        $filters = [
            'assignment_type' => [
                user_groups::ORGANISATION,
                user_groups::POSITION,
                assignment::TYPE_SYSTEM,
                assignment::TYPE_ADMIN
            ]
        ];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Accounting',
            'Coding',
            'Cooking',
            'Designing interiors',
            'Talking',
        ], $this->get_fieldset_from_result('display_name', $result));
    }

    public function test_can_be_filtered_by_competency_type() {
        $data = $this->generate_competencies();

        $user1 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);

        // has type 1
        $filters = ['type' => [ $data['types'][0] ]];
        $order_by = null;
        $order_dir = null;

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Accounting',
            'Chef proficiency',
            'Typing'
        ], $this->get_fieldset_from_result('display_name', $result));

        // has type 2
        $filters = ['type' => [ $data['types'][1] ]];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Baking skill-set',
            'Coding',
            'Cooking',
            'Designing interiors',
            'Hacking',
            'Leading',
            'Planning',
            'Talking',
        ], $this->get_fieldset_from_result('display_name', $result));

        // has type 1 and 2
        $filters = ['type' => $data['types']];

        $result = data_provider::for($user1->id)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated(null);
        $this->assertIsArray($result);
        $this->assertEqualsCanonicalizing([
            'Accounting',
            'Baking skill-set',
            'Chef proficiency',
            'Coding',
            'Cooking',
            'Designing interiors',
            'Hacking',
            'Leading',
            'Planning',
            'Talking',
            'Typing',
        ], $this->get_fieldset_from_result('display_name', $result));
    }

    /**
     * Create a few competencies with knows names to test search
     *
     * @param int|null $user_id
     * @return array
     */
    protected function generate_competencies(int $user_id = null) {
        $data = [
            'comps' => [],
            'fws' => [],
            'ass' => [],
            'types' => [],
        ];

        $data['fws'][] = $fw = $this->generator()->hierarchy_generator()->create_comp_frame([]);
        $data['fws'][] = $fw2 = $this->generator()->hierarchy_generator()->create_comp_frame([]);

        $data['types'][] = $type1 = $this->generator()->hierarchy_generator()->create_comp_type(['idnumber' => 'type1']);
        $data['types'][] = $type2 = $this->generator()->hierarchy_generator()->create_comp_type(['idnumber' => 'type2']);

        $data['comps'][] = $comp_one = $this->create_self_assignable_competency([
            'shortname' => 'acc',
            'fullname' => 'Accounting',
            'description' => 'Counting profits',
            'idnumber' => 'accc',
            'typeid' => $type1,
        ], $fw->id);

        $data['comps'][] = $comp_two = $this->create_self_assignable_competency([
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
            'typeid' => $type1,
        ], $fw2->id);

        $data['comps'][] = $comp_three = $this->create_self_assignable_competency([
            'shortname' => 'des',
            'fullname' => 'Designing interiors',
            'description' => 'Decorating things',
            'idnumber' => 'des',
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ], $fw->id);

        $data['comps'][] = $comp_four =  $this->create_self_assignable_competency([
            'shortname' => 'c-baker',
            'fullname' => 'Baking skill-set',
            'description' => 'Baking amazing things',
            'idnumber' => 'cook-baker',
            'typeid' => $type2,
        ], $fw2->id);

        $data['comps'][] = $comp_five = $this->create_self_assignable_competency([
            'shortname' => 'c-cook',
            'fullname' => 'Cooking',
            'description' => 'More cooking',
            'idnumber' => 'cook',
            'parentid' => $comp_three->id,
            'typeid' => $type2,
        ], $fw->id);

        $data['comps'][] = $comp_six = $this->create_self_assignable_competency([
            'shortname' => 'c-inv',
            'fullname' => 'Invisible',
            'description' => 'More hidden cooking',
            'idnumber' => 'cook-hidden',
            'visible' => false,
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ], $fw2->id);

        $data['comps'][] = $comp_seven = $this->create_self_assignable_competency([
            'shortname' => 'c-code',
            'fullname' => 'Coding',
            'description' => 'Coding skill',
            'idnumber' => 'coding',
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ], $fw2->id);

        $data['comps'][] = $comp_eight = $this->create_self_assignable_competency([
            'shortname' => 'c-hacking',
            'fullname' => 'Hacking',
            'description' => 'Hacking skills',
            'idnumber' => 'hacking',
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ], $fw2->id);

        $data['comps'][] = $comp_nine = $this->create_self_assignable_competency([
            'shortname' => 'c-talking',
            'fullname' => 'Talking',
            'description' => 'Talking skills',
            'idnumber' => 'talking',
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ], $fw2->id);

        // the following three competencies do not have assignments

        $data['comps'][] = $comp_ten = $this->create_self_assignable_competency([
            'shortname' => 'c-planning',
            'fullname' => 'Planning',
            'description' => 'Planning skills',
            'idnumber' => 'planning',
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ], $fw2->id);

        $data['comps'][] = $comp_eleven = $this->create_self_assignable_competency([
            'shortname' => 'c-leading',
            'fullname' => 'Leading',
            'description' => 'Leading skills',
            'idnumber' => 'leading',
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ], $fw2->id);

        $data['comps'][] = $comp_twelve = $this->create_self_assignable_competency([
            'shortname' => 'c-typing',
            'fullname' => 'Typing',
            'description' => 'Typing skills',
            'idnumber' => 'typing',
            'parentid' => $comp_one->id,
            'typeid' => $type1,
        ], $fw->id);

        $hierarchy_generator = $this->generator()->hierarchy_generator();
        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Framework 2']);
        $pos = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);

        $fw = $hierarchy_generator->create_org_frame(['fullname' => 'Framework 3']);
        $org = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 1']);


        $assignment_generator = $this->generator()->assignment_generator();

        $cohort = $assignment_generator->create_cohort();

        // Create an assignment for a competency
        $data['ass'][] = $assignment_generator->create_user_assignment($comp_one->id, $user_id, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_ADMIN]);
        $data['ass'][] = $assignment_generator->create_user_assignment($comp_three->id, $user_id, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_ADMIN]);
        $data['ass'][] = $assignment_generator->create_user_assignment($comp_two->id, $user_id, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_SELF]);
        $data['ass'][] = $assignment_generator->create_user_assignment($comp_four->id, $user_id, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_OTHER]);
        $data['ass'][] = $assignment_generator->create_user_assignment($comp_five->id, $user_id, ['status' => assignment::STATUS_ACTIVE, 'type' => assignment::TYPE_SYSTEM]);
        $data['ass'][] = $assignment_generator->create_position_assignment($comp_nine->id, $pos->id, ['status' => assignment::STATUS_ACTIVE]);
        $data['ass'][] = $assignment_generator->create_organisation_assignment($comp_seven->id, $org->id, ['status' => assignment::STATUS_ACTIVE]);
        $data['ass'][] = $assignment_generator->create_cohort_assignment($comp_eight->id, $cohort->id, ['status' => assignment::STATUS_ACTIVE]);

        if ($user_id) {
            job_assignment::create([
                'userid' => $user_id,
                'idnumber' => 'org1',
                'organisationid' => $org->id
            ]);
            job_assignment::create([
                'userid' => $user_id,
                'idnumber' => 'pos1',
                'positionid' => $pos->id
            ]);
            cohort_add_member($cohort->id, $user_id);

            $this->expand();
        }

        return $data;
    }

    private function create_self_assignable_competency(array $data, int $framework_id) {
        global $DB;

        $comp = $this->generator()->create_competency(null, $framework_id, $data);

        $DB->insert_record(
            'comp_assign_availability',
            ['comp_id' => $comp->id, 'availability' => competency::ASSIGNMENT_CREATE_SELF]
        );

        return $comp;
    }

    private function activate_self_assignable(int $competency_id) {
        global $DB;
        $DB->insert_record(
            'comp_assign_availability',
            ['comp_id' => $competency_id, 'availability' => competency::ASSIGNMENT_CREATE_SELF]
        );
    }

    private function activate_other_assignable(int $competency_id) {
        global $DB;
        $DB->insert_record(
            'comp_assign_availability',
            ['comp_id' => $competency_id, 'availability' => competency::ASSIGNMENT_CREATE_OTHER]
        );
    }

    private function get_fieldset_from_result(string $field, array $result): array {
        return array_map(function (self_assignable_competency $item) use ($field) {
            return $item->get_field($field);
        }, $result['items']);
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_competency');
    }

    private function expand() {
        // We need the expanded users for the logging to work
        $expand_task = new expand_task($GLOBALS['DB']);
        $expand_task->expand_all();
    }

}
