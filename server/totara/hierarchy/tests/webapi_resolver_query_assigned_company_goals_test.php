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
use hierarchy_goal\company_goal_assignment;
use hierarchy_goal\company_goal_assignment_type;
use hierarchy_goal\entity\company_goal;
use hierarchy_goal\entity\company_goal_assignment as company_goal_assignment_entity;
use totara_core\advanced_feature;
use totara_hierarchy\testing\generator;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara_hierarchy_assigned_company_goal resolver.
 *
 * @group totara_hierarchy
 * @group totara_goal
 */
class totara_hierarchy_webapi_resolver_query_assigned_company_goals_testcase extends advanced_testcase {
    private const QUERY = 'totara_hierarchy_assigned_company_goals';

    use webapi_phpunit_helper;

    public function test_find_default_params(): void {
        [$user_ids, $assignments] = $this->setup_env();

        $user_id = $user_ids->first();
        $this->setUser($user_id);

        $user_goal_ids = $assignments
            ->filter(
                function (company_goal_assignment $assignment) use ($user_id): bool {
                    return $assignment->get_user()->id == $user_id;
                }
            )
            ->map_to(
                function (company_goal_assignment $assignment): int {
                    return (int)$assignment->get_goal()->id;
                }
            );

        $no_of_goals = $user_goal_ids->count();

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
        $expected_type = company_goal_assignment_type::individual();
        foreach ($items as $item) {
            $types = $item->get_assignment_types();
            $this->assertEquals(1, $types->count());
            $this->assertEquals($expected_type, $types->first()->get_type());

            $retrieved[] = $item->get_goal()->id;
        }

        $this->assertCount($no_of_goals, $retrieved, 'wrong current page count');
        $this->assertEqualsCanonicalizing(
            $user_goal_ids->all(), $retrieved, 'wrong retrievals'
        );
    }

    public function test_sorted_pagination(): void {
        $no_of_goals = 10;
        $order_direction = 'desc';
        [$user_ids, $assignments] = $this->setup_env(1, $no_of_goals);

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

        $retrieved = [];
        foreach ($items as $item) {
            $retrieved[] = $item->get_goal()->fullname;
        }

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

        foreach ($items as $item) {
            $retrieved[] = $item->get_goal()->fullname;
        }

        // See if items were retrieved in the correct order.
        $expected = $assignments
            ->sort(
                function (company_goal_assignment $a, company_goal_assignment $b) use ($order_direction): int {
                    $a_goal_name = $a->get_goal()->fullname;
                    $b_goal_name = $b->get_goal()->fullname;

                    return $order_direction === 'desc'
                        ? $b_goal_name <=> $a_goal_name
                        : $a_goal_name <=> $b_goal_name;
                },
                $order_direction
            )
            ->map_to(
                function (company_goal_assignment $assignment): string {
                    return $assignment->get_goal()->fullname;
                }
            )
            ->all();

        $this->assertEquals($expected, $retrieved, 'retrieved in wrong order');

        // test new order by
        $retrieved = [];
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
        foreach ($items as $item) {
            $retrieved[] = $item->get_goal()->fullname;
        }

        // See if items were retrieved in the correct order.
        $expected = $assignments
            ->sort(
                function (company_goal_assignment $a, company_goal_assignment $b) use ($order_direction): int {
                    $goal1 = $a->goal;
                    $goal2 = $b->goal;

                    if ($goal1->targetdate !== $goal2->targetdate) {
                        [$left, $right] = $order_direction === 'desc'
                            ? [$goal2, $goal1]
                            : [$goal1, $goal2];

                        return $left->targetdate <=> $right->targetdate;
                    }

                    if ($goal1->fullname !== $goal2->fullname) {
                        return $goal1->fullname <=> $goal2->fullname;
                    }

                    return $goal1->id <=> $goal2->id;
                },
                $order_direction
            )
            ->map_to(
                function (company_goal_assignment $assignment): string {
                    return $assignment->get_goal()->fullname;
                }
            )
            ->all();
        $this->assertEquals($expected, $retrieved, 'retrieved in wrong order');
    }

