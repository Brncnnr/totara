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
use core\orm\collection;
use core\orm\query\exceptions\record_not_found_exception;
use core_phpunit\testcase;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\application;
use mod_approval\model\application\application_submission;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\form\form_data;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_approver_generator_object;
use totara_webapi\phpunit\webapi_phpunit_helper;
use mod_approval\testing\generator as mod_approval_generator;

/**
 * @coversDefaultClass \mod_approval\webapi\resolver\query\application_approvers
 *
 * @group approval_workflow
 */
class mod_approval_webapi_query_application_approvers_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private $query = 'mod_approval_application_approvers';

    /**
     * Gets the approval workflow generator instance
     *
     * @return mod_approval_generator
     */
    protected function generator(): mod_approval_generator {
        return mod_approval_generator::instance();
    }

    public function test_query_without_login() {
        $application = $this->create_submitted_application();
        $args['input'] = [
            'application_id' => $application->id,
            'workflow_stage_approval_level_id' => $application->current_state->get_approval_level_id(),
        ];
        $this->setUser(0);
        $this->expectException('require_login_exception');
        $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_as_guest() {
        $application = $this->create_submitted_application();
        $args['input'] = [
            'application_id' => $application->id,
            'workflow_stage_approval_level_id' => $application->current_state->get_approval_level_id(),
        ];
        $this->setGuestUser();
        $this->expectException('require_login_exception');
        $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_as_admin() {
        $application = $this->create_submitted_application();
        [$manager1, $manager2] = $this->create_approvers($application);
        $args['input'] = [
            'application_id' => $application->id,
            'workflow_stage_approval_level_id' => $application->current_state->get_approval_level_id(),
        ];
        $this->setAdminUser();
        /** @var collection $result*/
        $result = $this->resolve_graphql_query($this->query, $args);
        $this->assertInstanceOf(collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals($manager1->id, $result[$manager1->id]->id);
        $this->assertEquals($manager2->id, $result[$manager2->id]->id);
    }

    public function test_query_as_user() {
        $application = $this->create_submitted_application();
        [$manager1, $manager2] = $this->create_approvers($application);
        $args['input'] = [
            'application_id' => $application->id,
            'workflow_stage_approval_level_id' => $application->current_state->get_approval_level_id(),
        ];
        $this->setUser($application->user_id);
        /** @var collection */
        $result = $this->resolve_graphql_query($this->query, $args);
        $this->assertInstanceOf(collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals($manager1->id, $result[$manager1->id]->id);
        $this->assertEquals($manager2->id, $result[$manager2->id]->id);
    }

    public function test_query_with_invalid_parameters() {
        $application = $this->create_submitted_application();
        $this->create_approvers($application);
        $args['input'] = [
            'application_id' => $application->id,
            'workflow_stage_approval_level_id' => $application->current_state->get_approval_level_id(),
        ];
        $this->setUser($application->user_id);

        // Invalid $args
        $args_wrong_app['input'] = [
            'application_id' => 79,
            'workflow_stage_approval_level_id' => $args['input']['workflow_stage_approval_level_id'],
        ];
        try {
            $this->resolve_graphql_query($this->query, $args_wrong_app);
            $this->fail('Invalid assignment');
        } catch (moodle_exception $e) {
            $this->assertStringContainsString('Invalid assignment', $e->getMessage());
        }

        $args_empty_app['input'] = [
            'application_id' => '',
            'workflow_stage_approval_level_id' => $args['input']['workflow_stage_approval_level_id'],
        ];
        try {
            $this->resolve_graphql_query($this->query, $args_empty_app);
            $this->fail('invalid_parameter_exception');
        } catch (invalid_parameter_exception $e) {
            $this->assertStringContainsString('invalid application id', $e->getMessage());
        }

        $args_wrong_level['input'] = [
            'application_id' => $args['input']['application_id'],
            'workflow_stage_approval_level_id' => '987',
        ];
        try {
            $this->resolve_graphql_query($this->query, $args_wrong_level);
            $this->fail('record_not_found_exception');
        } catch (record_not_found_exception $e) {
            $this->assertStringContainsString('Can not find data record in database', $e->getMessage());
        }

        $args_empty_level['input'] = [
            'application_id' => $args['input']['application_id'],
            'workflow_stage_approval_level_id' => '',
        ];
        try {
            $this->resolve_graphql_query($this->query, $args_empty_level);
            $this->fail('invalid_parameter_exception');
        } catch (invalid_parameter_exception $e) {
            $this->assertStringContainsString('approval level id is required', $e->getMessage());
        }

        $args_empty_level['input'] = [
            'application_id' => $args['input']['application_id'],
            'workflow_stage_approval_level_id' => $this->generator()->create_approval_level(
                $this->generator()->create_workflow_stage(
                    $this->generator()->create_workflow_version(
                        $this->generator()->create_simple_request_workflow()->id,
                        $this->generator()->create_form_and_version()->id
                    )->id,
                    'Test Stage',
                    form_submission::get_enum()
                )->id,
                'Level 1',
                1
            )->id,
        ];
        try {
            $this->resolve_graphql_query($this->query, $args_empty_level);
            $this->fail('invalid_parameter_exception');
        } catch (invalid_parameter_exception $e) {
            $this->assertStringContainsString('requested approval_level does not belong to this application', $e->getMessage());
        }
    }

    public function test_execute_query() {
        $application = $this->create_submitted_application();
        [$manager1, $manager2] = $this->create_approvers($application);
        $args['input'] = [
            'application_id' => $application->id,
            'workflow_stage_approval_level_id' => $application->current_state->get_approval_level_id(),
        ];
        $this->setUser($application->user_id);

        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing([$manager1->email, $manager2->email], [$result[0]['email'], $result[1]['email']]);
    }

    public function test_execute_query_failing_with_an_invalid_parameter() {
        $application = $this->create_submitted_application();
        $this->create_approvers($application);
        $args['input'] = [
            'application_id' => $application->id,
            'workflow_stage_approval_level_id' => $application->current_state->get_approval_level_id(),
        ];
        $this->setUser($application->user_id);

        $args_empty_app['input'] = [
            'application_id' => '',
            'workflow_stage_approval_level_id' => $args['input']['workflow_stage_approval_level_id'],
        ];
        $result = $this->parsed_graphql_operation($this->query, $args_empty_app);
        $this->assert_webapi_operation_failed($result);
        $this->assertStringEndsWith('(empty string)', $result[1]);

        $args_empty_level['input'] = [
            'application_id' => $args['input']['application_id'],
            'workflow_stage_approval_level_id' => '',
        ];
        $result = $this->parsed_graphql_operation($this->query, $args_empty_level);
        $this->assert_webapi_operation_failed($result);
        $this->assertStringEndsWith('(empty string)', $result[1]);
    }

    /**
     * Set up some paging query options.
     *
     * @return application $application options for resolver
     */
    private function create_submitted_application(): application {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();

        // Create an applicant and an application
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $applicant = new user($user->id);
        $application = $this->create_application($workflow, $assignment, $applicant);

        // Create a submission and submit it
        $form_data = form_data::from_json('{"agency_code": 25}');
        $submission_entity = $this->generator()->create_application_submission(
            $application->id,
            $applicant->id,
            $application->current_state->get_stage_id(),
            $form_data
        );
        $submission = application_submission::load_by_entity($submission_entity);
        $submission->publish(user::logged_in()->id);
        submit::execute($application, user::logged_in()->id);
        $application->refresh();

        return $application;
    }

    /**
     * Set up some paging query options.
     *
     * @param application $application
     * @return user[] $approver options for resolver
     */
    private function create_approvers(application $application): array {

        $manager = $this->getDataGenerator()->create_user();
        $this->setUser($manager);
        $approver = new user($manager->id);
        $approver_go =
            new assignment_approver_generator_object(
                $application->assignment->id,
                $application->current_state->get_approval_level_id(),
                user_approver_type::TYPE_IDENTIFIER,
                $approver->id
            );
        $this->generator()->create_assignment_approver($approver_go);

        $manager2 = $this->getDataGenerator()->create_user();
        $this->setUser($manager2);
        $approver2 = new user($manager2->id);
        $approver_go2 =
            new assignment_approver_generator_object(
                $application->assignment->id,
                $application->current_state->get_approval_level_id(),
                user_approver_type::TYPE_IDENTIFIER,
                $approver2->id
            );
        $this->generator()->create_assignment_approver($approver_go2);

        return [$manager, $manager2];
    }
}
