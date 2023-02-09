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
 */

use core\orm\query\exceptions\record_not_found_exception;
use core_phpunit\testcase;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;
use mod_approval\testing\generator as approval_generator;
use mod_approval\testing\workflow_generator_object;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\workflow_stage_delete
 */
class mod_approval_webapi_mutation_workflow_stage_delete_testcase extends testcase {

    private const MUTATION = 'mod_approval_workflow_stage_delete';

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
                'workflow_stage_id' => '',
            ],
        ];
        $parsed_query = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($parsed_query);
    }

    public function test_delete_stage_with_draft_version() {
        $this->setAdminUser();
        $generator = approval_generator::instance();
        $workflow_type = $generator->create_workflow_type('Testing');

        // Create a form and version.
        $form_version = $generator->create_form_and_version('simple', 'Simple Request Form');
        $form_id = $form_version->form->id;

        // Draft workflow:
        $workflow_go = new workflow_generator_object($workflow_type->id, $form_id, $form_version->id);
        $workflow_go->name = "Draft Workflow";
        $workflow_go->id_number = "DRAFT-FLOW";
        $workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
        $workflow_version_entity->status = status::DRAFT;
        $workflow_version_entity->update();
        $workflow_version = workflow_version::load_by_entity($workflow_version_entity);
        $stage1 = workflow_stage::create($workflow_version, 'Test stage 1', form_submission::get_enum());
        $stage2 = workflow_stage::create($workflow_version, 'Test stage 2', approvals::get_enum());
        $stage2->add_approval_level('Approval');
        $stage2->add_approval_level('Disapproval');
        $stage3 = workflow_stage::create($workflow_version, 'Test stage 3', finished::get_enum());

        // Delete the second stage. Also proves that we can delete finished stages.
        $result = $this->parsed_graphql_operation(
            self::MUTATION,
            [
                'input' => [
                    'workflow_stage_id' => $stage2->id,
                ],
            ]
        );

        $this->assert_webapi_operation_successful($result);
        $result = reset($result);

        $workflow_version->refresh(true);
        $stages = $workflow_version->stages->all();

        // Check that there are two stages remaining, and that they have the correct id and sortorder.
        $this->assertCount(2, $stages);
        $this->assertEquals($stage1->id, $stages[0]->id);
        $this->assertEquals(1, $stages[0]->ordinal_number);
        $this->assertEquals($stage3->id, $stages[1]->id);
        $this->assertEquals(2, $stages[1]->ordinal_number);
    }

    public function test_delete_stage_with_active_version() {
        $this->setAdminUser();
        $generator = approval_generator::instance();
        $workflow_type = $generator->create_workflow_type('Testing');

        // Create a form and version.
        $form_version = $generator->create_form_and_version('simple', 'Simple Request Form');
        $form_id = $form_version->form->id;

        // Active workflow:
        $workflow_go = new workflow_generator_object($workflow_type->id, $form_id, $form_version->id);
        $workflow_go->name = "Active Workflow";
        $workflow_go->id_number = "ACTIVE-FLOW";
        $workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
        $workflow_version_entity->status = status::DRAFT;
        $workflow_version_entity->update();
        $workflow_version = workflow_version::load_by_entity($workflow_version_entity);
        workflow_stage::create($workflow_version, 'Active stage 1', form_submission::get_enum());
        $workflow_stage2 = workflow_stage::create($workflow_version, 'Active stage 2', form_submission::get_enum());
        $workflow_version->activate();

        $args = [
            'input' => [
                'workflow_stage_id' => $workflow_stage2->id,
            ],
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $error = end($result);
        $this->assertStringContainsString('Can only delete stage from a draft workflow version', $error);
    }

    public function test_delete_stage_with_archived_version() {
        $this->setAdminUser();
        $generator = approval_generator::instance();
        $workflow_type = $generator->create_workflow_type('Testing');

        // Create a form and version.
        $form_version = $generator->create_form_and_version('simple', 'Simple Request Form');
        $form_id = $form_version->form->id;

        // Archived workflows:
        $workflow_go = new workflow_generator_object($workflow_type->id, $form_id, $form_version->id);
        $workflow_go->name = "Archived Workflow";
        $workflow_go->id_number = "ARCHIVED-FLOW";
        $workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
        $workflow_version_entity->status = status::DRAFT;
        $workflow_version_entity->update();
        $workflow_version = workflow_version::load_by_entity($workflow_version_entity);
        workflow_stage::create($workflow_version, 'Archived stage 1', form_submission::get_enum());
        $workflow_stage2 = workflow_stage::create($workflow_version, 'Archived stage 2', form_submission::get_enum());
        $workflow_version->archive();

        $result = $this->parsed_graphql_operation(
            self::MUTATION,
            [
                'input' => [
                    'workflow_stage_id' => $workflow_stage2->id,
                ],
            ]
        );
        $error = end($result);
        $this->assertStringContainsString('Can only delete stage from a draft workflow version', $error);
    }

    public function test_delete_stage_with_first_stage() {
        $this->setAdminUser();
        $generator = approval_generator::instance();
        $workflow_type = $generator->create_workflow_type('Testing');

        // Create a form and version.
        $form_version = $generator->create_form_and_version('simple', 'Simple Request Form');
        $form_id = $form_version->form->id;

        // Draft workflow:
        $workflow_go = new workflow_generator_object($workflow_type->id, $form_id, $form_version->id);
        $workflow_go->name = "Archived Workflow";
        $workflow_go->id_number = "ARCHIVED-FLOW";
        $workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
        $workflow_version_entity->status = status::DRAFT;
        $workflow_version_entity->update();
        $workflow_version = workflow_version::load_by_entity($workflow_version_entity);
        $workflow_stage1 = workflow_stage::create($workflow_version, 'Archived stage 1', form_submission::get_enum());

        $result = $this->parsed_graphql_operation(
            self::MUTATION,
            [
                'input' => [
                    'workflow_stage_id' => $workflow_stage1->id,
                ],
            ]
        );
        $error = end($result);
        $this->assertStringContainsString('Cannot remove the first stage', $error);
    }

    public function test_delete_stage_with_last_finished_stage() {
        $this->setAdminUser();
        $generator = approval_generator::instance();
        $workflow_type = $generator->create_workflow_type('Testing');

        // Create a form and version.
        $form_version = $generator->create_form_and_version('simple', 'Simple Request Form');
        $form_id = $form_version->form->id;

        // Draft workflow:
        $workflow_go = new workflow_generator_object($workflow_type->id, $form_id, $form_version->id);
        $workflow_go->name = "Archived Workflow";
        $workflow_go->id_number = "ARCHIVED-FLOW";
        $workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
        $workflow_version_entity->status = status::DRAFT;
        $workflow_version_entity->update();
        $workflow_version = workflow_version::load_by_entity($workflow_version_entity);
        workflow_stage::create($workflow_version, 'Archived stage 1', form_submission::get_enum());
        $workflow_stage2 = workflow_stage::create($workflow_version, 'Archived stage 1', finished::get_enum());

        $result = $this->parsed_graphql_operation(
            self::MUTATION,
            [
                'input' => [
                    'workflow_stage_id' => $workflow_stage2->id,
                ],
            ]
        );
        $error = end($result);
        $this->assertStringContainsString('Cannot remove the last finished stage', $error);
    }

    public function test_with_invalid_stage() {
        $this->setAdminUser();

        $args = [
            'input' => [
                'workflow_stage_id' => 998,
            ],
        ];
        $this->expectException(record_not_found_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }
}
