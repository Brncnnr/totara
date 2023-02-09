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

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_competency
 */
class totara_competency_cohort_service_testcase extends advanced_testcase {

    use \totara_core\phpunit\webservice_utils;

    protected function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function test_it_lists_cohorts() {
        $this->generate_cohorts();

        $res = $this->call_webservice_api('totara_competency_cohort_index', [
            'filters' => [],
            'page' => 1,
            'order' => 'idnumber',
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
                'Students',
                'Cohort 2',
                'Moderators',
                'Tutors',
                'Cohort 1 staff',
            ],
            array_column($data['items'], 'display_name')
        );

        // Only certain fields have been returned
        $this->assertEqualsCanonicalizing(
            [
                'id',
                'display_name',
                'idnumber'
            ],
            array_keys($data['items'][0])
        );
    }

    public function test_it_searches_cohorts() {
        $this->generate_cohorts();

        $res = $this->call_webservice_api('totara_competency_cohort_index', [
            'filters' => ['text' => 'staff'],
            'page' => 1,
            'order' => 'name',
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
                'Tutors',
                'Moderators',
                'Cohort 1 staff',
            ],
            array_column($data['items'], 'display_name')
        );

        // Searching by description
        $res = $this->call_webservice_api('totara_competency_cohort_index', [
            'filters' => ['text' => 'here'],
            'page' => 1,
            'order' => 'name',
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
                'Cohort 1 staff',
                'Cohort 2',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_searches_cohorts_by_basket() {
        $cohorts = $this->generate_cohorts();

        $basket = new \totara_core\basket\session_basket('cohorts');
        $basket->add([$cohorts[1]->id, $cohorts[4]->id]);

        $res = $this->call_webservice_api('totara_competency_cohort_index', [
            'filters' => ['basket' => 'cohorts'],
            'page' => 1,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEqualsCanonicalizing(
            [
                'Cohort 2',
                'Moderators',
            ],
            array_column($data['items'], 'display_name')
        );
    }

    public function test_it_searches_cohorts_by_nonexistent_basket() {
        $this->generate_cohorts();

        $res = $this->call_webservice_api('totara_competency_cohort_index', [
            'filters' => ['basket' => 'idonotexist'],
            'page' => 1,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEmpty($data['items']);
    }

    public function test_it_has_visibility_filter() {
        $this->generate_cohorts();

        $res = $this->call_webservice_api('totara_competency_cohort_index', [
            'filters' => ['visible' => false],
            'page' => 1,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(1, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertNull($data['next']);
        $this->assertEquals(['Invisible'], array_column($data['items'], 'display_name'));

        $res = $this->call_webservice_api('totara_competency_cohort_index', [
            'filters' => ['visible' => null],
            'page' => 1,
            'order' => 'name',
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

    public function test_it_paginates_cohorts() {
        $this->generate_n_cohorts(70);

        $res = $this->call_webservice_api('totara_competency_cohort_index', [
            'filters' => [],
            'page' => 1,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(20, $first = $data['items']);
        $this->assertEquals(70, $data['total']);
        $this->assertEquals(1, $data['page']);
        $this->assertEquals(4, $data['pages']);
        $this->assertNull($data['prev']);
        $this->assertEquals(2, $data['next']);

        $res = $this->call_webservice_api('totara_competency_cohort_index', [
            'filters' => [],
            'page' => 2,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(20, $second = $data['items']);
        $this->assertEquals(70, $data['total']);
        $this->assertEquals(2, $data['page']);
        $this->assertEquals(4, $data['pages']);
        $this->assertEquals(1, $data['prev']);
        $this->assertEquals(3, $data['next']);

        $res = $this->call_webservice_api('totara_competency_cohort_index', [
            'filters' => [],
            'page' => 4,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(10, $fourth = $data['items']);
        $this->assertEquals(70, $data['total']);
        $this->assertEquals(4, $data['page']);
        $this->assertEquals(4, $data['pages']);
        $this->assertEquals(3, $data['prev']);
        $this->assertNull($data['next']);

        $res = $this->call_webservice_api('totara_competency_cohort_index', [
            'filters' => [],
            'page' => 5,
            'order' => 'name',
            'direction' => 'desc'
        ]);

        $data = $res['data'] ?? null;

        $this->assert_webservice_success($res);
        $this->assertCount(0, $fifth = $data['items']);
        $this->assertEquals(70, $data['total']);
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
     * Create a few audiences with knows names to test search
     */
    protected function generate_cohorts() {
        $gen = $this->getDataGenerator();

        $cohorts = [];

        // Create Cohort 1
        $cohorts[] = $gen->create_cohort([
            'name' => 'Cohort 1 staff',
            'description' => 'We add here important people',
            'idnumber' => 'the_first',
        ]);

        // Create Cohort 2
        $cohorts[] = $gen->create_cohort([
            'name' => 'Cohort 2',
            'description' => 'The rest is here',
            'idnumber' => 'misc',
        ]);

        // Create Cohort Students
        $cohorts[] = $gen->create_cohort([
            'name' => 'Students',
            'description' => 'Learners',
            'idnumber' => 'learners',
        ]);

        // Create Cohort Tutors
        $cohorts[] = $gen->create_cohort([
            'name' => 'Tutors',
            'description' => 'Teaching staff',
            'idnumber' => 'teaching_staff',
        ]);

        // Create Cohort Moderators
        $cohorts[] = $gen->create_cohort([
            'name' => 'Moderators',
            'description' => 'This is audience to group moderators',
            'idnumber' => 'mod_staff',
        ]);

        // Create Invisible
        $cohorts[] = $gen->create_cohort([
            'name' => 'Invisible',
            'description' => 'This cohort should not be normally selected',
            'idnumber' => 'inv',
            'visible' => false
        ]);

        return $cohorts;
    }

    /**
     * Create n audiences
     *
     * @param int $n Number of audiences
     * @return \stdClass[]
     */
    protected function generate_n_cohorts(int $n = 50) {
        $i = 1;

        $items = [];

        do {
            $items[] = $this->getDataGenerator()->create_cohort();

            $i++;
        } while ($i <= $n);

        return $items;
    }
}
