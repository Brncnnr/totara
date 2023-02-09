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
use mod_approval\entity\workflow\workflow_stage_formview;
use mod_approval\exception\access_denied_exception;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\entity\workflow\workflow_stage_interaction as workflow_stage_interaction_entity;
use mod_approval\entity\workflow\workflow_stage_interaction_transition as workflow_stage_interaction_transition_entity;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\workflow_reorder_levels
 */
class mod_approval_webapi_mutation_workflow_reorder_levels_testcase extends mod_approval_testcase {
    private const MUTATION = 'mod_approval_workflow_reorder_levels';

    /** @var user */
    private $user;
    /** @var workflow */
    private $workflow;
    /** @var workflow_stage */
    private $stage;

    use webapi_phpunit_helper;

    public function setUp(): void {
        parent::setUp();
        $this->user = $this->create_user();
        $this->setUser($this->user);
        $this->workflow = $this->create_workflow_for_user('test');
        $version = $this->workflow->latest_version;
        $first_stage = $version->stages->first();
        $this->stage = $version->get_next_stage($first_stage->id);
        builder::table(workflow_version_entity::TABLE)
            ->where('id', $version->id)
            ->update(['status' => status::DRAFT]);
        builder::table(workflow_stage_entity::TABLE)
            ->where('id', $this->stage->id)
            ->update(['active' => true]);
        $this->workflow->refresh(true);
        $this->stage->refresh();
        $this->stage->add_approval_level('level 2');
    }

    public function tearDown(): void {
        $this->user = $this->workflow = $this->stage = null;
        parent::tearDown();
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_admin() {
        $first = $this->stage->approval_levels->first();
        $last = $this->stage->approval_levels->last();
        $args = [
            'input' => [
                'workflow_stage_id' => $this->stage->id,
                'workflow_stage_approval_level_ids' => [$last->id, $first->id]
            ]
        ];

        $this->setAdminUser();
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $stage = $result['stage'];
        $this->assertInstanceOf(workflow_stage::class, $stage);
        $workflow = $stage->workflow_version->workflow;
        $this->assertInstanceOf(workflow::class, $workflow);
        $this->assertEquals($this->workflow->id, $workflow->id);
        $this->assertEquals($last->id, $workflow->latest_version->stages->find('id', $this->stage->id)->approval_levels->first()->id);
        $this->assertEquals($first->id, $workflow->latest_version->stages->find('id', $this->stage->id)->approval_levels->last()->id);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_manager() {
        $manager = $this->create_user();
        $role_id = builder::table('role')->where('shortname', 'manager')->one()->id;
        role_assign($role_id, $manager->id, $this->workflow->get_context());

        $first = $this->stage->approval_levels->first();
        $last = $this->stage->approval_levels->last();
        $args =  [
            'input' => [
                'workflow_stage_id' => $this->stage->id,
                'workflow_stage_approval_level_ids' => [$last->id, $first->id]
            ]
        ];

        $this->setUser($manager);
        $result = $this->resolve_graphql_mutation(self::MUTATION, $args);
        $stage = $result['stage'];
        $this->assertInstanceOf(workflow_stage::class, $stage);
        $workflow = $stage->workflow_version->workflow;
        $this->assertInstanceOf(workflow::class, $workflow);
        $this->assertEquals($this->workflow->id, $workflow->id);
        $this->assertEquals($last->id, $workflow->latest_version->stages->find('id', $this->stage->id)->approval_levels->first()->id);
        $this->assertEquals($first->id, $workflow->latest_version->stages->find('id', $this->stage->id)->approval_levels->last()->id);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_by_unauthorised_users() {
        $user = $this->create_user();
        $approver = $this->create_user();
        $role_id = builder::table('role')->where('shortname', 'approvalworkflowapprover')->one()->id;
        role_assign($role_id, $approver->id, $this->workflow->get_context());

        $first = $this->stage->approval_levels->first();
        $last = $this->stage->approval_levels->last();
        $args =  [
            'input' => [
                'workflow_stage_id' => $this->stage->id,
                'workflow_stage_approval_level_ids' => [$last->id, $first->id]
            ]
        ];

        $this->setUser($user);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Can not reorder approval levels', $ex->getMessage());
        }

        $this->setUser($approver);
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('access_denied_exception expected');
        } catch (access_denied_exception $ex) {
            $this->assertStringContainsString('Can not reorder approval levels', $ex->getMessage());
        }

        $this->setGuestUser();
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('require_login_exception expected');
        } catch (require_login_exception $ex) {
            $this->assertStringContainsString('Must be an authenticated user', $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_nonexisting_workflow_stage() {
        $first = $this->stage->approval_levels->first();
        $last = $this->stage->approval_levels->last();
        $args =  [
            'input' => [
                'workflow_stage_id' => $this->stage->id,
                'workflow_stage_approval_level_ids' => [$last->id, $first->id]
            ]
        ];
        // This is a little silly.
        builder::table(workflow_stage_interaction_transition_entity::TABLE)->delete();
        builder::table(workflow_stage_interaction_entity::TABLE)->delete();
        builder::table(workflow_stage_approval_level_entity::TABLE)->delete();
        builder::table(workflow_stage_approval_level_entity::TABLE)->delete();
        builder::table(workflow_stage_formview::TABLE)->delete();
        builder::table(workflow_stage_entity::TABLE)->delete();

        $this->setAdminUser();
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('record_not_found_exception expected');
        } catch (record_not_found_exception $ex) {
            $this->assertStringContainsString('Can not find data record in database.', $ex->getMessage());
        }
    }

    public function test_execute_query_successful() {
        $first = $this->stage->approval_levels->first();
        $last = $this->stage->approval_levels->last();
        $args = [
            'input' => [
                'workflow_stage_id' => $this->stage->id,
                'workflow_stage_approval_level_ids' => [$last->id, $first->id]
            ]
        ];

        $this->setAdminUser();
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);

        $result = $this->get_webapi_operation_data($result);
        $stage = workflow_stage::load_by_id($result['stage']['id']);
        $workflow = $stage->workflow_version->workflow;
        $stage_2 = $workflow->latest_version->stages->item($this->stage->id);
        $approval_levels = $stage_2->approval_levels->all();
        $this->assertEquals($this->workflow->id, $workflow->id);
        $this->assertCount(2, $approval_levels);
        $this->assertEquals($last->id, $approval_levels[0]->id);
        $this->assertEquals('level 2', $approval_levels[0]->name);
        $this->assertEquals(1, $approval_levels[0]->ordinal_number);
        $this->assertEquals($first->id, $approval_levels[1]->id);
        $this->assertEquals('Level 1', $approval_levels[1]->name);
        $this->assertEquals(2, $approval_levels[1]->ordinal_number);
    }

    public function test_execute_query_unsuccessful() {
        $this->setUser($this->user);
        $args = [
            'input' => [
                'workflow_stage_id' => $this->stage->id,
                'workflow_stage_approval_level_ids' => []
            ]
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Can not reorder approval levels');
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_with_invalid_input() {
        $args = [
            'workflow_stage_id' => $this->workflow->id
        ];
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('invalid_parameter_exception expected');
        } catch (invalid_parameter_exception $ex) {
            $this->assertStringContainsString('invalid workflow_stage_id', $ex->getMessage());
        }

        $args = [
            'input' => [
                'workflow_stage_id' => $this->stage->id,
            ]
        ];
        try {
            $this->resolve_graphql_mutation(self::MUTATION, $args);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('workflow_stage_approval_level_ids are missing', $ex->getMessage());
        }
    }
}
