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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_hierarchy
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara_hierarchy_goal_assignment_types resolver.
 *
 * @group totara_hierarchy
 * @group totara_goal
 */
class totara_hierarchy_webapi_resolver_query_goal_frameworks_testcase extends testcase {
    private const QUERY = 'totara_hierarchy_company_goal_frameworks';

    use webapi_phpunit_helper;

    public function test_require_login(): void {
        self::setUser(null);

        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_query(self::QUERY, []);
    }

    public function test_require_view_goal_frameworks_capability() {
        $user1 = self::getDataGenerator()->create_user();
        self::setUser($user1);

        $role_id = builder::table('role')->where('shortname', 'user')->value('id');
        unassign_capability('totara/hierarchy:viewgoalframeworks', $role_id);

        $this->expectException(required_capability_exception::class);
        $this->resolve_graphql_query(self::QUERY, []);
    }

    public function test_require_advanced_feature_goals() {
        self::setAdminUser();

        advanced_feature::disable('goals');

        $this->expectException(feature_not_available_exception::class);
        $this->resolve_graphql_query(self::QUERY, []);
    }

    public function test_empty_result(): void {
        self::setAdminUser();

        $actual_frameworks = $this->resolve_graphql_query(self::QUERY, []);

        self::assertEquals([], $actual_frameworks['items']);
    }

    public function test_result(): void {
        self::setAdminUser();

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $hierarchy_generator = self::getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework1_data = [
            'idnumber' => 'fw-1',
            'shortname' => 'FW1',
            'fullname' => 'Framework One',
        ];
        $framework1 = $hierarchy_generator->create_goal_frame($framework1_data);
        // Create company goal with goal framework 1
        $hierarchy_generator->create_goal([
            'fullname' => 'goal1',
            'frameworkid' => $framework1->id,
        ]);

        $framework2_data = [
            'idnumber' => 'fw-2',
            'shortname' => 'FW2',
            'fullname' => 'Framework Two',
        ];
        $framework2 = $hierarchy_generator->create_goal_frame($framework2_data);

        $expected_frameworks[$framework1->id] = $framework1_data;
        $expected_frameworks[$framework2->id] = $framework2_data;

        $actual_frameworks = $this->resolve_graphql_query(self::QUERY, [])['items'];
        self::assertCount(1, $actual_frameworks);
        foreach ($actual_frameworks as $actual_framework_data) {
            self::assertEqualsCanonicalizing($expected_frameworks[$actual_framework_data->id], [
                $actual_framework_data->idnumber,
                $actual_framework_data->shortname,
                $actual_framework_data->fullname,
            ]);
        }
    }
}
