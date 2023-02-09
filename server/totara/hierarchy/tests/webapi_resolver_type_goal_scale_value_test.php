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

use hierarchy_goal\assignment_type_extended;
use hierarchy_goal\personal_goal_assignment_type;
use hierarchy_goal\entity\scale_value as scale_value_entity;
use totara_hierarchy\testing\generator;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara hierarchy goal scale value type resolver.
 *
 * @group totara_hierarchy
 * @group totara_goal
 */
class totara_hierarchy_webapi_resolver_type_goal_scale_value_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private const TYPE = 'totara_hierarchy_goal_scale_value';

    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/scale_value/");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    public function test_invalid_field(): void {
        $scale_value = new scale_value_entity();
        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $scale_value);
    }

    public function test_resolve(): void {
        $scale_values = [
            1 => ['name' => 'This', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            2 => ['name' => 'That', 'proficient' => 0, 'sortorder' => 2, 'default' => 0],
            3 => ['name' => 'Other', 'proficient' => 0, 'sortorder' => 3, 'default' => 1]
        ];
        generator::instance()
            ->create_scale('goal', ['name' => 'test_personal_goal_scale'], $scale_values);

        $scale_value = scale_value_entity::repository()
            ->where('name', 'This')
            ->one(true);

        $testcases = [
            'id' => ['id', $scale_value->id],
            'name' => ['name', $scale_value->name]
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $expected] = $testcase;

            $value = $this->resolve_graphql_type(self::TYPE, $field, $scale_value, []);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }
}
