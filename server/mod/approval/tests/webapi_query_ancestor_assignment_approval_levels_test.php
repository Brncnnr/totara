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

use core\orm\query\exceptions\record_not_found_exception;
use core_phpunit\testcase;
use mod_approval\entity\assignment\assignment;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\assignment\approver_type as approver_type;
use mod_approval\model\assignment\assignment_approval_level;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_approver_generator_object;
use mod_approval\testing\override_assignments_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_approval\webapi\resolver\query\ancestor_assignment_approval_levels
 *
 * @group approval_workflow
 */
class mod_approval_webapi_query_ancestor_assignment_approval_levels_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;
    use override_assignments_test_setup;

    private $query = 'mod_approval_ancestor_assignment_approval_levels';

    public function test_query_without_login() {
        list($workflow, $stages, $override_assignments) = $this->create_workflow_with_basic_override_assignments();

        $args = [
            'input' => [
                'assignment_id' => $override_assignments[0]->id,
                'workflow_stage_id' => $stages[1]->id,
            ],
        ];

        $this->setUser(0);
        $this->expectException('require_login_exception');
        $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_as_guest() {
        list($workflow, $stages, $override_assignments) = $this->create_workflow_with_basic_override_assignments();

        $args = [
            'input' => [
                'assignment_id' => $override_assignments[0]->id,
                'workflow_stage_id' => $stages[1]->id,
            ],
        ];

        $this->setGuestUser();
        $this->expectException('require_login_exception');
        $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_as_admin() {
        list($workflow, $stages, $override_assignments) = $this->create_workflow_with_basic_override_assignments();

        $args = [
            'input' => [
                'assignment_id' => $override_assignments[0]->id,
                'workflow_stage_id' => $stages[1]->id,
            ],
        ];

        $this->setAdminUser();
        $result = $this->resolve_graphql_query($this->query, $args);
        $this->assertCount(2, $result['items']);
        $items = $result['items'];
        $this->assertInstanceOf(assignment_approval_level::class, $items[0]);
        $this->assertEquals($workflow->default_assignment->id, $items[0]->assignment->id);
    }

    public function test_query_as_user() {
        list($workflow, $stages, $override_assignments) = $this->create_workflow_with_basic_override_assignments();

        $args = [
            'input' => [
                'assignment_id' => $override_assignments[0]->id,
                'workflow_stage_id' => $stages[1]->id,
            ],
        ];

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(access_denied_exception::class);
        $this->expectExceptionMessage('Cannot manage workflow approvers');
        $this->resolve_graphql_query($this->query, $args);
    }

    public function test_query_with_invalid_parameters() {
        list($workflow, $stages, $override_assignments) = $this->create_workflow_with_basic_override_assignments();

        // Invalid assignment test.
        $args = [
            'input' => [
                'assignment_id' => -1,
                'workflow_stage_id' => $stages[1]->id,
            ],
        ];
        try {
            $this->resolve_graphql_query($this->query, $args);
            $this->fail('Invalid assignment not rejected');
        } catch (moodle_exception $e) {
            $this->assertStringContainsString('Invalid assignment', $e->getMessage());
        }

        // Invalid approval_level test.
        $args = [
            'input' => [
                'assignment_id' => $override_assignments[0]->id,
                'workflow_stage_id' => -1,
            ],
        ];
        try {
            $this->resolve_graphql_query($this->query, $args);
            $this->fail('Invalid workflow_stage not rejected');
        } catch (record_not_found_exception $e) {
            $this->assertStringContainsString('Can not find data record in database', $e->getMessage());
        }
    }

    public function test_execute_query() {
        list($workflow, $stages, $override_assignments) = $this->create_workflow_with_basic_override_assignments();
        $this->assertCount(2, $stages[1]->approval_levels);
        $level_2 = $stages[1]->approval_levels->last();

        $args = [
            'input' => [
                'assignment_id' => $override_assignments[2]->id,
                'workflow_stage_id' => $stages[1]->id,
            ],
        ];

        // Check assumptions about generator.
        $assignment = \mod_approval\model\assignment\assignment::load_by_id($override_assignments[2]->id);
        $approval_level = \mod_approval\model\workflow\workflow_stage_approval_level::load_by_id($level_2->id);
        $current_assignment_approval_level = new assignment_approval_level($assignment, $approval_level);
        $this->assertEquals($override_assignments[2]->id, $current_assignment_approval_level->assignment->id);
        $this->assertEquals('Extra level', $current_assignment_approval_level->approval_level->name);

        // Add an extra approver for override level 2.
        $approver1 = $this->getDataGenerator()->create_user();
        $obj = new assignment_approver_generator_object(
            $assignment->id,
            $approval_level->id,
            approver_type\user::TYPE_IDENTIFIER,
            $approver1->id,
        );
        $this->generator()->create_assignment_approver($obj);
        $current_approvers = $current_assignment_approval_level->get_approvers();
        $this->assertCount(2, $current_approvers);

        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $items = $result['items'];
        $this->assertCount(2, $items);
        $this->assertEquals('Sub-agency A', $items[1]['assignment']['name']);
        $this->assertEquals('Extra level', $items[1]['approval_level']['name']);
        $this->assertCount(1, $items[1]['approvers']);
        $this->assertEquals('RELATIONSHIP', $items[1]['approvers'][0]['type']);
    }

    public function test_execution_failure() {
        list($workflow, $stages, $override_assignments) = $this->create_workflow_with_basic_override_assignments();

        // Just straight-up missing an arg.
        $args = [
            'input' => [
                'assignment_id' => $override_assignments[2]->id,
            ],
        ];

        $result = $this->parsed_graphql_operation($this->query, $args);
        $this->assert_webapi_operation_failed($result);
    }
}
