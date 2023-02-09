<?php
/**
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 */

use core_phpunit\testcase;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\generator as approval_generator;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 */
class mod_approval_webapi_query_approval_levels_testcase extends testcase {

    use approval_workflow_test_setup;
    use webapi_phpunit_helper;

    private const QUERY = 'mod_approval_workflow_stage';
    private const OPERATION = 'mod_approval_approval_levels';

    public function test_query_requires_logged_in_user() {
        $data = $this->generate_data();
        $this->setGuestUser();
        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_query(self::QUERY, $data->args);
    }

    public function test_query_success() {
        $data = $this->generate_data();

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $result = $this->parsed_graphql_operation(self::OPERATION, $data->args);
        $this->assert_webapi_operation_successful($result);
        $query_data = reset($result);
        $workflow_stage = $query_data['stage'];
        $approval_levels = $workflow_stage['approval_levels'];

        $this->assertCount(1, $approval_levels);

        $actual_approval_level = reset($approval_levels);
        $expected_approval_level = $data->approval_levels->first();
        $this->assertEquals($expected_approval_level->id, $actual_approval_level['id']);
        $this->assertEquals($expected_approval_level->name, $actual_approval_level['name']);
        $this->assertEquals($expected_approval_level->ordinal_number, $actual_approval_level['ordinal_number']);
    }

    public function test_query_failure() {
        $data = $this->generate_data();
        $this->setGuestUser();
        $result = $this->parsed_graphql_operation(self::OPERATION, $data->args);
        $this->assert_webapi_operation_failed($result);
    }

    private function generate_data(): stdClass {
        $data = new stdClass();
        $generator = approval_generator::instance();
        $workflow_entity = $generator->create_simple_request_workflow();
        $workflow = workflow::load_by_entity($workflow_entity);

        $data->workflow = $workflow_entity;

        $first_stage = $data->workflow->versions()->first()->stages()->first();
        $data->stage = $workflow->latest_version->get_next_stage($first_stage->id);
        $data->approval_levels = $data->stage->approval_levels;

        $data->args = [
            'input' => [
                'workflow_stage_id' => $data->stage->id,
            ],
        ];

        return $data;
    }
}
