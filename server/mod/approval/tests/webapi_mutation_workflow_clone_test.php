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

use mod_approval\exception\access_denied_exception;
use mod_approval\model\assignment\assignment_type\provider as assignment_type_provider;
use mod_approval\model\assignment\assignment_type\cohort as cohort_assignment_type;
use mod_approval\model\workflow\workflow;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\workflow_clone
 */
class mod_approval_webapi_mutation_workflow_clone_testcase extends mod_approval_testcase {
    private const MUTATION = 'mod_approval_workflow_clone';

    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_clone_with_capability(): void {
        $this->setAdminUser();
        $workflow = $this->create_workflow_for_user();
        $this->create_application_for_user_on($workflow); // Creates default assignment.
        $new_workflow_name = 'cloned workflow';

        /** @var workflow $result */
        ['workflow' => $result] = $this->resolve_graphql_mutation(self::MUTATION, [
            'input' => [
                'workflow_id' => $workflow->id,
                'name' => $new_workflow_name,
            ]
        ]);
        $this->assertNotEquals($workflow->id, $result->id);
        $this->assertEquals($new_workflow_name, $result->name);
    }

    /**
     * @covers ::resolve
     */
    public function test_clone_without_capability(): void {
        $this->setAdminUser();
        $workflow = $this->create_workflow_for_user();
        $this->create_application_for_user_on($workflow); // Creates default assignment.

        $this->setUser($this->getDataGenerator()->create_user());
        try {
            $this->resolve_graphql_mutation(self::MUTATION, [
                'input' => [
                    'workflow_id' => $workflow->id,
                    'name' => 'cloned workflow',
                ]
            ]);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot clone workflow', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_execute_query_successful() {
        $this->setAdminUser();
        $workflow = $this->create_workflow_for_user();
        $this->create_application_for_user_on($workflow); // Creates default assignment.
        $new_workflow_name = 'Learning to build';

        $args = [
            'input' => [
                'workflow_id' => $workflow->id,
                'name' => $new_workflow_name,
            ]
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result['workflow'], 'result empty');
        $new_workflow = workflow::load_by_id($result['workflow']['id']);
        $this->assertEquals($new_workflow_name, $new_workflow->name);
    }

    /**
     * @covers ::resolve
     */
    public function test_clone_with_new_default_assignment() {
        $this->setAdminUser();
        $workflow = $this->create_workflow_for_user();
        $new_workflow_name = 'Learning to build';

        $new_default_assignment =  [
            'type' => cohort_assignment_type::get_enum(),
            'id' => $this->getDataGenerator()->create_cohort()->id,
        ];

        $args = [
            'input' => [
                'workflow_id' => $workflow->id,
                'name' => $new_workflow_name,
                'default_assignment' => $new_default_assignment,
            ]
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $new_workflow = workflow::load_by_id($result['workflow']['id']);
        $this->assertEquals($new_workflow_name, $new_workflow->name);
        $this->assertEquals(assignment_type_provider::get_by_enum($new_default_assignment['type'])::get_code(), $new_workflow->default_assignment->assignment_type);
        $this->assertEquals($new_default_assignment['id'], $new_workflow->default_assignment->assignment_identifier);
    }

    /**
     * @covers ::resolve
     */
    public function test_clone_without_default_assignment() {
        $this->setAdminUser();
        $workflow = $this->create_workflow_for_user();
        $this->create_application_for_user_on($workflow); // Creates default assignment.
        $new_workflow_name = 'Learning to build';

        $args = [
            'input' => [
                'workflow_id' => $workflow->id,
                'name' => $new_workflow_name,
            ]
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $new_workflow = workflow::load_by_id($result['workflow']['id']);
        $this->assertEquals($new_workflow_name, $new_workflow->name);
        $this->assertEquals(
            $workflow->default_assignment->assignment_type,
            $new_workflow->default_assignment->assignment_type
        );
        $this->assertEquals(
            $workflow->default_assignment->assignment_identifier,
            $new_workflow->default_assignment->assignment_identifier
        );
    }

    /**
     * @covers ::resolve
     */
    public function test_execute_query_failure_on_active() {
        $this->setAdminUser();
        $workflow = $this->create_workflow_for_user();
        $this->create_application_for_user_on($workflow); // Creates default assignment.

        $this->setUser($this->getDataGenerator()->create_user());
        $args = [
            'input' => [
                'workflow_id' => $workflow->id,
                'name' => 'cloned workflow',
            ]
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Cannot clone workflow');
    }
}
