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
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\exception\access_denied_exception;
use mod_approval\exception\model_exception;
use mod_approval\model\application\application;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_version;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\application_clone
 */
class mod_approval_webapi_mutation_application_clone_testcase extends mod_approval_testcase {
    private const MUTATION = 'mod_approval_application_clone';

    use webapi_phpunit_helper;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * @return array of [submission_model, args]
     */
    private function create_submission_for_user_input(): array {
        /** @var application $application */
        [$application, $args] = $this->create_application_for_user_input();
        workflow_version_entity::repository()->where('id', $application->workflow_version_id)
            ->update(['status' => status::DRAFT]);

        $another_stage = workflow_stage::create($application->workflow_version->refresh(), 'another stage', form_submission::get_enum());
        workflow_stage_formview::create($another_stage, 'test_field', true, false, 'XYZ');
        $application->workflow_version->activate();
        $submission1 = application_submission::create_or_update(
            $application,
            user::logged_in()->id,
            form_data::from_json('{"kia":"ora"}')
        );
        $this->application_update_stage_and_level_silently(
            $application,
            $another_stage->id,
            $application->current_state->get_approval_level_id()
        );
        $submission2 = application_submission::create_or_update(
            $application,
            user::logged_in()->id,
            form_data::from_json('{"test_field":"kaha"}')
        );
        $application->refresh(true);

        $this->assertEquals('{"test_field":"kaha"}', $application->last_submission->form_data);
        return [$submission1, $args];
    }

    /**
     * @covers ::resolve
     */
    public function test_clone_with_capability(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        [$submission, $args] = $this->create_submission_for_user_input();
        ['application' => $result] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        /** @var application_submission $submission */
        /** @var application $result */
        $this->assertNotEquals($submission->application->id, $result->id);
        $this->assertNull($result->last_submission->submitted);
        $this->assertEquals('{"kia":"ora"}', $result->last_submission->form_data);
    }

    /**
     * @covers ::resolve
     */
    public function test_clone_with_second_workflow_version(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        [$submission, $args] = $this->create_submission_for_user_input();

        /** @var application_submission $submission */
        $application = $submission->application;
        // Create other workflow_version in DRAFT state
        $new_workflow_version = workflow_version::create($application->workflow_version->workflow, $application->form_version);
        workflow_stage::create($new_workflow_version, 'New Stage', form_submission::get_enum());

        // Archived the old version
        $old_workflow_version_model = workflow_version::load_by_id($application->workflow_version->id);
        $old_workflow_version_model->archive();

        // Cannot perform cloning as we don't have active version
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot perform clone', $ex->getMessage());
        }
        // Activate new version
        $new_workflow_version->activate();

        // Perform cloning
        ['application' => $result] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        /** @var application $result */
        $this->assertEquals($new_workflow_version->id, $result->workflow_version->id);
        $this->assertEquals($new_workflow_version->stages->first()->id, $result->current_stage->id);
        $this->assertEquals('New Stage', $result->current_stage->name);
    }

    /**
     * @covers ::resolve
     */
    public function test_clone_without_capability(): void {
        $this->setUser($this->getDataGenerator()->create_user());
        [, $args] = $this->create_submission_for_user_input();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot perform clone', $ex->getMessage());
        }
        $this->setGuestUser();
        [, $args] = $this->create_submission_for_user_input();
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (require_login_exception $ex) {
            $this->assertStringContainsString('Must be an authenticated user', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_clone_with_bogus_parameters(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        [, $args] = $this->create_submission_for_user_input();
        try {
            $this->resolve_graphql_mutation(self::MUTATION, ['input' => ['application_id' => '']]);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid application id', $ex->getMessage());
        }
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args['input']);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid application id', $ex->getMessage());
        }
    }

    public function test_execute_query_successful(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        [$submission, $args] = $this->create_submission_for_user_input();
        /** @var application_submission $submission */
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $application_result = $result['application'] ?? null;
        $this->assertNotEmpty($application_result, 'result empty');
        $this->assertNotEquals($submission->application->id, $application_result['id']);
    }

    public function test_failed_ajax_call() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        [, $args] = $this->create_submission_for_user_input();

        $this->setGuestUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Must be an authenticated user');
    }
}
