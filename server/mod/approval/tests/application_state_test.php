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

use core_phpunit\testcase;
use mod_approval\model\application\application_state;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\testing\generator as mod_approval_generator;
use mod_approval\testing\workflow_generator_object;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\application\application_state
 */
class mod_approval_application_state_testcase extends testcase {

    /**
     * @covers ::get_stage_id
     * @covers ::is_draft
     * @covers ::get_approval_level
     */
    public function test_constructor_succeeds(): void {
        $application_state = new application_state(
            123,
            false,
            456
        );
        self::assertEquals(123, $application_state->get_stage_id());
        self::assertFalse($application_state->is_draft());
        self::assertEquals(456, $application_state->get_approval_level_id());
    }

    public function test_get_stage_id(): void {
        $application_state = new application_state(
            123
        );
        self::assertEquals(123, $application_state->get_stage_id());
    }

    public function test_is_draft(): void {
        $application_state = new application_state(
            123,
            true
        );
        self::assertEquals(true, $application_state->is_draft());
        $application_state = new application_state(
            123,
            false
        );
        self::assertEquals(false, $application_state->is_draft());
    }

    public function test_get_stage(): void {
        // Create a workflow stage.
        $mod_approval_generator = mod_approval_generator::instance();
        $workflow_type = $mod_approval_generator->create_workflow_type('test workflow type');
        $form_version = $mod_approval_generator->create_form_and_version();
        $form = $form_version->form;
        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id, status::DRAFT);
        $workflow_version = $mod_approval_generator->create_workflow_and_version($workflow_go);
        $workflow_stage = $mod_approval_generator->create_workflow_stage(
            $workflow_version->id,
            'Stage 1',
            form_submission::get_enum()
        );
        $workflow_version->status = status::ACTIVE;
        $workflow_version->save();

        $application_state = new application_state(
            $workflow_stage->id,
            true
        );

        $result_workflow_stage = $application_state->get_stage();
        self::assertEquals(workflow_stage::class, get_class($result_workflow_stage));
        self::assertEquals($workflow_stage->id, $result_workflow_stage->id);
    }

    public function test_is_stage_type(): void {
        // TODO writeme Nathan!
    }

    public function test_get_approval_level_id(): void {
        $application_state = new application_state(
            123,
            false,
            456
        );
        self::assertEquals(456, $application_state->get_approval_level_id());
    }

    public function test_get_approval_level(): void {
        // Create a workflow stage and approval level.
        $mod_approval_generator = mod_approval_generator::instance();
        $workflow_type = $mod_approval_generator->create_workflow_type('test workflow type');
        $form_version = $mod_approval_generator->create_form_and_version();
        $form = $form_version->form;
        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id, status::DRAFT);
        $workflow_version = $mod_approval_generator->create_workflow_and_version($workflow_go);
        $workflow_stage = $mod_approval_generator->create_workflow_stage(
            $workflow_version->id,
            'Stage 1',
            form_submission::get_enum()
        );
        $approval_level = $mod_approval_generator->create_approval_level($workflow_stage->id, 'Level 1', 1);
        $workflow_version->status = status::ACTIVE;
        $workflow_version->save();

        $application_state = new application_state(
            $workflow_stage->id,
            false,
            $approval_level->id
        );

        $result_approval_level = $application_state->get_approval_level();
        self::assertEquals(workflow_stage_approval_level::class, get_class($result_approval_level));
        self::assertEquals($approval_level->id, $result_approval_level->id);
    }

    public function test_is_same_as(): void {
        // Same without approval level.
        $application_state1 = new application_state(
            123,
            true
        );
        $application_state2 = new application_state(
            123,
            true
        );
        self::assertTrue($application_state1->is_same_as($application_state2));

        // Same with approval level.
        $application_state1 = new application_state(
            123,
            false,
            456
        );
        $application_state2 = new application_state(
            123,
            false,
            456
        );
        self::assertTrue($application_state1->is_same_as($application_state2));

        // Different approval level.
        $application_state1 = new application_state(
            123,
            false,
            456
        );
        $application_state2 = new application_state(
            123,
            false,
            999
        );
        self::assertFalse($application_state1->is_same_as($application_state2));

        // Different stage (approval levels would have to be different in real work example, but doesn't
        // matter for testing).
        $application_state1 = new application_state(
            123,
            false,
            456
        );
        $application_state2 = new application_state(
            777,
            false,
            456
        );
        self::assertFalse($application_state1->is_same_as($application_state2));

        // Different condition.
        $application_state1 = new application_state(
            123,
            true
        );
        $application_state2 = new application_state(
            123,
            false
        );
        self::assertFalse($application_state1->is_same_as($application_state2));
    }
}
