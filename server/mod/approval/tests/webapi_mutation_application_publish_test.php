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

use core\entity\tenant;
use core\entity\user;
use core\orm\query\builder;
use mod_approval\exception\access_denied_exception;
use mod_approval\exception\malicious_form_data_exception;
use mod_approval\exception\model_exception;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\application;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_version;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\application_publish
 */
class mod_approval_webapi_mutation_application_publish_testcase extends mod_approval_testcase {
    private const MUTATION = 'mod_approval_application_publish';

    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_publish_submission() {
        $this->setAdminUser();
        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();

        // Mark the application submitted, so it is in-approvals.
        $formdata = form_data::from_json('{"kia":"hello"}');
        $submission = application_submission::create_or_update($application, user::logged_in()->id, $formdata);
        $submission->publish(user::logged_in()->id);
        submit::execute($application, user::logged_in()->id);

        // Make a new submission.
        $args['input']['form_data'] = '{"kia":"ora"}';
        ['application' => $result] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        /** @var application $result */
        $this->assertEquals($application->id, $result->id);
        $this->assertNotNull($result->submitted);
        $second_submission_id = $result->get_last_submission()->id;
        $this->assertNotEquals($submission->id, $second_submission_id);
        $this->assertEquals('{"kia":"ora"}', $result->get_last_submission()->form_data);

        // Another submission from the same user will NOT replace the previous one, it will be a new submission.
        $args['input']['form_data'] = '{"kia":"kaha"}';
        ['application' => $result] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertEquals($application->id, $result->id);
        $this->assertNotNull($result->submitted);
        $third_submission_id = $result->get_last_submission()->id;
        $this->assertNotEquals($second_submission_id, $third_submission_id);
        $this->assertEquals('{"kia":"kaha"}', $result->get_last_submission()->form_data);
    }

