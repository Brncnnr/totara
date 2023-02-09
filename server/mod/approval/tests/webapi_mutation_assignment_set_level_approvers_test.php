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
 * @package mod_approval
 */

use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use core_phpunit\testcase;
use mod_approval\exception\access_denied_exception;
use mod_approval\exception\model_exception;
use mod_approval\entity\workflow\workflow_stage_approval_level as approval_level_entity;
use mod_approval\model\assignment\approver_type\relationship;
use mod_approval\model\assignment\approver_type\user;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approval_level;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\generator as mod_approval_generator;
use totara_core\entity\relationship as relationship_entity;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\assignment_set_level_approvers
 */
class mod_approval_webapi_mutation_assignment_set_level_approvers_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private $query = 'mod_approval_assignment_set_level_approvers';

    /**
     * Gets the approval workflow generator instance
     *
     * @return mod_approval_generator
     */
    protected function generator(): mod_approval_generator {
        return mod_approval_generator::instance();
    }

    public function test_set_level_approvers_for_default_assignment(): void {
        $data = $this->setup_assignments();
        $args = $this->build_args(
            $data['approval_level_entity']->id,
            $data['default_assignment']->id,
            $data['user']->id,
            $data['manager_relationship']->id
        );

        // Test with user without capability.
        $this->setUser($data['user']);
        try {
            $this->resolve_graphql_mutation($this->query, $args);
            $this->fail('Expected exception not thrown');
        } catch (access_denied_exception $e) {
            $this->assertStringContainsString('User cannot update the assignment approvers', $e->getMessage());
        }

        $this->setAdminUser();

        $result = $this->resolve_graphql_mutation($this->query, $args);
        $this->assertTrue($result['success']);
    }

    public function test_cannot_change_level_individual_approvers_for_default_assignment(): void {
        $data = $this->setup_assignments();
        $default_assignment_model = assignment::load_by_entity($data['default_assignment']);

        $user_role = builder::table('role')->where('shortname', 'user')->one();

        // Making changes with only manage_relationship_workflow_approvers capability.
        assign_capability(
            'mod/approval:manage_individual_workflow_approvers',
            CAP_PREVENT,
            $user_role->id,
            $default_assignment_model->workflow->get_context()->id,
            true
        );
        assign_capability(
            'mod/approval:manage_relationship_workflow_approvers',
            CAP_ALLOW,
            $user_role->id,
            $default_assignment_model->workflow->get_context()->id,
            true
        );

        $args = $this->build_args(
            $data['approval_level_entity']->id,
            $data['default_assignment']->id,
            $data['user']->id,
            $data['manager_relationship']->id
        );

        $this->setUser($data['user']);
        $this->expectException(access_denied_exception::class);
        $this->expectExceptionMessage('User cannot update individual approvers for assignment');
        $this->resolve_graphql_mutation($this->query, $args);
    }

    public function test_cannot_change_level_relationship_approvers_for_default_assignment(): void {
        $data = $this->setup_assignments();
        $default_assignment_model = assignment::load_by_entity($data['default_assignment']);

        $user_role = builder::table('role')->where('shortname', 'user')->one();

        // Making changes with only manage_relationship_workflow_approvers capability.
        assign_capability(
            'mod/approval:manage_individual_workflow_approvers',
            CAP_ALLOW,
            $user_role->id,
            $default_assignment_model->workflow->get_context()->id,
            true
        );
        assign_capability(
            'mod/approval:manage_relationship_workflow_approvers',
            CAP_PREVENT,
            $user_role->id,
            $default_assignment_model->workflow->get_context()->id,
            true
        );

        $args = $this->build_args(
            $data['approval_level_entity']->id,
            $data['default_assignment']->id,
            $data['user']->id,
            $data['manager_relationship']->id
        );

        $this->setUser($data['user']);
        $this->expectException(access_denied_exception::class);
        $this->expectExceptionMessage('User cannot update relationship approvers for assignment');
        $this->resolve_graphql_mutation($this->query, $args);
    }

    public function test_set_level_approvers_for_override_assignment(): void {
        $data = $this->setup_assignments();

        // Test without capability.
        $this->setUser($data['user']->id);
        $args = $this->build_args(
            $data['approval_level_entity']->id,
            $data['override_assignments'][0]->id,
            $data['user']->id,
            $data['manager_relationship']->id
        );

        try {
            $this->resolve_graphql_mutation($this->query, $args);
            $this->fail('Expected exception not thrown');
        } catch (access_denied_exception $e) {
            $this->assertStringContainsString('User cannot update the assignment approvers', $e->getMessage());
        }

        // Test with user having capabilities.
        $this->setAdminUser();
        $result = $this->resolve_graphql_mutation($this->query, $args);
        $this->assertTrue($result['success']);
    }

    public function test_set_level_approvers_on_override_that_had_been_on_default(): void {
        $data = $this->setup_assignments();
        $default_assignment = assignment::load_by_entity($data['default_assignment']);
        $first_override = assignment::load_by_entity($data['override_assignments'][0]);
        $approval_level = workflow_stage_approval_level::load_by_entity($data['approval_level_entity']);

        $default_assignment_approval_level = new assignment_approval_level($default_assignment, $approval_level);
        $first_override_approval_level = new assignment_approval_level($first_override, $approval_level);

        $args = $this->build_args(
            $approval_level->id,
            $default_assignment->id,
            $data['user']->id,
            $data['manager_relationship']->id
        );
        $result = $this->resolve_graphql_mutation($this->query, $args);
        $this->assertTrue($result['success']);
        $this->assertCount(2, $default_assignment_approval_level->get_approvers());
        $this->assertCount(2, $default_assignment_approval_level->get_approvers_with_inheritance());
        $this->assertCount(0, $first_override_approval_level->get_approvers());
        $this->assertCount(2, $first_override_approval_level->get_approvers_with_inheritance());

        // Remove the approvers from default assignment.
        $args['input']['approvers'] = [];
        $result = $this->resolve_graphql_mutation($this->query, $args);
        $this->assertTrue($result['success']);

        // Make sure there are no active approvers on the default assignment.
        $this->assertCount(0, $default_assignment_approval_level->get_approvers());
        $this->assertCount(0, $default_assignment_approval_level->get_approvers_with_inheritance());
        $this->assertCount(0, $first_override_approval_level->get_approvers());
        $this->assertCount(0, $first_override_approval_level->get_approvers_with_inheritance());

        // Now put the same approvers on the first override.
        $args = $this->build_args(
            $approval_level->id,
            $first_override->id,
            $data['user']->id,
            $data['manager_relationship']->id
        );
        $result = $this->resolve_graphql_mutation($this->query, $args);
        $this->assertTrue($result['success']);

        $this->assertCount(0, $default_assignment_approval_level->get_approvers());
        $this->assertCount(0, $default_assignment_approval_level->get_approvers_with_inheritance());
        $this->assertCount(2, $first_override_approval_level->get_approvers());
        $this->assertCount(2, $first_override_approval_level->get_approvers_with_inheritance());
    }

    public function test_query_without_login(): void {
        $data = $this->setup_assignments();
        $args = $this->build_args(
            $data['approval_level_entity']->id,
            $data['default_assignment']->id,
            $data['user']->id,
            $data['manager_relationship']->id
        );
        $this->setUser(0);

        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('You are not logged in');
        $this->resolve_graphql_mutation($this->query, $args);
    }

    public function test_query_as_guest(): void {
        $data = $this->setup_assignments();
        $args = $this->build_args(
            $data['approval_level_entity']->id,
            $data['default_assignment']->id,
            $data['user']->id,
            $data['manager_relationship']->id
        );
        $this->setGuestUser();

        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('Must be an authenticated user');
        $this->resolve_graphql_mutation($this->query, $args);
    }

    public function test_query_as_admin(): void {
        $data = $this->setup_assignments();
        $assignment_model = assignment::load_by_entity($data['default_assignment']);
        $args = $this->build_args(
            $data['approval_level_entity']->id,
            $data['default_assignment']->id,
            $data['user']->id,
            $data['manager_relationship']->id
        );
        $this->setAdminUser();

        $result = $this->resolve_graphql_mutation($this->query, $args);
        $this->assertTrue($result['success']);

        // Check role
        $approverroleid = builder::table('role')->where('shortname', 'approvalworkflowapprover')->value('id');
        $this->assertTrue(user_has_role_assignment($data['user']->id, $approverroleid, $assignment_model->get_context()->id));
    }

    public function test_query_with_invalid_parameters(): void {
        $data = $this->setup_assignments();

        // Invalid approval level.
        $fail_args = $this->build_args(
            123,
            $data['default_assignment']->id,
            $data['user']->id,
            $data['manager_relationship']->id
        );
        try {
            $this->resolve_graphql_mutation($this->query, $fail_args);
            $this->fail('Exception not triggered');
        } catch (record_not_found_exception $e) {
        }

        // Invalid assignment_id.
        $fail_args = $this->build_args(
            $data['approval_level_entity']->id,
            123,
            $data['user']->id,
            $data['manager_relationship']->id
        );
        try {
            $this->resolve_graphql_mutation($this->query, $fail_args);
            $this->fail('Exception not triggered');
        } catch (moodle_exception $e) {
            $this->assertStringContainsString('Invalid assignment', $e->getMessage());
        }

        // Unknown user id for user approver relationship.
        $fail_args = $this->build_args(
            $data['approval_level_entity']->id,
            $data['default_assignment']->id,
            123,
            $data['manager_relationship']->id
        );
        try {
            $this->resolve_graphql_mutation($this->query, $fail_args);
            $this->fail('Exception not triggered');
        } catch (model_exception $e) {
            $this->assertStringContainsString('Invalid assignment_approver identifier', $e->getMessage());
        }

        // Unknown relationship id for relationsip approver relationship.
        $fail_args = $this->build_args(
            $data['approval_level_entity']->id,
            $data['default_assignment']->id,
            $data['user']->id,
            123
        );
        try {
            $this->resolve_graphql_mutation($this->query, $fail_args);
            $this->fail('Exception not triggered');
        } catch (model_exception $e) {
            $this->assertStringContainsString('Invalid assignment_approver identifier', $e->getMessage());
        }

        // Unknown assignment approver type.
        $fail_args = $this->build_args(
            $data['approval_level_entity']->id,
            $data['default_assignment']->id,
            $data['user']->id,
            $data['manager_relationship']->id
        );
        $fail_args['input']['approvers'][0]['assignment_approver_type'] = 123;
        try {
            $this->resolve_graphql_mutation($this->query, $fail_args);
            $this->fail('Exception not triggered');
        } catch (model_exception $e) {
            $this->assertStringContainsString('Unknown approver type provided', $e->getMessage());
        }
    }

    public function test_parsed_graphql_operation(): void {
        $data = $this->setup_assignments();
        $args = $this->build_args(
            $data['approval_level_entity']->id,
            $data['default_assignment']->id,
            $data['user']->id,
            $data['manager_relationship']->id
        );

        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);
    }

    public function test_query_without_input_params(): void {
        $this->setAdminUser();
        $parsed_query = $this->parsed_graphql_operation($this->query, []);
        $this->assert_webapi_operation_failed($parsed_query);
    }

    private function setup_assignments(): array {
        $this->setAdminUser();
        [
            $workflow,
            $framework,
            $default_assignment,
            $override_assignments
        ] = $this->create_workflow_and_assignment('Testing', true);
        $user = $this->getDataGenerator()->create_user();

        /** @var approval_level_entity $approval_level_entity */
        $approval_level_entity = approval_level_entity::repository()->one();
        /** @var relationship_entity $manager_relationship */
        $manager_relationship = relationship_entity::repository()->where("idnumber", "=", "manager")->one(true);

        return [
            'approval_level_entity' => $approval_level_entity,
            'default_assignment' => $default_assignment,
            'override_assignments' => $override_assignments,
            'user' => $user,
            'manager_relationship' => $manager_relationship
        ];
    }

    /**
     * Turn a bunch of objects into input arguments.
     *
     * @param $approval_level_entity_id
     * @param $user_id
     * @param $manager_relationship_id
     * @return array[]
     */
    private function build_args(
        $approval_level_entity_id,
        $assignment_id,
        $user_id,
        $manager_relationship_id
    ): array {
        return [
            'input' => [
                'approval_level_id' => $approval_level_entity_id,
                'assignment_id' => $assignment_id,
                'approvers' => [
                    [
                        'assignment_approver_type' => user::get_enum(),
                        'identifier' => $user_id,
                    ],
                    [
                        'assignment_approver_type' => relationship::get_enum(),
                        'identifier' => $manager_relationship_id,
                    ],
                ],
            ],
        ];
    }

}