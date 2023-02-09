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
use core\pagination\cursor;
use core_phpunit\testcase;
use hierarchy_goal\data_providers\assigned_company_goals;
use hierarchy_goal\entity\company_goal_assignment as company_goal_assignment_entity;
use totara_hierarchy\testing\generator;

/**
 * @group totara_hierarchy
 * @group totara_goal
 */
class totara_hierarchy_assigned_company_goals_data_provider_testcase extends testcase {
    public function test_default_params(): void {
        $no_of_users = 1;
        $no_of_goals = 7;
        $this->setup_env($no_of_users, $no_of_goals);

        $provider = assigned_company_goals::create();
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider->fetch_paginated();

        $this->assertEquals($no_of_users * $no_of_goals, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
    }

    public function test_filters(): void {
        $no_of_users = 3;
        $no_of_goals = 2;
        [$user_ids, $assignments] = $this->setup_env($no_of_users, $no_of_goals);

        // Filter by single id value.
        $first_assignment = $assignments->first();
        $first_id = (int) $first_assignment->id;

        $provider = assigned_company_goals::create();

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider
            ->set_filters(['ids' => $first_id])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');
        $this->assertEquals($first_id, $items[0]->id, 'wrong item retrieved');

        // Filter by multiple id value.
        $last_assignment = $assignments->last();
        $last_id = (int) $last_assignment->id;
        $ids = [ $first_id, (int) $last_id ];

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider
            ->set_filters(['ids' => $ids])
            ->fetch_paginated();

        $this->assertEquals(count($ids), $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(count($ids), $items, 'wrong item count');

        $this->assertEqualsCanonicalizing($ids, array_column($items, 'id'), 'wrong items retrieved');

        // Filter by user id.
        $first_user_id = $user_ids->first();
        $expected_assignments =  $assignments->filter('userid', $first_user_id);

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider
            ->set_filters(['user_id' => $first_user_id])
            ->fetch_paginated();

        $this->assertEqualsCanonicalizing(
            $expected_assignments->pluck('id'),
            array_column($items, 'id'),
            'wrong retrievals'
        );

        $this->assertEquals($expected_assignments->count(), $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount($expected_assignments->count(), $items, 'wrong item count');

        // Filter by goal name.
        $first_goal_name = $first_assignment->goal->fullname;
        $expected_assignments = $assignments
            ->filter(
                function (company_goal_assignment_entity $entity) use ($first_goal_name): bool {
                    return $entity->goal->fullname === $first_goal_name;
                }
            )
            ->pluck('id');

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider
            ->set_filters(['goal_name' => $first_goal_name])
            ->fetch_paginated();

        $this->assertEquals(count($expected_assignments), $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(count($expected_assignments), $items, 'wrong item count');
        $this->assertEqualsCanonicalizing($expected_assignments, array_column($items, 'id'));

        // Filter combination.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider
            ->set_filters([
                'ids' => $first_id,
                'user_id' => $first_assignment->userid
            ])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider
            ->set_filters([
                'ids' => $ids,
                'user_id' => $first_assignment->userid
            ])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');
        $this->assertEqualsCanonicalizing(
            [$first_assignment->id], array_column($items, 'id')
        );

        // Filter no result combination.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider
            ->set_filters([
                'ids' => $first_id,
                'user_id' => $last_assignment->userid
            ])
            ->fetch_paginated();

        $this->assertEquals(0, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(0, $items, 'wrong item count');

        // Unknown filter.
        $key = 'unknown';
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/$key/");
        $provider
            ->set_filters([$key => '#00'])
            ->fetch_paginated();
    }

    public function test_sorted_pagination(): void {
        $page_size = 3;
        $no_of_goals = $page_size * 2 + 1;
        [, $assignments] = $this->setup_env(1, $no_of_goals);

        $order_direction = 'desc';
        $assignment_ids = $assignments
            ->sort('id', $order_direction)
            ->pluck('id');

        $provider = assigned_company_goals::create()
            ->set_page_size($page_size)
            ->set_order('id', $order_direction);

        // 1st round.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider->fetch_paginated();

        $this->assertEquals($no_of_goals, $total, 'wrong total count');
        $this->assertCount($page_size, $items, 'wrong current page count');
        $this->assertNotEmpty($enc_cursor, 'empty cursor');

        $retrieved = array_column($items, 'id');

        // 2nd round.
        $cursor = cursor::decode($enc_cursor);
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider->fetch_paginated($cursor);

        $this->assertEquals($no_of_goals, $total, 'wrong total count');
        $this->assertCount($page_size, $items, 'wrong current page count');
        $this->assertNotEmpty($enc_cursor, 'empty cursor');

        $retrieved = array_merge($retrieved, array_column($items, 'id'));

        // 3rd round.
        $cursor = cursor::decode($enc_cursor);
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider->fetch_paginated($cursor);

        $this->assertEquals($no_of_goals, $total, 'wrong total count');
        $this->assertCount(1, $items, 'wrong current page count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $retrieved = array_merge($retrieved, array_column($items, 'id'));

        // See if items were retrieved in the correct order.
        $this->assertEquals($assignment_ids, $retrieved, 'retrieved in wrong order');
    }

    public function test_sorted_target_date_pagination(): void {
        $page_size = 3;
        $no_of_goals = $page_size * 2 + 1;
        [, $assignments] = $this->setup_env(1, $no_of_goals);

        foreach ($assignments as $i => $assignment) {
            $goal = $assignment->goal;

            $goal->targetdate = $i === 0 || $i === $assignments->count() - 1
                ? time() + $i * 100000
                : null; // Deliberately null; to test if entity really changes it to 0.

            $goal->save();
            $assignment->refresh();
        }

        $order_direction = 'desc';
        $assignment_ids = $assignments
            ->sort(
                function (
                    company_goal_assignment_entity $assignment_1,
                    company_goal_assignment_entity $assignment_2
                ) use ($order_direction): int {
                    $goal1 = $assignment_1->goal;
                    $goal2 = $assignment_2->goal;

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
                }
            )
            ->pluck('id');

        // Note the secondary ordering by name because there are multiple goals
        // with the same target date. If this is not done, the pagination will
        // be wrong.
        $sorting_cols = assigned_company_goals::SORT_FIELDS;
        $provider = assigned_company_goals::create()
            ->set_page_size($page_size)
            ->set_order($sorting_cols[assigned_company_goals::SORT_TARGET_DATE], $order_direction)
            ->add_order($sorting_cols[assigned_company_goals::SORT_GOAL_NAME], 'ASC')
            ->add_order($sorting_cols[assigned_company_goals::SORT_GOAL_ID], 'ASC');

        // 1st round.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider->fetch_paginated();

        $this->assertEquals($no_of_goals, $total, 'wrong total count');
        $this->assertCount($page_size, $items, 'wrong current page count');
        $this->assertNotEmpty($enc_cursor, 'empty cursor');

        $retrieved = array_column($items, 'id');

        // 2nd round.
        $cursor = cursor::decode($enc_cursor);
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider->fetch_paginated($cursor);

        $this->assertEquals($no_of_goals, $total, 'wrong total count');
        $this->assertCount($page_size, $items, 'wrong current page count');
        $this->assertNotEmpty($enc_cursor, 'empty cursor');

        $retrieved = array_merge($retrieved, array_column($items, 'id'));

        // 3rd round.
        $cursor = cursor::decode($enc_cursor);
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider->fetch_paginated($cursor);

        $this->assertEquals($no_of_goals, $total, 'wrong total count');
        $this->assertCount(1, $items, 'wrong current page count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $retrieved = array_merge($retrieved, array_column($items, 'id'));

        // See if items were retrieved in the correct order.
        $this->assertEquals($assignment_ids, $retrieved, 'retrieved in wrong order');
    }

    private function setup_env(int $user_count=1, int $goal_count=2): array {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $hierarchy_generator = generator::instance();

        $user_ids = collection::new([]);
        for ($i = 0; $i < $user_count; $i++) {
            $user_ids->append($generator->create_user()->id);
        }

        $assignments = [];
        $fw_id = $hierarchy_generator->create_goal_frame(['name' => 'fw'])->id;
        for ($i = 0; $i < $goal_count; $i++) {
            $goal_id = $hierarchy_generator->create_goal([
                'fullname' => "goal$i", 'frameworkid' => $fw_id
            ])->id;
            $hierarchy_generator->goal_assign_individuals($goal_id, $user_ids->all());
        }

        $assignments = company_goal_assignment_entity::repository()->get();
        return [$user_ids, $assignments];
    }
}