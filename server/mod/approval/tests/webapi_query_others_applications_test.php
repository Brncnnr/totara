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
 * @package mod_approval
 */

use core\entity\user as user_entity;
use core_phpunit\testcase;
use mod_approval\data_provider\application\capability_map\capability_map_controller;
use mod_approval\data_provider\application\role_map\role_map_controller;
use mod_approval\model\assignment\approver_type\user;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_approval\webapi\resolver\query\others_applications
 *
 * @group approval_workflow
 * @group applications_dashboard
 */
class mod_approval_webapi_query_others_applications_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private $query = 'mod_approval_others_applications';

    private function create_workflow_manager() {
        // Create an user with view_in_dashboard_application_any capability
        $user = $this->getDataGenerator()->create_user();
        $sys_context = context_system::instance();
        $roleid = self::getDataGenerator()->create_role();
        assign_capability('mod/approval:view_in_dashboard_application_any', CAP_ALLOW, $roleid, $sys_context, true);
        role_assign($roleid, $user->id, $sys_context);
        return $user;
    }

    public function test_query_without_login() {
        global $DB;

        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application = $this->create_submitted_application($workflow, $assignment, $user_entity);

        $this->setUser(0);
        $this->expectException('require_login_exception');
        $result = $this->resolve_graphql_query($this->query);
    }

    public function test_query_as_guest() {
        global $DB;

        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application = $this->create_submitted_application($workflow, $assignment, $user_entity);

        $this->setGuestUser();
        $this->expectException('require_login_exception');
        $result = $this->resolve_graphql_query($this->query);
    }

    public function test_query_as_admin() {
        global $DB;

        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application1 = $this->create_submitted_application($workflow, $assignment, $user_entity);
        $application2 = $this->create_submitted_application($workflow, $assignment, $user_entity);

        $this->setAdminUser();
        role_map_controller::regenerate_all_maps();
        capability_map_controller::regenerate_all_maps(get_admin()->id);

        $result = $this->resolve_graphql_query($this->query);
        $this->assertCount(2, $result->items);
    }

    public function test_query_as_applicant() {
        global $DB;

        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->set_user_with_capability_maps($user1);
        $user_entity = new user_entity($user1->id);
        $application = $this->create_submitted_application($workflow, $assignment, $user_entity);

        $records = $DB->get_records('approval_application', ['user_id' => $user1->id], 'id desc');

        $result = $this->resolve_graphql_query($this->query);
        $this->assertCount(0, $result->items);
    }

    public function test_query_as_workflow_manager() {
        global $DB;

        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application = $this->create_submitted_application($workflow, $assignment, $user_entity);
        $workflow_manager = $this->create_workflow_manager();
        $this->set_user_with_capability_maps($workflow_manager);

        $records = $DB->get_records('approval_application', ['user_id' => $user1->id], 'id desc');

        $result = $this->resolve_graphql_query($this->query);
        $this->assertCount(1, $result->items);
        foreach ($records as $application) {
            $this->assertEquals($application->id, $result->items->item($application->id)->id);
        }
    }

    public function test_query_with_deleted_applicant() {
        global $DB;

        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application = $this->create_submitted_application($workflow, $assignment, $user_entity);

        $workflow_manager = $this->create_workflow_manager();
        $this->set_user_with_capability_maps($workflow_manager);

        $result = $this->resolve_graphql_query($this->query);
        $this->assertCount(1, $result->items);

        delete_user($user1);
        $result = $this->resolve_graphql_query($this->query);
        $this->assertCount(0, $result->items);
    }

    public function test_query_multiple_pages() {
        global $DB;

        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        // Create 22 applications, so 2 pages of 10 plus a page of 2.
        for ($i = 0; $i < 22; $i++) {
            $application = $this->create_submitted_application($workflow, $assignment, $user_entity);
        }

        $workflow_manager = $this->create_workflow_manager();
        $this->set_user_with_capability_maps($workflow_manager);

        // Load the first page of applications.
        $p1_records = $DB->get_records('approval_application', ['user_id' => $user1->id], 'id desc', '*', 0, 10);
        $options = $this->get_query_options();
        $result = $this->resolve_graphql_query($this->query, $options);
        /* @var \core\collection $p1_items */
        $p1_items = $result->items;
        $this->assertEquals(22, $result->total);
        $this->assertEquals(10, $p1_items->count());
        $p1_first_item = $p1_items->first();
        $p1_first_record = array_shift($p1_records);
        $this->assertEquals($p1_first_record->id, $p1_first_item->id);
        $p1_last_item = $p1_items->last();
        $p1_last_record = array_pop($p1_records);
        $this->assertEquals($p1_last_record->id, $p1_last_item->id);

        // Now load the next page of applications.
        $p2_records = $DB->get_records('approval_application', ['user_id' => $user1->id], 'id desc', '*', 10, 10);
        $options = $this->get_query_options(10, 2);
        $result = $this->resolve_graphql_query($this->query, $options);
        $p2_items = $result->items;
        $this->assertEquals(22, $result->total);
        $this->assertEquals(10, $p2_items->count());
        $p2_first_item = $p2_items->first();
        $p2_first_record = array_shift($p2_records);
        $this->assertEquals($p2_first_record->id, $p2_first_item->id);
        $p2_last_item = $p2_items->last();
        $p2_last_record = array_pop($p2_records);
        $this->assertEquals($p2_last_record->id, $p2_last_item->id);
        $this->assertNotEquals($p1_first_item->id, $p2_first_item->id);

        // Now load the last page of applications.
        $options = $this->get_query_options(10, 3);
        $result = $this->resolve_graphql_query($this->query, $options);
        $p2_items = $result->items;
        $this->assertEquals(22, $result->total);
        $this->assertEquals(2, $p2_items->count());
    }

    public function test_query_with_invalid_parameters() {
        global $DB;

        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application = $this->create_submitted_application($workflow, $assignment, $user_entity);

        $workflow_manager = $this->create_workflow_manager();
        $this->set_user_with_capability_maps($workflow_manager);

        // Invalid limit test
        $options = $this->get_query_options(-10);
        try {
            $result = $this->resolve_graphql_query($this->query, $options);
            $this->fail('Invalid limit not rejected');
        } catch (coding_exception $e) {
            $this->assertStringContainsString('The cursor needs a limit to be set, 0 or greater', $e->getMessage());
        }

        // Invalid page test
        $options = $this->get_query_options(10, -10);
        try {
            $result = $this->resolve_graphql_query($this->query, $options);
            $this->fail('Invalid limit not rejected');
        } catch (coding_exception $e) {
            $this->assertStringContainsString('Page has to be a positive integer', $e->getMessage());
        }

        // Invalid filter test
        $options = $this->get_query_options(10, 1, ['invalid_filter' => 'something']);
        try {
            $result = $this->resolve_graphql_query($this->query, $options);
            $this->fail('Invalid filter not rejected');
        } catch (coding_exception $e) {
            $this->assertStringContainsString("Filtering by 'invalid_filter' is not supported", $e->getMessage());
        }

        // Invalid sort_by test
        $options = $this->get_query_options(10, 1, null, 'invalid_sorter');
        try {
            $result = $this->resolve_graphql_query($this->query, $options);
            $this->fail('Invalid sort_by not rejected');
        } catch (coding_exception $e) {
            $this->assertStringContainsString("Sorting by 'invalid_sorter' is not supported", $e->getMessage());
        }
    }

    public function test_execute_query() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application = $this->create_submitted_application($workflow, $assignment, $user_entity);

        $workflow_manager = $this->create_workflow_manager();
        $this->set_user_with_capability_maps($workflow_manager);

        $options = $this->get_query_options();
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $this->assertCount(1, $result['items']);
        $this->assertEquals(1, $result['total']);
        $this->assertEmpty($result['next_cursor']);
    }

    public function test_execute_query_passing_with_no_options() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application = $this->create_submitted_application($workflow, $assignment, $user_entity);

        $workflow_manager = $this->create_workflow_manager();
        $this->set_user_with_capability_maps($workflow_manager);

        $options = ['query_options' => []];
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');

        $this->assertCount(1, $result['items']);
        $this->assertEquals(1, $result['total']);
        $this->assertEmpty($result['next_cursor']);
    }

    public function test_execute_query_failing_with_an_invalid_parameter() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application = $this->create_submitted_application($workflow, $assignment, $user_entity);

        $workflow_manager = $this->create_workflow_manager();
        $this->set_user_with_capability_maps($workflow_manager);

        $options = $this->get_query_options(-10, 1);
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_failed($result);
        $this->assertStringEndsWith('The cursor needs a limit to be set, 0 or greater', $result[1]);
    }

    public function test_execute_query_with_application_id_filter() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application1 = $this->create_submitted_application($workflow, $assignment, $user_entity);
        $application2 = $this->create_submitted_application($workflow, $assignment, $user_entity);
        $application3 = $this->create_submitted_application($workflow, $assignment, $user_entity);

        $workflow_manager = $this->create_workflow_manager();
        $this->set_user_with_capability_maps($workflow_manager);

        $options = $this->get_query_options(10, 1, ['application_id' => [$application2->id, $application3->id]]);
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $items = $result['items'];

        $this->assertCount(2, $items);
        $app_ids = [$items[0]['id'], $items[1]['id']];
        $this->assertContainsEquals($application2->id, $app_ids);
        $this->assertContainsEquals($application3->id, $app_ids);
    }

    public function test_execute_query_sorted_by_workflow_type_name() {
        $this->setAdminUser();
        list($workflow_c, $framework, $assignment_c) = $this->create_workflow_and_assignment('Workflow C');
        list($workflow_b, $framework, $assignment_b) = $this->create_workflow_and_assignment('Workflow B');
        list($workflow_a, $framework, $assignment_a) = $this->create_workflow_and_assignment('Workflow A');
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application0 = $this->create_submitted_application($workflow_a, $assignment_a, $user_entity);
        $application1 = $this->create_submitted_application($workflow_c, $assignment_c, $user_entity);
        $application2 = $this->create_submitted_application($workflow_b, $assignment_b, $user_entity);
        $application3 = $this->create_submitted_application($workflow_a, $assignment_a, $user_entity);

        $workflow_manager = $this->create_workflow_manager();
        $this->set_user_with_capability_maps($workflow_manager);

        $options = $this->get_query_options(10, 1, null, 'WORKFLOW_TYPE_NAME');
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $items = $result['items'];

        $expected_order = [$application3->id, $application0->id, $application2->id, $application1->id];

        $this->assertCount(4, $items);
        $result_order = array_map(
            function ($item) {
                return $item['id'];
            },
            $items
        );
        $this->assertEquals($expected_order, $result_order);
    }

    public function test_execute_query_filter_by_workflow_type_name() {
        $this->setAdminUser();
        list($workflow_c, $framework, $assignment_c) = $this->create_workflow_and_assignment('Workflow C');
        list($workflow_b, $framework, $assignment_b) = $this->create_workflow_and_assignment('Workflow B');
        list($workflow_a, $framework, $assignment_a) = $this->create_workflow_and_assignment('Workflow A');
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application0 = $this->create_submitted_application($workflow_a, $assignment_a, $user_entity);
        $application1 = $this->create_submitted_application($workflow_c, $assignment_c, $user_entity);
        $application2 = $this->create_submitted_application($workflow_b, $assignment_b, $user_entity);
        $application3 = $this->create_submitted_application($workflow_a, $assignment_a, $user_entity);

        $workflow_manager = $this->create_workflow_manager();
        $this->set_user_with_capability_maps($workflow_manager);

        // Load applications in workflow_type C.
        $options = $this->get_query_options(10, 1, ['workflow_type_name' => 'Workflow C']);
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $items = $result['items'];
        $this->assertCount(1, $items);
        $this->assertEquals($application1->id, $items[0]['id']);

        // Load applications in workflow_type A.
        $options = $this->get_query_options(10, 1, ['workflow_type_name' => 'Workflow A']);
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $items = $result['items'];
        $this->assertCount(2, $items);
    }

    public function test_execute_query_sorted_by_applicant_name() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'Alicia', 'lastname' => 'Zebra']);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Zoe', 'lastname' => 'Anteater']);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'Alex', 'lastname' => 'Gnu']);
        $this->setUser($user1);
        $user1_entity = new user_entity($user1->id);
        $application0 = $this->create_submitted_application($workflow, $assignment, $user1_entity);
        $this->setUser($user2);
        $user2_entity = new user_entity($user2->id);
        $application1 = $this->create_submitted_application($workflow, $assignment, $user2_entity);
        $this->setUser($user3);
        $user3_entity = new user_entity($user3->id);
        $application2 = $this->create_submitted_application($workflow, $assignment, $user3_entity);

        $workflow_manager = $this->create_workflow_manager();
        $this->set_user_with_capability_maps($workflow_manager);

        $options = $this->get_query_options(10, 1, null, 'APPLICANT_NAME');
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $items = $result['items'];

        $expected_order = [$application2->id, $application0->id, $application1->id];

        $this->assertCount(3, $items);
        $result_order = array_map(
            function ($item) {
                return $item['id'];
            },
            $items
        );
        $this->assertEquals($expected_order, $result_order);
    }

    public function test_execute_query_filter_by_applicant_name() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'Alicia', 'lastname' => 'Zebra']);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Zoe', 'lastname' => 'Anteater']);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'Alex', 'lastname' => 'Gnu']);
        $this->setUser($user1);
        $user1_entity = new user_entity($user1->id);
        $application0 = $this->create_submitted_application($workflow, $assignment, $user1_entity);
        $this->setUser($user2);
        $user2_entity = new user_entity($user2->id);
        $application1 = $this->create_submitted_application($workflow, $assignment, $user2_entity);
        $this->setUser($user3);
        $user3_entity = new user_entity($user3->id);
        $application2 = $this->create_submitted_application($workflow, $assignment, $user3_entity);

        $workflow_manager = $this->create_workflow_manager();
        $this->set_user_with_capability_maps($workflow_manager);

        $options = $this->get_query_options(10, 1, ['applicant_name' => 'al']);
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $items = $result['items'];
        $expected_ids = [$application2->id, $application0->id];
        $this->assertCount(2, $items);
        $result_ids = array_map(
            function ($item) {
                return $item['id'];
            },
            $items
        );
        $this->assertEqualsCanonicalizing($expected_ids, $result_ids);

        $options = $this->get_query_options(10, 1, ['applicant_name' => 'alic']);
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $items = $result['items'];
        $expected_ids = [$application0->id];
        $this->assertCount(1, $items);
        $result_ids = array_map(
            function ($item) {
                return $item['id'];
            },
            $items
        );
        $this->assertEqualsCanonicalizing($expected_ids, $result_ids);
    }

    public function test_execute_query_filter_by_your_progress() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        list($workflow2, $framework, $assignment2) = $this->create_workflow_and_assignment();
        $applicant = new user_entity($this->getDataGenerator()->create_user()->id);
        $this->setUser($applicant);
        $application1 = $this->create_submitted_application($workflow, $assignment, $applicant);
        $application2 = $this->create_submitted_application($workflow2, $assignment2, $applicant);

        // Create a workflow manager, who is also a level1 approver on workflow2 / application2
        $this->setAdminUser();
        $manager = $this->create_workflow_manager();
        $level1_2 = $application2->current_approval_level;
        $approver_go = new \mod_approval\testing\assignment_approver_generator_object($assignment2->id, $level1_2->id, user::TYPE_IDENTIFIER, $manager->id);
        $assignment_approver2 = $this->generator()->create_assignment_approver($approver_go);

        $this->set_user_with_capability_maps($manager);
        $options = $this->get_query_options(10, 1);
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertCount(2, $result['items']);

        $options = $this->get_query_options(10, 1, ['your_progress' => 'PENDING']);
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertCount(1, $result['items']);
        $this->assertEquals($application2->id, $result['items'][0]['id']);
        $this->assertEquals('PENDING', $result['items'][0]['your_progress']);

        $options = $this->get_query_options(10, 1, ['your_progress' => 'NA']);
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertCount(1, $result['items']);
        $this->assertEquals($application1->id, $result['items'][0]['id']);
        $this->assertEquals('NA', $result['items'][0]['your_progress']);
    }

    /**
     * Set up some paging query options.
     *
     * @param string|null $cursor
     * @param int $limit
     * @param array|null $filters
     * @param string|null $sort_by
     * @return array[][] options for resolver
     */
    private function get_query_options($limit = 10, $page = 1, $filters = null, $sort_by = null): array {
        $options = [
            'pagination' => [
                'limit' => $limit,
                'page' => $page,
            ],
        ];
        if (isset($filters)) {
            $options['filters'] = $filters;
        }
        if (isset($sort_by)) {
            $options['sort_by'] = $sort_by;
        }
        return ['query_options' => $options];
    }
}
