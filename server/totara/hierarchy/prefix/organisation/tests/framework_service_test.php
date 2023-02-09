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
 * @package hierarchy_organisation
 * @category test
 */

defined('MOODLE_INTERNAL') || die();

class hierarchy_organisation_framework_service_testcase extends advanced_testcase {

    use \totara_core\phpunit\webservice_utils;

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_it_lists_all_organisation_frameworks() {
        $fws = $this->generate_n_frameworks(150);

        $res = $this->call_webservice_api('hierarchy_organisation_framework_index', [
            'filters' => [],
            'page' => 0,
            'order' => 'id',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(150, $data['items']);
        $this->assertEquals(array_column($fws, 'fullname'), array_column($data['items'], 'fullname'));
    }

    public function test_it_lists_organisation_frameworks() {
        $this->generate_frameworks();

        $res = $this->call_webservice_api('hierarchy_organisation_framework_index', [
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

        $this->assertEquals(
            [
                'Cook',
                'Designer',
                'Accountant',
                'Chef',
                'Baker',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_has_text_filter() {
        $this->generate_frameworks();

        $res = $this->call_webservice_api('hierarchy_organisation_framework_index', [
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
        $res = $this->call_webservice_api('hierarchy_organisation_framework_index', [
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

    public function test_it_has_visible_filter() {
        $this->generate_frameworks();

        $res = $this->call_webservice_api('hierarchy_organisation_framework_index', [
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

        $res = $this->call_webservice_api('hierarchy_organisation_framework_index', [
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

    public function test_it_paginates_organisation_frameworks() {
        $this->generate_n_frameworks(80);

        $res = $this->call_webservice_api('hierarchy_organisation_framework_index', [
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

        $res = $this->call_webservice_api('hierarchy_organisation_framework_index', [
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

        $res = $this->call_webservice_api('hierarchy_organisation_framework_index', [
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

        $res = $this->call_webservice_api('hierarchy_organisation_framework_index', [
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

    /**
     * Create a few organisation frameworks with knows names to test search
     */
    protected function generate_frameworks() {
        $data = [
            'fws' => [],
        ];

        $data['fws'][] = $one = $this->create_organisation_framework([
            'shortname' => 'acc',
            'fullname' => 'Accountant',
            'description' => 'Counting profits',
            'idnumber' => 'acc',
        ]);

        $data['fws'][] = $this->create_organisation_framework([
            'shortname' => 'c-chef',
            'fullname' => 'Chef',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef',
        ]);

        $data['fws'][] = $three = $this->create_organisation_framework([
            'shortname' => 'des',
            'fullname' => 'Designer',
            'description' => 'Decorating things',
            'idnumber' => 'des',
            'parentid' => $one->id,
        ]);

        $data['fws'][] = $this->create_organisation_framework([
            'shortname' => 'c-baker',
            'fullname' => 'Baker',
            'description' => 'Baking amazing things',
            'idnumber' => 'cook-baker',
        ]);

        $data['fws'][] = $this->create_organisation_framework([
            'shortname' => 'c-cook',
            'fullname' => 'Cook',
            'description' => 'More cooking',
            'idnumber' => 'cook',
            'parentid' => $three->id,
        ]);

        $data['fws'][] = $this->create_organisation_framework([
            'shortname' => 'c-inv',
            'fullname' => 'Invisible',
            'description' => 'More hidden cooking',
            'idnumber' => 'cook-hidden',
            'visible' => false,
            'parentid' => $one->id,
        ]);

        return $data;
    }

    /**
     * Create n organisation frameworks
     *
     * @param int $n Number of organisation frameworks
     * @return \stdClass[]
     */
    protected function generate_n_frameworks(int $n = 50) {
        $i = 1;

        $items = [];

        do {
            $items[] = $this->create_organisation_framework([]);

            $i++;
        } while ($i <= $n);

        return $items;
    }

    /**
     * Create position framework
     *
     * @param array $attributes
     * @return stdClass
     */
    protected function create_organisation_framework(array $attributes = []) {
        return $this->pos_generator()->create_org_frame($attributes);
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
