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
 * @package mod_approval
 */

use mod_approval\entity\application\application_action as application_action_entity;
use mod_approval\entity\application\application_activity as application_activity_entity;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reset_approvals;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\activity\approvals_reset as approvals_reset_activity;
use mod_approval\model\application\application_state;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\generator as mod_approval_generator;
use mod_approval\testing\workflow_generator_object;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\application\action\reset_approvals
 */
class mod_approval_application_action_reset_approvals_testcase extends mod_approval_testcase {
    /**
     * @covers ::get_code
     */
    public function test_get_code(): void {
        $this->assertEquals(6, reset_approvals::get_code());
    }

    /**
     * @covers ::get_enum
     */
    public function test_get_enum(): void {
        $this->assertEquals('RESET_APPROVALS', reset_approvals::get_enum());
    }

    /**
     * @covers ::get_label
     */
    public function test_get_label(): void {
        $this->assertEquals(
            new lang_string('model_application_action_status_approvals_reset', 'mod_approval'),
            reset_approvals::get_label()
        );
    }

    /**
     * @covers ::execute
     */
    public function test_execute(): void {
        $mod_approval_generator = mod_approval_generator::instance();
        $submitter_user = self::getDataGenerator()->create_user();
        $approver_user = self::getDataGenerator()->create_user();
        $third_user = self::getDataGenerator()->create_user();

        // Create a workflow with one stage and three approval levels.
        $workflow_type = $mod_approval_generator->create_workflow_type('test workflow type');

        $form_version = $mod_approval_generator->create_form_and_version();
        $form = $form_version->form;

        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id, status::DRAFT);
        $workflow_version = $mod_approval_generator->create_workflow_and_version($workflow_go);

        $workflow_stage1 = $mod_approval_generator->create_workflow_stage($workflow_version->id, 'Stage 1', form_submission::get_enum());
        $workflow_stage2 = $mod_approval_generator->create_workflow_stage($workflow_version->id, 'Stage 2', approvals::get_enum());

        $approval_level1 = $mod_approval_generator->create_approval_level($workflow_stage2->id, 'Level 1', 1);
        $mod_approval_generator->create_approval_level($workflow_stage2->id, 'Level 2', 2);
        $mod_approval_generator->create_approval_level($workflow_stage2->id, 'Level 3', 3);
        $workflow = workflow::load_by_entity($workflow_version->workflow);
        $workflow->publish($workflow->get_latest_version());

        // Create application.
        $this->setAdminUser();
        $application = $this->create_application_for_user_on($workflow);

        // Mark the application in approvals on the second third approval level.
        $submission = application_submission::create_or_update(
            $application,
            $submitter_user->id,
            form_data::create_empty()
        );
        $submission->publish($submitter_user->id);
        submit::execute($application, $submitter_user->id);
        approve::execute($application, $approver_user->id);
        approve::execute($application, $approver_user->id);

        // There are two approve actions which are not superseded, and none which are superseded.
        self::assertEquals(2,application_action_entity::repository()
            ->where('code', '=', approve::get_code())
            ->where('superseded', '=', 0)
            ->count()
        );
        self::assertEquals(0,application_action_entity::repository()
            ->where('code', '=', approve::get_code())
            ->where('superseded', '=', 1)
            ->count()
        );

        // Run the function.
        reset_approvals::execute($application, $third_user->id);

        // There are two approve actions which are not superseded, and none which are superseded.
        self::assertEquals(0,application_action_entity::repository()
            ->where('code', '=', approve::get_code())
            ->where('superseded', '=', 0)
            ->count()
        );
        self::assertEquals(2,application_action_entity::repository()
            ->where('code', '=', approve::get_code())
            ->where('superseded', '=', 1)
            ->count()
        );

        // Level actions reset activity is recorded.
        self::assertEquals(1, application_activity_entity::repository()
            ->where('activity_type', '=', approvals_reset_activity::get_type())
            ->count()
        );

        // The application state has been moved back to the first approval level.
        self::assertEquals($approval_level1->id, $application->current_state->get_approval_level_id());

        // Cannot reset approvals when not in approvals.
        $application->set_current_state(new application_state($workflow_stage1->id));
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Cannot reset approvals when not in approvals');
        reset_approvals::execute($application, $approver_user->id);
    }
}
