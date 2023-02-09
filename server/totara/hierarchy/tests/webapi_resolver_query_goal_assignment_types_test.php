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

use hierarchy_goal\assignment_type;
use hierarchy_goal\company_goal_assignment_type;
use hierarchy_goal\personal_goal_assignment_type;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara_hierarchy_goal_assignment_types resolver.
 *
 * @group totara_hierarchy
 * @group totara_goal
 */
class totara_hierarchy_webapi_resolver_query_goal_assignment_types_testcase extends advanced_testcase {
    private const QUERY = 'totara_hierarchy_goal_assignment_types';

    use webapi_phpunit_helper;

    public function test_find(): void {
        $this->setAdminUser();

        $args = ['input' => ['scope' => 'PERSONAL']];
        $expected_types = personal_goal_assignment_type::all();
        $actual_types = $this->resolve_graphql_query(self::QUERY, $args);

        $this->assertEquals($expected_types->count(), $actual_types->count(), 'wrong count');
        $this->assertEqualsCanonicalizing($expected_types->all(), $actual_types->all(), 'wrong types');
    }

    public function test_successful_ajax_call(): void {
        $this->setAdminUser();

        $args = ['input' => ['scope' => 'COMPANY']];
        $expected_types = company_goal_assignment_type::all()
            ->map(
                function (assignment_type $type): array {
                    return $this->graphql_return($type);
                }
            )
            ->key_by('name');

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $actual_types = $this->get_webapi_operation_data($result);
        $this->assertCount($expected_types->count(), $actual_types, 'wrong count');

        foreach ($actual_types as $type) {
            $type_name = $type['name'] ?? null;
            $this->assertNotNull($type_name, 'no retrieved type name');

            $expected = $expected_types->item($type_name);
            $this->assertNotNull($expected, 'unknown type name');
            $this->assertEquals($expected, $type, 'wrong graphql return');
        }
    }

    public function test_failed_ajax_query(): void {
        $this->setAdminUser();

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'no goal scope provided');

        $args = ['input' => ['scope' => 'UNK']];
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'invalid value');

        $feature = 'goals';
        advanced_feature::disable($feature);
        $args = ['input' => ['scope' => 'COMPANY']];
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Feature goals is not available.');
        advanced_feature::enable($feature);

        self::setUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'logged in');
    }

    /**
     * Given the input type, returns data the graphql call is supposed to
     * return.
     *
     * @param assignment_type $type source type.
     *
     * @return array the expected graphql data values.
     */
    private function graphql_return(assignment_type $type): array {
        $type_resolve = function (string $field) use ($type) {
            return $this->resolve_graphql_type('totara_hierarchy_goal_assignment_type', $field, $type);
        };

        return [
            'value' => $type_resolve('value'),
            'name' => $type_resolve('name'),
            'label' => $type_resolve('label')
        ];
    }
}
