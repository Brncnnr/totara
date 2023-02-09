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

use core\entity\user;
use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use mod_approval\exception\access_denied_exception;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\workflow\workflow_stage;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\workflow_stage_edit
 */
class mod_approval_webapi_mutation_workflow_stage_edit_testcase extends mod_approval_testcase {
    private const MUTATION = 'mod_approval_workflow_stage_edit';

    /** @var user */
    private $user;
    /** @var workflow_stage */
    private $workflow_stage;

    use webapi_phpunit_helper;

    public function setUp(): void {
        parent::setUp();
        $this->user = $this->create_user();
        $this->setUser($this->user);
        $workflow = $this->create_workflow_for_user();
        $this->workflow_stage = $workflow->latest_version->stages->first();
        $this->setAdminUser();
    }

    public function tearDown(): void {
        $this->user = $this->workflow_stage = null;
        parent::tearDown();
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_admin() {
        $args = $this->get_args();
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertInstanceOf(workflow_stage::class, $result['stage']);
        $this->assertEquals($this->workflow_stage->id, $result['stage']->id);
        $new_name = $this->workflow_stage->name . ' 2';
        $this->assertEquals($new_name, $result['stage']->name);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_manager() {
        $manager = $this->create_user();
        $role_id = builder::table('role')->where('shortname', 'manager')->one()->id;
        role_assign($role_id, $manager->id, $this->workflow_stage->workflow_version->workflow->get_context());
        $this->setUser($manager);
        $args = $this->get_args();
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertInstanceOf(workflow_stage::class, $result['stage']);
        $this->assertEquals($this->workflow_stage->id, $result['stage']->id);
        $new_name = $this->workflow_stage->name . ' 2';
        $this->assertEquals($new_name, $result['stage']->name);
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
            $this->assertStringContainsString('Can not manage stages', $ex->getMessage());
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
    public function test_resolve_nonexisting_workflow_stage() {
        $args = $this->get_args();
        $args['input']['workflow_stage_id'] = 42;
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
        $new_name = $this->workflow_stage->name . ' 2';
        $this->assertEquals($this->workflow_stage->id, $result['stage']['id']);
        $this->assertEquals($new_name, $result['stage']['name']);
    }

    public function test_execute_query_unsuccessful() {
        $this->setUser($this->user);
        $args = [
            'input' => [
                'workflow_stage_id' => $this->workflow_stage->id,
                'name' => '?',
            ]
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Can not manage stages');
    }

    public function test_execute_query_cumulatively() {
        $this->setAdminUser();

        $args = [
            'input' => [
                'workflow_stage_id' => $this->workflow_stage->id,
                'name' => 'New name',
            ]
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertEquals('New name', $result['stage']['name']);

        $args = [
            'input' => [
                'workflow_stage_id' => $this->workflow_stage->id,
                'name' => 'New name 2',
            ]
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);
        $result = $this->get_webapi_operation_data($result);
        $this->assertEquals('New name 2', $result['stage']['name']);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_with_invalid_input() {
        $args = [
            'workflow_stage_id' => $this->workflow_stage->id
        ];
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid workflow_stage_id', $ex->getMessage());
        }

        $invalid_args = $args = $this->get_args();
        $invalid_args['input']['name'] = '';
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $invalid_args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('Stage name must not be empty', $ex->getMessage());
        }
    }

    public function test_execute_mutation_with_max_length() {
        $this->setUser($this->user);
        $args = [
            'input' => [
                'workflow_stage_id' => $this->workflow_stage->id,
                'name' => str_repeat('f', 256),
            ]
        ];

        self::expectExceptionMessage('Length of name can not exceed 255');
        self::expectException(\moodle_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    private function get_args() {
        return [
            'input' => [
                'workflow_stage_id' => $this->workflow_stage->id,
                'name' => $this->workflow_stage->name . ' 2',
            ]
        ];
    }
}