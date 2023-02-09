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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 * @category test
 */

use core\entity\user;
use core_phpunit\testcase;
use totara_competency\entity\assignment;
use totara_competency\user_groups;
use totara_core\basket\session_basket;
use totara_hierarchy\entity\competency;

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_competency
 */
class totara_competency_competency_service_testcase extends testcase {

    use \totara_core\phpunit\webservice_utils;

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_it_lists_competencies() {
        $this->generate_competencies();

        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['pages']);
        $this->assertNull($result['prev']);
        $this->assertNull($result['next']);

        $this->assertEquals([
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
        ], array_column($result['items'], 'display_name'));
    }

    public function test_it_lists_competencies_ordered_by_hierarchy() {
        $this->generate_competencies();

        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [],
            'page' => 0,
            'order' => 'framework_hierarchy',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);

        $expected_ids = competency::repository()
            ->order_by_raw('frameworkid ASC, sortthread ASC, id ASC')
            ->where('visible', true)
            ->get()
            ->pluck('id');

        $actual_ids = array_column($result['items'], 'id');

        $this->assertEquals($expected_ids, $actual_ids);
    }

    public function test_it_has_text_filter() {
        $this->generate_competencies();

        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'text' => 'des' ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['pages']);
        $this->assertNull($result['prev']);
        $this->assertNull($result['next']);

        $this->assertEquals([
            'Designing interiors',
        ], array_column($result['items'], 'display_name'));

        // Searching by description
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'text' => 'cook' ],
            'page' => 1,
            'order' => 'shortname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['pages']);
        $this->assertNull($result['prev']);
        $this->assertNull($result['next']);

        $this->assertEquals([
            'Baking skill-set',
            'Chef proficiency',
            'Cooking',
        ], array_column($result['items'], 'display_name'));
    }

    public function test_it_has_framework_filter() {
        [, $fws] = array_values($this->generate_competencies());

        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'framework' => $fws[1]->id ],
            'page' => 1,
            'order' => 'shortname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['pages']);
        $this->assertNull($result['prev']);
        $this->assertNull($result['next']);
        $this->assertEquals([
            'Baking skill-set',
            'Chef proficiency',
            'Coding',
            'Hacking',
            'Leading',
            'Planning',
            'Talking',
        ], array_column($result['items'], 'display_name'));
    }

    public function test_it_has_path_filter() {
        ['comps' => $comp] = $this->generate_competencies();

        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'path' => $comp[0]->id ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['pages']);
        $this->assertNull($result['prev']);
        $this->assertNull($result['next']);
        $this->assertEquals([
            'Coding',
            'Cooking',
            'Designing interiors',
            'Hacking',
            'Leading',
            'Planning',
            'Talking',
            'Typing',
        ], array_column($result['items'], 'display_name'));
    }

    public function test_it_has_basket_filter() {
        ['comps' => $comp] = $this->generate_competencies();

        $basket = new session_basket('comps');

        $basket->add([$comp[1]->id, $comp[3]->id, $comp[4]->id]);

        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'basket' => 'comps' ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['pages']);
        $this->assertNull($result['prev']);
        $this->assertNull($result['next']);
        $this->assertEquals([
            'Baking skill-set',
            'Chef proficiency',
            'Cooking',
        ], array_column($result['items'], 'display_name'));
    }

    public function test_it_has_parent_filter() {
        [$comp] = array_values($this->generate_competencies());

        $filters = [
            'parent' => $comp[0]->id,
            'visible' => null,
        ];
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => $filters,
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['pages']);
        $this->assertNull($result['prev']);
        $this->assertNull($result['next']);
        $this->assertEquals([
            'Coding',
            'Designing interiors',
            'Hacking',
            'Invisible',
            'Leading',
            'Planning',
            'Talking',
            'Typing',
        ], array_column($result['items'], 'display_name'));
    }

    public function test_it_has_visible_filter() {
        $this->generate_competencies();

        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'visible' => false ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'desc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['pages']);
        $this->assertNull($result['prev']);
        $this->assertNull($result['next']);
        $this->assertEquals([
            'Invisible'
        ], array_column($result['items'], 'display_name'));

        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'visible' => null ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'desc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(12, $result['items']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['pages']);
        $this->assertNull($result['prev']);
        $this->assertNull($result['next']);
    }

    public function test_it_has_status_filter() {

        ['fws' => $fws] = $this->generate_competencies();

        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'assignment_status' => [ 1 ] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['pages']);
        $this->assertNull($result['prev']);
        $this->assertNull($result['next']);
        $this->assertEquals([
            'Accounting',
            'Baking skill-set',
            'Chef proficiency',
            'Coding',
            'Cooking',
            'Designing interiors',
            'Hacking',
            'Talking',
        ], array_column($result['items'], 'display_name'));

        $filters = [
            'assignment_status' => [ 0 ],
            'framework' => $fws[1]->id,
        ];
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => $filters,
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['pages']);
        $this->assertNull($result['prev']);
        $this->assertNull($result['next']);
        $this->assertEquals([
            'Leading',
            'Planning'
        ], array_column($result['items'], 'display_name'));
    }

    public function test_it_has_assignment_type_filter() {
        $data = $this->generate_competencies();

        // Has position assignment
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'assignment_type' => [ user_groups::POSITION ] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
            'Talking',
        ], array_column($result['items'], 'display_name'));

        // Has organisation assignment
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'assignment_type' => [ user_groups::ORGANISATION ] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
            'Coding',
        ], array_column($result['items'], 'display_name'));

        // Has cohort assignment
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'assignment_type' => [ user_groups::COHORT ] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
            'Hacking',
        ], array_column($result['items'], 'display_name'));

        // Has position and organisation assignment
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'assignment_type' => [ user_groups::POSITION, user_groups::ORGANISATION ] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
            'Coding',
            'Talking'
        ], array_column($result['items'], 'display_name'));

        // Has self assignment
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'assignment_type' => [ assignment::TYPE_SELF ] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
            'Chef proficiency',
        ], array_column($result['items'], 'display_name'));

        // Has other assignment
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'assignment_type' => [ assignment::TYPE_OTHER ] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
            'Baking skill-set',
        ], array_column($result['items'], 'display_name'));

        // Has system assignment
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'assignment_type' => [ assignment::TYPE_SYSTEM ] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
            'Cooking',
        ], array_column($result['items'], 'display_name'));

        // Has admin assignment
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'assignment_type' => [ assignment::TYPE_ADMIN ] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
            'Accounting',
            'Designing interiors',
        ], array_column($result['items'], 'display_name'));

        // Has system, position and organisation assignment
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'assignment_type' => [ user_groups::ORGANISATION, user_groups::POSITION, assignment::TYPE_SYSTEM ] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
            'Coding',
            'Cooking',
            'Talking',
        ], array_column($result['items'], 'display_name'));


        // Has admin, system and position assignment
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'assignment_type' => [ user_groups::POSITION, assignment::TYPE_SYSTEM, assignment::TYPE_ADMIN ] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
            'Accounting',
            'Cooking',
            'Designing interiors',
            'Talking',
        ], array_column($result['items'], 'display_name'));

        // Has admin, system, position and organisation assignment
        $filters = [
            'assignment_type' => [
                user_groups::ORGANISATION,
                user_groups::POSITION,
                assignment::TYPE_SYSTEM,
                assignment::TYPE_ADMIN
            ]
        ];
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => $filters,
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
            'Accounting',
            'Coding',
            'Cooking',
            'Designing interiors',
            'Talking',
        ], array_column($result['items'], 'display_name'));
    }

    public function test_it_has_competency_type_filter() {
        $data = $this->generate_competencies();

        // has type 1
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'type' => [ $data['types'][0] ] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
            'Accounting',
            'Chef proficiency',
            'Typing'
        ], array_column($result['items'], 'display_name'));

        // has type 2
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'type' => [ $data['types'][1] ] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
            'Baking skill-set',
            'Coding',
            'Cooking',
            'Designing interiors',
            'Hacking',
            'Leading',
            'Planning',
            'Talking',
        ], array_column($result['items'], 'display_name'));

        // has type 1 and 2
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'type' => $data['types'] ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals([
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
        ], array_column($result['items'], 'display_name'));
    }

    public function test_it_paginates_competencies() {
        $this->generate_n_competencies(80);

        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(20, $first = $result['items']);
        $this->assertEquals(80, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(4, $result['pages']);
        $this->assertNull($result['prev']);
        $this->assertEquals(2, $result['next']);

        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [],
            'page' => 2,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(20, $second = $result['items']);
        $this->assertEquals(80, $result['total']);
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(4, $result['pages']);
        $this->assertEquals(1, $result['prev']);
        $this->assertEquals(3, $result['next']);

        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [],
            'page' => 4,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(20, $fourth = $result['items']);
        $this->assertEquals(80, $result['total']);
        $this->assertEquals(4, $result['page']);
        $this->assertEquals(4, $result['pages']);
        $this->assertEquals(3, $result['prev']);
        $this->assertNull($result['next']);

        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [],
            'page' => 5,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(0, $fifth = $result['items']);
        $this->assertEquals(80, $result['total']);
        $this->assertEquals(5, $result['page']);
        $this->assertEquals(4, $result['pages']);
        $this->assertEquals(4, $result['prev']);
        $this->assertNull($result['next']);

        // Check that results are valid and pages actually return different items
        $this->assertEmpty(array_intersect(array_column($first, 'id'), array_column($second, 'id')));
        $this->assertEmpty(array_intersect(array_column($first, 'id'), array_column($fourth, 'id')));
        $this->assertEmpty(array_intersect(array_column($second, 'id'), array_column($fourth, 'id')));
    }

    public function test_index_service_filters_out_competencies_by_id() {
        $data = $this->generate_competencies();
        //normal situation will return all competencies
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [],
            'page' => 0,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);
        $result = $res['data'] ?? null;
        $this->assert_webservice_success($res);
        $this->assertEquals(11, $result['total']);

        //index service will filter out a certain competency if provide a competency_id
        $first_comp_id = $data['comps'][0]->id;
        $res = $this->call_webservice_api('totara_competency_competency_index', [
            'filters' => [ 'excluded_competency_id' => $first_comp_id ],
            'page' => 0,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);
        $result = $res['data'] ?? null;
        $this->assert_webservice_success($res);
        $this->assertEquals(10, $result['total']);

        //assert first competency is not in list
        $comp_ids = array_column($result['items'],'id');
        $this->assertNotContainsEquals($first_comp_id,$comp_ids);
    }

    public function test_it_loads_individual_competency() {
        [ 'comps' => $comp ] = $this->generate_competencies();

        $res = $this->call_webservice_api('totara_competency_competency_show', [
            'id' => $comp[2]->id,
            'include' => [],
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals($comp[2]->id, $result['id']);
        $this->assertMatchesRegularExpression("/{$comp[2]->description}/", $result['description']);
        $this->assertEquals($comp[2]->frameworkid, $result['frameworkid']);
        $this->assertArrayNotHasKey('crumbtrail', $result);
        $this->assertArrayNotHasKey('assigned_user_groups', $result);
    }

    public function test_it_loads_individual_competency_with_crumbs() {
        [ 'comps' => $comp, 'fws' => $fws ] = $this->generate_competencies();

        $res = $this->call_webservice_api('totara_competency_competency_show', [
            'id' => $comp[2]->id,
            'include' => [ 'crumbs' => true ],
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals($comp[2]->id, $result['id']);
        $this->assertMatchesRegularExpression("/{$comp[2]->description}/", $result['description']);
        $this->assertEquals($comp[2]->frameworkid, $result['frameworkid']);

        $this->assertArrayHasKey('crumbtrail', $result);
        $this->assertArrayNotHasKey('assigned_user_groups', $result);

        $this->assertCount(3, $result['crumbtrail']);
        $this->assertEquals($comp[2]->frameworkid, $result['crumbtrail'][0]['id']);
        $this->assertEquals($fws[0]->fullname, $result['crumbtrail'][0]['name']);
        $this->assertEquals('framework', $result['crumbtrail'][0]['type']);

        $this->assertEquals($comp[0]->fullname, $result['crumbtrail'][1]['name']);
        $this->assertEquals($comp[0]->id, $result['crumbtrail'][1]['id']);
        $this->assertEquals('competency', $result['crumbtrail'][1]['type']);

        $this->assertEquals($comp[2]->id, $result['crumbtrail'][2]['id']);
        $this->assertEquals($comp[2]->fullname, $result['crumbtrail'][2]['name']);
        $this->assertEquals('competency', $result['crumbtrail'][2]['type']);
    }

    public function test_it_loads_individual_competency_with_user_group_names() {
        [ 'comps' => $comp, 'ass' => $ass ] = $this->generate_competencies();

        $assignment = new assignment($ass[1]);
        $user = new user($assignment->user_group_id);

        $res = $this->call_webservice_api('totara_competency_competency_show', [
            'id' => $comp[2]->id,
            'include' => [ 'usergroups' => true ],
        ]);

        $result = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals($comp[2]->id, $result['id']);
        $this->assertMatchesRegularExpression("/{$comp[2]->description}/", $result['description']);
        $this->assertEquals($comp[2]->frameworkid, $result['frameworkid']);

        $this->assertArrayNotHasKey('crumbtrail', $result);
        $this->assertArrayHasKey('assigned_user_groups', $result);

        $this->assertArrayHasKey('user_group_name', $result['assigned_user_groups'][0]);
        $this->assertEquals($user->firstname.' '.$user->lastname, $result['assigned_user_groups'][0]['user_group_name']);
    }

    public function test_non_existing_competency() {
        $this->generate_competencies();

        $res = $this->call_webservice_api('totara_competency_competency_show', [
            'id' => 666,
            'include' => [],
        ]);

        $result = $res['data'] ?? null;
        $this->assertEmpty($result);
    }

    /**
     * Create a few competencies with knows names to test search
     */
    protected function generate_competencies() {
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

        $data['comps'][] = $comp_one = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'acc',
            'fullname' => 'Accounting',
            'description' => 'Counting profits',
            'idnumber' => 'accc',
            'typeid' => $type1,
        ]);

        $data['comps'][] = $comp_two = $this->generator()->create_competency(null, $fw2->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
            'typeid' => $type1,
        ]);

        $data['comps'][] = $comp_three = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'des',
            'fullname' => 'Designing interiors',
            'description' => 'Decorating things',
            'idnumber' => 'des',
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ]);

        $data['comps'][] = $comp_four =  $this->generator()->create_competency(null, $fw2->id, [
            'shortname' => 'c-baker',
            'fullname' => 'Baking skill-set',
            'description' => 'Baking amazing things',
            'idnumber' => 'cook-baker',
            'typeid' => $type2,
        ]);

        $data['comps'][] = $comp_five = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'c-cook',
            'fullname' => 'Cooking',
            'description' => 'More cooking',
            'idnumber' => 'cook',
            'parentid' => $comp_three->id,
            'typeid' => $type2,
        ]);

        $data['comps'][] = $comp_six = $this->generator()->create_competency(null, $fw2->id, [
            'shortname' => 'c-inv',
            'fullname' => 'Invisible',
            'description' => 'More hidden cooking',
            'idnumber' => 'cook-hidden',
            'visible' => false,
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ]);

        $data['comps'][] = $comp_seven = $this->generator()->create_competency(null, $fw2->id, [
            'shortname' => 'c-code',
            'fullname' => 'Coding',
            'description' => 'Coding skill',
            'idnumber' => 'coding',
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ]);

        $data['comps'][] = $comp_eight = $this->generator()->create_competency(null, $fw2->id, [
            'shortname' => 'c-hacking',
            'fullname' => 'Hacking',
            'description' => 'Hacking skills',
            'idnumber' => 'hacking',
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ]);

        $data['comps'][] = $comp_nine = $this->generator()->create_competency(null, $fw2->id, [
            'shortname' => 'c-talking',
            'fullname' => 'Talking',
            'description' => 'Talking skills',
            'idnumber' => 'talking',
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ]);

        // the following three competencies do not have assignments

        $data['comps'][] = $comp_ten = $this->generator()->create_competency(null, $fw2->id, [
            'shortname' => 'c-planning',
            'fullname' => 'Planning',
            'description' => 'Planning skills',
            'idnumber' => 'planning',
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ]);

        $data['comps'][] = $comp_eleven = $this->generator()->create_competency(null, $fw2->id, [
            'shortname' => 'c-leading',
            'fullname' => 'Leading',
            'description' => 'Leading skills',
            'idnumber' => 'leading',
            'parentid' => $comp_one->id,
            'typeid' => $type2,
        ]);

        $data['comps'][] = $comp_twelve = $this->generator()->create_competency(null, $fw->id, [
            'shortname' => 'c-typing',
            'fullname' => 'Typing',
            'description' => 'Typing skills',
            'idnumber' => 'typing',
            'parentid' => $comp_one->id,
            'typeid' => $type1,
        ]);

        $hierarchy_generator = $this->generator()->hierarchy_generator();
        $fw = $hierarchy_generator->create_pos_frame(['fullname' => 'Framework 2']);
        $pos = $hierarchy_generator->create_pos(['frameworkid' => $fw->id, 'fullname' => 'Position 1']);

        $fw = $hierarchy_generator->create_org_frame(['fullname' => 'Framework 3']);
        $org = $hierarchy_generator->create_org(['frameworkid' => $fw->id, 'fullname' => 'Organisation 1']);

        $assignment_generator = $this->generator()->assignment_generator();

        $cohort = $assignment_generator->create_cohort();

        // Create an assignment for a competency
        $data['ass'][] = $assignment_generator->create_user_assignment($comp_one->id, null, ['type' => assignment::TYPE_ADMIN]);
        $data['ass'][] = $assignment_generator->create_user_assignment($comp_three->id, null, ['type' => assignment::TYPE_ADMIN]);
        $data['ass'][] = $assignment_generator->create_user_assignment($comp_two->id, null, ['type' => assignment::TYPE_SELF]);
        $data['ass'][] = $assignment_generator->create_user_assignment($comp_four->id, null, ['type' => assignment::TYPE_OTHER]);
        $data['ass'][] = $assignment_generator->create_user_assignment($comp_five->id, null, ['type' => assignment::TYPE_SYSTEM]);
        $data['ass'][] = $assignment_generator->create_position_assignment($comp_nine->id, $pos->id);
        $data['ass'][] = $assignment_generator->create_organisation_assignment($comp_seven->id, $org->id);
        $data['ass'][] = $assignment_generator->create_cohort_assignment($comp_eight->id, $cohort->id);

        return $data;
    }

    /**
     * Create n competencies
     *
     * @param int $n Number of competencies
     * @return \stdClass[]
     */
    protected function generate_n_competencies(int $n = 50) {
        $fw = $this->generator()->hierarchy_generator()->create_comp_frame([]);

        $i = 1;

        $items = [];

        do {
            $items[] = $this->generator()->create_competency(null, $fw->id);

            $i++;
        } while ($i <= $n);

        return $items;
    }
    /**
     * Get hierarchy specific generator
     *
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_competency');
    }
}