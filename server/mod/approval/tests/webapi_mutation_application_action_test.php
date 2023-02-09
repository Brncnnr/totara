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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use core\orm\query\builder;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\application\action\action;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\action\withdraw_before_submission;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\application\application;
use mod_approval\model\application\application_state;
use mod_approval\model\application\application_submission;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\form\form_data;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\application_action
 */
class mod_approval_webapi_mutation_application_action_testcase extends mod_approval_testcase {
    private const MUTATION = 'mod_approval_application_action';

    use webapi_phpunit_helper;

    /** @var user */
    private $user;

    /** @var user */
    private $approver;

    /** @var user */
    private $pariah;

    public function setUp(): void {
        parent::setUp();
        $this->user = new user($this->getDataGenerator()->create_user(['username' => 'user']));
        $this->approver = new user($this->getDataGenerator()->create_user(['username' => 'approver']));
        $this->pariah = new user($this->getDataGenerator()->create_user(['username' => 'pariah']));
        $this->setUser($this->user);
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->user = $this->approver = $this->pariah = null;
    }

    /**
     * @return array
     */
    private function create_application_with_submission_for_user_input(): array {
        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();
        $form_data = form_data::from_json('{"kia":"ora"}');
        $submission = application_submission::create_or_update($application, user::logged_in()->id, $form_data);
        $roleid = builder::table('role')->where('shortname', 'approvalworkflowapprover')->one(true)->id;

        // Not default capability for approver role
        assign_capability(
            'mod/approval:withdraw_in_approvals_application_any',
            CAP_ALLOW,
            $roleid,
            $application->get_context(),
            true
        );

        // Set approver for the first level
        $stage1 = $application->workflow_version->stages->first();
        $stage2 = $application->workflow_version->get_next_stage($stage1->id);
        $approval_level = $stage2->get_approval_levels()->first();
        $application->assignment->set_approvers_for_level(
            $approval_level,
            [['assignment_approver_type' => user_approver_type::TYPE_IDENTIFIER, 'identifier' => $this->approver->id]]
        );

        $submission->publish(user::logged_in()->id);
        submit::execute($application, user::logged_in()->id);
        return [$application, $args];
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_actions(): void {
        /** @var application $application */
        [$application, $args] = $this->create_application_with_submission_for_user_input();
        $original_state = $application->current_state;
        $this->setAdminUser();
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('Missing action', $ex->getMessage());
        }
        try {
            $args['input']['action'] = '>.<';
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('Invalid action', $ex->getMessage());
        }
        try {
            $this->resolve_graphql_mutation(self::MUTATION, ['input' => ['action' => 'APPROVE']]);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid application id', $ex->getMessage());
        }
        $application->refresh();
        $this->assertTrue($application->current_state->is_same_as($original_state));
    }

    /**
     * @covers ::resolve
     * @covers mod_approval\model\application\application_action::create
     */
    public function test_approved_by_approver(): void {
        /** @var application $application */
        [$application, $args] = $this->create_application_with_submission_for_user_input();
        $args['input']['action'] = 'APPROVE';
        $this->setUser($this->approver);
        ['application' => $result] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        /** @var application $application */
        /** @var application $result */
        $application->refresh();
        $this->assertEquals($application->id, $result->id);
        $this->assertEquals(approve::get_code(), $result->actions->first()->code);
        $this->assertTrue($result->current_state->is_stage_type(finished::get_code()));
    }

