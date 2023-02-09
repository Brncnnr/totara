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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core\orm\query\exceptions\record_not_found_exception;
use core_phpunit\testcase;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\assignment\approver_type as approver_type;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 */
class mod_approval_webapi_query_load_workflow_testcase extends testcase {

    use approval_workflow_test_setup;
    use webapi_phpunit_helper;

    private const QUERY = 'mod_approval_load_workflow';

    public function test_query_requires_logged_in_user() {
        $data = $this->generate_data();
        $this->setGuestUser();
        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'workflow_id' => $data['workflow']->id
                ]
            ]
        );
    }

    public function test_query_as_user() {
        $this->setAdminUser();
        $data = $this->generate_data();

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        try {
            $this->resolve_graphql_query(
                self::QUERY,
                [
                    'input' => [
                        'workflow_id' => $data['workflow']->id
                    ]
                ]
            );
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Access denied to manage the workflows', $ex->getMessage());
        }
    }

    public function test_query_without_input_params() {
        $this->setAdminUser();
        $this->generate_data();

        $parsed_query = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($parsed_query);
    }

    public function test_query_success() {
        global $CFG;
        $data = $this->generate_data();
        $this->setAdminUser();

        /** @var workflow $workflow_model */
        $workflow_model = $data['workflow'];
        $result = $this->parsed_graphql_operation(
            self::QUERY,
            [
                'input' => [
                    'workflow_id' => $data['workflow']->id
                ]
            ]
        );
        $this->assert_webapi_operation_successful($result);
        $query_data = reset($result);

        $workflow_result = $query_data['workflow'];
        $this->assertEquals($workflow_model->workflow_type->name, $workflow_result['workflow_type']['name']);
        $this->assertEquals($workflow_model->id, $workflow_result['id']);
        $this->assertEquals($workflow_model->id_number, $workflow_result['id_number']);

        $approvers_result = $workflow_result['latest_version']['stages'][1]['approval_levels'][0]['approvers'];
        $this->assertCount(2, $approvers_result);
        usort($approvers_result, function ($x, $y) {
            return $x['type'] <=> $y['type'];
        });
        $this->assertEquals('RELATIONSHIP', $approvers_result[0]['type']);
        $this->assertEquals('Manager', $approvers_result[0]['approver_entity']['name']);
        $this->assertEquals('USER', $approvers_result[1]['type']);
        $this->assertEquals('Kunle Odusan', $approvers_result[1]['approver_entity']['name']);
        $this->assertStringStartsWith($CFG->wwwroot, $approvers_result[1]['approver_entity']['card_display']['profile_url']);

        $interactor_result = $workflow_result['interactor'];
        $this->assertTrue($interactor_result['can_edit']);
        $this->assertFalse($interactor_result['can_activate']);
        $this->assertTrue($interactor_result['can_clone']);
        $this->assertTrue($interactor_result['can_archive']);
        $this->assertFalse($interactor_result['can_unarchive']);
        $this->assertFalse($interactor_result['can_delete']);
        $this->assertTrue($interactor_result['can_upload_approver_overrides']);
    }

    public function test_query_with_unknown_workflow_id() {
        $this->generate_data();
        $this->expectException(record_not_found_exception::class);
        $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'workflow_id' => 878,
                ]
            ]
        );
    }

    private function generate_data(): array {
        $this->setAdminUser();
        list($workflow_entity, , $assignment_entity) = $this->create_workflow_and_assignment();
        $workflow = workflow::load_by_entity($workflow_entity);
        $assignment = assignment::load_by_entity($assignment_entity);
        $first_stage_id = $workflow->latest_version->stages->first()->id;
        /** @var workflow_stage_approval_level $level */
        $level = $workflow->latest_version->get_next_stage($first_stage_id)->approval_levels->first();
        $manager = totara_core\relationship\relationship::load_by_idnumber('manager');
        $user = $this->getDataGenerator()->create_user(
            ['firstname' => 'Kunle', 'lastname' => 'Odusan', 'username' => 'kunle.odusan']
        );
        assignment_approver::create($assignment, $level, approver_type\relationship::TYPE_IDENTIFIER, $manager->id);
        assignment_approver::create($assignment, $level, approver_type\user::TYPE_IDENTIFIER, $user->id);

        return [
            'workflow' => $workflow,
        ];
    }
}
