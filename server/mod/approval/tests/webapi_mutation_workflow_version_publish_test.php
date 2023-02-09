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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use core\orm\query\order;
use mod_approval\exception\access_denied_exception;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_version;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\workflow_version_publish
 */
class mod_approval_webapi_mutation_workflow_version_publish_testcase extends mod_approval_testcase {
    private const MUTATION = 'mod_approval_workflow_version_publish';

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
        workflow_version::create($this->workflow, $this->workflow->form->get_latest_version());
        $args = [
            'input' => [
                'workflow_version_id' => $this->workflow->get_latest_version()->id,
            ]
        ];
        $this->assertTrue($this->workflow->get_interactor(user::logged_in()->id)->can_publish());
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertInstanceOf(workflow::class, $result['workflow']);
        $this->assertEquals($this->workflow->id, $result['workflow']->id);
        $this->assertEquals('Active', $result['workflow']->latest_version->status_label);
        $this->assertFalse($result['workflow']->get_interactor(user::logged_in()->id)->can_publish());
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_admin_with_workflow_version_id() {
        $workflow_version = workflow_version::create($this->workflow, $this->workflow->form->get_latest_version());
        $this->assertEquals(1, $workflow_version->status);
        $args = [
            'input' => [
                'workflow_version_id' => $workflow_version->id
            ]
        ];
        $this->assertTrue($this->workflow->get_interactor(user::logged_in()->id)->can_publish());
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertInstanceOf(workflow::class, $result['workflow']);
        $this->assertEquals($this->workflow->id, $result['workflow']->id);
        $this->assertEquals('Active', $result['workflow']->latest_version->status_label);
        $this->assertEquals($workflow_version->id, $result['workflow']->latest_version->id);
        $this->assertFalse($result['workflow']->get_interactor(user::logged_in()->id)->can_publish());
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_sitemanager() {
        workflow_version::create($this->workflow, $this->workflow->form->get_latest_version());
        $manager = $this->create_user();
        $role_id = builder::table('role')->where('shortname', 'manager')->one()->id;
        role_assign($role_id, $manager->id, $this->workflow->get_context());
        $this->setUser($manager);
        $args = [
            'input' => [
                'workflow_version_id' => $this->workflow->get_latest_version()->id,
            ]
        ];
        $this->assertTrue($this->workflow->get_interactor(user::logged_in()->id)->can_publish());
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertInstanceOf(workflow::class, $result['workflow']);
        $this->assertEquals($this->workflow->id, $result['workflow']->id);
        $this->assertEquals('Active', $result['workflow']->latest_version->status_label);
        $this->assertFalse($result['workflow']->get_interactor(user::logged_in()->id)->can_publish());
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_sitemanager_with_workflow_version_id() {
        $workflow_version = workflow_version::create($this->workflow, $this->workflow->form->get_latest_version());
        $this->assertEquals(1, $workflow_version->status);

        $manager = $this->create_user();
        $role_id = builder::table('role')->where('shortname', 'manager')->one()->id;
        role_assign($role_id, $manager->id, $this->workflow->get_context());
        $this->setUser($manager);
        $args = [
            'input' => [
                'workflow_version_id' => $workflow_version->id
            ]
        ];

        $this->assertTrue($this->workflow->get_interactor(user::logged_in()->id)->can_publish());
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertInstanceOf(workflow::class, $result['workflow']);
        $this->assertEquals($this->workflow->id, $result['workflow']->id);
        $this->assertEquals('Active', $result['workflow']->latest_version->status_label);
        $this->assertEquals($workflow_version->id, $result['workflow']->latest_version->id);
        $this->assertFalse($result['workflow']->get_interactor(user::logged_in()->id)->can_publish());
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_user() {
        $user = $this->create_user();
        $this->setUser($user);
        $args = [
            'input' => [
                'workflow_version_id' => $this->workflow->get_latest_version()->id
            ]
        ];
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot publish workflow', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_guest() {
        $this->setGuestUser();
        $args = [
            'input' => [
                'workflow_version_id' => $this->workflow->get_latest_version()->id
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
    public function test_resolve_archived_workflow() {
        $args = [
            'input' => [
                'workflow_version_id' => $this->workflow->get_latest_version()->id
            ]
        ];
        builder::table(workflow_version_entity::TABLE)
            ->where('workflow_id', $this->workflow->id)
            ->update(['status' => status::ARCHIVED]);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot publish workflow version', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_active_workflow() {
        $args = [
            'input' => [
                'workflow_version_id' => $this->workflow->get_latest_version()->id
            ]
        ];

        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot publish workflow version', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_nonexisting_workflow_version() {
        $args = [
            'input' => [
                'workflow_version_id' => 123
            ]
        ];
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
            $this->assertStringContainsString('Can not find data record in database.', $ex->getMessage());
        }
    }

    public function test_execute_query_successful() {
        workflow_version::create($this->workflow, $this->workflow->form->get_latest_version());
        $args = [
            'input' => [
                'workflow_version_id' => $this->workflow->get_latest_version()->id,
            ]
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertEquals($this->workflow->id, $result['workflow']['id']);
        $this->assertEquals('Active', $result['workflow']['latest_version']['status_label']);
    }

    public function test_execute_query_successful_with_workflow_version_id() {
        $workflow_version2 = workflow_version::create($this->workflow, $this->workflow->form->get_latest_version());
        $workflow_version2->activate();
        $this->assertEquals(status::ACTIVE, $workflow_version2->status);
        $workflow_version3 = workflow_version::create($this->workflow, $this->workflow->form->get_latest_version());
        $this->assertEquals(status::DRAFT, $workflow_version3->status);

        $args = [
            'input' => [
                'workflow_version_id' => $workflow_version3->id
            ]
        ];
        $this->assertTrue($this->workflow->get_interactor(user::logged_in()->id)->can_publish());
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertEquals($this->workflow->id, $result['workflow']['id']);
        $this->assertEquals('Active', $result['workflow']['latest_version']['status_label']);
        $this->assertFalse($result['workflow']['interactor']['can_publish']);

        $latest_version = workflow_version_entity::repository()
            ->where('workflow_id', $this->workflow->id)
            ->order_by('id', order::DIRECTION_DESC)
            ->first(true);

        $this->assertEquals($workflow_version3->id, $latest_version->id);
    }

    public function test_publishing_draft_when_active_already_exists() {
        $args = [
            'input' => [
                'workflow_version_id' => $this->workflow->get_latest_version()->id
            ]
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Cannot publish workflow version');
    }

    public function test_execute_query_failure_by_random_user() {
        $args = [
            'input' => [
                'workflow_version_id' => $this->workflow->get_latest_version()->id
            ]
        ];
        $this->setUser($this->user);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Cannot access this workflow (Cannot publish workflow version)');
    }

    public function test_execute_query_failure_by_guest() {
        $args = [
            'input' => [
                'workflow_version_id' => $this->workflow->get_latest_version()->id
            ]
        ];
        $this->setGuestUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Course or activity not accessible. (Must be an authenticated user)');
    }
}
