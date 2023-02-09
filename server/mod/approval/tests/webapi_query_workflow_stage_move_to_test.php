<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 */

use core\orm\query\exceptions\record_not_found_exception;
use core_phpunit\testcase;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 */
class mod_approval_webapi_query_workflow_stage_move_to_testcase extends testcase {

    use approval_workflow_test_setup;
    use webapi_phpunit_helper;

    private const QUERY = 'mod_approval_workflow_stage_move_to';

    public function test_query_requires_logged_in_user() {
        $data = $this->generate_data();
        $this->setGuestUser();
        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'workflow_stage_id' => $data['workflow']->latest_version->stages->first()->id
                ]
            ]
        );
    }

    public function test_query_as_user() {
        $this->setAdminUser();
        $data = $this->generate_data();
        /** @var workflow $workflow_model */
        $workflow_model = $data['workflow'];
        $stage = $workflow_model->latest_version->stages->first();

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        try {
            $this->resolve_graphql_query(
                self::QUERY,
                [
                    'input' => [
                        'workflow_stage_id' => $stage->id
                    ]
                ]
            );

            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot manage transition in this workflow', $ex->getMessage());
        }
    }

    public function test_query_without_input_params() {
        $this->setAdminUser();
        $this->generate_data();

        $parsed_query = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($parsed_query);
    }

    public function test_query_success() {
        $data = $this->generate_data();
        $this->setAdminUser();
        /** @var workflow $workflow_model */
        $workflow_model = $data['workflow'];
        $stage = $workflow_model->latest_version->stages->first();

        $result = $this->parsed_graphql_operation(
            self::QUERY,
            [
                'input' => [
                    'workflow_stage_id' => $stage->id
                ]
            ]
        );

        $this->assert_webapi_operation_successful($result);
        $query_data = reset($result);
        $this->assertCount(4, $query_data['options']);
        $result_next = $query_data['options']['0'];
        $result_stage3 = $query_data['options']['3'];
        $this->assertEquals('Next', $result_next['name']);
        $this->assertEquals($workflow_model->latest_version->stages->last()->id, $result_stage3['value']);
    }

    public function test_query_with_unknown_workflow_stage_id() {
        $this->generate_data();
        $this->expectException(record_not_found_exception::class);
        $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'workflow_stage_id' => 878,
                ]
            ]
        );
    }

    private function generate_data(): array {
        $this->setAdminUser();
        list($workflow_entity, , $assignment_entity) = $this->create_workflow_and_assignment();

        return [
            'workflow' => workflow::load_by_entity($workflow_entity),
        ];
    }
}