    public function test_filters(): void {
        $goal_total_count = 5;
        [$user_ids, $assignments, $framework_ids, $company_goal_type_ids] = $this->setup_env(3, $goal_total_count);

        $user_id = $user_ids->first();
        $this->setUser($user_id);

        $goal_id = (int)$assignments->first()->get_goal()->id;

        // Filter by framework id
        $args = [
            'input' => [
                'filters' => ['framework_id' => $framework_ids[0]]
            ]
        ];
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals(round($goal_total_count / 2), $total, 'wrong total count');
        // check exact items filter by framework id
        $expected = company_goal::repository()
            ->where('frameworkid', $framework_ids[0])
            ->order_by('id')
            ->get()
            ->pluck('id');

        $retrieved = array_map(function (company_goal_assignment $item) {
            return $item->get_goal()->id;
        }, $items);

        $this->assertEquals($expected, $retrieved, 'wrong items has been retrieved.');

        // Filter by company goal id
        $args = [
            'input' => [
                'filters' => ['type_id' => $company_goal_type_ids[0]]
            ]
        ];
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertEquals(round($goal_total_count / 2), $total, 'wrong total count');
        // check exact items filter by company goal id
        $expected = company_goal::repository()
            ->where('typeid', $company_goal_type_ids[0])
            ->order_by('id')
            ->get()
            ->pluck('id');
        $retrieved = array_map(function (company_goal_assignment $item) {
            return $item->get_goal()->id;
        }, $items);
        $this->assertEquals($expected, $retrieved, 'wrong items has been retrieved.');

        // Filter by single value.
        $args = [
            'input' => [
                'filters' => ['goal_ids' => $goal_id]
            ]
        ];

        $expected = $assignments
            ->filter(
                function (company_goal_assignment $assignment) use ($user_id, $goal_id): bool {
                    return $assignment->get_user()->id === $user_id
                        && $assignment->get_goal()->id === $goal_id;
                }
            )
            ->map_to(
                function (company_goal_assignment $assignment): int {
                    return (int)$assignment->get_goal()->id;
                }
            )
            ->all();

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $this->resolve_graphql_query(self::QUERY, $args);

        $retrieved = [];
        foreach ($items as $item) {
            $retrieved[] = $item->get_goal()->id;
        }

        $this->assertEquals(count($expected), $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(count($expected), $items, 'wrong item count');
        $this->assertEqualsCanonicalizing($expected, $retrieved);

        // Filter combination.
        $args = [
            'input' => [
                'filters' => [
                    'goal_ids' => $goal_id,
                    'user_id' => $user_id
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
                    'goal_ids' => '123',
                    'user_id' => $user_id
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

    public function test_ajax_default_params(): void {
        [$user_ids, $assignments] = $this->setup_env();

        $user_id = $user_ids->first();
        $this->setUser($user_id);

        $goal_ids = $assignments
            ->filter(
                function (company_goal_assignment $assignment) use ($user_id): bool {
                    return $assignment->get_user()->id === $user_id;
                }
            )
            ->map_to(
                function (company_goal_assignment $assignment): int {
                    return $assignment->get_goal()->id;
                }
            );
        $no_of_goals = $goal_ids->count();

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $items = $result['items'];
        $total = $result['total'];
        $enc_cursor = $result['next_cursor'];

        $this->assertCount($no_of_goals, $items, 'wrong item count');
        $this->assertEquals($no_of_goals, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $retrieved = [];
        $expected_type = company_goal_assignment_type::individual()->get_value();
        foreach ($items as $item) {
            $types = $item['assignment_types'];
            $this->assertCount(1, $types);
            $this->assertEquals($expected_type, $types[0]['type']['value']);

            $this->assertEquals($user_id, $item['user_id'], 'wrong user id');
            $retrieved[] = $item['goal']['id'];
        }

        $this->assertEqualsCanonicalizing($goal_ids->all(), $retrieved);
    }

    public function test_failed_ajax_query(): void {
        [$user_ids, ] = $this->setup_env();
        $user_id = $user_ids->first();
        $this->setUser($user_id);

        $args = [
            'input' => ['user_id' => $user_id]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed(
            $result,
            'Field "user_id" is not defined by type totara_hierarchy_company_goal_assignment_input.'
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

    private function setup_env(int $user_count=3, int $goal_count=5): array {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $hierarchy_generator = generator::instance();

        $user_ids = collection::new([]);
        for ($i = 0; $i < $user_count; $i++) {
            $user_id = (int)$generator->create_user()->id;
            $user_ids->append($user_id);
        }

        // create goal frameworks
        $framework_count = 2;
        $framework_ids = [];
        for ($i = 0; $i < $framework_count; $i++) {
            $framework_ids[] = $hierarchy_generator->create_goal_frame(['name' => 'fw'.$i])->id;
        }
        //create goal types
        $type_count = 2;
        $company_goal_type_ids = [];
        for ($i = 0; $i < $type_count; $i++) {
            $company_goal_type_ids[] = $hierarchy_generator->create_goal_type(['fullname' => 'test type #'.$i]);
        }

        for ($i = 0; $i < $goal_count; $i++) {
            $goal_id = $hierarchy_generator->create_goal([
                'fullname' => "goal$i",
                'frameworkid' => $framework_ids[$i % 2],
                'typeid' => $company_goal_type_ids[$i % 2],
            ])->id;

            // NB: the goal_assign_individuals() method always uses the individual
            // assignment type.
            $hierarchy_generator->goal_assign_individuals($goal_id, $user_ids->all());
        }

        $type = [company_goal_assignment_type::individual()];
        $assignments = company_goal_assignment_entity::repository()
            ->get()
            ->map(
                function (company_goal_assignment_entity $entity) use ($type): company_goal_assignment {
                    return new company_goal_assignment(
                        $entity->id, $entity->goal, $entity->user, $type, $entity->scale_value
                    );
                }
            );

        return [$user_ids, $assignments, $framework_ids, $company_goal_type_ids];
    }
}
