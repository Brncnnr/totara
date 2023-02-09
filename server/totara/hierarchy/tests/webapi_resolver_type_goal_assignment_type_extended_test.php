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
use hierarchy_goal\company_goal_assignment_type;
use hierarchy_goal\personal_goal_assignment_type;
use hierarchy_goal\entity\company_goal_assignment_type_extended;
use hierarchy_goal\entity\personal_goal as personal_goal_entity;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the extended totara hierarchy goal assignment type resolver.
 *
 * @group totara_hierarchy
 * @group totara_goal
 */
class totara_hierarchy_webapi_resolver_type_goal_assignment_type_extended_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private const TYPE = 'totara_hierarchy_goal_assignment_type_extended';

    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/assignment_type_extended/");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    public function test_invalid_field(): void {
        $field = 'unknown';
        $type = new assignment_type_extended(
            personal_goal_assignment_type::manager(), "testing"
        );

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $type);
    }

    public function test_resolve_personal_assignment_type(): void {
        $assignment = personal_goal_assignment_type::manager();
        $user_id = $this->getDataGenerator()->create_user()->id;

        $goal = new personal_goal_entity();
        $goal->id = 123;
        $goal->userid = $user_id;
        $goal->usercreated = $user_id;
        $goal->name = 'some name';
        $goal->targetdate = time();
        $goal->scaleid = 345;
        $goal->scalevalueid = 345;
        $goal->assigntype = $assignment->get_value();
        $goal->deleted = false;
        $goal->typeid = 0;
        $goal->visible = false;

        $type = assignment_type_extended::create_personal_goal_assignment_type(
            $assignment, $goal
        );

        $testcases = [
            'assignment type' => ['type', $assignment],
            'assignment description' => ['description', $type->get_description()]
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $expected] = $testcase;

            $value = $this->resolve_graphql_type(self::TYPE, $field, $type, []);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }

    public function test_resolve_company_goal_assignment_type(): void {
        $assignment_type  = company_goal_assignment_type::individual();

        $assignment = new company_goal_assignment_type_extended();
        $assignment->id = 123;
        $assignment->assigntype = $assignment_type->get_value();
        $assignment->usermodified = $this->getDataGenerator()->create_user()->id;
        $assignment->goalid = 456;
        $assignment->userid = 4435;
        $assignment->extrainfo = '';

        $type = assignment_type_extended::create_company_goal_assignment_type(
            $assignment_type, $assignment
        );

        $testcases = [
            'assignment type' => ['type', $assignment_type],
            'assignment description' => ['description', $type->get_description()]
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $expected] = $testcase;

            $value = $this->resolve_graphql_type(self::TYPE, $field, $type, []);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }
}