    /**
     * @covers ::resolve
     * @covers mod_approval\model\application\application_action::create
     */
    public function test_approved_by_pariah(): void {
        [$application, $args] = $this->create_application_with_submission_for_user_input();
        $args['input']['action'] = 'APPROVE';
        $this->setUser($this->pariah);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot take an action', $ex->getMessage());
        }
        $application->refresh();
        $this->assertTrue($application->current_state->is_stage_type(approvals::get_code()));
    }

    /**
     * @covers ::resolve
     * @covers mod_approval\model\application\application_action::create
     */
    public function test_rejected_by_approver(): void {
        [$application, $args] = $this->create_application_with_submission_for_user_input();
        $args['input']['action'] = 'REJECT';
        $this->setUser($this->approver);
        ['application' => $result] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        /** @var application $application */
        /** @var application $result */
        $this->assertEquals($application->id, $result->id);
        $application->refresh();
        $this->assertTrue($application->current_state->is_stage_type(form_submission::get_code()));
        $this->assertFalse($application->current_state->is_draft());
        $this->assertEquals(reject::get_code(), $result->actions->first()->code);
    }

    /**
     * @covers ::resolve
     * @covers mod_approval\model\application\application_action::create
     */
    public function test_rejected_by_pariah(): void {
        [$application, $args] = $this->create_application_with_submission_for_user_input();
        $args['input']['action'] = 'REJECT';
        $this->setUser($this->pariah);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot take an action', $ex->getMessage());
        }
        $application->refresh();
        $this->assertTrue($application->current_state->is_stage_type(approvals::get_code()));
    }

    /**
     * @covers ::resolve
     * @covers mod_approval\model\application\application_action::create
     */
    public function test_withdrawn_on_rejected(): void {
        $this->setUser($this->user);
        /** @var application $application */
        [$application, $args] = $this->create_application_with_submission_for_user_input();
        reject::execute($application, $this->approver->id);

        $application->refresh(true);
        $this->assertTrue($application->current_state->is_stage_type(form_submission::get_code()));
        $this->assertFalse($application->current_state->is_draft());
        $this->assertNull($application->current_state->get_approval_level_id());
        $this->assertEquals(reject::get_code(), $application->last_action->code);
        $this->assertEquals('Level 1', $application->last_action->approval_level->name);

        $args['input']['action'] = 'WITHDRAW_BEFORE_SUBMISSION';
        $this->resolve_graphql_mutation(self::MUTATION, $args);

        $application->refresh(true);
        $this->assertTrue($application->current_state->is_stage_type(form_submission::get_code()));
        $this->assertFalse($application->current_state->is_draft());
        $this->assertNull($application->current_state->get_approval_level_id());
        $this->assertEquals(withdraw_before_submission::get_code(), $application->last_action->code);
        $this->assertNull($application->last_action->approval_level);
    }

    /**
     * @covers ::resolve
     * @covers mod_approval\model\application\application_action::create
     */
    public function test_withdrawn_on_invalid_state(): void {
        /** @var application $application */
        list($application, $args) = $this->create_application_with_submission_for_user_input();
        $final_stage = $application->get_next_stage();
        $application->set_current_state(new application_state($final_stage->id));
        self::assertTrue($application->current_state->is_stage_type(finished::get_code()));
        $args['input']['action'] = 'WITHDRAW_IN_APPROVALS';
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot take an action', $ex->getMessage());
        }
        $this->setUser($this->approver);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot take an action', $ex->getMessage());
        }
    }

    /**
     * @return array
     */
    public function data_withdrawn_by_withdrawable(): array {
        return [
            'applicant' => [0],
            'approver' => [1],
        ];
    }

    /**
     * @param integer $who
     * @dataProvider data_withdrawn_by_withdrawable
     * @covers ::resolve
     * @covers mod_approval\model\application\application_action::create
     */
    public function test_withdrawn_by_withdrawable(int $who): void {
        [$application, $args] = $this->create_application_with_submission_for_user_input();
        $args['input']['action'] = 'WITHDRAW_IN_APPROVALS';
        $this->setUser([$this->user, $this->approver][$who]);
        ['application' => $result] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        /** @var application $application */
        /** @var application $result */
        $this->assertEquals($application->id, $result->id);
        $application->refresh();
        $this->assertTrue($application->current_state->is_stage_type(form_submission::get_code()));
        $this->assertFalse($application->current_state->is_draft());
        $this->assertEquals(withdraw_in_approvals::get_code(), $result->actions->first()->code);
    }

    /**
     * @covers ::resolve
     * @covers mod_approval\model\application\application_action::create
     */
    public function test_withdrawn_by_pariah(): void {
        /** @var application $application */
        [$application, $args] = $this->create_application_with_submission_for_user_input();
        $args['input']['action'] = 'WITHDRAW_IN_APPROVALS';
        $this->setUser($this->pariah);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot take an action', $ex->getMessage());
        }
        $application->refresh();
        $this->assertTrue($application->current_state->is_stage_type(approvals::get_code()));
        $original_stage_id = $application->current_state->get_stage_id();
        $application->set_current_state(new application_state($original_stage_id, true));
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot take an action', $ex->getMessage());
        }
        $application->refresh();
        $this->assertEquals($original_stage_id, $application->current_state->get_stage_id());
        $this->assertTrue($application->current_state->is_draft());
    }

    /**
     * @return array
     */
    public function data_double_actions(): array {
        return [
            'approve to approve' => [approve::class, approve::class],
            'approve to reject' => [approve::class, reject::class],
            'approve to withdraw_in_approvals' => [approve::class, withdraw_in_approvals::class],
            'reject to approve' => [reject::class, approve::class],
            'reject to reject' => [reject::class, reject::class],
            'withdraw_in_approvals to approve' => [withdraw_in_approvals::class, approve::class],
            'withdraw_in_approvals to reject' => [withdraw_in_approvals::class, reject::class],
            'withdraw_in_approvals to withdraw_in_approvals' => [withdraw_in_approvals::class, withdraw_in_approvals::class],
        ];
    }

    /**
     * @param string|action $from_action
     * @param string|action $to_action
     * @dataProvider data_double_actions
     * @covers ::resolve
     * @covers mod_approval\model\application\application_action::create
     */
    public function test_double_actions_fail(string $from_action, string $to_action): void {
        // Create the application.
        $this->setAdminUser();
        $admin_id = user::logged_in()->id;
        /** @var application $application */
        [$application, $args] = $this->create_application_with_submission_for_user_input();

        // Perform the first action.
        $from_action::execute($application, $admin_id);

        // Perform the second action.
        $args['input']['action'] = $to_action::get_enum();
        self::expectException(access_denied_exception::class);
        self::expectExceptionMessage('Cannot take an action');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_execute_query_successful_on_approval(): void {
        [$application, $args] = $this->create_application_with_submission_for_user_input();
        $args['input']['action'] = 'APPROVE';
        $this->setUser($this->approver);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $application_result = $result['application'] ?? null;
        $this->assertNotEmpty($application_result, 'result empty');
        $this->assertEquals($application->id, $application_result['id']);
        $application->refresh();
        $this->assertEquals('FINISHED', $application->overall_progress);
    }

    public function test_execute_query_successful_on_rejection(): void {
        [$application, $args] = $this->create_application_with_submission_for_user_input();
        $args['input']['action'] = 'REJECT';
        $this->setUser($this->approver);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $application_result = $result['application'] ?? null;
        $this->assertNotEmpty($application_result, 'result empty');
        $this->assertEquals($application->id, $application_result['id']);
        $application->refresh();
        $this->assertEquals('REJECTED', $application->overall_progress);
    }

    public function test_execute_query_successful_on_withdrawal(): void {
        [$application, $args] = $this->create_application_with_submission_for_user_input();
        $args['input']['action'] = 'WITHDRAW_IN_APPROVALS';
        $this->setUser($this->approver);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $application_result = $result['application'] ?? null;
        $this->assertNotEmpty($application_result, 'result empty');
        $this->assertEquals($application->id, $application_result['id']);
        $application->refresh();
        $this->assertEquals('WITHDRAWN', $application->overall_progress);
    }

    public function test_execute_query_failed(): void {
        [, $args] = $this->create_application_for_user_input();
        $args['input']['action'] = 'APPROVE';
        $this->setAdminUser();

        $result = $this->parsed_graphql_operation(self::MUTATION, $args['input']);
        $this->assert_webapi_operation_failed($result, 'required type "mod_approval_application_action_input!" was not provided');

        $args['input']['action'] = 'APRROVE';
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'did you mean APPROVE?');
    }

    public function test_failed_ajax_call() {
        [, $args] = $this->create_application_for_user_input();
        $args['input']['action'] = 'APPROVE';
        $this->setGuestUser();

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Course or activity not accessible. (Must be an authenticated user)');
    }
}
