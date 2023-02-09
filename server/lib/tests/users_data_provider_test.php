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
use core\entity\tenant;
use core\entity\user;
use core\pagination\cursor;
use core_phpunit\testcase;
use totara_tenant\testing\generator as tenant_generator;

/**
 * @coversDefaultClass \core\data_providers\users
 *
 * @group core_user
 */
class core_users_data_provider_testcase extends testcase {
    /**
     * @covers ::fetch_paginated
     */
    public function test_default_params(): void {
        $no_of_users = users::DEFAULT_PAGE_SIZE - 1;
        $expected = $this->create_users($no_of_users)->pluck('id');

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = users::create_active_users_provider()->fetch_paginated();

        $this->assertEquals($no_of_users, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount($no_of_users, $items, 'wrong current page count');
        $this->assertEqualsCanonicalizing($expected, array_column($items, 'id'), 'wrong retrievals');
    }

    /**
     * @covers ::set_filters
     * @covers ::fetch_paginated
     */
    public function test_filters(): void {
        $no_of_users = 3;
        $all_users = $this->create_users($no_of_users);

        // Filter by single id value.
        $first_user = $all_users->first();
        $first_user_name = $first_user->lastname;
        $first_user_id = (int)$first_user->id;

        $last_user = $all_users->last();
        $last_user_name = $last_user->lastname;
        $last_user_id = (int)$last_user->id;

        $users = users::create_active_users_provider();

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $users
            ->set_filters(['ids' => $first_user_id])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');
        $this->assertEquals($first_user_id, $items[0]->id, 'wrong item retrieved');

        // Filter by multiple id value.
        $ids = [$first_user_id, $last_user_id];

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $users
            ->set_filters(['ids' => $ids])
            ->fetch_paginated();

        $this->assertEquals(count($ids), $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(count($ids), $items, 'wrong item count');

        $this->assertEqualsCanonicalizing($ids, array_column($items, 'id'), 'wrong items retrieved');

        // Filter by name.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $users
            ->set_filters(['name' => $first_user_name])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');
        $this->assertEquals($first_user_id, $items[0]->id, 'wrong item retrieved');

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $users
            ->set_filters(['name' => 'Test'])
            ->fetch_paginated();

        $this->assertEquals($no_of_users, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount($no_of_users, $items, 'wrong item count');
        $this->assertEqualsCanonicalizing($all_users->pluck('id'), array_column($items, 'id'));

        // Filter combination.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $users
            ->set_filters([
                'ids' => $first_user_id,
                'name' => 'Test'
            ])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $users
            ->set_filters([
                'ids' => $ids,
                'name' => $first_user_name
            ])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');
        $this->assertEqualsCanonicalizing([$first_user_id], array_column($items, 'id'));

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $users
            ->set_filters([
                'ids' => $ids,
                'name' => $last_user_name
            ])
            ->fetch_paginated();

        $this->assertEquals(1, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(1, $items, 'wrong item count');
        $this->assertEqualsCanonicalizing([$last_user_id], array_column($items, 'id'));

        // Filter no result combination.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $users
            ->set_filters(['ids' => $first_user_id, 'name' => $last_user_name])
            ->fetch_paginated();

        $this->assertEquals(0, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(0, $items, 'wrong item count');

        // Unknown filter.
        $key = 'unknown';
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/$key/");
        $users
            ->set_filters([$key => '#00'])
            ->fetch_paginated();
    }

    /**
     * @covers ::set_filters
     * @covers ::set_page_size
     * @covers ::fetch_paginated
     */
    public function test_filters_empty_values(): void {
        $no_of_users = 5;
        $all_users = $this->create_users($no_of_users);

        $users = users::create_active_users_provider()->set_page_size($no_of_users);

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $users
            ->set_filters([
                'ids' => [],    // This is a valid filter to filter everything out.
                'name' => '  ', // This filter is ignored.
            ])
            ->fetch_paginated();

        $this->assertEquals(0, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount(0, $items, 'wrong item count');

        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $users->set_filters([
                'ids' => null,  // Now this filter will be ignored.
                'name' => null
            ])
            ->fetch_paginated();

        $this->assertEquals($no_of_users, $total, 'wrong total count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertCount($no_of_users, $items, 'wrong item count');
        $this->assertEqualsCanonicalizing($all_users->pluck('id'), array_column($items, 'id'));
    }

    /**
     * @covers ::set_order
     * @covers ::set_page_size
     * @covers ::fetch_paginated
     */
    public function test_sorted_pagination(): void {
        $page_size = 7;
        $no_of_users = $page_size * 2 + 1;
        $all_users = $this->create_users($no_of_users);

        $order_direction = 'desc';
        $user_ids = $all_users
            ->sort('id', $order_direction)
            ->pluck('id');

        $users = users::create_active_users_provider()
            ->set_page_size($page_size)
            ->set_order('id', $order_direction);

        // 1st round.
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $users->fetch_paginated();

        $this->assertEquals($no_of_users, $total, 'wrong total count');
        $this->assertCount($page_size, $items, 'wrong current page count');
        $this->assertNotEmpty($enc_cursor, 'empty cursor');

        $retrieved = array_column($items, 'id');

        // 2nd round.
        $cursor = cursor::decode($enc_cursor);
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $users->fetch_paginated($cursor);

        $this->assertEquals($no_of_users, $total, 'wrong total count');
        $this->assertCount($page_size, $items, 'wrong current page count');
        $this->assertNotEmpty($enc_cursor, 'empty cursor');

        $retrieved = array_merge($retrieved, array_column($items, 'id'));

        // 3rd round.
        $cursor = cursor::decode($enc_cursor);
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = $users->fetch_paginated($cursor);

        $this->assertEquals($no_of_users, $total, 'wrong total count');
        $this->assertCount(1, $items, 'wrong current page count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');

        $retrieved = array_merge($retrieved, array_column($items, 'id'));

        // See if items were retrieved in the correct order.
        $this->assertEquals($user_ids, $retrieved, 'retrieved in wrong order');
    }

    public function test_with_multi_tenancy_enabled(): void {
        $tenant1_users = $this->create_tenanted_users(5);
        $tenant2_users = $this->create_tenanted_users(4);

        $this->setUser($tenant1_users->last());
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = users::create_active_users_provider()->fetch_paginated();

        $this->assertEquals($tenant1_users->count(), $total, 'wrong total count');
        $this->assertCount($tenant1_users->count(), $items, 'wrong current page count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertEqualsCanonicalizing(
            $tenant1_users->pluck('id'), array_column($items, 'id'), 'wrong retrievals'
        );

        $this->setUser($tenant2_users->last());
        [
            "items" => $items,
            "total" => $total,
            "next_cursor" => $enc_cursor
        ] = users::create_active_users_provider()->fetch_paginated();

        $this->assertEquals($tenant2_users->count(), $total, 'wrong total count');
        $this->assertCount($tenant2_users->count(), $items, 'wrong current page count');
        $this->assertEmpty($enc_cursor, 'non empty cursor');
        $this->assertEqualsCanonicalizing(
            $tenant2_users->pluck('id'), array_column($items, 'id'), 'wrong retrievals'
        );

    }

    /**
     * Generates 'normal' users.
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

            $users[] = new user($user);
        }

        return collection::new($users);
    }

    /**
     * Generates users in a tenancy.
     *
     * @param int $count no of users to generate.
     *
     * @return collection a list of user_entity objects
     */
    private function create_tenanted_users(int $count = 10): collection {
        $this->setAdminUser();
        $tenant_generator = tenant_generator::instance();
        $tenant_generator->enable_tenants();
        $tenant = new tenant($tenant_generator->create_tenant());

        $users = [];
        $generator = $this->getDataGenerator();
        foreach (range(0, $count - 1) as $i) {
            $user = $generator->create_user([
                'firstname' => 'Test',
                'lastname' => sprintf('User #%02d', $i),
                'tenantid' => $tenant->id
            ]);

            $users[] = new user($user);
        }

        return collection::new($users);
    }
}