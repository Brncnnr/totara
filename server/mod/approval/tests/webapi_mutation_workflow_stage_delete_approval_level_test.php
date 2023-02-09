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
use mod_approval\entity\workflow\workflow_stage_approval_level;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level as approval_level;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\generator as approval_generator;
use mod_approval\testing\workflow_generator_object;
use mod_approval\testing\formview_generator_object;
use mod_approval\exception\access_denied_exception;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 * Class mod_approval_webapi_mutation_workflow_stage_delete_approval_level_testcase
 */
class mod_approval_webapi_mutation_workflow_stage_delete_approval_level_testcase extends testcase {

    private const MUTATION = 'mod_approval_workflow_stage_delete_approval_level';

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    public function test_query_requires_logged_in_user() {
        $this->setGuestUser();
        $this->expectException('require_login_exception');
        $this->resolve_graphql_mutation(self::MUTATION, ['input' => []]);
    }

    public function test_query_without_input_params() {
        $this->setAdminUser();
        $parsed_query = $this->parsed_graphql_operation(self::MUTATION, []);
        $this->assert_webapi_operation_failed($parsed_query);

        $args = [
            'input' => [
                'workflow_stage_approval_level_id' => '',
            ],
        ];
        $parsed_query = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($parsed_query);

        $workflow_stage = $this->generate_data();
        /** @var workflow_stage $draft_approval_level */
        $approval_level = $workflow_stage->get_approval_levels()->first();
        $args = [
            'workflow_stage_approval_level_id' => $approval_level->id,
        ];
        $parsed_query = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($parsed_query);
    }

    public function test_delete_without_capability() {
        $this->setAdminUser();
        $workflow_stage = $this->generate_data();
        /** @var approval_level $approval_level */
        $approval_level = $workflow_stage->get_approval_levels()->first();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $args = [
            'input' => [
                'workflow_stage_approval_level_id' => $approval_level->id,
            ],
        ];

        $this->expectException(access_denied_exception::class);
        $this->expectExceptionMessage('Can not delete approval level');
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function data_status(): array {
        return [
            'active' => [status::ACTIVE],
            'archived' => [status::ARCHIVED],
        ];
    }

    /**
     * @param integer $status
     * @dataProvider data_status
     */
    public function test_delete_from_published_version(int $status) {
        $this->setAdminUser();
        $workflow_stage = $this->generate_data();
        $version = new workflow_version_entity($workflow_stage->workflow_version_id);
        $version->status = $status;
        $version->save();
        $workflow_stage->refresh(true);
        $this->assertEquals($status, $workflow_stage->workflow_version->status);
        /** @var approval_level $approval_level */
        $approval_level = $workflow_stage->get_approval_levels()->first();
        $this->assertNotEmpty($approval_level);

        $args = [
            'input' => [
                'workflow_stage_approval_level_id' => $approval_level->id,
            ],
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Can only delete approval level from a draft workflow version');
    }

    public function test_delete_from_inactive_stage() {
        $this->setAdminUser();
        $workflow_stage = $this->generate_data();
        $workflow_stage->deactivate();
        $this->assertFalse($workflow_stage->active);
        /** @var approval_level $approval_level */
        $approval_level = $workflow_stage->get_approval_levels()->first();
        $this->assertNotEmpty($approval_level);

        $args = [
            'input' => [
                'workflow_stage_approval_level_id' => $approval_level->id,
            ],
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Can not delete approval level from inactive workflow stage');
    }

    public function test_delete_from_active_stage() {
        $this->setAdminUser();
        $workflow_stage = $this->generate_data();
        $workflow_stage->activate();
        $this->assertTrue($workflow_stage->active);
        /** @var approval_level $approval_level */
        $approval_level = $workflow_stage->get_approval_levels()->first();
        $this->assertNotEmpty($approval_level);

        $args = [
            'input' => [
                'workflow_stage_approval_level_id' => $approval_level->id,
            ],
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);
        $result = reset($result);

        $workflow_stage->refresh(true);

        $this->assertEmpty($workflow_stage->approval_levels->all());

        // Test that this record id does not exists anymore
        $this->assertNull(workflow_stage_approval_level::repository()->find($approval_level->id));
    }

    /**
     * @return workflow_stage
     */
    private function generate_data(): workflow_stage {
        $generator = approval_generator::instance();
        $workflow_type = $generator->create_workflow_type('Testing');

        // Create a form and version.
        $form_version = $generator->create_form_and_version();
        $form_id = $form_version->form->id;

        $workflow_go = new workflow_generator_object($workflow_type->id, $form_id, $form_version->id, status::DRAFT);
        $workflow_go->name = "Workflow";
        $workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
        $workflow_version = workflow_version::load_by_entity($workflow_version_entity);
        $workflow_stage = workflow_stage::create($workflow_version, 'Draft stage', approvals::get_enum());
        $formview_go = new formview_generator_object('agency_code', $workflow_stage->id);
        $generator->create_formview($formview_go);

        return $workflow_stage;
    }
}