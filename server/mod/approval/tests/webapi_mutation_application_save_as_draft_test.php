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
use mod_approval\model\application\action\submit;
use mod_approval\model\application\application;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\application_save_as_draft
 */
class mod_approval_webapi_mutation_application_save_as_draft_testcase extends mod_approval_testcase {
    private const MUTATION = 'mod_approval_application_save_as_draft';

    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_save_draft_application() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();
        $args['input']['form_data'] = '{"kia":"gasd","ora":"asldkh"}';
        ['application' => $result] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        /** @var application $result */
        $this->assertEquals($application->id, $result->id);
        $this->assertNull($result->submitted);
        $submission = $result->get_last_submission();
        $this->assertEquals('{"kia":"gasd","ora":"asldkh"}', $submission->form_data);
        // Update draft
        $args['input']['form_data'] = '{"ora":"kaha"}';
        ['application' => $result] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertEquals($application->id, $result->id);
        $this->assertNull($result->submitted);
        $this->assertEquals($submission->id, $result->get_last_submission()->id);
        $this->assertEquals('{"ora":"kaha"}', $result->get_last_submission()->form_data);
    }

    /**
     * @covers ::resolve
     */
    public function test_save_bad_form_data() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        [, $args] = $this->create_application_for_user_input();
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
    }

    /**
     * @covers ::resolve
     */
    public function test_save_submitted_application_fails() {
        $this->setAdminUser();
        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();

        // Mark the application as submitted.
        $formdata = form_data::from_json('{"kia":"ora"}');
        $submission = application_submission::create_or_update($application, user::logged_in()->id, $formdata);
        $submission->publish(user::logged_in()->id);
        submit::execute($application, user::logged_in()->id);

        // Try saving as draft while submitted.
        $args['input']['form_data'] = '{"kia":"kaha"}';
        self::expectException(model_exception::class);
        self::expectExceptionMessage("Can't save as draft because not unsubmitted");
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * @covers ::resolve
     */
    public function test_save_by_outsider() {
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
    public function test_save_by_foreign_tenant() {
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
    public function test_save_when_logout() {
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
    public function test_save_without_capability() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();
        $args['input']['form_data'] = '{"kia":"ora"}';

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Authenticated users have the capability to edit their own applications by default, so remove it.
        $roleid = builder::table('role')->where('shortname', 'user')->one(true)->id;
        assign_capability('mod/approval:edit_unsubmitted_application_applicant', CAP_PREVENT, $roleid, $application->get_context(), true);

        self::expectException(access_denied_exception::class);
        self::expectExceptionMessage('Cannot deal with application submission');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * @covers ::resolve
     */
    public function test_save_non_existing_application() {
        $args = [
            'input' => [
                'application_id' => 42,
                'form_data' => '{"kia":"ora"}'
            ]
        ];
        $this->setAdminUser();
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Invalid assignment');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_execute_query_successful_on_draft() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();
        $args['input']['form_data'] = '{"kia":"ora"}';
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $application_result = $result['application'] ?? null;
        $this->assertNotEmpty($application_result, 'result empty');
        $this->assertEquals($application->id, $application_result['id']);
    }

    public function test_execute_query_failed_on_submitted() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();
        $formdata = form_data::from_json('{"kia":"ora"}');
        $submission = application_submission::create_or_update($application, $user->id, $formdata);
        $submission->publish($user->id);
        submit::execute($application, $user->id);

        $args['input']['form_data'] = '{"kia":"kaha"}';
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Cannot deal with application submission');
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
