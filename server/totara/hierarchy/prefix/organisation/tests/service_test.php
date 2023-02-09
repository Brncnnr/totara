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

class hierarchy_organisation_service_testcase extends advanced_testcase {

    use \totara_core\phpunit\webservice_utils;

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_it_lists_organisations() {
        $this->generate_organisations();

        $res = $this->call_webservice_api('hierarchy_organisation_index', [
            'filters' => [],
            'page' => 1,
            'order' => 'shortname',
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
                'Sony Inc',
                'Samsung Inc',
                'Procter &#38; Gamble',
                'Good Will',
                'Apple',
            ],
            array_column($data['items'], 'display_name')
        );

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

    public function test_it_has_search_filter() {
        $this->generate_organisations();

        $res = $this->call_webservice_api('hierarchy_organisation_index', [
            'filters' => ['text' => 'inc'],
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
                'Samsung Inc',
                'Sony Inc',
            ],
            array_column($data['items'], 'display_name')
        );

        // Searching by description
        $res = $this->call_webservice_api('hierarchy_organisation_index', [
            'filters' => ['text' => 'good'],
            'page' => 1,
            'order' => 'idnumber',
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
                'Good Will',
                'Procter &#38; Gamble',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_has_framework_filter() {
        [, $fws] = array_values($this->generate_organisations());

        $res = $this->call_webservice_api('hierarchy_organisation_index', [
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
                'Good Will',
                'Samsung Inc',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_has_path_filter() {
        ['orgs' => $orgs] = $this->generate_organisations();

        $res = $this->call_webservice_api('hierarchy_organisation_index', [
            'filters' => ['path' => $orgs[0]->id],
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
                'Apple',
                'Good Will',
                'Procter &#38; Gamble',
                'Samsung Inc',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_has_basket_filter() {
        ['orgs' => $orgs] = $this->generate_organisations();

        $basket = new \totara_core\basket\session_basket('orgs');

        $basket->add([$orgs[2]->id, $orgs[4]->id]);

        $res = $this->call_webservice_api('hierarchy_organisation_index', [
            'filters' => ['basket' => 'orgs'],
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
                'Apple',
                'Good Will',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_has_parent_filter() {
        [$orgs] = array_values($this->generate_organisations());

        $res = $this->call_webservice_api('hierarchy_organisation_index', [
            'filters' => ['parent' => $orgs[0]->id],
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
                'Apple',
                'Procter &#38; Gamble',
                'Samsung Inc',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_has_visible_filter() {
        $this->generate_organisations();

        $res = $this->call_webservice_api('hierarchy_organisation_index', [
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

        $res = $this->call_webservice_api('hierarchy_organisation_index', [
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
        ['types' => $types] = $this->generate_organisations();

        // has type 1
        $res = $this->call_webservice_api('hierarchy_organisation_index', [
            'filters' => ['type' => [$types[0]]],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(
            [
                'Procter &#38; Gamble',
                'Sony Inc',
            ],
            array_column($data['items'], 'display_name')
        );

        // has type 2
        $res = $this->call_webservice_api('hierarchy_organisation_index', [
            'filters' => ['type' => [$types[1]]],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(
            [
                'Apple',
                'Good Will',
                'Samsung Inc',
            ],
            array_column($data['items'], 'display_name')
        );

        // has type 1 and 2
        $res = $this->call_webservice_api('hierarchy_organisation_index', [
            'filters' => ['type' => $types],
            'page' => 1,
            'order' => 'fullname',
            'direction' => 'asc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(
            [
                'Apple',
                'Good Will',
                'Procter &#38; Gamble',
                'Samsung Inc',
                'Sony Inc',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_paginates_organisations() {
        $this->generate_n_organisations(65);

        $res = $this->call_webservice_api('hierarchy_organisation_index', [
            'filters' => [],
            'page' => 1,
            'order' => 'description',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(20, $first = $data['items']);
        $this->assertEquals(65, $data['total']);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(4, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertEquals(2, $data['next']);

        $res = $this->call_webservice_api('hierarchy_organisation_index', [
            'filters' => [],
            'page' => 2,
            'order' => 'description',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(20, $second = $data['items']);
        $this->assertEquals(65, $data['total']);
        $this->assertEquals(2, $data['page']);
        $this->assertEquals(4, $data['pages']);
        $this->assertEquals(1, $data['prev']);
        $this->assertEquals(3, $data['next']);

        $res = $this->call_webservice_api('hierarchy_organisation_index', [
            'filters' => [],
            'page' => 4,
            'order' => 'description',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(5, $fourth = $data['items']);
        $this->assertEquals(65, $data['total']);
        $this->assertEquals(4, $data['page']);
        $this->assertEquals(4, $data['pages']);
        $this->assertEquals(3, $data['prev']);
        $this->assertNull($data['next']);

        $res = $this->call_webservice_api('hierarchy_organisation_index', [
            'filters' => [],
            'page' => 5,
            'order' => 'description',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(0, $fifth = $data['items']);
        $this->assertEquals(65, $data['total']);
        $this->assertEquals(5, $data['page']);
        $this->assertEquals(4, $data['pages']);
        $this->assertEquals(4, $data['prev']);
        $this->assertNull($data['next']);

        // Check that results are valid and pages actually return different items
        $this->assertEmpty(array_intersect(array_column($first, 'id'), array_column($second, 'id')));
        $this->assertEmpty(array_intersect(array_column($first, 'id'), array_column($fourth, 'id')));
        $this->assertEmpty(array_intersect(array_column($second, 'id'), array_column($fourth, 'id')));
    }

    public function test_it_loads_individual_organisation() {
        [
            'orgs' => $org,
            'fws' => $fws,
        ] = $this->generate_organisations();

        $res = $this->call_webservice_api('hierarchy_organisation_show', [
            'id' => $org[2]->id,
            'include' => [],
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals($org[2]->id, $data['id']);
        $this->assertEquals($org[2]->shortname, $data['shortname']);
        $this->assertEquals($org[2]->idnumber, $data['idnumber']);
        $this->assertEquals($org[2]->description, $data['description']);
        $this->assertEquals($org[2]->frameworkid, $data['frameworkid']);
        $this->assertEquals($org[2]->visible, $data['visible']);

        // Test it can apply crumbs as well
        $res = $this->call_webservice_api('hierarchy_organisation_show', [
            'id' => $org[4]->id,
            'include' => ['crumbs' => true],
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals($org[4]->id, $data['id']);
        $this->assertEquals($org[4]->shortname, $data['shortname']);
        $this->assertEquals($org[4]->idnumber, $data['idnumber']);
        $this->assertEquals($org[4]->description, $data['description']);
        $this->assertEquals($org[4]->frameworkid, $data['frameworkid']);
        $this->assertEquals($org[4]->visible, $data['visible']);

        $this->assertCount(4, $data['crumbtrail']);
        $this->assertEquals($org[4]->frameworkid, $data['crumbtrail'][0]['id']);
        $this->assertEquals($fws[1]->fullname, $data['crumbtrail'][0]['name']);
        $this->assertEquals('framework', $data['crumbtrail'][0]['type']);

        $this->assertEquals($org[0]->fullname, $data['crumbtrail'][1]['name']);
        $this->assertEquals($org[0]->id, $data['crumbtrail'][1]['id']);
        $this->assertEquals('organisation', $data['crumbtrail'][1]['type']);

        $this->assertEquals($org[3]->id, $data['crumbtrail'][2]['id']);
        $this->assertEquals($org[3]->fullname, $data['crumbtrail'][2]['name']);
        $this->assertEquals('organisation', $data['crumbtrail'][2]['type']);

        $this->assertEquals($org[4]->id, $data['crumbtrail'][3]['id']);
        $this->assertEquals($org[4]->fullname, $data['crumbtrail'][3]['name']);
        $this->assertEquals('organisation', $data['crumbtrail'][3]['type']);
    }

    public function test_non_existing_organisation() {
        $res = $this->call_webservice_api('hierarchy_organisation_show', [
            'id' => 999,
            'include' => [],
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEmpty($data);
    }

    /**
     * Create a few organisations with knows names to test search
     *
     * @return \stdClass[]
     */
    protected function generate_organisations() {
        $data = [
            'orgs' => [],
            'fws' => [],
            'types' => []
        ];

        $data['fws'][] = $fw = $this->create_organisation_framework();
        $data['fws'][] = $fw2 = $this->create_organisation_framework();

        $data['types'][] = $type1 = $this->org_generator()->create_org_type(['idnumber' => 'type1']);
        $data['types'][] = $type2 = $this->org_generator()->create_org_type(['idnumber' => 'type2']);

        $data['orgs'][] = $one = $this->create_organisation([
            'shortname' => 'Sony',
            'fullname' => 'Sony Inc',
            'description' => 'Digital discovery',
            'idnumber' => 'sinc',
            'typeid' => $type1,
        ], $fw->id);

        $data['orgs'][] = $this->create_organisation([
            'shortname' => 'P&G',
            'fullname' => 'Procter & Gamble',
            'description' => 'Consumer Goods corp',
            'idnumber' => 'cgc',
            'parentid' => $one->id,
            'typeid' => $type1,
        ], $fw->id);

        $data['orgs'][] = $this->create_organisation([
            'shortname' => 'Apple',
            'fullname' => 'Apple',
            'description' => 'Stay connected',
            'idnumber' => 'apple',
            'parentid' => $one->id,
            'typeid' => $type2,
        ], $fw->id);

        $data['orgs'][] = $four = $this->create_organisation([
            'shortname' => 'Samsung',
            'fullname' => 'Samsung Inc',
            'description' => 'Always connected',
            'idnumber' => 'sams',
            'parentid' => $one->id,
            'typeid' => $type2,
        ], $fw2->id);

        $data['orgs'][] = $this->create_organisation([
            'shortname' => 'GW',
            'fullname' => 'Good Will',
            'description' => 'Your family store',
            'idnumber' => 'gws',
            'parentid' => $four->id,
            'typeid' => $type2,
        ], $fw2->id);

        $data['orgs'][] = $this->create_organisation([
            'shortname' => 'Inv',
            'fullname' => 'Invisible',
            'description' => 'Always connected',
            'idnumber' => 'oinv',
            'visible' => false,
            'typeid' => $type2,
        ], $fw2->id);

        return $data;
    }

    /**
     * Create n organisations
     *
     * @param int $n Number of organisations
     * @return \stdClass[]
     */
    protected function generate_n_organisations(int $n = 50) {
        $fw = $this->create_organisation_framework();

        $i = 1;

        $items = [];

        do {
            $items[] = $this->create_organisation([], $fw->id);

            $i++;
        } while ($i <= $n);

        return $items;
    }

    /**
     * Create organisation
     *
     * @param array $attributes
     * @param int|null $framework_id
     * @return stdClass
     */
    protected function create_organisation(array $attributes = [], ?int $framework_id = null) {

        if (is_null($framework_id)) {
            $framework_id = $this->create_organisation_framework();
        }

        $attributes = array_merge($attributes, ['frameworkid' => $framework_id]);

        return $this->org_generator()->create_org($attributes);
    }

    /**
     * Create organisation framework
     *
     * @param array $attributes
     * @return stdClass
     */
    protected function create_organisation_framework(array $attributes = []) {
        return $this->org_generator()->create_org_frame($attributes);
    }

    /**
     * Get hierarchy specific generator
     *
     * @return \totara_hierarchy\testing\generator
     */
    protected function org_generator() {
        return $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
    }
}
