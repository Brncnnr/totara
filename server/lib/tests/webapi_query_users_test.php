<?php
/**
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
 * @package core_user
 */

use core\collection;
use core\data_providers\users;
use core\entity\user as user_entity;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \core\webapi\resolver\query\users
 *
 * @group core_user
 */
class core_webapi_query_users_testcase extends testcase {

    private const QUERY = 'core_users';

    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_find_default_params(): void {
        $no_of_users = users::DEFAULT_PAGE_SIZE - 1;
        $expected = $this->create_users($no_of_users)->pluck('id');

        $result = $this->resolve_graphql_query(self::QUERY, []);
        $this->assertIsArray($result);

        $total = $result['total'] ?? -999;
        $this->assertEquals($no_of_users, $total, 'wrong total count');

        $enc_cursor = $result['next_cursor'] ?? -999;
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $items = collection::new($result['items'] ?? []);
        $this->assertEquals($no_of_users, $items->count(), 'wrong current page count');
        $this->assertEqualsCanonicalizing($expected, $items->pluck('id'), 'wrong retrievals');
    }

    /**
     * @covers ::resolve
     */
    public function test_filters(): void {
        $all_users = $this->create_users();

        // Filter by id.
        $first_user = $all_users->first();
        $first_user_id = (int)$first_user->id;
        $first_user_name = $first_user->lastname;

        // Filter by single id.
        $ids = [$first_user_id];
        $args = [
            'query' => [
                'filters' => [
                    'ids' => $ids
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

        $this->assertEqualsCanonicalizing($ids, array_column($items, 'id'));

        // Filter by name.
        $args = [
            'query' => [
                'filters' => [
                    'name' => $first_user_name
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

        $this->assertEqualsCanonicalizing($ids, array_column($items, 'id'));

        // Filter combination.
        $args = [
            'query' => [
                'filters' => [
                    'name' => $first_user_name,
                    'ids' => $ids
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
                    'name' => 'does not exist',
                    'ids' => $ids
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
    public function test_sorted_pagination(): void {
        $no_of_users = 10;
        $order_direction = 'DESC';
        $user_ids = $this->create_users($no_of_users)
            ->sort('id', $order_direction)
            ->pluck('id');

        $page_size = $no_of_users - 1;

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

        $this->assertEquals($no_of_users, $total, 'wrong total count');
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

        $this->assertEquals($no_of_users, $total, 'wrong total count');
        $this->assertCount(1, $items, 'wrong current page count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $retrieved = array_merge($retrieved, array_column($items, 'id'));

        // See if items were retrieved in the correct order.
        $this->assertEquals($user_ids, $retrieved, 'retrieved in wrong order');
    }

    /**
     * @covers ::resolve
     */
    public function test_ajax_default_params(): void {
        $no_of_users = 2;
        $users_by_ids = $this->create_users($no_of_users)->key_by('id');

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);

        $items = $result['items'];
        $total = $result['total'];
        $enc_cursor = $result['next_cursor'];

        $this->assertCount($no_of_users, $items, 'wrong item count');
        $this->assertEquals($no_of_users, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $item_ids = array_column($items, 'id');
        $this->assertEqualsCanonicalizing($users_by_ids->pluck('id'), $item_ids);
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_query(): void {
        // Wrong input.
        $args = [
            'query' => ['type' => "UNKNOWN"]
        ];

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Field "type" is not defined by type core_users_query');
    }

    /**
     * Generates users.
     *
     * @param int $count no of users to generate.
     *
     * @return collection a list of user_entity objects.
     */
    private function create_users(int $count = 10): collection {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $users = [];
        foreach (range(0, $count - 1) as $i) {
            $user = $generator->create_user([
                'firstname' => 'Test',
                'lastname' => sprintf('User #%02d', $i)
            ]);

            $users[] = new user_entity($user);
        }

        return collection::new($users);
    }
}
