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
 */

use core\entity\user;
use core_phpunit\testcase;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\application;
use mod_approval\model\application\application_action;
use mod_approval\model\application\application_submission;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\form\form_data;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 */
class mod_approval_webapi_query_load_application_activities_testcase extends testcase {

    use approval_workflow_test_setup;
    use webapi_phpunit_helper;

    private const QUERY = 'mod_approval_load_application_activities';

    public function test_query_requires_logged_in_user() {
        $data = $this->generate_data();
        $this->setGuestUser();
        $this->expectException(require_login_exception::class);
        $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'application_id' => $data['application']->id
                ]
            ]
        );
    }

    public function test_query_without_input_params() {
        $this->setAdminUser();
        $this->generate_data();

        $parsed_query = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed($parsed_query);
    }

    public function test_query_success() {
        $data = $this->generate_data();
        $this->setUser($data['applicant']->id);

        $result = $this->parsed_graphql_operation(
            self::QUERY,
            [
                'input' => [
                    'application_id' => $data['application']->id
                ]
            ]
        );
        $this->assert_webapi_operation_successful($result);

        $query_data = reset($result);
        $this->assertEquals(
            $data['application']->user->id,
            $query_data['activities'][0]['user']['id']
        );
        $this->assertEquals(
            $data['application']->current_stage->id,
            $query_data['activities'][5]['stage']['id']
        );
    }

    public function test_cannot_load_application_without_capabilities() {
        $data = $this->generate_data();
        $random_user = $this->getDataGenerator()->create_user();
        $this->setUser($random_user->id);

        $this->expectException(access_denied_exception::class);
        $this->expectExceptionMessage("Cannot access this application");

        $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'application_id' => $data['application']->id
                ]
            ]
        );
    }

    public function test_query_with_unknown_application_id() {
        $this->generate_data();
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage("Invalid assignment");
        $this->resolve_graphql_query(
            self::QUERY,
            [
                'input' => [
                    'application_id' => 878,
                ]
            ]
        );
    }

    private function generate_data(): array {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment('Testing', false, false);
        $workflow = workflow::load_by_entity($workflow);
        $assignment_model = assignment::load_by_entity($assignment);
        /** @var workflow_stage $stage1*/
        $stage1 = $workflow->latest_version->stages->first();
        $stage2 = $workflow->latest_version->get_next_stage($stage1->id);

        // Add another level, otherwise application will be completed when approver approves it.
        $stage2->add_approval_level('Level 2');

        // Add an approver for level1.
        $approver = new user($this->getDataGenerator()->create_user()->id);
        $level1 = $stage2->approval_levels->first();
        assignment_approver::create(
            $assignment_model,
            $level1,
            user_approver_type::TYPE_IDENTIFIER,
            $approver->id
        );
        $workflow->refresh()->publish($workflow->get_latest_version());

        // Create an applicant.
        $user1 = $this->getDataGenerator()->create_user();
        $application = application::create($workflow->latest_version, $assignment_model, $user1->id);

        // Submit form as applicant.
        $form_data = form_data::from_json('{"agency_code":"what?"}');
        $submission = application_submission::create_or_update($application, $user1->id, $form_data);
        $submission->publish($user1->id);
        submit::execute($application, $user1->id);

        // Approve level1 as approver.
        application_action::create($application, $approver->id, new approve());
        $application->refresh(true);

        return [
            'application' => $application,
            'applicant' => new user($user1->id),
            'approver' => $approver
        ];
    }
}
