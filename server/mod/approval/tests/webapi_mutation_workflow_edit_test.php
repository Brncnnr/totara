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

use core\entity\user;
use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use mod_approval\exception\access_denied_exception;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\workflow\workflow;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\workflow_edit
 */
class mod_approval_webapi_mutation_workflow_edit_testcase extends mod_approval_testcase {
    private const MUTATION = 'mod_approval_workflow_edit';

    /** @var user */
    private $user;
    /** @var workflow */
    private $workflow;

    use webapi_phpunit_helper;

    public function setUp(): void {
        parent::setUp();
        $this->user = $this->create_user();
        $this->setUser($this->user);
        $this->workflow = $this->create_workflow_for_user();
        $this->setAdminUser();
    }

    public function tearDown(): void {
        $this->user = $this->workflow = null;
        parent::tearDown();
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_admin() {
        $args = $this->get_args();
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertInstanceOf(workflow::class, $result['workflow']);
        $this->assertEquals($this->workflow->id, $result['workflow']->id);
        $new_name = $this->workflow->name . ' 2';
        $this->assertEquals($new_name, $result['workflow']->name);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_manager() {
        $manager = $this->create_user();
        $role_id = builder::table('role')->where('shortname', 'manager')->one()->id;
        role_assign($role_id, $manager->id, $this->workflow->get_context());
        $this->setUser($manager);
        $args = $this->get_args();
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot update workflow', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_user() {
        $user = $this->create_user();
        $this->setUser($user);
        $args = $this->get_args();
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot update workflow', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_guest() {
        $this->setGuestUser();
        $args = $this->get_args();
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('require_login_exception expected');
        } catch (require_login_exception $ex) {
            $this->assertStringContainsString('(Must be an authenticated user)', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_nonexisting_workflow() {
        $args = $this->get_args();
        builder::table(workflow_entity::TABLE)->where('id', $this->workflow->id)->delete();
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
            $this->assertStringContainsString('Can not find data record in database.', $ex->getMessage());
        }
    }

    public function test_execute_query_successful() {
        $args = $this->get_args();

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $new_name = $this->workflow->name . ' 2';
        $new_id_number = $this->workflow->id_number . '2';
        $new_description = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.';
        $this->assertEquals($this->workflow->id, $result['workflow']['id']);
        $this->assertEquals($new_name, $result['workflow']['name']);
        $this->assertEquals($new_id_number, $result['workflow']['id_number']);
        $this->assertEquals($new_description, $result['workflow']['description']);
    }

    public function test_execute_query_unsuccessful() {
        $this->setUser($this->user);
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id,
                'name' => '?',
                'id_number' => '?',
            ]
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Cannot update workflow');
    }

    public function test_execute_query_cumulatively() {
        $this->setAdminUser();

        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id,
                'name' => 'New name',
                'id_number' => 'New id number',
                'description' => 'New description',
            ]
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertEquals('New name', $result['workflow']['name']);
        $this->assertEquals('New id number', $result['workflow']['id_number']);
        $this->assertEquals('New description', $result['workflow']['description']);

        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id,
                'name' => 'New name 2',
                'id_number' => 'New id number 2',
                // no description
            ]
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertEquals('New name 2', $result['workflow']['name']);
        $this->assertEquals('New id number 2', $result['workflow']['id_number']);
        $this->assertEquals('', $result['workflow']['description']);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_with_invalid_input() {
        $args = [
            'workflow_id' => $this->workflow->id
        ];
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid workflow_id', $ex->getMessage());
        }

        $invalid_args = $args = $this->get_args();
        $invalid_args['input']['name'] = '';
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $invalid_args);
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('Workflow name cannot be empty', $ex->getMessage());
        }

        $invalid_args = $args;
        $invalid_args['input']['id_number'] = '';
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $invalid_args);
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('Workflow id_number cannot be empty', $ex->getMessage());
        }
    }

    private function get_args() {
        return [
            'input' => [
                'workflow_id' => $this->workflow->id,
                'name' => $this->workflow->name . ' 2',
                'id_number' => $this->workflow->id_number . '2',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.'
            ]
        ];
    }
}