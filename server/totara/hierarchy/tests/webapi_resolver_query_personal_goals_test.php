<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package totara_hierarchy
 */

use core\collection;
use hierarchy_goal\personal_goal_assignment_type;
use hierarchy_goal\entity\personal_goal as personal_goal_entity;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_hierarchy\testing\generator;

/**
 * Tests the totara_hierarchy_personal_goals resolver.
 *
 * @group totara_hierarchy
 * @group totara_goal
 */
class totara_hierarchy_webapi_resolver_query_personal_goals_testcase extends advanced_testcase {
    private const QUERY = 'totara_hierarchy_personal_goals';

    use webapi_phpunit_helper;

    public function test_find_default_params(): void {
        [$user_ids, $goals] = $this->setup_env();

        $user_id = $user_ids->first();
        $this->setUser($user_id);

        $user_goal_names = $goals
            ->filter('userid', $user_id)
            ->pluck('name');
        $no_of_goals = count($user_goal_names);

        $result = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('next_cursor', $result);

        $items = $result['items'];
        $total = $result['total'];
        $enc_cursor = $result['next_cursor'];

        $this->assertEquals($no_of_goals, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $retrieved = [];
        foreach ($items as $item) {
            $retrieved[] = $item->name;
        }
        $this->assertCount($no_of_goals, $retrieved, 'wrong current page count');
        $this->assertEqualsCanonicalizing(
            $user_goal_names, $retrieved, 'wrong retrievals'
        );
    }

    public function test_sorted_pagination(): void {
        $no_of_goals = 10;
        $order_direction = 'DESC';
        [$user_ids, $goals] = $this->setup_env(1, $no_of_goals);

        $this->setUser($user_ids->first());
        $page_size = $no_of_goals - 1;

        $args = [
            'input' => [
                'filters' => [],
                'order_by' => 'GOAL_NAME',
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

        $this->assertEquals($no_of_goals, $total, 'wrong total count');
        $this->assertCount($page_size, $items, 'wrong current page count');
        $this->assertNotEmpty($enc_cursor, 'empty cursor');

        $retrieved = array_column($items, 'name');

        // 2nd round.
        $args = [
            'input' => [
                'filters' => [],
                'order_by' => 'GOAL_NAME',
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

        $this->assertEquals($no_of_goals, $total, 'wrong total count');
        $this->assertCount(1, $items, 'wrong current page count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $retrieved = array_merge($retrieved, array_column($items, 'name'));

        // See if items were retrieved in the correct order.
        $goal_ids = $goals
            ->sort('name', $order_direction)
            ->pluck('name');
        $this->assertEquals($goal_ids, $retrieved, 'retrieved in wrong order');

        // test the new sort order of target_date
        $args = [
            'input' => [
                'filters' => [],
                'order_by' => 'TARGET_DATE',
            ]
        ];

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals($no_of_goals, $total, 'wrong total count');
        $retrieved = array_column($items, 'name');
        $goal_ids = $goals
            ->sort(
                function(personal_goal_entity $a, personal_goal_entity $b) use ($order_direction):int {
                    if ($a->targetdate !== $b->targetdate) {
                        [$left, $right] = $order_direction === 'desc'
                            ? [$b, $a]
                            : [$a, $b];

                        return $left->targetdate <=> $right->targetdate;
                    }

                    if ($a->name !== $b->name) {
                        return $a->name <=> $b->name;
                    }

                    return $a->id <=> $b->id;
                },
                $order_direction
            )
            ->pluck('name');
        $this->assertEquals($goal_ids, $retrieved, 'retrieved in wrong order');
    }

    public function test_filters(): void {

        [$user_ids, $goals, $deletes, $types] = $this->setup_env();

        $user_id = $user_ids->first();
        $this->setUser($user_id);

        // Filter by goal type
        $args = [
            'input' => [
                'filters' => ['type_id' => $types[0]->id]
            ]
        ];
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals(round(5 / 2), $total, 'wrong total count');
        // check exact items filter by type id
        $expected = personal_goal_entity::repository()
            ->where('typeid', $types[0]->id)
            ->where('userid', $user_id)
            ->order_by('id')
            ->get()
            ->pluck('id');
        $retrieved = array_map(function (personal_goal_entity $item) {
            return $item->id;
        }, $items);
        $this->assertEquals($expected, $retrieved, 'wrong items has been retrieved.');

        // Filter by single value.
        $assignment_type = personal_goal_assignment_type::self();
        $args = [
            'input' => [
                'filters' => ['assignment_type' => $assignment_type->get_name()]
            ]
        ];

        $expected = $goals
            ->filter('userid', $user_id)
            ->filter('assigntype', $assignment_type->get_value())
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
        $goal = $goals->last();
        $this->setUser($goal->userid);

        $args = [
            'input' => [
                'filters' => [
                    'name' => $goal->name,
                    'ids' => (int)$goal->id
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
            'input' => [
                'filters' => [
                    'name' => '#00',
                    'ids' => (int)$goal->id
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

    public function test_deleted_filter(): void {
        [$user_ids, $goals, $goals_deleted] = $this->setup_env(3, 5, true);

        $user_id = $user_ids->first();
        $this->setUser($user_id);

        // Check deleted goals
        $args = [
            'input' => [
                'filters' => ['deleted' => true],
            ],
        ];

        $expected = $goals_deleted
            ->filter('userid', $user_id)
            ->pluck('id');

        [
            "items"       => $items,
            "total"       => $total,
            "next_cursor" => $enc_cursor,
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals(count($expected), $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(count($expected), $items, 'wrong item count');
        $this->assertEqualsCanonicalizing($expected, array_column($items, 'id'));

        // Check not deleted goals
        $args = [
            'input' => [
                'filters' => ['deleted' => false],
            ],
        ];

        $expected = $goals
            ->filter('userid', $user_id)
            ->pluck('id');

        [
            "items"       => $items,
            "total"       => $total,
            "next_cursor" => $enc_cursor,
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals(count($expected), $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(count($expected), $items, 'wrong item count');
        $this->assertEqualsCanonicalizing($expected, array_column($items, 'id'));

        // Check goals without any filter
        $args = ['input' => []];

        $expected = $goals
            ->filter('userid', $user_id)
            ->pluck('id');

        [
            "items"       => $items,
            "total"       => $total,
            "next_cursor" => $enc_cursor,
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals(count($expected), $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(count($expected), $items, 'wrong item count');
        $this->assertEqualsCanonicalizing($expected, array_column($items, 'id'));
    }

    public function test_ajax_default_params(): void {
        [$user_ids, $goals] = $this->setup_env();

        $user_id = $user_ids->first();
        $this->setUser($user_id);

        $user_goals = $goals->filter('userid', $user_id);
        $no_of_goals = count($user_goals);

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $items = $result['items'];
        $total = $result['total'];
        $enc_cursor = $result['next_cursor'];

        $this->assertCount($no_of_goals, $items, 'wrong item count');
        $this->assertEquals($no_of_goals, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $item_ids = array_column($items, 'id');
        $this->assertEqualsCanonicalizing($user_goals->pluck('id'), $item_ids);
    }

    public function test_failed_ajax_query(): void {
        [$user_ids, ] = $this->setup_env();
        $this->setUser($user_ids->first());

        $args = ['input' => ['type' => "UNKNOWN"]];
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed(
            $result,
            'Field "type" is not defined by type totara_hierarchy_personal_goal_input.'
        );

        $feature = 'goals';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'Feature goals is not available.');
        advanced_feature::enable($feature);

        self::setGuestUser();
        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'permissions');

        self::setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'logged in');
    }

    /**
     * Generates test goals.
     *
     * @param int user_count no of users to generate.
     * @param int goal_count of personal goals per user.
     * @param bool include_deleted personal goals per user.
     *
     * @return array a [user ids, personal goals, deleted personal goals] tuple.
     */
    private function setup_env(int $user_count=3, int $goal_count=5, bool $include_deleted= false): array {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $hierarchy_generator = generator::instance();

        $user_ids = collection::new([]);
        $goals = collection::new([]);
        $goals_deleted = collection::new([]);

        // create personal goal types
        $goal_type_count = 2;
        $goal_types = [];
        for ($i = 0; $i < $goal_type_count; $i++) {
            $goal_types[] = $hierarchy_generator->create_personal_goal_type([
                'fullname' => 'test personal type #'.$i,
                'shortname' => 'pgoaltype'.$i,
                'idnumber' =>  'pgtype'.$i,
            ]);
        }

        for ($i = 0; $i < $user_count; $i++) {
            $user_id = $generator->create_user()->id;
            $user_ids->append($user_id);

            for ($j = 0; $j < $goal_count; $j++) {
                if ($j % 3 === 0) {
                    $assign_type = personal_goal_assignment_type::self();
                    $user_created = 0;
                } else {
                    $assign_type = personal_goal_assignment_type::manager();
                    $user_created = $generator->create_user()->id;
                }

                $goal_id = $hierarchy_generator->create_personal_goal(
                    $user_id,
                    [
                        'name' => "user$i pg$j",
                        'assigntype' => $assign_type->get_value(),
                        'usercreated' => $user_created,
                        'typeid' => $goal_types[$j % 2]->id,
                    ]
                )->id;

                $goals->append(new personal_goal_entity($goal_id));
            }
            if ($include_deleted) {
                $deleted_goal_id = $hierarchy_generator->create_personal_goal(
                    $user_id,
                    [
                        'name'        => "user$i pg-deleted",
                        'assigntype'  => $assign_type->get_value(),
                        'usercreated' => $user_created,
                        'deleted'     => 1,
                    ]
                )->id;

                $goals_deleted->append(new personal_goal_entity($deleted_goal_id));
            }
        }

        return [$user_ids, $goals, $goals_deleted, $goal_types];
    }
}
