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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use core\orm\collection;
use core_phpunit\testcase;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\exception\access_denied_exception;
use mod_approval\exception\model_exception;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_approval\webapi\resolver\query\assignment_identifiers
 *
 * @group approval_workflow
 */
class mod_approval_webapi_query_assignment_identifiers_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private $query = 'mod_approval_assignment_identifiers';

    public function test_query_without_login() {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 'ORGANISATION'
        ];
        $this->setUser(0);
        $this->expectException('require_login_exception');
        $result = $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_as_guest() {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 'ORGANISATION'
        ];
        $this->setGuestUser();
        $this->expectException('require_login_exception');
        $result = $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_as_admin() {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 'ORGANISATION'
        ];
        $this->setAdminUser();
        $result = $this->resolve_graphql_query($this->query, $args);
        $this->assertInstanceOf(collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals($framework->agency->id, $result[$framework->agency->id]);
    }

    public function test_query_as_user() {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 'ORGANISATION'
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

        $result = $this->resolve_graphql_query($this->query, $args);
        $this->assertInstanceOf(collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertEquals($framework->agency->id, $result[$framework->agency->id]);
    }

    public function test_query_with_invalid_parameters() {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $this->setAdminUser();

        // Empty workflow.
        $args['input'] = [
            'workflow_id' => '',
            'assignment_type' => 'ORGANISATION'
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
            'assignment_type' => 'ORGANISATION'
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
            'assignment_type' => ''
        ];
        try {
            $result = $this->resolve_graphql_query($this->query, $args);
            $this->fail('Expected exception');
        } catch (model_exception $e) {
            $this->assertStringContainsString('Unknown assignment type enum', $e->getMessage());
        }

        // Invalid assignment_type.
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 1
        ];
        try {
            $result = $this->resolve_graphql_query($this->query, $args);
            $this->fail('Expected exception');
        } catch (model_exception $e) {
            $this->assertStringContainsString('Unknown assignment type enum', $e->getMessage());
        }
    }

    public function test_execute_query() {
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment('Testing', true);
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 'ORGANISATION'
        ];
        $this->setAdminUser();

        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $this->assertCount(5, $result);
        $expected = [
            $framework->agency->id,
            $framework->agency->subagency_a->id,
            $framework->agency->subagency_a->program_a->id,
            $framework->agency->subagency_a->program_b->id,
            $framework->agency->subagency_b->id
        ];
        $this->assertEqualsCanonicalizing($expected, $result);
    }

    public function test_query_excludes_archived_assignments() {
        /** @var workflow_entity $workflow */
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment('Testing', true);
        $args['input'] = [
            'workflow_id' => $workflow->id,
            'assignment_type' => 'ORGANISATION'
        ];
        $this->setAdminUser();

        // Archive sub agency b assignment
        /** @var mod_approval\entity\assignment\assignment $sub_agency_b_assignment */
        $sub_agency_b_assignment = $workflow->assignments->find('assignment_identifier', $framework->agency->subagency_b->id);
        $sub_agency_b_assignment->status = status::ARCHIVED;
        $sub_agency_b_assignment->save();

        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $this->assertCount(4, $result);
        $this->assertNotContains($framework->agency->subagency_b->id, $result);
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
    }

    public function test_execute_query_multiple_assignment_types() {
        list($workflow1, $framework1, $assignment1) = $this->create_workflow_and_assignment('Testing 1', true);
        list($workflow2, $framework2, $assignment2) = $this->create_workflow_and_assignment('Testing 2', true);
        $positions = $this->generate_pos_hierarchy();
        $audience = $this->getDataGenerator()->create_cohort();

        // Create some additional overrides on workflow2
        $workflow2_model = workflow::load_by_entity($workflow2);
        assignment::create(
            $workflow2_model->get_container(),
            assignment_type\position::get_code(),
            $positions->division->position_a->id
        );
        assignment::create(
            $workflow2_model->get_container(),
            assignment_type\cohort::get_code(),
            $audience->id
        );
        assignment::create(
            $workflow2_model->get_container(),
            assignment_type\position::get_code(),
            $positions->division->position_b->id
        );

        $this->setAdminUser();

        // Workflow1 - Organisation
        $args['input'] = [
            'workflow_id' => $workflow1->id,
            'assignment_type' => 'ORGANISATION'
        ];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertCount(5, $result);
        $expected = [
            $framework1->agency->id,
            $framework1->agency->subagency_a->id,
            $framework1->agency->subagency_a->program_a->id,
            $framework1->agency->subagency_a->program_b->id,
            $framework1->agency->subagency_b->id
        ];
        $this->assertEqualsCanonicalizing($expected, $result);

        // Workflow1 - Position
        $args['input'] = [
            'workflow_id' => $workflow1->id,
            'assignment_type' => 'POSITION'
        ];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertCount(0, $result);

        // Workflow1 - Cohort
        $args['input'] = [
            'workflow_id' => $workflow1->id,
            'assignment_type' => 'COHORT'
        ];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertCount(0, $result);

        // Workflow2 - Organisation
        $args['input'] = [
            'workflow_id' => $workflow2->id,
            'assignment_type' => 'ORGANISATION'
        ];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertCount(5, $result);
        $expected = [
            $framework2->agency->id,
            $framework2->agency->subagency_a->id,
            $framework2->agency->subagency_a->program_a->id,
            $framework2->agency->subagency_a->program_b->id,
            $framework2->agency->subagency_b->id
        ];
        $this->assertEqualsCanonicalizing($expected, $result);

        // Workflow2 - Position
        $args['input'] = [
            'workflow_id' => $workflow2->id,
            'assignment_type' => 'POSITION'
        ];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertCount(2, $result);
        $expected = [
            $positions->division->position_a->id,
            $positions->division->position_b->id
        ];
        $this->assertEqualsCanonicalizing($expected, $result);

        // Workflow2 - Cohort
        $args['input'] = [
            'workflow_id' => $workflow2->id,
            'assignment_type' => 'COHORT'
        ];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertCount(1, $result);
        $expected = [
            $audience->id
        ];
        $this->assertEquals($expected, $result);
    }
}
