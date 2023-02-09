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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use core_phpunit\testcase;
use totara_job\job_assignment;
use mod_approval\entity\assignment\assignment;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\assignment\assignment_resolver;
use mod_approval\entity\workflow\workflow_type;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_generator_object;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_approval\webapi\resolver\query\create_new_application_menu
 *
 * @group approval_workflow
 */
class mod_approval_webapi_query_create_new_application_menu_testcase extends testcase {
    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private $query = 'mod_approval_create_new_application_menu';

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    public function test_query_without_login() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->create_user_with_job_assignment($framework->agency->subagency_a->program_a->id);

        $this->setUser(0);
        $this->expectException('require_login_exception');
        $args['query'] = [];
        $result = $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_as_guest() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->create_user_with_job_assignment($framework->agency->subagency_a->program_a->id);

        $this->setGuestUser();
        $this->expectException('require_login_exception');
        $args['query'] = [];
        $result = $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_as_admin() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->create_user_with_job_assignment($framework->agency->subagency_a->program_a->id);

        $this->setAdminUser();
        $args['query'] = [];
        $result = $this->resolve_graphql_query($this->query, $args);
        $this->assertCount(0, $result);
    }

    public function test_query_as_user() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();

        $user1 = $this->create_user_with_job_assignment($framework->agency->subagency_a->program_a->id);
        $this->setUser($user1);

        $user_entity = new user($user1->id);
        $creator = clone $user_entity;
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();

        $args['query'] = [];
        $result = $this->resolve_graphql_query($this->query, $args);
        $this->assertCount(1, $result);
        foreach ($items as $mx => $menu_item) {
            $this->assertEquals($menu_item->assignment_id, $result->item($mx)->assignment_id);
            $this->assertEquals($menu_item->workflow_type, $result->item($mx)->workflow_type);
            $this->assertEquals($menu_item->job_assignment, $result->item($mx)->job_assignment);
            $this->assertEquals($menu_item->job_assignment_id, $result->item($mx)->job_assignment_id);
        }
    }

    public function test_query_with_multiple_job_assignments() {
        $this->setAdminUser();
        /**
         * $framework->agency = $agency;
         * $framework->agency->subagency_a = $subagency_a;
         * $framework->agency->subagency_a->program_a = $program_a;
         * $framework->agency->subagency_a->program_b = $program_b;
         * $framework->agency->subagency_b = $subagency_b;
         *
         */
        // Create a workflow with assignment at agency
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        // Create other assignments at subagency a and program b
        $assignment_go = new \mod_approval\testing\assignment_generator_object($workflow->course_id, 1, $framework->agency->subagency_a->id);
        $suba_assignment = $this->generator()->create_assignment($assignment_go);
        $assignment_go = new \mod_approval\testing\assignment_generator_object($workflow->course_id, 1, $framework->agency->subagency_a->program_b->id);
        $progb_assignment = $this->generator()->create_assignment($assignment_go);
        $this->assertEquals(3, \mod_approval\entity\assignment\assignment::repository()->count());

        // Create a user with job assignment to program_a
        $user1 = $this->create_user_with_job_assignment($framework->agency->subagency_a->program_a->id);
        $this->setUser($user1);

        // Resolve should return 1 result, for subagency a
        $args['query'] = [];
        $result = $this->resolve_graphql_query($this->query, $args);
        $this->assertCount(1, $result);
        $item = $result->current();
        $this->assertEquals($suba_assignment->id, $item->assignment_id);
        $this->assertEquals('Test Job Assignment', $item->job_assignment);

        // Add another job assignment to program b
        $ja2 = \totara_job\job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => '002',
            'organisationid' => $framework->agency->subagency_a->program_b->id,
            'fullname' => 'Test Job Assignment 2'
        ]);

        // Resolve should return 2 results, for subagency a and program b
        $args['query'] = [];
        $result = $this->resolve_graphql_query($this->query, $args);
        $this->assertCount(2, $result);
        $item = $result->current();
        $this->assertEquals($suba_assignment->id, $item->assignment_id);
        $this->assertEquals('Test Job Assignment', $item->job_assignment);
        $result->next();
        $next_item = $result->current();
        $this->assertEquals($progb_assignment->id, $next_item->assignment_id);
        $this->assertEquals('Test Job Assignment 2', $next_item->job_assignment);
        $this->assertEquals($ja2->id, $next_item->job_assignment_id);
    }

    public function test_query_with_multiple_workflow_types() {
        $this->setAdminUser();
        // Create a workflow with assignment at agency
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();

        // Create another workflow with a different type
        $workflow_2 = $this->generator()->create_simple_request_workflow('Demo', 'Demo Workflow');
        $assignment_go = new assignment_generator_object($workflow_2->course_id, assignment_type\organisation::get_code(), $framework->agency->id);
        $assignment_go->is_default = true;
        $assignment_2 = $this->generator()->create_assignment($assignment_go);
        $this->assertEquals(2, \mod_approval\entity\workflow\workflow_type::repository()->count());

        // Create a user with job assignment to agency
        $user1 = $this->create_user_with_job_assignment($framework->agency->id);
        $this->setUser($user1);

        // Resolve should return 2 results
        $args['query'] = [];
        $result = $this->resolve_graphql_query($this->query, $args);
        // Sort the result to remove ambiguity
        $result->sort('assignment_id');
        $this->assertCount(2, $result);
        $item = $result->current();
        $this->assertEquals($assignment->id, $item->assignment_id);
        $this->assertEquals('Testing', $item->workflow_type);
        $result->next();
        $next_item = $result->current();
        $this->assertEquals($assignment_2->id, $next_item->assignment_id);
        $this->assertEquals('Demo', $next_item->workflow_type);
    }

    public function test_execute_query() {
        $this->setAdminUser();
        // Create a workflow with assignment at agency
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        // Create other assignments at subagency a and program b
        $assignment_go = new \mod_approval\testing\assignment_generator_object($workflow->course_id, 1, $framework->agency->subagency_a->id);
        $suba_assignment = $this->generator()->create_assignment($assignment_go);
        $this->assertEquals(2, \mod_approval\entity\assignment\assignment::repository()->count());
        // Create a user with job assignment to program_a
        $user1 = $this->create_user_with_job_assignment($framework->agency->subagency_a->program_a->id);
        $this->setUser($user1);

        $args['query'] = [];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');
        $this->assertCount(1, $result);
        $menu_item = $result[0];
        $this->assertEquals($suba_assignment->id, $menu_item['assignment_id']);
        $this->assertEquals('Testing', $menu_item['workflow_type']);
        $this->assertEquals('Test Job Assignment', $menu_item['job_assignment']);
    }

    public function test_execute_query_with_no_possible_assignments() {
        $this->setAdminUser();
        // Create a workflow with assignment at agency
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        // Create other assignments at subagency a and program b
        $assignment_go = new \mod_approval\testing\assignment_generator_object($workflow->course_id, 1, $framework->agency->subagency_a->id);
        $suba_assignment = $this->generator()->create_assignment($assignment_go);
        $this->assertEquals(2, \mod_approval\entity\assignment\assignment::repository()->count());

        // Create a user with no job assignments
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $args['query'] = [];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);
        $this->assertEmpty($result[0]);
    }

    public function test_execute_query_failing_because_not_authenticated() {
        $this->setAdminUser();
        // Create a workflow with assignment at agency
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        // Create other assignments at subagency a and program b
        $assignment_go = new \mod_approval\testing\assignment_generator_object($workflow->course_id, 1, $framework->agency->subagency_a->id);
        $suba_assignment = $this->generator()->create_assignment($assignment_go);
        $this->assertEquals(2, \mod_approval\entity\assignment\assignment::repository()->count());
        // Create a user with job assignment to program_a
        $user1 = $this->create_user_with_job_assignment($framework->agency->subagency_a->program_a->id);

        // Ah, but login as guest
        $this->setGuestUser();

        $args['query'] = [];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_failed($result);
        $this->assertEquals($result[1], 'Course or activity not accessible. (Must be an authenticated user)');
    }

    public function test_query_as_admin_on_behalf() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->create_user_with_job_assignment($framework->agency->subagency_a->program_a->id);

        $user_entity = new user($user1->id);
        $creator = user::logged_in();
        $resolver = new assignment_resolver($user_entity, $creator);
        $resolver->resolve();
        $items = $resolver->get_menu_items();
        $args['query'] = ['applicant_id' => $user1->id];
        $result = $this->resolve_graphql_query($this->query, $args);
        $this->assertCount(1, $result);
        foreach ($items as $mx => $menu_item) {
            $this->assertEquals($menu_item->assignment_id, $result->item($mx)->assignment_id);
            $this->assertEquals($menu_item->workflow_type, $result->item($mx)->workflow_type);
            $this->assertEquals($menu_item->job_assignment, $result->item($mx)->job_assignment);
            $this->assertEquals($menu_item->job_assignment_id, $result->item($mx)->job_assignment_id);
        }
    }

    public function test_query_with_multiple_job_assignments_on_behalf() {
        $this->setAdminUser();

        // Create a workflow with assignment at agency
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        // Create other assignments at subagency a and program b
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\organisation::get_code(), $framework->agency->subagency_a->id);
        $suba_assignment = $this->generator()->create_assignment($assignment_go);
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\organisation::get_code(), $framework->agency->subagency_a->program_b->id);
        $progb_assignment = $this->generator()->create_assignment($assignment_go);
        $this->assertEquals(3, assignment::repository()->count());

        // Create a user with job assignment to program_a
        $user1 = $this->create_user_with_job_assignment($framework->agency->subagency_a->program_a->id);

        // Resolve should return 1 result, for subagency a
        $args['query'] = ['applicant_id' => $user1->id];
        $result = $this->resolve_graphql_query($this->query, $args);
        $this->assertCount(1, $result);
        $item = $result->current();
        $this->assertEquals($suba_assignment->id, $item->assignment_id);
        $this->assertEquals('Test Job Assignment', $item->job_assignment);

        // Add another job assignment to program b
        $ja2 = job_assignment::create([
            'userid' => $user1->id,
            'idnumber' => '002',
            'organisationid' => $framework->agency->subagency_a->program_b->id,
            'fullname' => 'Test Job Assignment 2'
        ]);

        // Resolve should return 2 results, for subagency a and program b
        $args['query'] = ['applicant_id' => $user1->id];
        $result = $this->resolve_graphql_query($this->query, $args);
        $this->assertCount(2, $result);
        $item = $result->current();
        $this->assertEquals($suba_assignment->id, $item->assignment_id);
        $this->assertEquals('Test Job Assignment', $item->job_assignment);
        $result->next();
        $next_item = $result->current();
        $this->assertEquals($progb_assignment->id, $next_item->assignment_id);
        $this->assertEquals('Test Job Assignment 2', $next_item->job_assignment);
        $this->assertEquals($ja2->id, $next_item->job_assignment_id);
    }

    public function test_query_with_multiple_workflow_types_on_behalf() {
        $this->setAdminUser();
        // Create a workflow with assignment at agency
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();

        // Create another workflow with a different type
        $workflow_2 = $this->generator()->create_simple_request_workflow('Demo', 'Demo Workflow');
        $assignment_go = new assignment_generator_object($workflow_2->course_id, assignment_type\organisation::get_code(), $framework->agency->id);
        $assignment_go->is_default = true;
        $assignment_2 = $this->generator()->create_assignment($assignment_go);
        $this->assertEquals(2, workflow_type::repository()->count());

        // Create a user with job assignment to agency
        $user1 = $this->create_user_with_job_assignment($framework->agency->id);

        // Resolve should return 2 results
        $args['query'] = ['applicant_id' => $user1->id];
        $result = $this->resolve_graphql_query($this->query, $args);
        // Sort the result to remove ambiguity
        $result->sort('assignment_id');
        $this->assertCount(2, $result);
        $item = $result->current();
        $this->assertEquals($assignment->id, $item->assignment_id);
        $this->assertEquals('Testing', $item->workflow_type);
        $result->next();
        $next_item = $result->current();
        $this->assertEquals($assignment_2->id, $next_item->assignment_id);
        $this->assertEquals('Demo', $next_item->workflow_type);
    }

    public function test_execute_query_on_behalf() {
        $this->setAdminUser();
        // Create a workflow with assignment at agency
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        // Create other assignments at subagency a and program b
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\organisation::get_code(), $framework->agency->subagency_a->id);
        $suba_assignment = $this->generator()->create_assignment($assignment_go);
        $this->assertEquals(2, assignment::repository()->count());
        // Create a user with job assignment to program_a
        $user1 = $this->create_user_with_job_assignment($framework->agency->subagency_a->program_a->id);

        $args['query'] = ['applicant_id' => $user1->id];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');
        $this->assertCount(1, $result);
        $menu_item = $result[0];
        $this->assertEquals($suba_assignment->id, $menu_item['assignment_id']);
        $this->assertEquals('Testing', $menu_item['workflow_type']);
        $this->assertEquals('Test Job Assignment', $menu_item['job_assignment']);
    }


    public function test_execute_query_failing_because_not_authenticated_on_behalf() {
        $this->setAdminUser();
        // Create a workflow with assignment at agency
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        // Create other assignments at subagency a and program b
        $assignment_go = new assignment_generator_object($workflow->course_id, assignment_type\organisation::get_code(), $framework->agency->subagency_a->id);
        $suba_assignment = $this->generator()->create_assignment($assignment_go);
        $this->assertEquals(2, assignment::repository()->count());
        // Create a user with job assignment to program_a
        $user1 = $this->create_user_with_job_assignment($framework->agency->subagency_a->program_a->id);

        // Ah, but login as guest
        $this->setGuestUser();

        $args['query'] = ['applicant_id' => $user1->id];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_failed($result);
        $this->assertEquals($result[1], 'Course or activity not accessible. (Must be an authenticated user)');
    }

    public function test_query_for_specific_workflow_type() {
        $this->setAdminUser();
        // Create a workflow with assignment at agency
        list($workflow1, $framework, $assignment1) = $this->create_workflow_and_assignment('Foo');
        list($workflow2, $framework, $assignment2) = $this->create_workflow_and_assignment_on_framework($framework, 'Bar');
        list($workflow3, $framework, $assignment3) = $this->create_workflow_and_assignment_on_framework($framework, 'Quux');

        // Create a user with job assignment to agency
        $user1 = $this->create_user_with_job_assignment($framework->agency->id);
        $this->setUser($user1);

        // First make a request with no workflow_type set.
        $args['query'] = ['applicant_id' => $user1->id];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertCount(3, $result);

        $workflow2_model = \mod_approval\model\workflow\workflow::load_by_entity($workflow2);
        $args['query'] = ['applicant_id' => $user1->id, 'workflow_type_id' => $workflow2_model->workflow_type_id];
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Bar', $result[0]['workflow_type']);
        $this->assertEquals($assignment2->id, $result[0]['assignment_id']);
    }

    private function create_user_with_job_assignment($org_id) {
        // Add a user and assign to program_a
        $user = $this->getDataGenerator()->create_user();

        $ja = job_assignment::create([
            'userid' => $user->id,
            'idnumber' => '001',
            'organisationid' => $org_id,
            'fullname' => 'Test Job Assignment'
        ]);

        return $user;
    }
}