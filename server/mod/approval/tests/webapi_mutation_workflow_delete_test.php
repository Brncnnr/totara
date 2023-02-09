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

use container_approval\approval as approval_container;
use core\entity\user;
use core\orm\query\builder;
use core\orm\query\exceptions\record_not_found_exception;
use mod_approval\exception\access_denied_exception;
use mod_approval\entity\assignment\assignment as assignment_entity;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\entity\workflow\workflow_stage_formview as workflow_stage_formview_entity;
use mod_approval\entity\workflow\workflow_stage_interaction as workflow_stage_interaction_entity;
use mod_approval\entity\workflow\workflow_stage_interaction_action as workflow_stage_interaction_action_entity;
use mod_approval\entity\workflow\workflow_stage_interaction_transition as workflow_stage_interaction_transition_entity;
use mod_approval\model\assignment\approver_type\relationship as relationship_approver_type;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use totara_core\relationship\relationship;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\workflow_delete
 */
class mod_approval_webapi_mutation_workflow_delete_testcase extends mod_approval_testcase {
    private const MUTATION = 'mod_approval_workflow_delete';

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
    public function test_delete_draft_workflow() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        builder::table(workflow_version_entity::TABLE)->where('workflow_id', $this->workflow->id)->update(['status' => status::DRAFT]);
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertTrue($result);
    }

    /**
     * @covers ::resolve
     * @covers mod_approval\model\workflow\workflow::delete
     */
    public function test_delete_with_stage_and_assignment() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        builder::table(workflow_version_entity::TABLE)->where('workflow_id', $this->workflow->id)->update(['status' => status::DRAFT]);
        $assignment = $this->workflow->get_default_assignment();
        $assignment->activate();
        $stage_1 = $this->workflow->latest_version->stages->first();
        $stage_2 = $this->workflow->latest_version->get_next_stage($stage_1->id);
        assignment_approver::create($assignment, $stage_2->approval_levels->first(), relationship_approver_type::TYPE_IDENTIFIER, relationship::load_by_idnumber('manager')->id);
        $courses = builder::table('course')->where('category', approval_container::get_default_category_id());
        $modules = builder::table('course_modules', 'cm')->join(['modules', 'm'], 'cm.module', 'm.id')->where('m.name', 'approval');
        $this->assertEquals(1, $courses->count());
        $this->assertEquals(1, $modules->count());
        $this->assertEquals(1, builder::table(workflow_entity::TABLE)->count());
        $this->assertEquals(1, builder::table(workflow_version_entity::TABLE)->count());
        $this->assertEquals(1, builder::table(assignment_entity::TABLE)->count());
        $this->assertEquals(1, builder::table(assignment_approver_entity::TABLE)->count());
        $this->assertEquals(3, builder::table(workflow_stage_entity::TABLE)->count());
        $this->assertEquals(1, builder::table(workflow_stage_approval_level_entity::TABLE)->count());
        $this->assertEquals(10, builder::table(workflow_stage_formview_entity::TABLE)->count());
        $this->assertEquals(6, builder::table(workflow_stage_interaction_entity::TABLE)->count());
        $this->assertEquals(6, builder::table(workflow_stage_interaction_transition_entity::TABLE)->count());
        $this->assertEquals(0, builder::table(workflow_stage_interaction_action_entity::TABLE)->count());
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $this->assertTrue($result);
        $this->assertEquals(0, $courses->count());
        $this->assertEquals(0, $modules->count());
        $this->assertEquals(0, builder::table(workflow_entity::TABLE)->count());
        $this->assertEquals(0, builder::table(workflow_version_entity::TABLE)->count());
        $this->assertEquals(0, builder::table(assignment_entity::TABLE)->count());
        $this->assertEquals(0, builder::table(assignment_approver_entity::TABLE)->count());
        $this->assertEquals(0, builder::table(workflow_stage_entity::TABLE)->count());
        $this->assertEquals(0, builder::table(workflow_stage_approval_level_entity::TABLE)->count());
        $this->assertEquals(0, builder::table(workflow_stage_formview_entity::TABLE)->count());
        $this->assertEquals(0, builder::table(workflow_stage_interaction_entity::TABLE)->count());
        $this->assertEquals(0, builder::table(workflow_stage_interaction_transition_entity::TABLE)->count());
        $this->assertEquals(0, builder::table(workflow_stage_interaction_action_entity::TABLE)->count());
    }

    /**
     * @covers ::resolve
     * @covers mod_approval\interactor\workflow_interactor::can_delete
     */
    public function test_delete_by_manager() {
        builder::table(workflow_version_entity::TABLE)->where('workflow_id', $this->workflow->id)->update(['status' => status::DRAFT]);
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
        $this->assertTrue($result);
    }

    /**
     * @covers ::resolve
     * @covers mod_approval\interactor\workflow_interactor::can_delete
     */
    public function test_cannot_delete_by_manager_without_caps() {
        builder::table(workflow_version_entity::TABLE)->where('workflow_id', $this->workflow->id)->update(['status' => status::DRAFT]);
        $manager = $this->create_user();
        $role_id = builder::table('role')->where('shortname', 'manager')->one()->id;
        role_assign($role_id, $manager->id, $this->workflow->get_context());
        assign_capability('mod/approval:edit_draft_workflow', CAP_PREVENT, $role_id, $this->workflow->get_context(), true);
        $this->setUser($manager);
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot delete workflow', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_cannot_delete_active_workflow() {
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
            $this->assertStringContainsString('All versions must be draft', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_cannot_delete_archived_workflow() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        builder::table(workflow_version_entity::TABLE)->where('workflow_id', $this->workflow->id)->update(['status' => status::ARCHIVED]);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('All versions must be draft', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_cannot_delete_with_invalid_input() {
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
    public function test_cannot_delete_with_nonexisting_workflow() {
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

    /**
     * @covers ::resolve
     */
    public function test_cannot_delete_by_random_user() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        builder::table(workflow_version_entity::TABLE)->where('workflow_id', $this->workflow->id)->update(['status' => status::DRAFT]);
        $this->setUser($this->user);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Cannot delete workflow', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_cannot_delete_by_guest() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        $this->setGuestUser();
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('require_login_exception expected');
        } catch (require_login_exception $ex) {
            $this->assertStringContainsString('Must be an authenticated user', $ex->getMessage());
        }
    }

    public function test_execute_query_successful() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        builder::table(workflow_version_entity::TABLE)->where('workflow_id', $this->workflow->id)->update(['status' => status::DRAFT]);

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $this->assertNotEmpty($result, 'result empty');
        $this->assertTrue($result);
    }

    public function test_execute_query_failure_on_active() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        builder::table(workflow_version_entity::TABLE)->where('workflow_id', $this->workflow->id)->update(['status' => status::ACTIVE]);

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'All versions must be draft');
    }

    public function test_execute_query_failure_by_random_user() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        builder::table(workflow_version_entity::TABLE)->where('workflow_id', $this->workflow->id)->update(['status' => status::DRAFT]);
        $this->setUser($this->user);
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Cannot access this workflow (Cannot delete workflow)');
    }

    public function test_execute_query_failure_by_guest() {
        $args = [
            'input' => [
                'workflow_id' => $this->workflow->id
            ]
        ];
        builder::table(workflow_version_entity::TABLE)->where('workflow_id', $this->workflow->id)->update(['status' => status::DRAFT]);
        $this->setGuestUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Course or activity not accessible. (Must be an authenticated user)');
    }
}