    /**
     * @covers ::resolve
     */
    public function test_publish_bad_form_data() {
        $this->setAdminUser();
        [$application, $args] = $this->create_application_for_user_input();

        // Mark the application submitted, so it is in-approvals.
        $formdata = form_data::from_json('{"kia":"hello"}');
        $submission = application_submission::create_or_update($application, user::logged_in()->id, $formdata);
        $submission->publish(user::logged_in()->id);
        submit::execute($application, user::logged_in()->id);

        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('form_data is required', $ex->getMessage());
        }
        try {
            $args['input']['form_data'] = '';
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('form_data is required', $ex->getMessage());
        }
        try {
            $args['input']['form_data'] = 'baa';
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('malicious_form_data_exception expected');
        } catch (malicious_form_data_exception $ex) {
            $this->assertStringContainsString('malicious form data', $ex->getMessage());
        }
        try {
            $args['input']['form_data'] = '[]';
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('malicious_form_data_exception expected');
        } catch (malicious_form_data_exception $ex) {
            $this->assertStringContainsString('malicious form data', $ex->getMessage());
        }
        // This checks that validation is required - the main difference between this and save_as_draft.
        try {
            $args['input']['form_data'] = '{"ora":"3"}';
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('malicious_form_data_exception expected');
        } catch (malicious_form_data_exception $ex) {
            $this->assertStringContainsString('Required field(s) are not set: kia', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_publish_unsubmitted_application() {
        $this->setAdminUser();
        /** @var application $application */
        [, $args] = $this->create_application_for_user_input();
        $args['input']['form_data'] = '{"kia":"kaha"}';
        self::expectException(model_exception::class);
        self::expectExceptionMessage("Can't publish because not in-approvals");
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * @covers ::resolve
     */
    public function test_publish_by_outsider() {
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        [, $args] = $this->create_application_for_user_input();
        $args['input']['form_data'] = '{"kia":"ora"}';
        $this->setUser($user2);
        self::expectException(access_denied_exception::class);
        self::expectExceptionMessage('Cannot deal with application submission');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * @covers ::resolve
     */
    public function test_publish_by_foreign_tenant() {
        /** @var $tengen \totara_tenant\testing\generator $tenant_generator */
        $tengen = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tengen->enable_tenants();

        $ten1 = new tenant($tengen->create_tenant());
        $ten2 = new tenant($tengen->create_tenant());

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $ten1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $ten2->id]);
        $this->setUser($user1);
        [, $args] = $this->create_application_for_user_input();
        $args['input']['form_data'] = '{"kia":"ora"}';
        $this->setUser($user2);
        self::expectException(access_denied_exception::class);
        self::expectExceptionMessage('Cannot access assignment');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * @covers ::resolve
     */
    public function test_publish_when_logout() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        [, $args] = $this->create_application_for_user_input();
        $args['input']['form_data'] = '{"kia":"ora"}';
        $this->setUser();
        self::expectException(require_login_exception::class);
        self::expectExceptionMessage('You are not logged in');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * @covers ::resolve
     */
    public function test_publish_without_capability() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();
        $args['input']['form_data'] = '{"kia":"ora"}';

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Authenticated users have the capability to edit applications where they are the applicant by default, so remove it.
        $roleid = builder::table('role')->where('shortname', 'user')->one(true)->id;
        assign_capability('mod/approval:edit_unsubmitted_application_applicant', CAP_PREVENT, $roleid, $application->get_context(), true);

        self::expectException(access_denied_exception::class);
        self::expectExceptionMessage('Cannot deal with application submission');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * @covers ::resolve
     */
    public function test_publish_with_submitted_capability() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();
        $formdata = form_data::from_json('{"kia":"ora"}');
        $submission = application_submission::create_or_update($application, $user->id, $formdata);
        $submission->publish($user->id);
        submit::execute($application, $user->id);
        $args['input']['form_data'] = '{"kia":"ora"}';

        // Authenticated users have the capability to edit applications where they are the applicant by default, so remove it.
        $roleid = builder::table('role')->where('shortname', 'user')->one(true)->id;
        assign_capability(
            'mod/approval:edit_unsubmitted_application_applicant',
            CAP_PREVENT,
            $roleid,
            $application->get_context(),
            true
        );
        assign_capability(
            'mod/approval:edit_first_approval_level_application_applicant',
            CAP_ALLOW,
            $roleid,
            $application->get_context(),
            true
        );

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $application_result = $result['application'] ?? null;
        $this->assertNotEmpty($application_result, 'result empty');
        $this->assertEquals($application->id, $application_result['id']);
    }

    /**
     * @covers ::resolve
     */
    public function test_save_with_in_approvals_capability() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();
        $formdata = form_data::from_json('{"kia":"ora"}');
        $submission = application_submission::create_or_update($application, $user->id, $formdata);
        $submission->publish($user->id);
        submit::execute($application, $user->id);
        $args['input']['form_data'] = '{"kia":"ora"}';

        // Authenticated users have the capability to edit applications where they are the applicant by default, so remove it.
        $roleid = builder::table('role')->where('shortname', 'user')->one(true)->id;
        assign_capability('mod/approval:edit_unsubmitted_application_applicant', CAP_PREVENT, $roleid, $application->get_context(), true);
        assign_capability('mod/approval:edit_in_approvals_application_applicant', CAP_ALLOW, $roleid, $application->get_context(), true);

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $application_result = $result['application'] ?? null;
        $this->assertNotEmpty($application_result, 'result empty');
        $this->assertEquals($application->id, $application_result['id']);
    }

    /**
     * Tests save and not reset approvals without the privilege to
     *
     * @covers ::resolve
     */
    public function test_save_and_reset_approval_levels_without_capability() {
        $user = $this->create_user();
        $this->setUser($user);

        $application = $this->create_application_for_user();

        // Mark the application submitted.
        $form_data = form_data::from_json('{"kia":"ora"}');
        $submission = application_submission::create_or_update($application, $user->id, $form_data);
        $submission->publish($user->id);
        submit::execute($application, $user->id);

        // Save application without reset approvals.
        $args = [
            'input' => [
                'form_data' => '{"kia":"hello world"}',
                'application_id' => $application->id,
                'keep_approvals' => true
            ],
        ];

        $role_id = builder::table('role')->where('shortname', 'user')->one(true)->id;
        assign_capability(
            'mod/approval:edit_in_approvals_application_applicant',
            CAP_ALLOW,
            $role_id,
            $application->get_context(),
            true
        );
        assign_capability(
            'mod/approval:edit_without_invalidating_approvals_applicant',
            CAP_PREVENT,
            $role_id,
            $application->get_context(),
            true
        );

        $this->expectException(access_denied_exception::class);
        $this->expectExceptionMessage(access_denied_exception::submission()->getMessage());
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * Tests save and not reset approvals with the privilege to
     *
     * @covers ::resolve
     */
    public function test_save_and_not_reset_approval_levels() {
        $user = $this->create_user();
        $this->setUser($user);

        $application = $this->create_application_for_user(null, [$this, 'setup_workflow_for_save_and_reset_approval_levels']);
        $stage1 = $application->current_stage;
        $stage2 = $application->workflow_version->get_next_stage($stage1->id);
        $first_approval_level = $stage2->approval_levels->first();
        $second_approval_level = $stage2->feature_manager->approval_levels->get_next($first_approval_level->id);

        // Mark the application submitted.
        $form_data = form_data::from_json('{"kia":"ora"}');
        $submission = application_submission::create_or_update($application, $user->id, $form_data);
        $submission->publish($user->id);
        submit::execute($application, $user->id);

        // Approve level 1.
        approve::execute($application, $user->id);
        $this->assertEquals($second_approval_level->id, $application->current_state->get_approval_level_id());

        // Save application without reset approvals.
        $this->setAdminUser();
        $args = [
            'input' => [
                'application_id' => $application->id,
                'form_data' => '{"kia":"hello world"}',
                'keep_approvals' => true
            ],
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        // Still on second approval level.
        $application->refresh();
        $this->assertEquals($second_approval_level->id, $application->current_state->get_approval_level_id());
    }

    /**
     * Tests save and reset approvals with the privilege to
     *
     * @covers ::resolve
     */
    public function test_save_and_reset_approval_levels() {
        $user = $this->create_user();
        $this->setUser($user);

        $application = $this->create_application_for_user(null, [$this, 'setup_workflow_for_save_and_reset_approval_levels']);
        $stage1 = $application->current_stage;
        $stage2 = $application->workflow_version->get_next_stage($stage1->id);
        $first_approval_level = $stage2->approval_levels->first();
        $second_approval_level = $stage2->feature_manager->approval_levels->get_next($first_approval_level->id);

        // Mark the application submitted.
        $form_data = form_data::from_json('{"kia":"ora"}');
        $submission = application_submission::create_or_update($application, $user->id, $form_data);
        $submission->publish($user->id);
        submit::execute($application, $user->id);

        // Approve level 1.
        approve::execute($application, $user->id);
        $this->assertEquals($second_approval_level->id, $application->current_state->get_approval_level_id());

        // Publish application without reset approvals.
        $this->setAdminUser();
        $args = [
            'input' => [
                'form_data' => '{"kia":"hello world"}',
                'application_id' => $application->id,
                'keep_approvals' => false
            ],
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);
        $application->refresh();

        // Reset to first approval level.
        $this->assertEquals($first_approval_level->id, $application->current_state->get_approval_level_id());
    }

    public function setup_workflow_for_save_and_reset_approval_levels(workflow_version $workflow_version) {
        $form_stage = workflow_stage::create($workflow_version, 'stage 1', form_submission::get_enum());
        workflow_stage_formview::create($form_stage, 'kia', true, false, 'KIA');
        workflow_stage_formview::create($form_stage, 'ora', false, false, 'ORA');

        $approval_stage = workflow_stage::create($workflow_version, 'stage 2', approvals::get_enum());
        $approval_stage->add_approval_level('level 2');

        workflow_stage_formview::create($approval_stage, 'kia', true, false, 'KIA');
        workflow_stage_formview::create($approval_stage, 'ora', false, false, 'ORA');

        workflow_stage::create($workflow_version, 'stage 3', finished::get_enum());
    }

    /**
     * @covers ::resolve
     */
    public function test_save_non_existing_application() {
        $args = [
            'input' => [
                'form_data' => '{"kia":"ora"}',
                'application_id' => 42,
            ]
        ];
        $this->setAdminUser();
        self::expectException('moodle_exception');
        self::expectExceptionMessage('Invalid assignment');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_execute_query_unsuccessful_on_draft() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        /** @var application $application */
        [, $args] = $this->create_application_for_user_input();

        $args['input']['form_data'] = '{"kia":"ora"}';
        self::expectException(model_exception::class);
        self::expectExceptionMessage("Can't publish because not in-approvals");
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_execute_query_successful_on_submitted() {
        $this->setAdminUser();
        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();

        // Mark the application submitted.
        $formdata = form_data::from_json('{"kia":"ora"}');
        $submission = application_submission::create_or_update($application, user::logged_in()->id, $formdata);
        $submission->publish(user::logged_in()->id);
        submit::execute($application, user::logged_in()->id);

        $args['input']['form_data'] = '{"kia":"ora"}';
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $interactor = [
            'can_edit' => true,
            'can_withdraw' => true,
            'can_delete' => false,
            'can_clone' => true,
            'can_edit_without_invalidating' => true
        ];
        $application_result = $result['application'] ?? null;
        $this->assertNotEmpty($application_result, 'result empty');
        $this->assertEquals($application->id, $application_result['id']);
        $this->assertEquals($interactor, $application_result['interactor']);
    }

    public function test_failed_ajax_call() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        [, $args] = $this->create_application_for_user_input();
        $args['input']['form_data'] = '{"kia":"ora"}';

        $this->setGuestUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Course or activity not accessible. (Must be an authenticated user)');
    }
}
