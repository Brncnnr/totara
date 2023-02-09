<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 */

use core_phpunit\testcase;
use mod_approval\entity\workflow\workflow_stage_approval_level;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\assignment_generator_object;
use totara_webapi\phpunit\webapi_phpunit_helper;
use mod_approval\testing\generator as approval_generator;
use mod_approval\testing\workflow_generator_object;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\mutation\workflow_stage_edit_approval_level
 */
class mod_approval_webapi_mutation_workflow_stage_edit_approval_level_testcase extends testcase {

    private const MUTATION = 'mod_approval_workflow_stage_edit_approval_level';

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

        $data = $this->generate_data();

        /** @var workflow_stage $draft_workflow_stage */
        $draft_workflow_stage = $data['draft']['stage'];
        $approval_level = $draft_workflow_stage->get_approval_levels()->first();
        $args = [
            'workflow_stage_approval_level_id' => $approval_level->id,
        ];
        $parsed_query = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($parsed_query);
    }

    public function test_edit_approval_level_with_multi_lang_name() {
        $this->setAdminUser();
        $data = $this->generate_data();

        /** @var workflow_stage $draft_workflow_stage */
        $draft_workflow_stage = $data['draft']['stage'];
        $approval_level = $draft_workflow_stage->get_approval_levels()->first();
        $approval_level_name = '<span lang="en" class="multilang">English title</span>
                <span lang="de" class="multilang">deutscher Titel</span>';

        $result = $this->parsed_graphql_operation(
            self::MUTATION,
            [
                'input' => [
                    'workflow_stage_approval_level_id' => $approval_level->id,
                    'name' => $approval_level_name,
                ],
            ]
        );
        $result = reset($result);

        $draft_workflow_stage->refresh(true);
        $approval_levels = $draft_workflow_stage->approval_levels->all();

        $this->assertNotEmpty($approval_levels[0]);

        $approval_level = $approval_levels[0];
        $this->assertEquals($approval_level_name, $approval_level->name);
    }

    public function test_edit_without_capability() {
        $this->setAdminUser();
        $data = $this->generate_data();
        $draft_workflow_stage = $data['draft']['stage'];
        $approval_level = $draft_workflow_stage->get_approval_levels()->first();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $args = [
            'input' => [
                'workflow_stage_approval_level_id' => $approval_level->id,
                'name' => $approval_level->name,
            ],
        ];

        $this->expectException(access_denied_exception::class);
        $this->expectExceptionMessage('Can not edit approval level');
        $this->resolve_graphql_mutation(self::MUTATION, $args);

        // Mutation is succeeds when the capability is granted to the user
        $workflow = $draft_workflow_stage->get_workflow();
        $context = $workflow->get_context();
        assign_capability("mod/approval:add_workflow_approval_level", CAP_ALLOW, $user->role->id, $context, true);

        $this->resolve_graphql_mutation(self::MUTATION, $args);
    }

    public function data_status(): array {
        return [
            'active' => [status::ACTIVE],
            'archived' => [status::ARCHIVED],
        ];
    }

    public function test_edit_in_inactive_stage() {
        $this->setAdminUser();
        $data = $this->generate_data();

        /** @var workflow_stage $workflow_stage */
        $workflow_stage = $data['active']['stage'];
        $workflow_stage->deactivate();
        $this->assertFalse($workflow_stage->active);

        $approval_level = $workflow_stage->get_approval_levels()->first();
        $this->assertNotEmpty($approval_level);

        $args = [
            'input' => [
                'workflow_stage_approval_level_id' => $approval_level->id,
                'name' => 'Name',
            ],
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_failed($result, 'Can only edit approval level attached to a draft workflow version');
    }

    public function test_edit_in_active_stage() {
        $this->setAdminUser();
        $data = $this->generate_data();

        /** @var workflow_stage $workflow_stage */
        $workflow_stage = $data['draft']['stage'];
        $this->assertTrue($workflow_stage->active);

        $approval_level = $workflow_stage->get_approval_levels()->first();
        $this->assertNotEmpty($approval_level);

        $args = [
            'input' => [
                'workflow_stage_approval_level_id' => $approval_level->id,
                'name' => 'Name',
            ],
        ];
        $result = $this->parsed_graphql_operation(self::MUTATION, $args);
        $this->assert_webapi_operation_successful($result);
        $result = reset($result);

        $workflow_stage->refresh(true);

        $this->assertNotEmpty($workflow_stage->approval_levels->all());

        // Test that this record id does not exists anymore
        $this->assertNotEmpty(workflow_stage_approval_level::repository()->find($approval_level->id));
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
        $assignment_go = new assignment_generator_object(
            $draft_workflow_version_entity->workflow->course_id,
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $assignment_go->is_default = true;
        $assignment_go->status = status::ACTIVE;
        $this->generator()->create_assignment($assignment_go);
        $draft_workflow_version = workflow_version::load_by_entity($draft_workflow_version_entity);
        $draft_workflow_stage = workflow_stage::create($draft_workflow_version, 'Draft stage 1', approvals::get_enum());

        // Active workflow:
        $workflow_go = new workflow_generator_object($workflow_type->id, $form_id, $form_version->id);
        $workflow_go->name = "Crafty Workflow";
        $workflow_go->id_number = "WATER-FLOW";
        $active_workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
        $assignment_go->course = $active_workflow_version_entity->workflow->course_id;
        $this->generator()->create_assignment($assignment_go);
        $active_workflow_version_entity->status = status::DRAFT;
        $active_workflow_version_entity->update();
        $active_workflow_version = workflow_version::load_by_entity($active_workflow_version_entity);
        $active_workflow_stage = workflow_stage::create($active_workflow_version, 'Active stage 1', approvals::get_enum());
        $active_workflow_version->activate();

        // Archived workflows:
        $workflow_go = new workflow_generator_object($workflow_type->id, $form_id, $form_version->id);
        $workflow_go->name = "Closed-off Workflow";
        $workflow_go->id_number = "ARCHIVED-FLOW";
        $archived_workflow_version_entity = $generator->create_workflow_and_version($workflow_go);
        $archived_workflow_version_entity->status = status::DRAFT;
        $archived_workflow_version_entity->update();
        $assignment_go->course = $archived_workflow_version_entity->workflow->course_id;
        $this->generator()->create_assignment($assignment_go);
        $archived_workflow_version = workflow_version::load_by_entity($archived_workflow_version_entity);
        $archived_workflow_stage = workflow_stage::create($archived_workflow_version, 'Archived stage 1', approvals::get_enum());
        $archived_workflow_version->archive();

        return [
            'draft' => [
                'stage' => $draft_workflow_stage,
                'version' => $draft_workflow_version,
            ],
            'active' => [
                'stage' => $active_workflow_stage,
                'version' => $active_workflow_version,
            ],
            'archived' => [
                'stage' => $archived_workflow_stage,
                'version' => $archived_workflow_version,
            ],
        ];
    }
}
