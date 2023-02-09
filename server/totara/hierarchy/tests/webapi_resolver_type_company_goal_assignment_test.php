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

use core\entity\user;

use hierarchy_goal\assignment_type_extended;
use hierarchy_goal\company_goal_assignment;
use hierarchy_goal\company_goal_assignment_type;
use hierarchy_goal\entity\company_goal as company_goal_entity;
use hierarchy_goal\entity\company_goal_assignment_type_extended;
use hierarchy_goal\entity\scale_value as scale_value_entity;

use totara_hierarchy\testing\generator;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara hierarchy company goals assignments type resolver.
 *
 * @group totara_hierarchy
 * @group totara_goal
 */
class totara_hierarchy_webapi_resolver_type_company_goal_assignment_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private const TYPE = 'totara_hierarchy_company_goal_assignment';

    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/company_goal_assignment/");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    public function test_invalid_field(): void {
        $user_id = $this->getDataGenerator()->create_user()->id;

        $assignment = new company_goal_assignment(
            123,
            new company_goal_entity(),
            new user($user_id),
            []
        );

        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $assignment);
    }

    public function test_resolve(): void {
        $goal = new company_goal_entity();
        $goal->id = 123;
        $goal->shortname = 'some short name';
        $goal->description = 'some desc';
        $goal->idnumber = 'some idnumber';
        $goal->frameworkid = 345;
        $goal->path = '1/2/2';
        $goal->parentid = 345;
        $goal->visible = false;
        $goal->targetdate = time();
        $goal->proficiencyexpected = 345;
        $goal->fullname = 'some full name';
        $goal->depthlevel = 2;
        $goal->typeid = 0;
        $goal->sortthread = 0;

        $user_id = $this->getDataGenerator()->create_user()->id;
        $assignment_type  = company_goal_assignment_type::individual();

        $source = new company_goal_assignment_type_extended();
        $source->id = 123;
        $source->assigntype = $assignment_type->get_value();
        $source->usermodified = $user_id;
        $source->goalid = 456;
        $source->userid = 4435;
        $source->extrainfo = '';

        $assignment_type_extended = assignment_type_extended::create_company_goal_assignment_type(
            $assignment_type, $source
        );

        $scale_values = [
            1 => ['name' => 'This', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            2 => ['name' => 'That', 'proficient' => 0, 'sortorder' => 2, 'default' => 0],
            3 => ['name' => 'Other', 'proficient' => 0, 'sortorder' => 3, 'default' => 1]
        ];
        generator::instance()
            ->create_scale('goal', ['name' => 'test_company_goal_scale'], $scale_values);

        $scale_value = scale_value_entity::repository()
            ->where('name', 'This')
            ->one(true);

        $assignment = new company_goal_assignment(
            3243,
            $goal,
            new user($user_id),
            [$assignment_type_extended],
            $scale_value
        );

        $testcases = [
            'id' => ['id', $assignment->get_id()],
            'user id' => ['user_id', $user_id],
            'assigned goal' => ['goal', $goal],
            'assignment methods' => ['assignment_types', $assignment->get_assignment_types()],
            'scale value' => ['scale_value', $scale_value]
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $expected] = $testcase;

            $value = $this->resolve_graphql_type(self::TYPE, $field, $assignment, []);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }
}
