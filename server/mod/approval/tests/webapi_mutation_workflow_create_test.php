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

use core\orm\query\exceptions\record_not_found_exception;
use mod_approval\exception\access_denied_exception;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\entity\workflow\workflow_type as workflow_type_entity;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\form\form_version;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\workflow_create
 */
class mod_approval_webapi_mutation_workflow_create_testcase extends mod_approval_testcase {

    use webapi_phpunit_helper;

    private const MUTATION = 'mod_approval_workflow_create';

    /**
     * @covers ::resolve
     */
    public function test_workflow_create(): void {
        [$workflow_type, $form, $agency] = $this->generate_data();
        $this->setAdminUser();

        $args = [
            'input' => [
                'name' => 'Test Name',
                'workflow_type_id' => $workflow_type->id,
                'form_id' => $form->id,
                'description' => 'Test Description',
                'id_number' => 'Test ID Number',
            ],
        ];

        /** @var workflow $workflow */
        ['workflow' => $workflow] = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertNotEmpty($workflow->id);
        $this->assertEquals($args['input']['name'], $workflow->name);
        $this->assertEquals($workflow_type->id, $workflow->workflow_type->id);
        $this->assertEquals($form->id, $workflow->form->id);
        $this->assertEquals($args['input']['name'], $workflow->name);
        $this->assertEquals($args['input']['description'], $workflow->description);
        $this->assertEquals($args['input']['id_number'], $workflow->id_number);

        // Check that the default stages are created. These are not returned in the result, so check the DB.
        $stages = workflow_stage_entity::repository()
            ->join([workflow_version_entity::TABLE, 'workflow_version'], 'workflow_version_id', '=', 'id')
            ->where('workflow_version.workflow_id', '=', $workflow->id)
            ->order_by('sortorder')
            ->get()
            ->to_array();
        self::assertCount(2, $stages);
        self::assertEquals(1, $stages[0]['sortorder']);
        self::assertEquals(get_string('default_workflow_start_stage_name', 'mod_approval'), $stages[0]['name']);
        self::assertEquals(form_submission::get_code(), $stages[0]['type_code']);
        self::assertEquals(2, $stages[1]['sortorder']);
        self::assertEquals(get_string('default_workflow_finished_stage_name', 'mod_approval'), $stages[1]['name']);
        self::assertEquals(finished::get_code(), $stages[1]['type_code']);
    }

    /**
     * @covers ::resolve
     */
    public function test_execute_query_failure_on_workflow_type() {
        [$workflow_type, $form, $agency] = $this->generate_data();
        $this->setAdminUser();

        $args = [
            'input' => [
                'name' => 'Test Name',
                'workflow_type_id' => '',
                'form_id' => $form->id,
                'description' => 'Test Description',
                'id_number' => 'Test ID Number',
            ],
        ];

        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('workflow_type_id is required', $ex->getMessage());
        }

