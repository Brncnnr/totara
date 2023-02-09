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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use mod_approval\exception\access_denied_exception;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\workflow_unarchive
 */
class mod_approval_webapi_mutation_workflow_unarchive_testcase extends mod_approval_testcase {
    private const MUTATION = 'mod_approval_workflow_unarchive';

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
        $this->fake_state_workflow($this->workflow, status::ARCHIVED);
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
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertInstanceOf(workflow::class, $result['workflow']);
        $this->assertEquals($this->workflow->id, $result['workflow']->id);
        $this->assertEquals('Active', $result['workflow']->latest_version->status_label);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_manager() {
        $manager = $this->create_user();
        $role_id = builder::table('role')->where('shortname', 'manager')->one()->id;
        role_assign($role_id, $manager->id, $this->workflow->get_context());
        $this->setUser($manager);
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertInstanceOf(workflow::class, $result['workflow']);
        $this->assertEquals($this->workflow->id, $result['workflow']->id);
        $this->assertEquals('Active', $result['workflow']->latest_version->status_label);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_user() {
        $user = $this->create_user();
        $this->setUser($user);
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot unarchive workflow', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_guest() {
        $this->setGuestUser();
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
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
    public function test_resolve_draft_workflow() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        builder::table(workflow_version_entity::TABLE)->where('workflow_id', $this->workflow->id)->update(['status' => status::DRAFT]);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot unarchive workflow', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_active_workflow() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        builder::table(workflow_version_entity::TABLE)->where('workflow_id', $this->workflow->id)->update(['status' => status::ACTIVE]);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot unarchive workflow', $ex->getMessage());
        }
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
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_nonexisting_workflow() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        builder::table(workflow_entity::TABLE)->where('id', $this->workflow->id)->delete();
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
            $this->assertStringContainsString('Can not find data record in database.', $ex->getMessage());
        }
    }

    public function test_execute_query_successful() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertEquals($this->workflow->id, $result['workflow']['id']);
        $this->assertEquals('Active', $result['workflow']['latest_version']['status_label']);
    }

    public function test_execute_query_failure_on_draft() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        builder::table(workflow_version_entity::TABLE)->where('workflow_id', $this->workflow->id)->update(['status' => status::DRAFT]);

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Cannot unarchive workflow');
    }

    public function test_execute_query_failure_by_random_user() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        $this->setUser($this->user);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Cannot access this workflow (Cannot unarchive workflow)');
    }

    public function test_execute_query_failure_by_guest() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        $this->setGuestUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Course or activity not accessible. (Must be an authenticated user)');
    }
}
