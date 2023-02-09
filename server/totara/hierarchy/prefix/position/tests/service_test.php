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
 * @package hierarchy_position
 * @category test
 */

use totara_core\basket\session_basket;

defined('MOODLE_INTERNAL') || die();

class hierarchy_position_service_testcase extends advanced_testcase {

    use \totara_core\phpunit\webservice_utils;

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_it_lists_positions() {
        $this->generate_positions();

        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => [],
            'page' => 1,
            'order' => 'description',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(1, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertNull($data['next']);

        $this->assertEquals([
            'Cook',
            'Designer',
            'Accountant',
            'Chef',
            'Baker',
        ], array_column($data['items'], 'display_name'));

        // Only certain fields have been returned
        $this->assertEqualsCanonicalizing(
            [
                'id',
                'fullname',
                'display_name',
                'idnumber',
                'crumbtrail'
            ],
            array_keys($data['items'][0])
        );
    }

    public function test_it_has_text_filter() {
        $this->generate_positions();

        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => ['text' => 'des'],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(1, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertNull($data['next']);

        $this->assertEquals(['Designer'], array_column($data['items'], 'display_name'));

        // Searching by description
        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => ['text' => 'cook'],
            'page' => 1,
            'order' => 'shortname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(1, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertNull($data['next']);

        $this->assertEquals(
            [
                'Baker',
                'Chef',
                'Cook',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_has_framework_filter() {
        [, $fws] = array_values($this->generate_positions());

        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => ['framework' => $fws[1]->id],
            'page' => 1,
            'order' => 'shortname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(1, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertNull($data['next']);
        $this->assertEquals(
            [
                'Baker',
                'Chef',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_has_path_filter() {
        ['pos' => $pos] = $this->generate_positions();

        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => ['path' => $pos[0]->id],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(1, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertNull($data['next']);
        $this->assertEquals(
            [
                'Cook',
                'Designer',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_has_basket_filter() {
        ['pos' => $pos] = $this->generate_positions();

        $basket = new session_basket('pos');

        $basket->add([$pos[1]->id, $pos[3]->id, $pos[4]->id]);

        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => ['basket' => 'pos'],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(1, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertNull($data['next']);
        $this->assertEquals(
            [
                'Baker',
                'Chef',
                'Cook',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_has_parent_filter() {
        [$pos] = array_values($this->generate_positions());

        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => ['parent' => $pos[0]->id, 'visible' => null, ],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(1, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertNull($data['next']);
        $this->assertEquals(
            [
                'Designer',
                'Invisible',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_has_visible_filter() {
        $this->generate_positions();

        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => ['visible' => false],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(1, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertNull($data['next']);
        $this->assertEquals(['Invisible'], array_column($data['items'], 'display_name'));

        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => ['visible' => null],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(6, $data['items']);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(1, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertNull($data['next']);
    }

    public function test_it_has_type_filter() {
        ['types' => $types] = $this->generate_positions();

        // has type 1
        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => ['type' => [$types[0]]],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(
            [
                'Accountant',
                'Chef',
            ],
            array_column($data['items'], 'display_name')
        );

        // has type 2
        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => ['type' => [$types[1]]],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(
            [
                'Baker',
                'Cook',
                'Designer',
            ],
            array_column($data['items'], 'display_name')
        );

        // has type 1 and 2
        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => ['type' => $types],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(
            [
                'Accountant',
                'Baker',
                'Chef',
                'Cook',
                'Designer',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_paginates_positions() {
        $this->generate_n_positions(80);

        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => [],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(20, $first = $data['items']);
        $this->assertEquals(80, $data['total']);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(4, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertEquals(2, $data['next']);

        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => [],
            'page' => 2,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(20, $second = $data['items']);
        $this->assertEquals(80, $data['total']);
        $this->assertEquals(2, $data['page']);
        $this->assertEquals(4, $data['pages']);
        $this->assertEquals(1, $data['prev']);
        $this->assertEquals(3, $data['next']);

        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => [],
            'page' => 4,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(20, $fourth = $data['items']);
        $this->assertEquals(80, $data['total']);
        $this->assertEquals(4, $data['page']);
        $this->assertEquals(4, $data['pages']);
        $this->assertEquals(3, $data['prev']);
        $this->assertNull($data['next']);

        $res = $this->call_webservice_api('hierarchy_position_index', [
            'filters' => [],
            'page' => 5,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(0, $fifth = $data['items']);
        $this->assertEquals(80, $data['total']);
        $this->assertEquals(5, $data['page']);
        $this->assertEquals(4, $data['pages']);
        $this->assertEquals(4, $data['prev']);
        $this->assertNull($data['next']);

        // Check that results are valid and pages actually return different items
        $this->assertEmpty(array_intersect(array_column($first, 'id'), array_column($second, 'id')));
        $this->assertEmpty(array_intersect(array_column($first, 'id'), array_column($fourth, 'id')));
        $this->assertEmpty(array_intersect(array_column($second, 'id'), array_column($fourth, 'id')));
    }

    public function test_it_loads_individual_position() {
        ['pos' => $pos, 'fws' => $fws] = $this->generate_positions();

        $res = $this->call_webservice_api('hierarchy_position_show', [
            'id' => $pos[2]->id,
            'include' => [],
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals($pos[2]->id, $data['id']);
        $this->assertEquals($pos[2]->shortname, $data['shortname']);
        $this->assertEquals($pos[2]->idnumber, $data['idnumber']);
        $this->assertEquals($pos[2]->description, $data['description']);
        $this->assertEquals($pos[2]->frameworkid, $data['frameworkid']);
        $this->assertEquals($pos[2]->visible, $data['visible']);

        // Test it can apply crumbs as well
        $res = $this->call_webservice_api('hierarchy_position_show', [
            'id' => $pos[4]->id,
            'include' => ['crumbs' => true],
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals($pos[4]->id, $data['id']);
        $this->assertEquals($pos[4]->shortname, $data['shortname']);
        $this->assertEquals($pos[4]->idnumber, $data['idnumber']);
        $this->assertEquals($pos[4]->description, $data['description']);
        $this->assertEquals($pos[4]->frameworkid, $data['frameworkid']);
        $this->assertEquals($pos[4]->visible, $data['visible']);

        $this->assertCount(4, $data['crumbtrail']);
        $this->assertEquals($pos[4]->frameworkid, $data['crumbtrail'][0]['id']);
        $this->assertEquals($fws[0]->fullname, $data['crumbtrail'][0]['name']);
        $this->assertEquals('framework', $data['crumbtrail'][0]['type']);

        $this->assertEquals($pos[0]->id, $data['crumbtrail'][1]['id']);
        $this->assertEquals($pos[0]->fullname, $data['crumbtrail'][1]['name']);
        $this->assertEquals('position', $data['crumbtrail'][1]['type']);

        $this->assertEquals($pos[2]->id, $data['crumbtrail'][2]['id']);
        $this->assertEquals($pos[2]->fullname, $data['crumbtrail'][2]['name']);
        $this->assertEquals('position', $data['crumbtrail'][2]['type']);

        $this->assertEquals($pos[4]->id, $data['crumbtrail'][3]['id']);
        $this->assertEquals($pos[4]->fullname, $data['crumbtrail'][3]['name']);
        $this->assertEquals('position', $data['crumbtrail'][3]['type']);
    }

    public function test_non_existing_position() {
        $res = $this->call_webservice_api('hierarchy_position_show', [
            'id' => 999,
            'include' => [],
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEmpty($data);
    }

    /**
     * Create a few positions with knows names to test search
     */
    protected function generate_positions() {
        $data = [
            'pos' => [],
            'fws' => [],
            'types' => [],
        ];

        $data['fws'][] = $fw = $this->create_position_framework();
        $data['fws'][] = $fw2 = $this->create_position_framework();

        $data['types'][] = $type1 = $this->pos_generator()->create_pos_type(['idnumber' => 'type1']);
        $data['types'][] = $type2 = $this->pos_generator()->create_pos_type(['idnumber' => 'type2']);

        $data['pos'][] = $one = $this->create_position([
            'shortname' => 'acc',
            'fullname' => 'Accountant',
            'description' => 'Counting profits',
            'idnumber' => 'acc',
            'typeid' => $type1,
        ], $fw->id);

        $data['pos'][] = $this->create_position([
            'shortname' => 'c-chef',
            'fullname' => 'Chef',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef',
            'typeid' => $type1,
        ], $fw2->id);

        $data['pos'][] = $three = $this->create_position([
            'shortname' => 'des',
            'fullname' => 'Designer',
            'description' => 'Decorating things',
            'idnumber' => 'des',
            'parentid' => $one->id,
            'typeid' => $type2,
        ], $fw->id);

        $data['pos'][] = $this->create_position([
            'shortname' => 'c-baker',
            'fullname' => 'Baker',
            'description' => 'Baking amazing things',
            'idnumber' => 'cook-baker',
            'typeid' => $type2,
        ], $fw2->id);

        $data['pos'][] = $this->create_position([
            'shortname' => 'c-cook',
            'fullname' => 'Cook',
            'description' => 'More cooking',
            'idnumber' => 'cook',
            'parentid' => $three->id,
            'typeid' => $type2,
        ], $fw->id);

        $data['pos'][] = $this->create_position([
            'shortname' => 'c-inv',
            'fullname' => 'Invisible',
            'description' => 'More hidden cooking',
            'idnumber' => 'cook-hidden',
            'visible' => false,
            'parentid' => $one->id,
            'typeid' => $type2,
        ], $fw2->id);

        return $data;
    }

    /**
     * Create n positions
     *
     * @param int $n Number of positions
     * @return \stdClass[]
     */
    protected function generate_n_positions(int $n = 50) {
        $fw = $this->create_position_framework();

        $i = 1;

        $items = [];

        do {
            $items[] = $this->create_position([], $fw->id);

            $i++;
        } while ($i <= $n);

        return $items;
    }

    /**
     * Create position
     *
     * @param array $attributes
     * @param int|null $framework_id
     * @return stdClass
     */
    protected function create_position(array $attributes = [], ?int $framework_id = null) {

        if (is_null($framework_id)) {
            $framework_id = $this->create_position_framework();
        }

        $attributes = array_merge($attributes, ['frameworkid' => $framework_id]);

        return $this->pos_generator()->create_pos($attributes);
    }

    /**
     * Create position framework
     *
     * @param array $attributes
     * @return stdClass
     */
    protected function create_position_framework(array $attributes = []) {
        return $this->pos_generator()->create_pos_frame($attributes);
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_hierarchy\testing\generator
     */
    protected function pos_generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
    }
}
