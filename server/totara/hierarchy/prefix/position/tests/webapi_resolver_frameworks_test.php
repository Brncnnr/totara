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

use totara_webapi\phpunit\webapi_phpunit_helper;

class hierarchy_position_webapi_resolver_position_frameworks_testcase extends advanced_testcase {

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
            'totara/hierarchy:viewpositionframeworks',
            CAP_ALLOW,
            $user1->id,
            $user1_context
        );

        $user2_context = \context_user::instance($user2->id);
        $this->set_capability(
            'totara/hierarchy:viewpositionframeworks',
            CAP_PREVENT,
            $user2->id,
            $user2_context
        );

        // User should have access to position frameworks.
        $this->setUser($user1->id);
        $this->resolve_graphql_query('totara_hierarchy_position_frameworks');

        // User should not have access to position frameworks.
        $this->setUser($user2->id);
        $this->expectException(required_capability_exception::class);
        $this->resolve_graphql_query('totara_hierarchy_position_frameworks');
    }

    /**
     * Confirm that we get the correct amount of records back.
     */
    public function test_frameworks() {
        global $DB;

        $gen = $this->getDataGenerator();
        $user = $gen->create_user();

        // Only a manager has capability to view position frameworks so we need
        // to make the user a manager.
        $context = \context_user::instance($user->id);
        $manager_role = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        role_assign($manager_role, $user->id, $context->id);
        $this->setUser($user->id);

        // Test direct querying.
        $hierarchy = new \hierarchy();
        $hierarchy->shortprefix = 'pos';
        $result = $hierarchy->get_frameworks();
        $this->assertIsArray($result);
        $this->assertEquals(10, sizeof($result));

        // Test via graphql.
        $result = $this->resolve_graphql_query('totara_hierarchy_position_frameworks');
        $this->assertIsArray($result);
        $this->assertEquals(10, sizeof($result));
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
