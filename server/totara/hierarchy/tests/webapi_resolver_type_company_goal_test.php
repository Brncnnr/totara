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

use hierarchy_goal\entity\company_goal as company_goal_entity;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara hierarchy company goal type resolver.
 *
 * @group totara_hierarchy
 * @group totara_goal
 */
class totara_hierarchy_webapi_resolver_type_company_goal_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private const TYPE = 'totara_hierarchy_company_goal';

    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/company_goal/");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    public function test_invalid_field(): void {
        $goal = new company_goal_entity();
        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $goal);
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

        $testcases = [
            'id' => ['id', $goal->id],
            'short name' => ['short_name', $goal->shortname],
            'description' => ['description', $goal->description],
            'id number' => ['id_number', $goal->idnumber],
            'parent framework' => ['framework_id', $goal->frameworkid],
            'path' => ['path', $goal->path],
            'parent goal id' => ['parent_id', $goal->parentid],
            'visible' => ['visible', $goal->visible],
            'completion target date' => ['target_date', $goal->targetdate],
            'proficiency expected' => ['proficiency_expected', $goal->proficiencyexpected],
            'full name' => ['full_name', $goal->fullname],
            'goal type' => ['type_id', $goal->typeid]
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $expected] = $testcase;

            $value = $this->resolve_graphql_type(self::TYPE, $field, $goal, []);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }
}
