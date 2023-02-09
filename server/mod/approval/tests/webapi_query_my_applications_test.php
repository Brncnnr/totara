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
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_approval\webapi\resolver\query\my_applications
 *
 * @group approval_workflow
 * @group applications_dashboard
 */
class mod_approval_webapi_query_my_applications_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private $query = 'mod_approval_my_applications';

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    public function test_query_without_login() {
        global $DB;

        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application = $this->create_application($workflow, $assignment, $user_entity);

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
        $application = $this->create_application($workflow, $assignment, $user_entity);

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
        $application = $this->create_application($workflow, $assignment, $user_entity);

        $this->setAdminUser();
        $result = $this->resolve_graphql_query($this->query);
        $this->assertCount(0, $result->items);
    }

    public function test_query_as_user() {
        global $DB;

        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $user_entity = new user_entity($user1->id);
        $application = $this->create_application($workflow, $assignment, $user_entity);

        $records = $DB->get_records('approval_application', ['user_id' => $user1->id], 'id desc');

        $result = $this->resolve_graphql_query($this->query);
        $this->assertCount(1, $result->items);
        foreach ($records as $application) {
            $this->assertEquals($application->id, $result->items->item($application->id)->id);
        }
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
            $application = $this->create_application($workflow, $assignment, $user_entity);
        }

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
        $application = $this->create_application($workflow, $assignment, $user_entity);

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
        $application = $this->create_application($workflow, $assignment, $user_entity);

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
        $application = $this->create_application($workflow, $assignment, $user_entity);

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
        $application = $this->create_application($workflow, $assignment, $user_entity);

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
        $application1 = $this->create_application($workflow, $assignment, $user_entity);
        $application2 = $this->create_application($workflow, $assignment, $user_entity);
        $application3 = $this->create_application($workflow, $assignment, $user_entity);

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
        $application0 = $this->create_application($workflow_a, $assignment_a, $user_entity);
        $application1 = $this->create_application($workflow_c, $assignment_c, $user_entity);
        $application2 = $this->create_application($workflow_b, $assignment_b, $user_entity);
        $application3 = $this->create_application($workflow_a, $assignment_a, $user_entity);

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
