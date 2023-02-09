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
use hierarchy_goal\entity\personal_goal as personal_goal_entity;
use hierarchy_goal\entity\scale_value as scale_value_entity;
use totara_hierarchy\testing\generator;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara hierarchy personal goal type resolver.
 *
 * @group totara_hierarchy
 * @group totara_goal
 */
class totara_hierarchy_webapi_resolver_type_personal_goal_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private const TYPE = 'totara_hierarchy_personal_goal';

    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/personal_goal/");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    public function test_invalid_field(): void {
        $goal = new personal_goal_entity();
        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $goal);
    }

    public function test_resolve(): void {
        $assignment = personal_goal_assignment_type::manager();
        $user_id = $this->getDataGenerator()->create_user()->id;

        $scale_values = [
            1 => ['name' => 'This', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            2 => ['name' => 'That', 'proficient' => 0, 'sortorder' => 2, 'default' => 0],
            3 => ['name' => 'Other', 'proficient' => 0, 'sortorder' => 3, 'default' => 1]
        ];
        $scale = generator::instance()
            ->create_scale('goal', ['name' => 'test_personal_goal_scale'], $scale_values);

        $scale_value = scale_value_entity::repository()
            ->where('name', 'This')
            ->one(true);

        $goal = new personal_goal_entity();
        $goal->id = 123;
        $goal->userid = $user_id;
        $goal->usercreated = $user_id;
        $goal->description = 'some desc';
        $goal->name = 'some name';
        $goal->targetdate = time();
        $goal->scaleid = $scale->id;
        $goal->scalevalueid = $scale_value->id;
        $goal->assigntype = $assignment->get_value();
        $goal->deleted = false;
        $goal->typeid = 0;
        $goal->visible = false;

        $method = assignment_type_extended::create_personal_goal_assignment_type(
            $assignment, $goal
        );

        $testcases = [
            'assignment type' => ['assignment_type', $method],
            'id' => ['id', $goal->id],
            'user id' => ['user_id', $goal->userid],
            'description' => ['description', $goal->description],
            'name' => ['name', $goal->name],
            'target date' => ['target_date', $goal->targetdate],
            'scale id' => ['scale_id', $goal->scaleid],
            'scale value id' => ['scale_value_id', $goal->scalevalueid],
            'scale value' => ['scale_value', $scale_value],
            'goal type' => ['typeid', $goal->typeid],
            'deleted' => ['deleted', $goal->deleted],
            'visible' => ['visible', $goal->visible],
            'goal type' => ['type_id', $goal->typeid]
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $expected] = $testcase;

            $value = $this->resolve_graphql_type(self::TYPE, $field, $goal, []);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }
}
