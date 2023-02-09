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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\testing\generator as mod_approval_generator;
use mod_approval\testing\workflow_generator_object;

require_once(__DIR__ . '/testcase.php');

/**
 * Integration tests for transitioning an application into different stage.
 *
 * @group approval_workflow
 */
class mod_approval_application_transitions_testcase extends mod_approval_testcase {

    public function test_trial() {
        list($workflow) = $this->setup_workflow();

        // Create an application.
        $this->setAdminUser();
        $this->create_application_for_user_on($workflow);
        // todo: finish test
    }

    public function test_transitioning_between_stages(): void {
        list($workflow, $workflow_stage1, $workflow_stage2, $approval_level1, $approval_level2, $workflow_stage3) =
            $this->setup_workflow();

        // Create an application.
        $this->setAdminUser();
        $application = $this->create_application_for_user_on($workflow);

        // Starts out draft on stage 1 - form_submission.
        $current_state = $application->current_state;
        self::assertEquals($workflow_stage1->id, $current_state->get_stage_id());
        self::assertTrue($current_state->is_stage_type(form_submission::get_code()));
        self::assertTrue($current_state->is_draft());
        self::assertNull($current_state->get_approval_level_id());

        // Move to the next state.
        $next_state = $application->current_stage->state_manager->get_next_state($application->current_state);
        $application->set_current_state($next_state);

        // Next is approval level 1 on stage 2 - approvals.
        $current_state = $application->current_state;
        self::assertEquals($workflow_stage2->id, $current_state->get_stage_id());
        self::assertTrue($current_state->is_stage_type(approvals::get_code()));
        self::assertFalse($current_state->is_draft());
        self::assertEquals($approval_level1->id, $current_state->get_approval_level_id());

        // Move to the next state.
        $next_state = $application->current_stage->state_manager->get_next_state($application->current_state);
        $application->set_current_state($next_state);

        // Then approval level 2 on stage 2 - approvals.
        $current_state = $application->current_state;
        self::assertEquals($workflow_stage2->id, $current_state->get_stage_id());
        self::assertTrue($current_state->is_stage_type(approvals::get_code()));
        self::assertFalse($current_state->is_draft());
        self::assertEquals($approval_level2->id, $current_state->get_approval_level_id());

        // Move to the next state.
        $next_state = $application->current_stage->state_manager->get_next_state($application->current_state);
        $application->set_current_state($next_state);

        // Then it moves to stage 3 - Finished.
        $current_state = $application->current_state;
        self::assertEquals($workflow_stage3->id, $current_state->get_stage_id());
        self::assertTrue($current_state->is_stage_type(finished::get_code()));
        self::assertFalse($current_state->is_draft());
        self::assertNull($current_state->get_approval_level_id());

        // Try moving from a finished stage type.
        try {
            $application->current_stage->state_manager->get_next_state($application->current_state);
            $this->fail("Getting next state when an application is in a finished stage type should throw an exception");
        } catch (coding_exception $exception) {
            $this->assertInstanceOf(coding_exception::class, $exception);
            $this->assertStringContainsString('Finished stages do not have next states', $exception->getMessage());
        }
    }

    /**
     * @return array
     */
    private function setup_workflow(): array {
        $mod_approval_generator = mod_approval_generator::instance();

        // Create a workflow with two stages, with stage 1 containing two approval levels.
        $workflow_type = $mod_approval_generator->create_workflow_type('test workflow type');

        $form_version = $mod_approval_generator->create_form_and_version();
        $form = $form_version->form;

        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id, status::DRAFT);
        $workflow_version = $mod_approval_generator->create_workflow_and_version($workflow_go);

        $workflow_stage1 = $mod_approval_generator
            ->create_workflow_stage($workflow_version->id, 'Stage 1', form_submission::get_enum());
        $workflow_stage2 = $mod_approval_generator
            ->create_workflow_stage($workflow_version->id, 'Stage 2', approvals::get_enum());
        $workflow_stage3 = $mod_approval_generator
            ->create_workflow_stage($workflow_version->id, 'Stage 3', finished::get_enum());

        $workflow_stage2_model = workflow_stage::load_by_entity($workflow_stage2);
        $approval_level1 = $workflow_stage2_model->approval_levels->first();
        $approval_level2 = $workflow_stage2_model->add_approval_level('Level 2');
        $workflow = workflow::load_by_entity($workflow_version->workflow);
        $workflow->publish($workflow->get_latest_version());

        return array($workflow, $workflow_stage1, $workflow_stage2, $approval_level1, $approval_level2, $workflow_stage3);
    }
}
