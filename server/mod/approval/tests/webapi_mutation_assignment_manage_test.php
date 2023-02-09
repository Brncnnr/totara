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

use core_phpunit\testcase;
use mod_approval\testing\generator as approval_generator;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_generator_object;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\workflow\workflow;
use mod_approval\entity\workflow\workflow as workflow_entity;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 */
class mod_approval_webapi_mutation_assignment_manage_testcase extends testcase {

    private const MUTATION = 'mod_approval_assignment_manage';

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    /**
     * Gets the approval workflow generator instance
     *
     * @return approval_generator
     */
    protected function generator(): approval_generator {
        return approval_generator::instance();
    }

    public function test_create() {
        /** @var workflow_entity $workflow */
        [, $framework, $workflow_stage_id] = $this->generate_data();
        $args['input'] = [
            'type' => assignment_type\organisation::get_enum(),
            'identifier' => $framework->agency->subagency_a->id,
            'workflow_stage_id' => $workflow_stage_id
        ];
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);

        $this->assertNotEmpty($result->assignment);
        $assignment_result = $result->assignment;
        $this->assertEquals(assignment_type\organisation::get_code(), $assignment_result->assignment_type);
        $this->assertEquals($framework->agency->subagency_a->id, $assignment_result->assignment_identifier);
        $this->assertEquals($framework->agency->subagency_a->shortname, $assignment_result->id_number);
        $this->assertNotEmpty($assignment_result->contextid);
        $assignment = assignment::load_by_id($assignment_result->id);
        $this->assertEquals($assignment_result->contextid, $assignment->get_contextid());
        $this->assertNotEmpty($result->assignment_approval_levels);
        $this->assertInstanceOf('mod_approval\model\assignment\assignment', $result->assignment);
        $this->assertInstanceOf('mod_approval\model\assignment\assignment_approval_level', $result->assignment_approval_levels[0]);
        // Check that the new assignment is activated.
        $this->assertTrue($assignment->is_active());
    }

    public function test_execute_query_successful() {
        /** @var workflow_entity $workflow */
        [, $framework, $workflow_stage_id, ] = $this->generate_data();

        $args['input'] = [
            'type' => 'ORGANISATION',
            'identifier' => $framework->agency->subagency_a->id,
            'workflow_stage_id' => $workflow_stage_id
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');
    }

    public function test_failed_ajax_call() {
        /** @var workflow_entity $workflow */
        [, $framework, $workflow_stage_id, ] = $this->generate_data();

        $args['input'] = [
            'type' => 'ORGANISATION',
            'identifier' => $framework->agency->id,
            'workflow_stage_id' => $workflow_stage_id
        ];

        $this->setGuestUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Course or activity not accessible. (Must be an authenticated user)');
    }

    /**
     * Generates the setup used by these tests.
     *
     * @return array of workflow, organisation framework, workflow_stage ID of approvals stage, assignment
     */
    private function generate_data() {
        list($workflow_entity, $framework, $assignment_entity) = $this->create_workflow_and_assignment();
        $workflow = workflow::load_by_entity($workflow_entity);
        $stage1 = $workflow->latest_version->stages->first();
        $stage2 = $workflow->latest_version->get_next_stage($stage1->id);
        $assignment = assignment::load_by_entity($assignment_entity);

        // Make sure the framework ids to be used in GQL args are ints.
        $framework->agency->id = (int)$framework->agency->id;
        $framework->agency->subagency_a->id = (int)$framework->agency->subagency_a->id;

        return [$workflow, $framework, $stage2->id, $assignment];
    }
}