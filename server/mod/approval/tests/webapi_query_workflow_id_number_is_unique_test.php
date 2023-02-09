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
use container_approval\approval;
use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\query\workflow_id_number_is_unique
 */
class mod_approval_webapi_query_workflow_id_number_is_unique_testcase extends mod_approval_testcase {
    private const QUERY = 'mod_approval_workflow_id_number_is_unique';

    /** @var user */
    private $manager1;
    /** @var user */
    private $manager2;
    /** @var workflow */
    private $workflow1;
    /** @var workflow */
    private $workflow2;

    use webapi_phpunit_helper;

    public function setUp(): void {
        parent::setUp();
        $this->manager1 = $this->create_user(['username' => 'manager1']);
        $this->manager2 = $this->create_user(['username' => 'manager2']);
        $this->setUser($this->manager1);
        $this->workflow1 = $this->create_workflow_for_user();
        $this->workflow2 = $this->create_workflow_for_user();
        builder::table(workflow_entity::TABLE)->where('id', $this->workflow1->id)->update(['id_number' => 'first-workflow-id']);
        builder::table(workflow_entity::TABLE)->where('id', $this->workflow2->id)->update(['id_number' => 'second-workflow-id']);
        $this->workflow1->refresh();
        $this->workflow2->refresh();
        $this->fake_state_workflow($this->workflow1, status::DRAFT);
        $this->fake_state_workflow($this->workflow2, status::DRAFT);
        $role_id = builder::table('role')->where('shortname', 'manager')->one()->id;
        role_assign($role_id, $this->manager1->id, $this->workflow1->get_context());
        role_assign($role_id, $this->manager2->id, $this->workflow1->get_context());
        role_assign($role_id, $this->manager1->id, approval::get_default_category_context());
        role_assign($role_id, $this->manager2->id, approval::get_default_category_context());
        $this->setAdminUser();
    }

    public function tearDown(): void {
        $this->manager1 = $this->manager2 = $this->workflow1 = $this->workflow2 = null;
        parent::tearDown();
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_admin() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => 'first-workflow-id',
            ],
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertTrue($result);

        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => 'third-workflow-id',
            ],
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertTrue($result);

        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => 'second-workflow-id',
            ],
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertFalse($result);
    }

    public function data_managers(): array {
        return [['manager1'], ['manager2']];
    }

    /**
     * @covers ::resolve
     * @param string $username
     * @dataProvider data_managers
     */
    public function test_resolve_by_managers(string $username) {
        $manager = core_user::get_user_by_username($username, '*', null, MUST_EXIST);
        $this->setUser($manager);
        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => 'first-workflow-id',
            ],
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertTrue($result);

        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => 'third-workflow-id',
            ],
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertTrue($result);

        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => 'second-workflow-id',
            ],
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertFalse($result);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_user() {
        $user = $this->create_user();
        $this->setUser($user);
        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => 'first-workflow-id',
            ],
        ];
        try {
            $this->resolve_graphql_query(self::QUERY, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot edit workflow', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_guest() {
        $this->setGuestUser();
        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => 'first-workflow-id',
            ],
        ];
        try {
            $this->resolve_graphql_query(self::QUERY, $args);
            $this->fail('require_login_exception expected');
        } catch (require_login_exception $ex) {
            $this->assertStringContainsString('(Must be an authenticated user)', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_nonexisting_workflow() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => 'first-workflow-id',
            ],
        ];
        builder::table(workflow_entity::TABLE)->where('id', $this->workflow1->id)->delete();
        try {
            $this->resolve_graphql_query(self::QUERY, $args);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
            $this->assertStringContainsString('Can not find data record in database.', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_without_workflow() {
        $args = [
            'input' => [
                'id_number' => 'first-workflow-id',
            ],
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertFalse($result);

        $args = [
            'input' => [
                'id_number' => 'aotearoa',
            ],
        ];
        $result = $this->resolve_graphql_query(self::QUERY, $args);
        $this->assertTrue($result);
    }

    public function test_execute_query_successful() {
        $this->setUser($this->manager2);

        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => 'third-workflow-id',
            ],
        ];
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);
        $this->assertTrue($this->get_webapi_operation_data($result));

        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => 'second-workflow-id',
            ],
        ];
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);
        $this->assertFalse($this->get_webapi_operation_data($result));
    }

    public function test_execute_query_failure_by_random_user() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => 'first-workflow-id',
            ],
        ];
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Cannot access this workflow (Cannot edit workflow)');
    }

    public function test_execute_query_failure_by_guest() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => 'first-workflow-id',
            ],
        ];
        $this->setGuestUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Course or activity not accessible. (Must be an authenticated user)');
    }

    public function test_execute_query_failure_missing_require_inputs() {
        $this->setUser($this->manager2);

        $args = [
            'workflow_id' => $this->workflow1->id,
            'id_number' => 'first-workflow-id',
        ];
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Variable "$input" of required type "mod_approval_workflow_id_number_is_unique_input!" was not provided.');

        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
            ],
        ];
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Field value.id_number of required type String! was not provided.');

        $args = [
            'input' => [
                'workflow_id' => $this->workflow1->id,
                'id_number' => '',
            ],
        ];
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed($result, 'Expected id_number parameter to be a non-empty string.');
    }
}
