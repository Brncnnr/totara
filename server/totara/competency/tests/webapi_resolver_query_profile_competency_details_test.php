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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Marco Song <marco.song@totaralearning.com>
 * @package totara_competency
 */

global $CFG;
require_once $CFG->dirroot . '/totara/competency/tests/profile_query_resolver_test.php';

use totara_core\advanced_feature;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_query_profile_competency_details_testcase extends profile_query_resolver_test {
    /**
     * @inheritDoc
     */
    protected function get_query_name(): string {
        return 'totara_competency_profile_competency_details';
    }

    public function test_view_own_query_successful() {
        $data = $this->create_data();
        $this->setUser($data->user);
        $args = [
            'user_id' => $data->user->id,
            'competency_id' => $data->comp->id,
        ];
        $result = $this->resolve_graphql_query($this->get_query_name(), $args);
        $this->assertEquals($data->assignment->id, $result->assignments->first()->id);
        $this->assertEquals($data->comp->id, $result->competency->id);
    }

    public function test_view_other_query_successful() {
        $data = $this->create_data();
        $this->setUser($data->manager);
        $args = [
            'user_id' => $data->user->id,
            'competency_id' => $data->comp->id,
        ];

        $result = $this->resolve_graphql_query($this->get_query_name(), $args);
        $this->assertEquals($data->assignment->id, $result->assignments->first()->id);
        $this->assertEquals($data->comp->id, $result->competency->id);
    }

    /**
     * Test the query through the GraphQL stack.
     */
    public function test_ajax_query_successful() {
        $data = $this->create_data();
        $comp = $data->comp;

        $args = [
            'user_id' => $data->user->id,
            'competency_id' => $comp->id,
        ];

        $query = $this->get_query_name();
        $result = $this->parsed_graphql_operation($query, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertCount(1, $result['items']);

        $assignment = $data->assignment;
        $actual_assignment = $result['items'][0]['assignment'];
        $this->assertEquals($assignment->id, $actual_assignment['id']);
        $this->assertEquals($assignment->type, $actual_assignment['type']);
        $this->assertEquals($assignment->user_group_type, $actual_assignment['user_group_type']);

        $actual_comp = $result['competency'];
        $this->assertEquals($comp->id, $actual_comp['id']);
        $this->assertEquals($comp->fullname, $actual_comp['fullname']);
        $this->assertEquals($comp->description, $actual_comp['description']);

        // Competency ID of 0 is converted to null by typing core_id type of competency_id
        $args['competency_id'] = 0;
        $result = $this->parsed_graphql_operation($query, $args);
        $this->assert_webapi_operation_failed($result, 'Argument "competency_id" of non-null type "core_id!" must not be null.');

        // Invalid or unknown competency ids should return a successful but null result .
        $result = $this->get_webapi_operation_data($result);
        $this->assertNull($result);

        $args['competency_id'] = 2934;
        $result = $this->parsed_graphql_operation($query, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNull($result);
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_query(): void {
        $data = $this->create_data();
        $query = $this->get_query_name();
        $args = [
            'user_id' => $data->user->id,
            'competency_id' => $data->comp->id,
        ];

        $feature = 'competency_assignment';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation($query, $args);
        $this->assert_webapi_operation_failed($result, "Feature $feature is not available.");
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation($query, []);
        $this->assert_webapi_operation_failed($result, 'Variable "$user_id" of required type "core_id!" was not provided.');
    }
}