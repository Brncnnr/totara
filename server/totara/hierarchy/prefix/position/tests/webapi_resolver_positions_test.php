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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package hierarchy_position
 */

use hierarchy_position\data_providers\positions as positions_provider;
use totara_webapi\phpunit\webapi_phpunit_helper;

class hierarchy_position_webapi_resolver_positions_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        /** @var \totara_hierarchy\testing\generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        // Create some noise.
        for ($x = 1; $x <= 10; ++$x) {
            $framework = $gen->create_pos_frame([]);
            for ($y = 1; $y <= 5; ++$y) {
                $gen->create_pos([
                    'frameworkid' => $framework->id,
                    'fullname' => "position {$x}-{$y}"
                ]);
            }
        }
    }

    /**
     * Create a framework with positions.
     * @return array
     * @throws coding_exception
     */
    protected function create_framework_positions(): array {
        /** @var \totara_hierarchy\testing\generator $gen */
        $gen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $framework = $gen->create_pos_frame([]);
        $positions = [];

        // Create top level positions.
        for ($x = 1; $x <= 5; ++$x) {
            $positions[] = $gen->create_pos([
                'frameworkid' => $framework->id,
            ]);
        }

        // Create a position type.
        $typeid = $gen->create_pos_type();

        // Specific name and type.
        $positions[] = $gen->create_pos([
            'frameworkid' => $framework->id,
            'fullname' => 'noitasinagro',
            'typeid' => $typeid
        ]);

        // Give a position some children.
        $parent_id = $positions[2]->id;
        for ($x = 1; $x <= 10; ++$x) {
            $positions[] = $gen->create_pos([
                'frameworkid' => $framework->id,
                'parentid' => $parent_id,
                'fullname' => "Child position {$x}"
            ]);
        }

        return [
            'framework' => $framework,
            'positions' => $positions,
            'typeid' => $typeid,
        ];
    }

    /**
     * Test the following capabilities:
     *  - User with capability can view positions.
     *  - User without capability cannot view positions.
     */
    public function test_capability() {
        $gen = $this->getDataGenerator();

        // Create users.
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();

        $user1_context = \context_user::instance($user1->id);
        $this->set_capability(
            'totara/hierarchy:viewposition',
            CAP_ALLOW,
            $user1->id,
            $user1_context
        );

        $user2_context = \context_user::instance($user2->id);
        $this->set_capability(
            'totara/hierarchy:viewposition',
            CAP_PREVENT,
            $user2->id,
            $user2_context
        );

        // User should have access to positions.
        $this->setUser($user1->id);
        $result = $this->resolve_graphql_query('totara_hierarchy_positions');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(50, $result['total']);

        // User should not have access to positions.
        $this->setUser($user2->id);
        $this->expectException(required_capability_exception::class);
        $result = $this->resolve_graphql_query('totara_hierarchy_positions');
    }

    /**
     * Confirm that we get the correct amount of records back based on page size.
     */
    public function test_page_size() {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();

        $this->setUser($user->id);
        $result = (new positions_provider())
            ->set_page_size(3)
            ->fetch_paginated();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(50, $result['total']);

        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertEquals(3, sizeof($result['items']));
    }

    /**
     * Confirm that ordering works as expected.
     * This tests the data provider directly.
     */
    public function test_order_direct() {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user->id);

        // Ascending order.
        $result = (new positions_provider())
            ->set_order('id', 'asc')
            ->fetch_paginated();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertIsObject($result['items'][0]);
        $this->assertEquals('position 1-1', $result['items'][0]->fullname);

        // Descending order.
        $result = (new positions_provider())
            ->set_order('id', 'desc')
            ->fetch_paginated();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertIsObject($result['items'][0]);
        $this->assertEquals('position 10-5', $result['items'][0]->fullname);
    }

    /**
     * Confirm that ordering works as expected.
     * This tests the data via the graphql query.
     */
    public function test_order_graphql() {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user->id);

        $result = $this->resolve_graphql_query(
            'totara_hierarchy_positions',
            [
                'query' => [
                    'order_by' => 'id',
                    'order_dir' => 'ASC'
                ]
            ]
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertIsObject($result['items'][0]);
        $this->assertEquals('position 1-1', $result['items'][0]->fullname);

        $result = $this->resolve_graphql_query(
            'totara_hierarchy_positions',
            [
                'query' => [
                    'order_by' => 'id',
                    'order_dir' => 'DESC'
                ]
            ]
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertIsObject($result['items'][0]);
        $this->assertEquals('position 10-5', $result['items'][0]->fullname);
    }

    /**
     * Confirm that positions are filtered correctly.
     * This tests the data provider directly.
     */
    public function test_filters_direct() {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user->id);
        $context = \context_user::instance($user->id);

        $data = $this->create_framework_positions();
        $parent_id = $data['positions'][2]->id;

        // Parent ID.
        $result = (new positions_provider())
            ->set_filters([
                'parent_id' => $parent_id,
            ])
            ->fetch_paginated();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(10, $result['total']);
        $this->assertStringContainsString('Child position', $result['items'][0]->fullname);

        // IDS.
        $result = (new positions_provider())
            ->set_filters([
                'ids' => [$parent_id],
            ])
            ->fetch_paginated();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals($parent_id, $result['items'][0]->id);

        // Name.
        $result = (new positions_provider())
            ->set_filters([
                'name' => 'tasina'
            ])
            ->fetch_paginated();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('noitasinagro', $result['items'][0]->fullname);

        // Type.
        $result = (new positions_provider())
            ->set_filters([
                'type_id' => $data['typeid'],
            ])
            ->fetch_paginated();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('noitasinagro', $result['items'][0]->fullname);
    }

    /**
     * Confirm that positions are filtered correctly.
     * This tests the data via the graphql query.
     */
    public function test_filters_graphql() {
        $gen = $this->getDataGenerator();
        $user = $gen->create_user();
        $this->setUser($user->id);

        $data = $this->create_framework_positions();
        $parent_id = $data['positions'][2]->id;

        // All null.
        $result = $this->resolve_graphql_query(
            'totara_hierarchy_positions',
            []
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(66, $result['total']);

        // Parent ID.
        $result = $this->resolve_graphql_query(
            'totara_hierarchy_positions',
            [
                'query' => [
                    'filters' => [
                        'parent_id' => $parent_id,
                    ],
                ]
            ]
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(10, $result['total']);
        $this->assertStringContainsString('Child position', $result['items'][0]->fullname);

        // IDS.
        $result = $this->resolve_graphql_query(
            'totara_hierarchy_positions',
            [
                'query' => [
                    'filters' => [
                        'ids' => [$parent_id],
                    ],
                ]
            ]
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals($parent_id, $result['items'][0]->id);

        // Name.
        $result = $this->resolve_graphql_query(
            'totara_hierarchy_positions',
            [
                'query' => [
                    'filters' => [
                        'name' => 'tasina'
                    ],
                ]
            ]
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('noitasinagro', $result['items'][0]->fullname);

        // Type.
        $result = $this->resolve_graphql_query(
            'totara_hierarchy_positions',
            [
                'query' => [
                    'filters' => [
                        'type_id' => $data['typeid'],
                    ],
                ]
            ]
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('noitasinagro', $result['items'][0]->fullname);
    }

    /**
     * @param int $permission
     * @param int $userid
     * @param context $context
     *
     * @return void
     */
    private function set_capability(string $capability, int $permission, int $userid, context $context): void {
        $roles = get_archetype_roles('user');
        foreach ($roles as $role) {
            role_assign($role->id, $userid, $context->id);
            assign_capability($capability, $permission, $role->id, $context, true);
        }
    }
}
