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

use mod_approval\entity\application\application_activity as application_activity_entity;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\activity\level_approved as level_approved_activity;
use mod_approval\model\application\activity\level_ended as level_ended_activity;
use mod_approval\model\application\activity\level_started as level_started_activity;
use mod_approval\model\application\activity\stage_all_approved as stage_all_approved_activity;
use mod_approval\model\application\application_state;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\generator as mod_approval_generator;
use mod_approval\testing\workflow_generator_object;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\application\action\approve
 */
class mod_approval_application_action_approve_testcase extends mod_approval_testcase {
    /**
     * @covers ::get_code
     */
    public function test_get_code(): void {
        $this->assertEquals(1, approve::get_code());
    }

    /**
     * @covers ::get_enum
     */
    public function test_get_enum(): void {
        $this->assertEquals('APPROVE', approve::get_enum());
    }

    /**
     * @covers ::get_label
     */
    public function test_get_label(): void {
        $this->assertEquals(
            new lang_string('model_application_action_status_approved', 'mod_approval'),
            approve::get_label()
        );
    }

    /**
     * @covers ::execute
     */
    public function test_execute(): void {
        $mod_approval_generator = mod_approval_generator::instance();
        $submitter_user = self::getDataGenerator()->create_user();
        $approver_user = self::getDataGenerator()->create_user();

        // Create a workflow with one stage and two approval levels.
        $workflow_type = $mod_approval_generator->create_workflow_type('test workflow type');

        $form_version = $mod_approval_generator->create_form_and_version();
        $form = $form_version->form;

        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id, status::DRAFT);
        $workflow_version = $mod_approval_generator->create_workflow_and_version($workflow_go);
        $workflow_version = workflow_version::load_by_entity($workflow_version);

        $stage1 = $mod_approval_generator->create_workflow_stage($workflow_version->id, 'Stage 1', form_submission::get_enum());

        $stage2 = $mod_approval_generator->create_workflow_stage($workflow_version->id, 'Stage 2', approvals::get_enum());
        $approval_level1 = $mod_approval_generator->create_approval_level($stage2->id, 'Level 1', 1);
        $approval_level2 = $mod_approval_generator->create_approval_level($stage2->id, 'Level 2', 2);

        $stage3 = $mod_approval_generator->create_workflow_stage($workflow_version->id, 'Stage 3', finished::get_enum());

        $workflow_version->workflow->publish($workflow_version);

        // Create application.
        $this->setAdminUser();
        $application = $this->create_application_for_user_on($workflow_version->workflow);
        // Submit the application.
        $submission = application_submission::create_or_update(
            $application,
            $submitter_user->id,
            form_data::from_json('{"kia":"kaha"}')
        );
        $submission->publish($submitter_user->id);
        submit::execute($application, $submitter_user->id);

        // Start with no activities.
        application_activity_entity::repository()->delete();

        // Run the function.
        approve::execute($application, $approver_user->id);

        // Check that the action has been recorded.
        $this->assertEquals(approve::get_code(), $application->last_action->code);

        // Level approved activity is recorded.
        self::assertEquals(1, application_activity_entity::repository()
            ->where('activity_type', '=', level_approved_activity::get_type())
            ->where('workflow_stage_approval_level_id', '=', $approval_level1->id)
            ->count()
        );

        // Level ended and level started activities are recorded when there is another approval level.
        self::assertEquals(1, application_activity_entity::repository()
            ->where('activity_type', '=', level_ended_activity::get_type())
            ->where('workflow_stage_approval_level_id', '=', $approval_level1->id)
            ->count()
        );
        self::assertEquals(1, application_activity_entity::repository()
            ->where('activity_type', '=', level_started_activity::get_type())
            ->where('workflow_stage_approval_level_id', '=', $approval_level2->id)
            ->count()
        );

        // Moves to next level when not last level.
        $current_state = $application->current_state;
        self::assertEquals($stage2->id, $current_state->get_stage_id());
        self::assertTrue($current_state->is_stage_type(approvals::get_code()));
        self::assertFalse($current_state->is_draft());
        self::assertEquals($approval_level2->id, $current_state->get_approval_level_id());

        // Run the function again.
        approve::execute($application, $approver_user->id);

        // Stage all approved activity is recorded on the last approval level.
        self::assertEquals(1, application_activity_entity::repository()
            ->where('activity_type', '=', stage_all_approved_activity::get_type())
            ->where('workflow_stage_id', '=', $stage2->id)
            ->where('workflow_stage_approval_level_id', '=', $approval_level2->id)
            ->count()
        );

        // Moves to finished after last level.
        $current_state = $application->current_state;
        self::assertEquals($stage3->id, $current_state->get_stage_id());
        self::assertTrue($current_state->is_stage_type(finished::get_code()));
        self::assertFalse($current_state->is_draft());
        self::assertNull($current_state->get_approval_level_id());

        // Cannot approve when not in approvals.
        $application->set_current_state(new application_state($stage1->id, true));
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Cannot approve application because the state is not in approvals');
        approve::execute($application, $approver_user->id);
    }
}