        $args = [
            'input' => [
                'name' => 'Test Name',
                'workflow_type_id' => 78,
                'form_id' => $form->id,
                'description' => 'Test Description',
                'id_number' => 'Test ID Number',
            ],
        ];

        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('record_not_found_exception');
        } catch (record_not_found_exception $ex) {
            $this->assertStringContainsString('Can not find data record in database', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_execute_query_failure_on_form() {
        [$workflow_type, $form, $agency] = $this->generate_data();
        $this->setAdminUser();

        $args = [
            'input' => [
                'name' => 'Test Name',
                'workflow_type_id' => $workflow_type->id,
                'form_id' => '',
                'description' => 'Test Description',
                'id_number' => 'Test ID Number',
            ],
        ];

        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('form_id is required', $ex->getMessage());
        }

        $args = [
            'input' => [
                'name' => 'Test Name',
                'workflow_type_id' => $workflow_type->id,
                'form_id' => 56,
                'description' => 'Test Description',
                'id_number' => 'Test ID Number',
            ],
        ];

        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('record_not_found_exception');
        } catch (record_not_found_exception $ex) {
            $this->assertStringContainsString('Can not find data record in database', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_execute_query_failure_on_assignment() {
        [$workflow_type, $form, $agency] = $this->generate_data();
        $this->setAdminUser();

        $args = [
            'input' => [
                'name' => 'Test Name',
                'workflow_type_id' => $workflow_type->id,
                'form_id' => $form->id,
                'assignment_type' => assignment_type\organisation::get_enum(),
                'assignment_identifier' => 67,
                'description' => 'Test Description',
                'id_number' => 'Test ID Number',
            ],
        ];

        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('record_not_found_exception');
        } catch (record_not_found_exception $ex) {
            $this->assertStringContainsString('Can not find data record', $ex->getMessage());
        }

        $args = [
            'input' => [
                'name' => 'Test Name',
                'workflow_type_id' => $workflow_type->id,
                'form_id' => $form->id,
                'assignment_type' => assignment_type\cohort::get_enum(),
                'assignment_identifier' => 76,
                'description' => 'Test Description',
                'id_number' => 'Test ID Number1',
            ],
        ];

        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('record_not_found_exception');
        } catch (record_not_found_exception $ex) {
            $this->assertStringContainsString('Can not find data record', $ex->getMessage());
        }

        $args = [
            'input' => [
                'name' => 'Test Name',
                'workflow_type_id' => $workflow_type->id,
                'form_id' => $form->id,
                'assignment_type' => assignment_type\position::get_enum(),
                'assignment_identifier' => 8,
                'description' => 'Test Description',
                'id_number' => 'Test ID Number2',
            ],
        ];

        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('record_not_found_exception');
        } catch (record_not_found_exception $ex) {
            $this->assertStringContainsString('Can not find data record', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_execute_query_failure_on_capability() {
        $this->generate_data();
        $user = $this->create_user();
        $this->setUser($user);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, []);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot create workflow', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_execute_query_failure_on_guest() {
        $this->generate_data();
        $this->setGuestUser();
        try {
            $this->resolve_graphql_mutation(self::MUTATION, []);
            $this->fail('require_login_exception expected');
        } catch (require_login_exception $ex) {
            $this->assertStringContainsString('Course or activity not accessible. (Must be an authenticated user)', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_execute_query_unsuccessful() {
        [$workflow_type, $form, $agency] = $this->generate_data();
        $user = $this->create_user();
        $this->setUser($user);

        $args = [
            'input' => [
                'name' => 'Test Name',
                'workflow_type_id' => $workflow_type->id,
                'form_id' => $form->id,
                'assignment_type' => assignment_type\organisation::get_enum(),
                'assignment_identifier' => $agency->id,
                'description' => 'Test Description',
                'id_number' => 'Test ID Number',
            ],
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Cannot create workflow');
    }

    /**
     * @covers ::resolve
     */
    public function test_execute_query_successful() {
        [$workflow_type, $form, $agency] = $this->generate_data();
        $this->setAdminUser();

        $args = [
            'input' => [
                'name' => 'Test Name',
                'workflow_type_id' => $workflow_type->id,
                'form_id' => $form->id,
                'assignment_type' => assignment_type\organisation::get_enum(),
                'assignment_identifier' => $agency->id,
                'description' => 'Test Description',
                'id_number' => 'Test ID Number',
            ],
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result['workflow']);
        $workflow = $result['workflow'];
        $this->assertNotEmpty($workflow['id']);
        $this->assertEquals($args['input']['name'], $workflow['name']);
        $this->assertEquals($args['input']['description'], $workflow['description']);
        $this->assertEquals($args['input']['workflow_type_id'], $workflow['workflow_type']['id']);
        $this->assertEquals($args['input']['id_number'], $workflow['id_number']);
    }

    /**
     * @covers ::resolve
     */
    public function test_execute_query_required_values_only(): void {
        [$workflow_type, $form, $agency] = $this->generate_data();
        $this->setAdminUser();

        $args = [
            'input' => [
                'name' => 'Test Name',
                'workflow_type_id' => $workflow_type->id,
                'form_id' => $form->id,
                'description' => 'Test Description',
                'id_number' => 'Test ID Number',
                'assignment_type' => assignment_type\organisation::get_enum(),
                'assignment_identifier' => $agency->id,
            ],
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result['workflow']);
        $workflow = $result['workflow'];
        $this->assertNotEmpty($workflow['id']);
        $this->assertEquals($args['input']['name'], $workflow['name']);
        $this->assertEquals($args['input']['workflow_type_id'], $workflow['workflow_type']['id']);
        $this->assertEquals('Test Description', $workflow['description']);
        $this->assertNotEmpty($workflow['id_number']);
    }

    private function generate_data() {
        $workflow_type = new workflow_type_entity();
        $workflow_type->name = 'New workflow type';
        $workflow_type->active = true;
        $workflow_type->save();

        $form = form::create('simple', 'test form');
        $json_schema = file_get_contents(__DIR__ . "/fixtures/schema/test.json");
        $form_version = form_version::create($form, 'test form version', $json_schema);

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $hierarchy_generator->create_framework('organisation');
        $agency = $hierarchy_generator->create_org(
            [
                'frameworkid' => $framework->id,
                'fullname' => 'Agency',
                'idnumber' => '001',
                'shortname' => 'org'
            ]
        );
        return [$workflow_type, $form, $agency];
    }
}