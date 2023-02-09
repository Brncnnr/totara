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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

use core_phpunit\testcase;
use mod_approval\exception\access_denied_exception;
use mod_approval\exception\model_exception;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_approval\webapi\resolver\query\override_for_assignment_type
 *
 * @group approval_workflow
 */
class mod_approval_webapi_query_override_for_assignment_type_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private $query = 'mod_approval_override_for_assignment_type';

    public function test_query_without_login() {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 'ORGANISATION',
            'assignment_identifier' => $assignment->assignment_identifier
        ];
        $this->setUser(0);
        $this->expectException('require_login_exception');
        $result = $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_as_guest() {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 'ORGANISATION',
            'assignment_identifier' => $assignment->assignment_identifier
        ];
        $this->setGuestUser();
        $this->expectException('require_login_exception');
        $result = $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_as_admin() {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 'ORGANISATION',
            'assignment_identifier' => $assignment->assignment_identifier
        ];
        $this->setAdminUser();
        $query_data = $this->resolve_graphql_query($this->query, $args);
        $this->assertEquals($assignment->id, $query_data->id);

        // If no workflow_assignment for particular type and identifier return null
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 'ORGANISATION',
            'assignment_identifier' => 56
        ];
        $result = $this->resolve_graphql_query($this->query, $args);
        $this->assertEmpty($result);
    }

    public function test_query_as_user() {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 'ORGANISATION',
            'assignment_identifier' => $assignment->assignment_identifier
        ];
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        try {
            $result = $this->resolve_graphql_query($this->query, $args);
            $this->fail('Expected access denied exception');
        } catch (access_denied_exception $e) {
            $this->assertStringContainsString("Cannot manage assignment overrides", $e->getMessage());
        }

        // Now assign the necessary capability to the user.
        $sys_context = context_system::instance();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('mod/approval:manage_workflow_assignment_overrides', CAP_ALLOW, $roleid, $sys_context);
        role_assign($roleid, $user->id, $sys_context);

        $query_data = $this->resolve_graphql_query($this->query, $args);
        $this->assertEquals($assignment->id, $query_data->id);
    }

    public function test_query_with_invalid_parameters() {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $this->setAdminUser();

        // Empty workflow.
        $args['input'] = [
            'workflow_id' => '',
            'assignment_type' => 'ORGANISATION',
            'assignment_identifier' => $assignment->assignment_identifier
        ];
        try {
            $result = $this->resolve_graphql_query($this->query, $args);
            $this->fail('Expected invalid workflow_id exception');
        } catch (moodle_exception $e) {
            $this->assertStringContainsString('invalid workflow_id', $e->getMessage());
        }

        // Invalid workflow.
        $args['input'] = [
            'workflow_id' => -1,
            'assignment_type' => 'ORGANISATION',
            'assignment_identifier' => $assignment->assignment_identifier
        ];
        try {
            $result = $this->resolve_graphql_query($this->query, $args);
            $this->fail('Expected record not found exception');
        } catch (moodle_exception $e) {
            $this->assertStringContainsString('Can not find data record in database', $e->getMessage());
        }

        // Empty assignment_type.
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => '',
            'assignment_identifier' => $assignment->assignment_identifier
        ];
        try {
            $result = $this->resolve_graphql_query($this->query, $args);
            $this->fail('Expected exception');
        } catch (invalid_parameter_exception $e) {
            $this->assertStringContainsString('Invalid assignment parameters, assignment type is required', $e->getMessage());
        }

        // Invalid assignment_type.
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 1,
            'assignment_identifier' => $assignment->assignment_identifier
        ];
        try {
            $result = $this->resolve_graphql_query($this->query, $args);
            $this->fail('Expected exception');
        } catch (model_exception $e) {
            $this->assertStringContainsString('Unknown assignment type enum: 1', $e->getMessage());
        }

        // Invalid assignment_identifier.
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 1,
            'assignment_identifier' => ''
        ];
        try {
            $result = $this->resolve_graphql_query($this->query, $args);
            $this->fail('Expected exception');
        } catch (invalid_parameter_exception $e) {
            $this->assertStringContainsString('Invalid assignment parameters, assignment identifier is required', $e->getMessage());
        }
    }

    public function test_execute_query() {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment('Testing', true);
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 'ORGANISATION',
            'assignment_identifier' => $assignment->assignment_identifier
        ];
        $this->setAdminUser();

        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $this->assertEquals($assignment->id, $result['id']);
        $this->assertEquals("Agency", $result['name']);
    }

    public function test_execute_query_failing_with_missing_parameters() {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment('Testing', true);
        $this->setAdminUser();

        // Missing workflow_id.
        $args['input'] = [
            'assignment_type' => 'ORGANISATION'
        ];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_failed($result);
        $this->assertStringEndsWith('Field value.workflow_id of required type core_id! was not provided.', $result[1]);

        // Missing assignment_type.
        $args['input'] = [
            'workflow_id' => $workflow->id,
        ];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_failed($result);
        $this->assertStringEndsWith('Field value.assignment_type of required type mod_approval_assignment_type! was not provided.', $result[1]);

        // Missing assignment_identifier.
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 'ORGANISATION'
        ];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_failed($result);
        $this->assertStringEndsWith('Field value.assignment_identifier of required type core_id! was not provided.', $result[1]);
    }
}