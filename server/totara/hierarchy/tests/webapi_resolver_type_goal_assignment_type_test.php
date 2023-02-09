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

use hierarchy_goal\company_goal_assignment_type;
use hierarchy_goal\personal_goal_assignment_type;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara hierarchy goal assignment type resolver.
 *
 * @group totara_hierarchy
 * @group totara_goal
 */
class totara_hierarchy_webapi_resolver_type_goal_assignment_type_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private const TYPE = 'totara_hierarchy_goal_assignment_type';

    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/assignment_type/");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    public function test_invalid_field(): void {
        $assignment = company_goal_assignment_type::individual();
        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $assignment);
    }

    public function test_resolve(): void {
        $assignment = personal_goal_assignment_type::self();

        $testcases = [
            'name' => ['name', $assignment->get_name()],
            'display ' => ['label', $assignment->get_label()],
            'value' => ['value', $assignment->get_value()]
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $expected] = $testcase;

            $value = $this->resolve_graphql_type(self::TYPE, $field, $assignment, []);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }
}
