<?php
/*
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Ning Zhou <ning.zhou@totaralearning.com>
 * @package totara_hierarchy
 */

use core_phpunit\testcase;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\pathway;
use totara_hierarchy\entity\competency as competency_entity;
use totara_hierarchy\testing\generator;
use totara_webapi\phpunit\webapi_phpunit_helper;

class totara_hierarchy_webapi_resolver_query_competencies_testcase extends testcase {
    use webapi_phpunit_helper;

    private const QUERY = 'totara_hierarchy_competencies';

    public function test_query_competency(): void {
        $number_of_competencies = 3;
        $this->init_mockup($number_of_competencies);
        $result = $this->resolve_graphql_query(self::QUERY, []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals($number_of_competencies, $result['total'], 'wrong total count');
    }

    public function test_successful_ajax_call(): void {
        $number_of_competencies = 3;
        $this->init_mockup($number_of_competencies);
        $result = $this->parsed_graphql_operation(self::QUERY, []);

        $this->assertNotNull($result, 'fail to retrieved competency');
    }

    public function test_successful_ajax_call_with_filters_or_pagination(): void {
        $number_of_competencies_1 = 3;
        $number_of_competencies_2 = 7;
        $competencies_1 = $this->init_mockup($number_of_competencies_1, true);
        $competencies_2 = $this->init_mockup($number_of_competencies_2);

        // test pagination of query results
        $args = [
            'input' => [
                'pagination' => [
                    'limit' => 6
                ]
            ]
        ];
        // should have 6 items
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertNotEmpty($result['next_cursor'], 'should not be empty cursor');
        $this->assertEquals(
            $args['input']['pagination']['limit'],
            count($result['items']),
            'wrong pagination count'
        );

        $args = [
            'input' => [
                'pagination' => [
                    'limit' => 6,
                    'cursor' => $result['next_cursor'],
                ]
            ]
        ];
        // should have 4 items
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals(
            $number_of_competencies_1 + $number_of_competencies_2 - $args['input']['pagination']['limit'],
            count($result['items']),
            'wrong pagination count'
        );
        $this->assertEmpty($result['next_cursor'], 'should be empty cursor');

        $args = [
            'input' => [
                'pagination' => [
                    'limit' => 6,
                    'page' => 2,
                ]
            ]
        ];
        // should have 4 items
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals(
            $number_of_competencies_1 + $number_of_competencies_2 - $args['input']['pagination']['limit'],
            count($result['items']),
            'wrong pagination count'
        );
        $this->assertEquals(
            $number_of_competencies_1 + $number_of_competencies_2,
            $result['total'],
            'wrong total count'
        );

        // test query competencies of framework 1
        $args = [
            'input' => [
                'filters' => [
                    'framework_id' => $competencies_1[0]->frameworkid
                ]
            ]
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $result_ids = array_map(function (competency_entity $competency) {
            return $competency->id;
        }, $result['items']);
        $expect_ids = array_map(function ($competency) {
            return $competency->id;
        }, $competencies_1);
        $this->assertEquals($expect_ids, $result_ids, 'wrong competency count');

        // test query competencies of certain ids
        $args = [
            'input' => [
                'filters' => [
                    'ids' => [
                        $competencies_1[0]->id,
                        $competencies_2[1]->id,
                        $competencies_2[3]->id,
                    ]
                ]
            ]
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals(count($args['input']['filters']['ids']), $result['total'], 'wrong competency count');

        //test query competencies of excluded ids
        $args = [
            'input' => [
                'filters' => [
                    'excluded_ids' => [
                        $competencies_1[0]->id,
                        $competencies_2[1]->id,
                        $competencies_2[3]->id,
                    ]
                ]
            ]
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $expect_number = $number_of_competencies_1 + $number_of_competencies_2 - count($args['input']['filters']['excluded_ids']);
        $this->assertEquals($expect_number, $result['total'], 'wrong competency count');

        // test query competencies according to the portion of name
        $args = [
            'input' => [
                'filters' => [
                    'name' => 'ency2',
                ]
            ]
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals(2, $result['total'], 'wrong competency count');

        // test query competencies order by achievement path
        $args = [
            'input' => [
                'order_by' => 'competency_name',
                'order_dir' => 'DESC'
            ]
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals(array_pop($competencies_2)->fullname, $result['items'][0]->fullname, 'wrong competency count');

        // test query competencies order by achievement path
        $args = [
            'input' => [
                'order_by' => 'achievement_path',
                'order_dir' => 'DESC'
            ]
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals(array_pop($competencies_1)->fullname, $result['items'][0]->fullname, 'wrong competency count');

        // test query competencies without achievement path
        $args = [
            'input' => [
                'filters' => [
                    'no_path' => 1,
                ]
            ]
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals($number_of_competencies_2, $result['total'], 'wrong competency count');

        // create competencies with child competencies
        $number_of_children = 2;
        $competencies_with_two_children = $this->init_mockup($number_of_competencies_1, false, $number_of_children);
        $args = [
            'input' => [
                'filters' => [
                    'no_hierarchy' => 1,
                ]
            ]
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $expect_number = $number_of_competencies_1 + $number_of_competencies_2 + $number_of_competencies_1 * 3;
        $this->assertEquals($expect_number, $result['total'], 'wrong competency count');
        $args = [
            'input' => [
                'filters' => [
                    'no_hierarchy' => 0,
                ]
            ]
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $expect_number = $number_of_competencies_1 + $number_of_competencies_2 + $number_of_competencies_1;
        $this->assertEquals($expect_number, $result['total'], 'wrong competency count');
        $args = [
            'input' => [
                'filters' => [
                    'parent_id' => $competencies_with_two_children[0]->id,
                ]
            ]
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals($number_of_children, $result['total'], 'wrong competency count');
    }

    /**
     * Test filter for competencies without achievement paths.
     *
     * @return void
     */
    public function test_no_path_filter() {
        $this->setAdminUser();
        $hierarchy_generator = generator::instance();
        $framework_id = $hierarchy_generator->create_comp_frame(['name' => 'fw'])->id;

        // Create two competencies without pathway.
        $competency_without_1 = $hierarchy_generator->create_comp([
            'fullname' => 'competency_without_1',
            'frameworkid' => $framework_id,
        ]);
        $competency_without_2 = $hierarchy_generator->create_comp([
            'fullname' => 'competency_without_2',
            'frameworkid' => $framework_id,
        ]);

        // Create two competencies with active pathways.
        $competency_with_active_1 = $hierarchy_generator->create_comp([
            'fullname' => 'competency_with_active_1',
            'frameworkid' => $framework_id,
        ]);
        $competency_with_active_2 = $hierarchy_generator->create_comp([
            'fullname' => 'competency_with_active_2',
            'frameworkid' => $framework_id,
        ]);
        $this->create_pathways_for_competency($competency_with_active_1);
        $this->create_pathways_for_competency($competency_with_active_2);

        // Create two competencies with only archived pathways.
        $competency_with_archived_1 = $hierarchy_generator->create_comp([
            'fullname' => 'competency_with_archived_1',
            'frameworkid' => $framework_id,
        ]);
        $competency_with_archived_2 = $hierarchy_generator->create_comp([
            'fullname' => 'competency_with_archived_2',
            'frameworkid' => $framework_id,
        ]);
        $this->create_pathways_for_competency($competency_with_archived_1, pathway::PATHWAY_STATUS_ARCHIVED);
        $this->create_pathways_for_competency($competency_with_archived_2, pathway::PATHWAY_STATUS_ARCHIVED);

        // Create two competencies with both inactive and active pathways.
        $competency_with_both_1 = $hierarchy_generator->create_comp([
            'fullname' => 'competency_with_both_1',
            'frameworkid' => $framework_id,
        ]);
        $competency_with_both_2 = $hierarchy_generator->create_comp([
            'fullname' => 'competency_with_both_2',
            'frameworkid' => $framework_id,
        ]);
        $this->create_pathways_for_competency($competency_with_both_1);
        $this->create_pathways_for_competency($competency_with_both_2);
        $this->create_pathways_for_competency($competency_with_both_1, pathway::PATHWAY_STATUS_ARCHIVED);
        $this->create_pathways_for_competency($competency_with_both_2, pathway::PATHWAY_STATUS_ARCHIVED);

        $args = [
            'input' => [
                'filters' => [
                    'no_path' => false,
                ]
            ]
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assert_result([
            $competency_without_1->id,
            $competency_without_2->id,
            $competency_with_active_1->id,
            $competency_with_active_2->id,
            $competency_with_archived_1->id,
            $competency_with_archived_2->id,
            $competency_with_both_1->id,
            $competency_with_both_2->id,
        ], $result);

        $args = [
            'input' => [
                'filters' => [
                    'no_path' => true,
                ]
            ]
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assert_result([
            $competency_without_1->id,
            $competency_without_2->id,
            $competency_with_archived_1->id,
            $competency_with_archived_2->id,
        ], $result);
    }

    /**
     * @param array $expected_competency_ids
     * @param $result
     * @return void
     */
    private function assert_result(array $expected_competency_ids, $result): void {
        $this->assertEquals(count($expected_competency_ids), $result['total']);
        $actual_competency_ids = array_map(function (competency_entity $competency) {
            return $competency->id;
        }, $result['items']);
        $this->assertEqualsCanonicalizing($expected_competency_ids, $actual_competency_ids);
    }

    /**
     * Create two pathways for the given competency.
     *
     * @param stdClass $competency
     * @param int $status
     * @return void
     */
    private function create_pathways_for_competency(stdClass $competency, $status = pathway::PATHWAY_STATUS_ACTIVE): void {
        $max_sortorder = pathway_entity::repository()->select_raw("MAX(sortorder) AS sortorder")->one();
        $sortorder = $max_sortorder->sortorder ?? 0;

        $path = new pathway_entity();
        $path->competency_id = $competency->id;
        $path->sortorder = $sortorder + 1;
        $path->path_type = 'criteria_group';
        $path->path_instance_id = 0;
        $path->status = $status;
        $path->save();

        // Create another one.
        // Just to make sure we don't have duplicate rows in the results coming from bad left joins and such.
        $path = new pathway_entity();
        $path->competency_id = $competency->id;
        $path->sortorder = $sortorder + 2;
        $path->path_type = 'manual';
        $path->path_instance_id = 0;
        $path->status = $status;
        $path->save();
    }

    public function test_failed_ajax_call(): void {
        self::setUser();
        $args = [
            'input' => [
                'filters' => [
                    'no_hierarchy' => 'false',
                ]
            ]
        ];
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'logged in');
    }

    private function init_mockup(int $competency_count = 5, $with_path = false, $number_of_children = 0): array {
        $this->setAdminUser();
        $hierarchy_generator = generator::instance();
        $framework_id = $hierarchy_generator->create_comp_frame(['name' => 'fw'])->id;
        $competencies = [];
        $path_types = [
            'fake_multivalue_type',
            'criteria_group',
            'learning_plan',
            'fake_singlevalue_type',
            'manual',
        ];
        for ($i = 0; $i < $competency_count; $i++) {
            $competency = $hierarchy_generator->create_comp([
                'fullname' => 'competency' . $i,
                'frameworkid' => $framework_id,
            ]);

            if ($with_path) {
                $path = new pathway_entity();
                $path->competency_id = $competency->id;
                $path->sortorder = 1;
                $path->path_type = $path_types[rand(0, count($path_types) - 1)];
                $path->path_instance_id = 0;
                $path->status = pathway::PATHWAY_STATUS_ACTIVE;
                $path->save();
            }

            if ($number_of_children) {
                for ($j = 0; $j < $number_of_children; $j++) {
                    $hierarchy_generator->create_comp([
                        'fullname' => 'competency_' . $i . '_children_' . $j,
                        'frameworkid' => $framework_id,
                        'parentid' => $competency->id,
                    ]);
                }
            }

            $competencies[] = $competency;
        }

        return $competencies;
    }
}
