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
use mod_approval\model\application\action\submit;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\application\activity\withdrawn as withdrawn_activity;
use mod_approval\model\application\application_state;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\generator as mod_approval_generator;
use mod_approval\testing\workflow_generator_object;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\application\action\withdraw_in_approvals
 */
class mod_approval_application_action_withdraw_in_approvals_testcase extends mod_approval_testcase {
    /**
     * @covers ::get_code
     */
    public function test_get_code(): void {
        $this->assertEquals(2, withdraw_in_approvals::get_code());
    }

    /**
     * @covers ::get_enum
     */
    public function test_get_enum(): void {
        $this->assertEquals('WITHDRAW_IN_APPROVALS', withdraw_in_approvals::get_enum());
    }

    /**
     * @covers ::get_label
     */
    public function test_get_label(): void {
        $this->assertEquals(
            new lang_string('model_application_action_status_withdrawn', 'mod_approval'),
            withdraw_in_approvals::get_label()
        );
    }

    public function test_execute(): void {
        $mod_approval_generator = mod_approval_generator::instance();
        $submitter_user = self::getDataGenerator()->create_user();
        $approver_user = self::getDataGenerator()->create_user();
        $third_user = self::getDataGenerator()->create_user();

        // Create a workflow with two stages and three approval levels.
        $workflow_type = $mod_approval_generator->create_workflow_type('test workflow type');

        $form_version = $mod_approval_generator->create_form_and_version();
        $form = $form_version->form;

        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id, status::DRAFT);
        $workflow_version = $mod_approval_generator->create_workflow_and_version($workflow_go);

        $workflow_stage1 = $mod_approval_generator->create_workflow_stage($workflow_version->id, 'Stage 1', form_submission::get_enum());

        $workflow_stage2 = $mod_approval_generator->create_workflow_stage($workflow_version->id, 'Stage 2', approvals::get_enum());
        $approval_level1 = $mod_approval_generator->create_approval_level($workflow_stage2->id, 'Level 1', 1);
        $approval_level2 = $mod_approval_generator->create_approval_level($workflow_stage2->id, 'Level 2', 2);

        $workflow_stage3 = $mod_approval_generator->create_workflow_stage($workflow_version->id, 'End', finished::get_enum());
        $workflow = workflow::load_by_entity($workflow_version->workflow);
        $workflow->publish($workflow->get_latest_version());

        // Create application.
        $this->setAdminUser();
        $application = $this->create_application_for_user_on($workflow);

        // Mark the application in approvals on the first stage.
        $submission = application_submission::create_or_update(
            $application,
            $submitter_user->id,
            form_data::create_empty()
        );
        $submission->publish($submitter_user->id);
        submit::execute($application, $submitter_user->id);

        // Start with no activities.
        application_activity_entity::repository()->delete();

        // Run the function.
        withdraw_in_approvals::execute($application, $approver_user->id);

        // Check that the action has been recorded.
        $this->assertEquals(withdraw_in_approvals::get_code(), $application->last_action->code);

        // Withdrawn activity is recorded.
        self::assertEquals(1, application_activity_entity::repository()
            ->where('activity_type', '=', withdrawn_activity::get_type())
            ->where('workflow_stage_approval_level_id', '=', $approval_level1->id)
            ->count()
        );

        // Moves to BEFORE_SUBMISSION on the first stage (not DRAFT!).
        $current_state = $application->current_state;
        self::assertEquals($workflow_stage1->id, $current_state->get_stage_id());
        self::assertTrue($current_state->is_stage_type(form_submission::get_code()));
        self::assertFalse($current_state->is_draft());
        self::assertNull($current_state->get_approval_level_id());

        // Move to second approval level on second stage.
        $application->set_current_state(new application_state(
            $workflow_stage2->id,
            false,
            $approval_level2->id
        ));

        // Run the function.
        withdraw_in_approvals::execute($application, $third_user->id);

        // It was moved to BEFORE_SUBMISSION on the first stage.
        $current_state = $application->current_state;
        self::assertEquals($workflow_stage1->id, $current_state->get_stage_id());
        self::assertTrue($current_state->is_stage_type(form_submission::get_code()));
        self::assertFalse($current_state->is_draft());
        self::assertNull($current_state->get_approval_level_id());

        // Cannot withdraw_in_approvals when not in approvals.
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Cannot withdraw application in approvals because the state is not in approvals');
        withdraw_in_approvals::execute($application, $approver_user->id);
    }
}
