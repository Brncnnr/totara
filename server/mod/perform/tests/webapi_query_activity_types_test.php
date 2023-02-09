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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use core\webapi\execution_context;
use mod_perform\entity\activity\activity_type as activity_type_entity;
use mod_perform\models\activity\activity_type as activity_type_model;
use totara_core\advanced_feature;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\activity_types
 *
 * @group perform
 */
class mod_perform_webapi_query_activity_types_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_activity_types';

    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_find(): void {
        [$expected_types, $context] = $this->setup_env();

        $actual_types = $this->resolve_graphql_query('mod_perform_activity_types');

        $this->assertCount(count($expected_types), $actual_types, 'wrong count');
        $this->assertEqualsCanonicalizing($expected_types, $actual_types->all(), 'wrong types');
    }

    /**
     * @covers ::resolve
     */
    public function test_successful_ajax_call(): void {
        [$expected_types, $context] = $this->setup_env();

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_successful($result);

        $actual_types = $this->get_webapi_operation_data($result);
        $this->assertCount(count($expected_types), $actual_types, 'wrong count');

        foreach ($actual_types as $type) {
            $type_id = $type['id'] ?? null;
            $this->assertNotNull($type_id, 'no retrieved type id');
            $this->assertArrayHasKey($type_id, $expected_types, 'unknown type');

            $expected = $this->graphql_return($expected_types[$type_id], $context);
            $this->assertEquals($expected, $type, 'wrong graphql return');
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_query(): void {
        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'Feature performance_activities is not available.');
        advanced_feature::enable($feature);

        self::setGuestUser();
        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($result, 'permission');
    }

    /**
     * Generates test data.
     *
     * @param int $no_of_custom no of custom activity types to create.
     *
     * @return array (activity types, graphql execution context) tuple. Note the
     *         types include the system (predefined types) as well as the custom
     *         ones.
     */
    private function setup_env(int $no_of_custom = 2): array {
        self::setAdminUser();

        for ($i = 0; $i < $no_of_custom; $i++) {
            activity_type_model::create("my custom type #$i");
        }

        $types = activity_type_entity::repository()
            ->get()
            ->map_to(activity_type_model::class)
            ->all(true);

        $context = $this->create_webapi_context(self::QUERY);

        return [$types, $context];
    }

    /**
     * Given the input type, returns data the graphql call is supposed to
     * return.
     *
     * @param activity_type_model $type source type.
     * @param execution_context $context graphql execution context.
     *
     * @return array the expected graphql data values.
     */
    private function graphql_return(activity_type_model $type, execution_context $context): array {
        $type_resolve = function (string $field) use ($type, $context) {
            return $this->resolve_graphql_type('mod_perform_activity_type', $field, $type);
        };

        return [
            'id' => $type_resolve('id'),
            'name' => $type_resolve('name'),
            'display_name' => $type_resolve('display_name')
        ];
    }
}
