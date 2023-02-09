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

use core\entity\user;
use core\orm\query\builder;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\application;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\application_delete
 */
class mod_approval_webapi_mutation_application_delete_testcase extends mod_approval_testcase {
    private const MUTATION = 'mod_approval_application_delete';

    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_delete() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        [, $args] = $this->create_application_for_user_input();
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertTrue($result);
    }

    /**
     * @covers ::resolve
     */
    public function test_delete_twice() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        [, $args] = $this->create_application_for_user_input();
        $this->resolve_graphql_mutation(self::MUTATION, $args);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('moodle_exception');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Invalid assignment', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_delete_submitted() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();
        $formdata = form_data::from_json('{"kia":"ora"}');
        $submission = application_submission::create_or_update($application, (new user($user))->id, $formdata);
        $submission->publish( $user->id);
        submit::execute($application, $user->id);

        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('coding_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot access this application', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_delete_with_required_field_missing() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $args['input'] = [
            'application_id' => ''
        ];
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('Invalid parameter value detected (invalid application id)', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_delete_without_capability() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();

        // Users have the capability to delete applications where they created the application by default, so remove it.
        $roleid = builder::table('role')->where('shortname', 'user')->one(true)->id;
        assign_capability('mod/approval:delete_draft_application_owner', CAP_PREVENT, $roleid, $application->get_context(), true);

        self::expectException(access_denied_exception::class);
        self::expectExceptionMessage('Cannot access this application');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    /**
     * @covers ::resolve
     */
    public function test_delete_non_existing_application() {
        $args['input'] = [
            'application_id' => 42,
        ];
        $this->setAdminUser();
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('moodle_exception');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Invalid assignment', $ex->getMessage());
        }
    }

    public function test_execute_query_successful() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        [, $args] = $this->create_application_for_user_input();

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');
        $this->assertTrue($result);
    }

    public function test_execute_query_successful_with_submission() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $application = $this->create_application_for_user();
        /** @var application $application */
        $submission = application_submission::create_or_update($application, $application->user_id, form_data::from_json('{}'));
        $this->assertNotNull($application->last_submission);

        $args['input'] = [
            'application_id' => $application->id
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');
        $this->assertTrue($result);
    }

    public function test_execute_query_failed_on_already_submitted() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();
        $formdata = form_data::from_json('{"kia":"ora"}');
        $submission = application_submission::create_or_update($application, (new user($user))->id, $formdata);
        $submission->publish( $user->id);
        submit::execute($application, $user->id);

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Cannot access this application');
    }

    public function test_failed_ajax_call() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        [, $args] = $this->create_application_for_user_input();

        $this->setGuestUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Course or activity not accessible. (Must be an authenticated user)');
    }
}
