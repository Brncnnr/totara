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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

use core_phpunit\testcase;
use mod_approval\entity\assignment\assignment;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\assignment\approver_type\user;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_approver_generator_object;
use mod_approval\testing\override_assignments_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_approval\webapi\resolver\query\override_assignments
 *
 * @group approval_workflow
 */
class mod_approval_webapi_query_override_assignments_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;
    use override_assignments_test_setup;

    private $query = 'mod_approval_override_assignments';

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function workflow_generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    public function test_query_without_login() {
        list(, $stages) = $this->create_workflow_with_basic_override_assignments();

        $args = [
            'input' => [
                'workflow_stage_id' => $stages[0]->id,
            ],
        ];

        $this->setUser(0);
        $this->expectException('require_login_exception');
        $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_as_guest() {
        list(, $stages) = $this->create_workflow_with_basic_override_assignments();

        $args = [
            'input' => [
                'workflow_stage_id' => $stages[0]->id,
            ],
        ];

        $this->setGuestUser();
        $this->expectException('require_login_exception');
        $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_as_admin() {
        list(, $stages, $override_assignments) = $this->create_workflow_with_basic_override_assignments();

        $args = [
            'input' => [
                'workflow_stage_id' => $stages[0]->id,
            ],
        ];

        $this->setAdminUser();
        $result = $this->resolve_graphql_query($this->query, $args);
        self::assertCount(count($override_assignments), $result->items);
    }

    public function test_query_as_user() {
        list(, $stages) = $this->create_workflow_with_basic_override_assignments();

        $args = [
            'input' => [
                'workflow_stage_id' => $stages[0]->id,
            ],
        ];

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(access_denied_exception::class);
        $this->expectExceptionMessage('Cannot manage workflow approvers');
        $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_multiple_pages() {
        list(, $stages, $override_assignments) = $this->create_workflow_with_basic_override_assignments();

        // Get page 1 with 1 item on the page - first page test.
        $args = $this->get_args($stages[0]->id, 1, 1, null, 'NAME_DESC');
        $result = $this->resolve_graphql_query($this->query, $args);
        self::assertEquals(5, $result->total);
        self::assertCount(1, $result->items);
        self::assertNotEmpty($result->next_cursor);
        /** @var assignment $assignment */
        $assignment_approvals = reset($result->items);
        self::assertEquals($override_assignments[3]->id, $assignment_approvals->assignment->id);

        // Get page 2 with 1 item on the page - middle page test.
        $args = $this->get_args($stages[0]->id, 1, 2, null, 'NAME_DESC');
        $result = $this->resolve_graphql_query($this->query, $args);
        self::assertEquals(5, $result->total);
        self::assertCount(1, $result->items);
        self::assertNotEmpty($result->next_cursor);
        /** @var assignment $assignment */
        $assignment_approvals = reset($result->items);
        self::assertEquals($override_assignments[2]->id, $assignment_approvals->assignment->id);

        // Get page 5 with 1 item on the page - last page with count == limit test.
        $args = $this->get_args($stages[0]->id, 1, 5, null, 'NAME_DESC');
        $result = $this->resolve_graphql_query($this->query, $args);
        self::assertEquals(5, $result->total);
        self::assertCount(1, $result->items);
        self::assertEmpty($result->next_cursor);
        /** @var assignment $assignment */
        $assignment_approvals = reset($result->items);
        self::assertEquals($override_assignments[4]->id, $assignment_approvals->assignment->id);

        // Get page 1 with 3 items on the page - multi-item test.
        $args = $this->get_args($stages[0]->id, 3, 1, null, 'NAME_DESC');
        $result = $this->resolve_graphql_query($this->query, $args);
        self::assertEquals(5, $result->total);
        self::assertCount(3, $result->items);
        self::assertNotEmpty($result->next_cursor);

        // Get page 2 with 3 items on the page - last page with count != limit test.
        $args = $this->get_args($stages[0]->id, 3, 2, null, 'NAME_DESC');
        $result = $this->resolve_graphql_query($this->query, $args);
        self::assertEquals(5, $result->total);
        self::assertCount(2, $result->items);
        self::assertEmpty($result->next_cursor);
        /** @var assignment $assignment */
        $assignment_approvals = reset($result->items);
        self::assertEquals($override_assignments[0]->id, $assignment_approvals->assignment->id);

        // Get everything in one page - start and end on same page test.
        $args = $this->get_args($stages[0]->id, 6, 1, null, 'NAME_DESC');
        $result = $this->resolve_graphql_query($this->query, $args);
        self::assertEquals(5, $result->total);
        self::assertCount(5, $result->items);
        self::assertEmpty($result->next_cursor);
    }

    public function test_query_with_invalid_parameters() {
        list(, $stages) = $this->create_workflow_with_basic_override_assignments();

        // Invalid limit test
        $options = $this->get_args($stages[0]->id, -10);
        try {
            $this->resolve_graphql_query($this->query, $options);
            $this->fail('Invalid limit not rejected');
        } catch (coding_exception $e) {
            self::assertStringContainsString('The cursor needs a limit to be set, 0 or greater', $e->getMessage());
        }

        // Invalid page test
        $options = $this->get_args($stages[0]->id, 10, -10);
        try {
            $this->resolve_graphql_query($this->query, $options);
            $this->fail('Invalid limit not rejected');
        } catch (coding_exception $e) {
            self::assertStringContainsString('Page has to be a positive integer', $e->getMessage());
        }

        // Invalid filter test
        $options = $this->get_args($stages[0]->id, 10, 1, ['invalid_filter' => 'something']);
        try {
            $this->resolve_graphql_query($this->query, $options);
            $this->fail('Invalid filter not rejected');
        } catch (coding_exception $e) {
            self::assertStringContainsString("Filtering by 'invalid_filter' is not supported", $e->getMessage());
        }

        // Invalid sort_by test
        $options = $this->get_args($stages[0]->id, 10, 1, null, 'invalid_sorter');
        try {
            $this->resolve_graphql_query($this->query, $options);
            $this->fail('Invalid sort_by not rejected');
        } catch (coding_exception $e) {
            self::assertStringContainsString("Sorting by 'invalid_sorter' is not supported", $e->getMessage());
        }
    }

    public function test_execute_query() {
        $data = $this->create_workflow_with_complex_override_assignments();

        // Add an extra approver for override level 2, but inactive.
        $approver1 = $this->getDataGenerator()->create_user();
        $obj = new assignment_approver_generator_object(
            $data['test_workflow']['override_assignments'][2]->id,
            $data['test_workflow']['level2']->id,
            user::TYPE_IDENTIFIER,
            $approver1->id,
        );
        $obj->active = false;
        $this->generator()->create_assignment_approver($obj);

        $args = $this->get_args($data['test_workflow']['stage']->id, 10, 1, null, 'NAME_DESC');
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);

        $this->assertCount(6, $result['items']);
        $this->assertEquals(6, $result['total']);

        // Assert items are sorted by assignment name in descending order.
        $this->assertEquals('Sub-agency B', $result['items'][0]['assignment']['name']);
        $this->assertEquals('Sub-agency A Program B', $result['items'][1]['assignment']['name']);
        $this->assertEquals('Sub-agency A Program A', $result['items'][2]['assignment']['name']);
        $this->assertEquals('Sub-agency A', $result['items'][3]['assignment']['name']);
        $this->assertEquals('Control Sub-agency A Program A', $result['items'][4]['assignment']['name']);
        $this->assertEquals('Audience', $result['items'][5]['assignment']['name']);
        $this->assertNotEmpty($result['items'][0]['assignment']['contextid']);
        $this->assertNotEmpty($result['items'][1]['assignment']['contextid']);
        $this->assertNotEmpty($result['items'][2]['assignment']['contextid']);
        $this->assertNotEmpty($result['items'][3]['assignment']['contextid']);
        $this->assertNotEmpty($result['items'][4]['assignment']['contextid']);
        $this->assertNotEmpty($result['items'][5]['assignment']['contextid']);

        $levels = $result['items'][0]['assignment_approval_levels'];
        $this->assertCount(2, $levels);
        $this->assertEquals('Level 1', $levels[0]['approval_level']['name']);
        $this->assertEquals('Agency', $levels[0]['inherited_from_assignment_approval_level']['assignment']['name']);
        $this->assertEmpty($levels[0]['approvers']);
        $this->assertEquals('Level 2', $levels[1]['approval_level']['name']);
        $this->assertEquals('Agency', $levels[1]['inherited_from_assignment_approval_level']['assignment']['name']);
        $this->assertEmpty($levels[1]['approvers']);

        $levels = $result['items'][1]['assignment_approval_levels'];
        $this->assertCount(2, $levels);
        $this->assertEquals('Level 1', $levels[0]['approval_level']['name']);
        $this->assertEquals('Sub-agency A', $levels[0]['inherited_from_assignment_approval_level']['assignment']['name']);
        $this->assertEmpty($levels[0]['approvers']);
        $this->assertEquals('Level 2', $levels[1]['approval_level']['name']);
        $this->assertEmpty($levels[1]['inherited_from_assignment_approval_level']);
        $level_approvers = $levels[1]['approvers'];

        $this->assertCount(2, $level_approvers);

        $approver_names = [];
        foreach ($level_approvers as $approver) {
            $this->assertEquals(user::get_enum(), $approver['type']);
            $approver_names[] = $approver['approver_entity']['name'];
        }

        $this->assertEqualsCanonicalizing(
            [
                $data['test_workflow']['user1']->fullname,
                $data['test_workflow']['user2']->fullname,
            ],
            $approver_names
        );

        $levels = $result['items'][2]['assignment_approval_levels'];
        $this->assertCount(2, $levels);
        $this->assertEquals('Level 1', $levels[0]['approval_level']['name']);
        $this->assertEmpty($levels[0]['inherited_from_assignment_approval_level']);
        $this->assertCount(1, $levels[0]['approvers']);
        $this->assertEquals('RELATIONSHIP', $levels[0]['approvers'][0]['type']);
        $this->assertEquals('Manager', $levels[0]['approvers'][0]['approver_entity']['name']);
        $this->assertEquals('Level 2', $levels[1]['approval_level']['name']);
        $this->assertEquals('Agency', $levels[1]['inherited_from_assignment_approval_level']['assignment']['name']);
        $this->assertEmpty($levels[1]['approvers']);

        $levels = $result['items'][3]['assignment_approval_levels'];
        $this->assertCount(2, $levels);
        $this->assertEquals('Level 1', $levels[0]['approval_level']['name']);
        $this->assertEmpty($levels[0]['inherited_from_assignment_approval_level']);
        $this->assertCount(1, $levels[0]['approvers']);
        $this->assertEquals('RELATIONSHIP', $levels[0]['approvers'][0]['type']);
        $this->assertEquals('Manager', $levels[0]['approvers'][0]['approver_entity']['name']);
        $this->assertEquals('Level 2', $levels[1]['approval_level']['name']);
        $this->assertEquals('Agency', $levels[1]['inherited_from_assignment_approval_level']['assignment']['name']);
        $this->assertEmpty($levels[1]['approvers']);

        $levels = $result['items'][4]['assignment_approval_levels'];
        $this->assertCount(2, $levels);
        $this->assertEquals('Level 1', $levels[0]['approval_level']['name']);
        $this->assertEquals('Agency', $levels[0]['inherited_from_assignment_approval_level']['assignment']['name']);
        $this->assertEmpty($levels[0]['approvers']);
        $this->assertEquals('Level 2', $levels[1]['approval_level']['name']);
        $this->assertEquals('Agency', $levels[1]['inherited_from_assignment_approval_level']['assignment']['name']);
        $this->assertEmpty($levels[1]['approvers']);

        $levels = $result['items'][5]['assignment_approval_levels'];
        $this->assertCount(2, $levels);
        $this->assertEquals('Level 1', $levels[0]['approval_level']['name']);
        $this->assertEmpty($levels[0]['inherited_from_assignment_approval_level']);
        $this->assertCount(1, $levels[0]['approvers']);
        $this->assertEquals('RELATIONSHIP', $levels[0]['approvers'][0]['type']);
        $this->assertEquals('Manager', $levels[0]['approvers'][0]['approver_entity']['name']);
        $this->assertEquals('Level 2', $levels[1]['approval_level']['name']);
        $this->assertEquals('Agency', $levels[1]['inherited_from_assignment_approval_level']['assignment']['name']);
        $this->assertEmpty($levels[1]['approvers']);
    }

    public function test_execute_query_passing_with_no_options() {
        list(, $stages) = $this->create_workflow_with_basic_override_assignments();

        $args = [
            'input' => [
                'workflow_stage_id' => $stages[0]->id,
            ],
        ];

        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        self::assertNotEmpty($result, 'result empty');

        self::assertCount(5, $result['items']);
        self::assertEquals(5, $result['total']);
        self::assertEmpty($result['next_cursor']);
    }

    public function test_execute_query_failing_with_an_invalid_parameter() {
        list(, $stages) = $this->create_workflow_with_basic_override_assignments();

        $args = $this->get_args($stages[0]->id, -10);
        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_failed($result);
        self::assertStringEndsWith('The cursor needs a limit to be set, 0 or greater', $result[1]);
    }

    public function test_execute_query_sorted_by_name_asc() {
        list(, $stages, $override_assignments) = $this->create_workflow_with_basic_override_assignments();

        $options = $this->get_args($stages[0]->id, 10, 1, null, 'NAME_ASC');
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $items = $result['items'];

        $expected_order = [
            $override_assignments[4]->id,
            $override_assignments[0]->id,
            $override_assignments[1]->id,
            $override_assignments[2]->id,
            $override_assignments[3]->id,
        ];

        self::assertCount(5, $items);
        $result_order = array_map(
            function ($item) {
                return $item['assignment']['id'];
            },
            $items
        );
        self::assertEquals($expected_order, $result_order);
    }

    public function test_execute_query_sorted_by_name_desc() {
        list(, $stages, $override_assignments) = $this->create_workflow_with_basic_override_assignments();

        $options = $this->get_args($stages[0]->id, 10, 1, null, 'NAME_DESC');
        $result = $this->parsed_graphql_operation($this->query, $options);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $items = $result['items'];

        $expected_order = [
            $override_assignments[3]->id,
            $override_assignments[2]->id,
            $override_assignments[1]->id,
            $override_assignments[0]->id,
            $override_assignments[4]->id,
        ];

        self::assertCount(5, $items);
        $result_order = array_map(
            function ($item) {
                return $item['assignment']['id'];
            },
            $items
        );
        self::assertEquals($expected_order, $result_order);
    }

    /**
     * Tests filtering by name.
     *
     * override 0: sub a
     * override 1: sub a prog a
     * override 2: sub a prog b
     * override 3: sub b
     * override 4: audience
     *
     * @return void
     */
    public function test_execute_query_filtered_by_name(): void {
        list(, $stages, $override_assignments) = $this->create_workflow_with_basic_override_assignments();

        $filters = ['name' => 'prog'];
        $options = $this->get_args($stages[0]->id, 10, 1, $filters);

        $parsed_query = $this->parsed_graphql_operation($this->query, $options);
        $result = reset($parsed_query);
        $this->assertEquals(2, $result['total']);
        $this->assertCount(2, $result['items']);

        $assignment_ids = [];
        foreach ($result['items'] as $item) {
            $assignment_ids[] = $item['assignment']['id'];
        }
        $this->assertEqualsCanonicalizing([$override_assignments[1]->id, $override_assignments[2]->id], $assignment_ids);
    }

    /**
     * Set up the query args, including page, filter and sorting.
     *
     * @param int $workflow_stage_id
     * @param int $limit
     * @param int $page
     * @param null $filters
     * @param null $sort_by
     * @return array
     */
    private function get_args(int $workflow_stage_id, int $limit = 10, int $page = 1, $filters = null, $sort_by = null): array {
        $input = [
            'input' => [
                'workflow_stage_id' => $workflow_stage_id,
                'pagination' => [
                    'limit' => $limit,
                    'page' => $page,
                ],
            ],
        ];
        if (isset($filters)) {
            $input['input']['filters'] = $filters;
        }
        if (isset($sort_by)) {
            $input['input']['sort_by'] = $sort_by;
        }
        return $input;
    }

}
