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
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\generator as approval_generator;
use mod_approval\testing\workflow_generator_object;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\workflow_version_add_stage
 */
class mod_approval_webapi_mutation_workflow_version_add_stage_testcase extends testcase {

    private const MUTATION = 'mod_approval_workflow_version_add_stage';

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
                'workflow_version_id' => '',
                'name' => '',
                'type' => '',
            ],
        ];
        $parsed_query = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($parsed_query);
    }

    public function test_add_workflow_stage_with_draft_version() {
        $this->setAdminUser();
        $data = $this->generate_data();

        /** @var workflow_version $draft_workflow_version */
        $draft_workflow_version = $data['draft']['version'];

        $a= $draft_workflow_version->stages;

        // Create first stage.
        $stage_name_1 = 'ST01';
        $stage_result_1 = $this->parsed_graphql_operation(
            self::MUTATION,
            [
                'input' => [
                    'workflow_version_id' => $draft_workflow_version->id,
                    'name' => $stage_name_1,
                    'type' => 'FORM_SUBMISSION',
                ],
            ]
        );
        $this->assert_webapi_operation_successful($stage_result_1);
        $stage_result_1 = reset($stage_result_1);

        // Create second stage.
        $stage_name_2 = 'ST02';
        $stage_result_2 = $this->parsed_graphql_operation(
            self::MUTATION,
            [
                'input' => [
                    'workflow_version_id' => $draft_workflow_version->id,
                    'name' => $stage_name_2,
                    'type' => 'APPROVALS',
                ],
            ]
        );
        $this->assert_webapi_operation_successful($stage_result_2);
        $stage_result_2 = reset($stage_result_2);

        $draft_workflow_version->refresh(true);
        $stages = $draft_workflow_version->stages->all();

        $this->assertEquals($stage_result_1['stage']['id'], $stages[0]->id);
        $this->assertEquals($stage_name_1, $stages[0]->name);
        $this->assertEquals(1, $stages[0]->ordinal_number);

        $this->assertEquals($stage_result_2['stage']['id'], $stages[1]->id);
        $this->assertEquals($stage_name_2, $stages[1]->name);
        $this->assertEquals(2, $stages[1]->ordinal_number);
    }

    public function test_add_workflow_stage_with_active_version() {
        $this->setAdminUser();
        $data = $this->generate_data();

        /** @var workflow_version $draft_workflow_version */
        $active_workflow_version = $data['active']['version'];
        $args = [
            'input' => [
                'workflow_version_id' => $active_workflow_version->id,
                'name' => 'abc',
                'type' => 'FORM_SUBMISSION',
            ],
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $error = end($result);
        $this->assertStringContainsString('Can only add stage to a draft workflow version', $error);
    }

    public function test_add_workflow_stage_with_archived_version() {
        $this->setAdminUser();
        $data = $this->generate_data();

        /** @var workflow_version $draft_workflow_version */
        $archived_workflow_version = $data['archived']['version'];
        $args = [
            'input' => [
                'workflow_version_id' => $archived_workflow_version->id,
                'name' => 'abc',
                'type' => 'FORM_SUBMISSION',
            ],
        ];

        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $error = end($result);
        $this->assertStringContainsString('Can only add stage to a draft workflow version', $error);
    }

    public function test_add_stage_to_invalid_workflow_version() {
        $this->setAdminUser();

        $args = [
            'input' => [
                'workflow_version_id' => 998,
                'name' => 'abc',
                'type' => 'FORM_SUBMISSION',
            ],
        ];
        $this->expectException(record_not_found_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function test_add_stage_with_max_length() {
        $this->setAdminUser();
        $data = $this->generate_data();

        /** @var workflow_version $draft_workflow_version */
        $archived_workflow_version = $data['archived']['version'];
        $args = [
            'input' => [
                'workflow_version_id' => $archived_workflow_version->id,
                'type' => 'FORM_SUBMISSION',
                'name' => str_repeat('f', 256),
            ]
        ];

        self::expectExceptionMessage('Length of name can not exceed 255');
        self::expectException(\moodle_exception::class);
        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    private function generate_data(): array {
        $generator = approval_generator::instance();
        $workflow_type = $generator->create_workflow_type('Testing');

        // Create a form and version.
        $form_version = $generator->create_form_and_version('simple', 'Simple Request Form');
        $form_id = $form_version->form->id;

        // Draft workflows:
        $workflow_go = new workflow_generator_object($workflow_type->id, $form_id, $form_version->id);
        $workflow_go->name = "Calm Workflow";
        $workflow_go->id_number = "DRAFT-FLOW";
        $draft_workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
        $draft_workflow_version_entity->status = status::DRAFT;
        $draft_workflow_version_entity->update();
        $draft_workflow_version = workflow_version::load_by_entity($draft_workflow_version_entity);

        // Active workflow:
        $workflow_go = new workflow_generator_object($workflow_type->id, $form_id, $form_version->id);
        $workflow_go->name = "Crafty Workflow";
        $workflow_go->id_number = "WATER-FLOW";
        $active_workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
        $active_workflow_version = workflow_version::load_by_entity($active_workflow_version_entity);

        // Archived workflows:
        $workflow_go = new workflow_generator_object($workflow_type->id, $form_id, $form_version->id);
        $workflow_go->name = "Closed-off Workflow";
        $workflow_go->id_number = "ARCHIVED-FLOW";
        $archived_workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
        $archived_workflow_version_entity->status = status::ARCHIVED;
        $archived_workflow_version_entity->update();
        $archived_workflow_version = workflow_version::load_by_entity($archived_workflow_version_entity);

        return [
            'draft' => [
                'version' => $draft_workflow_version,
            ],
            'active' => [
                'version' => $active_workflow_version,
            ],
            'archived' => [
                'version' => $archived_workflow_version,
            ],
        ];
    }
}