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

defined('MOODLE_INTERNAL') || die();

use core\collection;
use core\entity\cohort as cohort_entity;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \core\webapi\resolver\query\cohorts
 *
 * @group core_cohort
 */
class core_webapi_query_cohorts_testcase extends advanced_testcase {

    private const QUERY = 'core_cohorts';

    use webapi_phpunit_helper;

    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
    }

    /**
     * @covers ::resolve
     */
    public function test_find_default_params(): void {
        $no_of_cohorts = 2;
        $expected = $this->setup_env($no_of_cohorts)->pluck('name');

        $result = $this->resolve_graphql_query(self::QUERY, []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('next_cursor', $result);

        $items = $result['items'];
        $total = $result['total'];
        $enc_cursor = $result['next_cursor'];

        $this->assertEquals($no_of_cohorts, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $retrieved = [];
        foreach ($items as $item) {
            $retrieved[] = $item->name;
        }
        $this->assertCount($no_of_cohorts, $retrieved, 'wrong current page count');
        $this->assertEqualsCanonicalizing($expected, $retrieved, 'wrong retrievals');
    }

    /**
     * @covers ::resolve
     */
    public function test_sorted_pagination(): void {
        $no_of_cohorts = 10;
        $order_direction = 'DESC';
        $cohort_ids = $this->setup_env($no_of_cohorts)
            ->sort('id', $order_direction)
            ->pluck('id');

        $page_size = $no_of_cohorts - 1;

        $args = [
            'query' => [
                'filters' => [],
                'order_by' => 'id',
                'order_dir' => $order_direction,
                'result_size' => $page_size,
                'cursor' => null
            ]
        ];

        // 1st round.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals($no_of_cohorts, $total, 'wrong total count');
        $this->assertCount($page_size, $items, 'wrong current page count');
        $this->assertNotEmpty($enc_cursor, 'empty cursor');

        $retrieved = array_column($items, 'id');

        // 2nd round.
        $args = [
            'query' => [
                'filters' => [],
                'order_by' => 'id',
                'order_dir' => $order_direction,
                'result_size' => $page_size,
                'cursor' => $enc_cursor
            ]
        ];

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals($no_of_cohorts, $total, 'wrong total count');
        $this->assertCount(1, $items, 'wrong current page count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $retrieved = array_merge($retrieved, array_column($items, 'id'));

        // See if items were retrieved in the correct order.
        $this->assertEquals($cohort_ids, $retrieved, 'retrieved in wrong order');
    }

    /**
     * @covers ::resolve
     */
    public function test_filters(): void {
        $all_cohorts = $this->setup_env();

        // Filter by single value.
        $args = [
            'query' => [
                'filters' => [
                    'type' => 'DYNAMIC'
                ]
            ]
        ];

        $expected = $all_cohorts->filter('cohorttype', cohort::TYPE_DYNAMIC)
            ->pluck('id');

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals(count($expected), $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(count($expected), $items, 'wrong item count');

        $this->assertEqualsCanonicalizing($expected, array_column($items, 'id'));

        // Filter combination.
        $cohort = $all_cohorts->last();
        $args = [
            'query' => [
                'filters' => [
                    'name' => 'cohort',
                    'ids' => (int)$cohort->id
                ]
            ]
        ];

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');

        // Filter no result combination.
        $args = [
            'query' => [
                'filters' => [
                    'name' => '#00',
                    'ids' => (int)$cohort->id
                ]
            ]
        ];

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals(0, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(0, $items, 'wrong item count');
    }

    /**
     * @covers ::resolve
     */
    public function test_ajax_default_params(): void {
        $no_of_cohorts = 2;
        $cohorts_by_ids = $this->setup_env($no_of_cohorts)->key_by('id');

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);

        $items = $result['items'];
        $total = $result['total'];
        $enc_cursor = $result['next_cursor'];

        $this->assertCount($no_of_cohorts, $items, 'wrong item count');
        $this->assertEquals($no_of_cohorts, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $item_ids = array_column($items, 'id');
        $this->assertEqualsCanonicalizing($cohorts_by_ids->pluck('id'), $item_ids);
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_query(): void {
        // No input.
        $args = [
            'query' => ['type' => "UNKNOWN"]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Field "type" is not defined by type core_cohorts_query');
    }

    public function test_using_context_id(): void {
        $no_of_cohorts = 2;
        $cohorts_by_ids = $this->setup_env($no_of_cohorts)->key_by('id');

        $generator = $this->getDataGenerator();

        $cat1 = $generator->create_category([
            'name' => 'cat1'
        ]);

        $cat11 = $generator->create_category([
            'parent' => $cat1->id,
            'name' => 'cat11'
        ]);

        $cat2 = $generator->create_category([
            'name' => 'cat2'
        ]);

        $aud1 = $generator->create_cohort([
            'contextid' => $cat1->get_context()->id,
            'name' => 'aud1'
        ]);

        $aud11 = $generator->create_cohort([
            'contextid' => $cat11->get_context()->id,
            'name' => 'aud11'
        ]);

        $aud2 = $generator->create_cohort([
            'contextid' => $cat2->get_context()->id,
            'name' => 'aud2'
        ]);

        $args = [
            'query' => [
                'leaf_context_id' => $cat2->get_context()->id
            ]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);

        $items = $result['items'];
        $total = $result['total'];
        $enc_cursor = $result['next_cursor'];

        $this->assertCount($no_of_cohorts + 1, $items, 'wrong item count');
        $this->assertEquals($no_of_cohorts + 1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $item_ids = array_column($items, 'id');
        $this->assertEqualsCanonicalizing(array_merge($cohorts_by_ids->pluck('id'), [$aud2->id]), $item_ids);

        $args = [
            'query' => [
                'leaf_context_id' => $cat11->get_context()->id,
            ]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);

        $items = $result['items'];
        $total = $result['total'];
        $enc_cursor = $result['next_cursor'];

        $this->assertCount($no_of_cohorts + 2, $items, 'wrong item count');
        $this->assertEquals($no_of_cohorts + 2, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $item_ids = array_column($items, 'id');
        $this->assertEqualsCanonicalizing(
            array_merge(
                $cohorts_by_ids->pluck('id'),
                [
                    $aud1->id,
                    $aud11->id
                ]
            ),
            $item_ids
        );
    }

    /**
     * Generates test data.
     *
     * @param int $count no of cohorts to generate.
     *
     * @return collection a list of cohort_entity objects.
     */
    private function setup_env(int $count = 10): collection {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $cohorts = [];
        foreach (range(0, $count - 1) as $i) {
            $cohort_type = $i % 3 === 0 ? cohort::TYPE_DYNAMIC : cohort::TYPE_STATIC;
            $base = sprintf('Test cohort #%02d', $i);

            $cohort = $generator->create_cohort([
                'active' => true,
                'component' => '',
                'contextid' => context_system::instance()->id,
                'description' => "$base description",
                'descriptionformat' => FORMAT_MOODLE,
                'idnumber' => "$base idnumber",
                'name' => $base,
                'cohorttype' => $cohort_type,
                'visible' => true
            ]);

            $cohorts[] = new cohort_entity($cohort);
        }

        return collection::new($cohorts);
    }

}
