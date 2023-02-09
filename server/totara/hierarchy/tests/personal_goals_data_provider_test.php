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
use hierarchy_goal\assignment_type;
use hierarchy_goal\personal_goal_assignment_type;
use hierarchy_goal\data_providers\personal_goals;
use hierarchy_goal\entity\personal_goal as personal_goal_entity;
use totara_hierarchy\testing\generator;

/**
 * @group totara_hierarchy
 * @group totara_goal
 */
class totara_hierarchy_personal_goals_data_provider_testcase extends testcase {
    public function test_default_params(): void {
        $no_of_users = 1;
        $no_of_goals = 7;
        $this->setup_env($no_of_users, $no_of_goals);

        $provider = personal_goals::create();
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
        $assignment_type = personal_goal_assignment_type::manager();
        [$user_ids, $goals] = $this->setup_env($no_of_users, $no_of_goals, $assignment_type);

        // Filter by single id value.
        $first_goal = $goals->first();
        $first_id = (int) $first_goal->id;

        $provider = personal_goals::create();

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
        $last_goal = $goals->last();
        $last_id = (int) $last_goal->id;
        $ids = [ $first_id, $last_id ];

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

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider
            ->set_filters(['user_id' => $first_user_id])
            ->fetch_paginated();

        $expected_goals =  $goals->filter('userid', $first_user_id);

        $this->assertEqualsCanonicalizing(
            $expected_goals->pluck('id'),
            array_column($items, 'id'),
            'wrong retrievals'
        );

        $this->assertEquals($expected_goals->count(), $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount($expected_goals->count(), $items, 'wrong item count');

        // Filter by goal name.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider
            ->set_filters(['name' => $first_goal->name])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');
        $this->assertEqualsCanonicalizing([$first_id], array_column($items, 'id'));

        // Filter by goal assignment type.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider
            ->set_filters(['assignment_type' => $assignment_type->get_value()])
            ->fetch_paginated();

        $total_goals = $no_of_users * $no_of_goals;
        $this->assertEquals($total_goals, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount($total_goals, $items, 'wrong item count');
        $this->assertEqualsCanonicalizing($goals->pluck('id'), array_column($items, 'id'));

        // Filter combination.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider
            ->set_filters([
                'ids' => $first_id,
                'name' => $first_goal->name
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
                'name' => $first_goal->name
            ])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');
        $this->assertEqualsCanonicalizing([$first_id], array_column($items, 'id'));

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider
            ->set_filters([
                'ids' => $ids,
                'name' => $last_goal->name
            ])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');
        $this->assertEqualsCanonicalizing([$last_id], array_column($items, 'id'));

        // Filter no result combination.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $provider
            ->set_filters(['ids' => $first_id, 'name' => $last_goal->name])
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
        [, $goals] = $this->setup_env(1, $no_of_goals);

        $order_direction = 'desc';
        $goal_ids = $goals
            ->sort('id', $order_direction)
            ->pluck('id');

        $provider = personal_goals::create()
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
        $this->assertEquals($goal_ids, $retrieved, 'retrieved in wrong order');
    }

    public function test_sorted_target_date_pagination(): void {
        $page_size = 3;
        $no_of_goals = $page_size * 2 + 1;
        [, $goals] = $this->setup_env(1, $no_of_goals);

        foreach ($goals as $i => $goal) {
            $goal->targetdate = $i === 0 || $i === $goals->count() - 1
                ? time() + $i * 100000
                : null; // Deliberately null; to test if entity really changes it to 0.

            $goal->save();
        }

        $order_direction = 'desc';
        $goal_ids = $goals
            ->sort(
                function (
                    personal_goal_entity $p1,
                    personal_goal_entity $p2
                ) use ($order_direction): int {
                    if ($p1->targetdate !== $p2->targetdate) {
                        [$left, $right] = $order_direction === 'desc'
                            ? [$p2, $p1]
                            : [$p1, $p2];

                        return $left->targetdate <=> $right->targetdate;
                    }

                    if ($p1->name !== $p2->name) {
                        return $p1->name <=> $p2->name;
                    }

                    return $p1->id <=> $p2->id;
                }
            )
            ->pluck('id');

        // Note the secondary ordering by name because there are multiple goals
        // with the same target date. If this is not done, the pagination will
        // be wrong.
        $sorting_cols = personal_goals::SORT_FIELDS;
        $provider = personal_goals::create()
            ->set_page_size($page_size)
            ->set_order($sorting_cols[personal_goals::SORT_TARGET_DATE], $order_direction)
            ->add_order($sorting_cols[personal_goals::SORT_GOAL_NAME], 'ASC')
            ->add_order($sorting_cols[personal_goals::SORT_GOAL_ID], 'ASC');

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
        $this->assertEquals($goal_ids, $retrieved, 'retrieved in wrong order');
    }

    private function setup_env(
        int $user_count=1,
        int $goal_count=2,
        ?assignment_type $assignment_type = null
    ): array {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $hierarchy_generator = generator::instance();

        $user_ids = collection::new([]);
        $goals = collection::new([]);
        $type = $assignment_type
            ? $assignment_type->get_value()
            : personal_goal_assignment_type::self()->get_value();

        for ($i = 0; $i < $user_count; $i++) {
            $user_id = $generator->create_user()->id;
            $user_ids->append($user_id);

            for ($j = 0; $j < $goal_count; $j++) {
                $goal_id = $hierarchy_generator->create_personal_goal(
                    $user_id,
                    ['name' => "user$i pg$j", 'assigntype' => $type]
                )->id;

                $goals->append(new personal_goal_entity($goal_id));
            }
        }

        return [$user_ids, $goals];
    }
}