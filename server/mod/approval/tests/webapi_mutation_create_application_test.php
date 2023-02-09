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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use core_phpunit\testcase;
use mod_approval\exception\model_exception;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\application\application as application_model;
use mod_approval\model\assignment\assignment;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\approval_workflow_test_setup;
use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\create_application
 */
class mod_approval_webapi_mutation_create_application_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private $query = 'mod_approval_create_application';

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    public function test_create_application(): void {
        [$args, $user] = $this->set_assignment();

        $result = $this->resolve_graphql_mutation($this->query, $args);
        $this->assertNotNull($result['application_id']);
        $this->assertIsNumeric($result['application_id']);
    }

    public function test_query_without_login(): void {
        [$args, $user] = $this->set_assignment();
        $this->setUser(0);

        $this->expectException('require_login_exception');
        $this->expectExceptionMessage('You are not logged in');
        $this->resolve_graphql_mutation($this->query, $args);
    }

    public function test_query_as_guest(): void {
        [$args, $user] = $this->set_assignment();
        $this->setGuestUser();

        $this->expectException('require_login_exception');
        $this->expectExceptionMessage('Must be an authenticated user');
        $this->resolve_graphql_mutation($this->query, $args);
    }

    public function test_query_as_admin(): void {
        [$args, $user] = $this->set_assignment();
        $this->setAdminUser();
        $result = $this->resolve_graphql_mutation($this->query, $args);
        $this->assertNotNull($result['application_id']);
        $this->assertIsNumeric($result['application_id']);

        $application = application_model::load_by_id($result['application_id']);
        $this->assertEquals($args['input']['applicant_id'], $application->user_id);
        $this->assertEquals($args['input']['assignment_id'], $application->assignment->id);
        $this->assertEquals($args['input']['job_assignment_id'], $application->job_assignment->id);
    }

    public function test_query_as_user(): void {
        [$args, $user] = $this->set_assignment();
        $this->setUser($user);
        $result = $this->resolve_graphql_mutation($this->query, $args);
        $this->assertNotNull($result['application_id']);
        $this->assertIsNumeric($result['application_id']);

        $application = application_model::load_by_id($result['application_id']);
        $this->assertEquals($args['input']['applicant_id'], $application->user_id);
        $this->assertEquals($args['input']['assignment_id'], $application->assignment->id);
        $this->assertEquals($args['input']['job_assignment_id'], $application->job_assignment->id);
    }

    public function test_query_without_capability(): void {
        [$args, $user] = $this->set_assignment();
        $this->setUser($user);

        // Authenticated users have the capability to create applications by default, so remove it.
        $roleid = builder::table('role')->where('shortname', 'user')->one(true)->id;
        $assignment = assignment::load_by_id($args['input']['assignment_id']);
        assign_capability('mod/approval:create_application_applicant', CAP_PREVENT, $roleid, $assignment->get_context(), true);

        $this->expectException(access_denied_exception::class);
        $this->expectExceptionMessage('User cannot create application for the given applicant');
        $this->resolve_graphql_mutation($this->query, $args);
    }

    public function test_query_with_invalid_parameters() {
        [$args, $user] = $this->set_assignment();
        $this->setUser($user);

        $fail_args['input'] = [
            'assignment_id' => '',
            'applicant_id'  => $user->id
        ];
        try {
            $this->resolve_graphql_mutation($this->query, $fail_args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $e) {
            $this->assertStringContainsString('Invalid parameter value detected (invalid assignment id)', $e->getMessage());
        }

        $fail_args['input'] = [
            'assignment_id' => 'My Assignment',
            'applicant_id'  => $user->id
        ];
        try {
            $this->resolve_graphql_mutation($this->query, $fail_args);
            $this->fail('moodle_exception');
        } catch (moodle_exception $e) {
            $this->assertStringContainsString('Invalid assignment', $e->getMessage());
        }

        $fail_args['input'] = [
            'assignment_id' => '999999999999999999999',
            'applicant_id'  => $user->id
        ];
        try {
            $this->resolve_graphql_mutation($this->query, $fail_args);
            $this->fail('moodle_exception');
        } catch (moodle_exception $e) {
            $this->assertStringContainsString('Invalid assignment', $e->getMessage());
        }

        $fail_args['input'] = [
            'assignment_id' => $args['input']['assignment_id'],
            'applicant_id'  => $user->id,
            'job_assignment_id' => '789899'
        ];
        try {
            $result = $this->resolve_graphql_mutation($this->query, $fail_args);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $e) {
            $this->assertStringContainsString('Can not find data record in database', $e->getMessage());
        }

        $ja2 = job_assignment::create_default($this->getDataGenerator()->create_user()->id);

        $fail_args['input'] = [
            'assignment_id' => $args['input']['assignment_id'],
            'applicant_id'  => $user->id,
            'job_assignment_id' => $ja2->id,
        ];
        try {
            $result = $this->resolve_graphql_mutation($this->query, $fail_args);
            $this->fail('model_exception expected');
        } catch (model_exception $e) {
            $this->assertStringContainsString('Job assignment belongs to other user', $e->getMessage());
        }

        $fail_args['input'] = [
            'assignment_id' => $args['input']['assignment_id'],
            'applicant_id'  => 'John Smith'
        ];
        try {
            $result = $this->resolve_graphql_mutation($this->query, $fail_args);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $e) {
            $this->assertStringContainsString('Can not find data record in database', $e->getMessage());
        }

        $fail_args['input'] = [
            'assignment_id' => $args['input']['assignment_id'],
            'applicant_id'  => '9999999999999999999'
        ];
        try {
            $result = $this->resolve_graphql_mutation($this->query, $fail_args);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $e) {
            $this->assertStringContainsString('Can not find data record in database', $e->getMessage());
        }
    }
    public function test_query_with_non_active_workflow() {
        [$args, $user, $workflow] = $this->set_assignment();

        $this->setUser($user);

        $workflow_model = workflow::load_by_entity($workflow);
        $workflow_model->latest_version->archive();

        // Create new workflow_version in DRAFT state
        $new_workflow_version = workflow_version::create($workflow_model, $workflow_model->form->latest_version);
        workflow_stage::create($new_workflow_version, 'New Stage', form_submission::get_enum());

        // Cannot create application as any workflow_version is active
        try {
            $this->resolve_graphql_mutation($this->query, $args);
            $this->fail('model_exception');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('Workflow is not active', $ex->getMessage());
        }

        $new_workflow_version->activate();

        $result = $this->resolve_graphql_mutation($this->query, $args);
        $application = application_model::load_by_id($result['application_id']);

        $this->assertEquals($new_workflow_version->id, $application->workflow_version->id);
        $this->assertEquals($new_workflow_version->stages->first()->id, $application->current_stage->id);
        $this->assertEquals('New Stage', $application->current_stage->name);
    }


    public function test_parsed_graphql_operation() {
        [$args, $user] = $this->set_assignment();
        $this->setUser($user);

        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $application = application_model::load_by_id($result[0]['application_id']);
        $this->assertEquals($args['input']['applicant_id'], $application->user_id);
        $this->assertEquals($args['input']['assignment_id'], $application->assignment->id);
        $this->assertEquals($args['input']['job_assignment_id'], $application->job_assignment->id);

        // missing $input
        $fail_args = [
            'assignment_id' => $args['input']['assignment_id'],
            'applicant_id'  => $user->id
        ];
        $result = $this->parsed_graphql_operation($this->query, $fail_args);
        $this->assert_webapi_operation_failed($result, 'Variable "$input" of required type "mod_approval_create_application_input!" was not provided.');

        // missing assignment_id
        $fail_args['input'] = [
            'applicant_id'  => $user->id
        ];
        $result = $this->parsed_graphql_operation($this->query, $fail_args);
        $this->assert_webapi_operation_failed($result);

        // missing applicant_id
        $fail_args['input'] = [
            'assignment_id' => $args['input']['assignment_id'],
        ];
        $result = $this->parsed_graphql_operation($this->query, $fail_args);
        $this->assert_webapi_operation_failed($result);

        // wrong job_assignment_id
        $fail_args['input'] = [
            'assignment_id' => $args['input']['assignment_id'],
            'applicant_id' => $user->id,
            'job_assignment_id' => '78993'
        ];
        $result = $this->parsed_graphql_operation($this->query, $fail_args);
        $this->assert_webapi_operation_failed($result);

        // job_assignment_id belongs to other user
        $ja2 = job_assignment::create([
            'userid' => 4526,
            'idnumber' => '002',
            'organisationid' => 42,
            'fullname' => 'Test Job Assignment'
        ]);

        $fail_args['input'] = [
            'assignment_id' => $args['input']['assignment_id'],
            'applicant_id' => $user->id,
            'job_assignment_id' => $ja2->id,
        ];
        $result = $this->parsed_graphql_operation($this->query, $fail_args);
        $this->assert_webapi_operation_failed($result);

        // missing values
        $fail_args['input'] = [
            'assignment_id' => '',
            'applicant_id'  => ''
        ];
        $result = $this->parsed_graphql_operation($this->query, $fail_args);
        $this->assert_webapi_operation_failed($result);
    }

    private function set_assignment(): array {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user = $this->getDataGenerator()->create_user();
        // Assign user to agency
        $ja = job_assignment::create([
            'userid' => $user->id,
            'idnumber' => '001',
            'organisationid' => $framework->agency->id,
            'fullname' => 'Test Job Assignment'
        ]);

        $args['input'] = [
            'assignment_id' => $assignment->id,
            'applicant_id'  => $user->id,
            'job_assignment_id' => $ja->id,
        ];

        return [$args, $user, $workflow];
    }
}
