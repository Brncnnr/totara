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

use mod_approval\model\application\application_state;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\generator as mod_approval_generator;
use mod_approval\testing\workflow_generator_object;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @coversDefaultClass \mod_approval\webapi\resolver\type\application_state
 *
 * @group approval_workflow
 */
class mod_approval_webapi_type_application_state_testcase extends mod_approval_testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private const TYPE = 'mod_approval_application_state';

    /**
     * Gets the approval workflow generator instance
     *
     * @return mod_approval_generator
     */
    protected function generator(): mod_approval_generator {
        return mod_approval_generator::instance();
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_input(): void {
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Wrong object is passed');

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_field(): void {
        $application_state = new application_state(123, true);

        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Tried to access unknown field: xyz');

        $this->resolve_graphql_type(self::TYPE, 'xyz', $application_state);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        // Create a workflow stage and approval level.
        $mod_approval_generator = mod_approval_generator::instance();
        $workflow_type = $mod_approval_generator->create_workflow_type('test workflow type');
        $form_version = $mod_approval_generator->create_form_and_version();
        $form = $form_version->form;
        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id, status::DRAFT);
        $workflow_version = $mod_approval_generator->create_workflow_and_version($workflow_go);
        $form_stage = $mod_approval_generator->create_workflow_stage(
            $workflow_version->id,
            'Stage 1',
            form_submission::get_enum()
        );
        $approval_stage = $mod_approval_generator->create_workflow_stage(
            $workflow_version->id,
            'Stage 2',
            approvals::get_enum()
        );
        $approval_level = $mod_approval_generator->create_approval_level(
            $approval_stage->id,
            'Level 1',
            1
        );
        $finished_stage = $mod_approval_generator->create_workflow_stage(
            $workflow_version->id,
            'Stage 3',
            finished::get_enum()
        );
        $workflow_version->status = status::ACTIVE;
        $workflow_version->save();

        $application_state = new application_state(
            $form_stage->id,
            false,
            $approval_level->id
        );

        // Stage (returns stage model).
        $result = $this->resolve_graphql_type(self::TYPE, 'stage', $application_state);
        self::assertEquals(workflow_stage::class, get_class($result));
        self::assertEquals($form_stage->id, $result->id);

        // Approval level (returns approval level model).
        $result = $this->resolve_graphql_type(self::TYPE, 'approval_level', $application_state);
        self::assertEquals(workflow_stage_approval_level::class, get_class($result));
        self::assertEquals($approval_level->id, $result->id);

        // Is draft.
        $application_state = new application_state($form_stage->id, true);
        self::assertTrue($this->resolve_graphql_type(self::TYPE, 'is_draft', $application_state));
        $application_state = new application_state($form_stage->id, false, $approval_level->id);
        self::assertFalse($this->resolve_graphql_type(self::TYPE, 'is_draft', $application_state));

        // Is before submission.
        $application_state = new application_state($form_stage->id, true); // Draft is before submission.
        self::assertTrue($this->resolve_graphql_type(self::TYPE, 'is_before_submission', $application_state));
        $application_state = new application_state($approval_stage->id, false, $approval_level->id);
        self::assertFalse($this->resolve_graphql_type(self::TYPE, 'is_before_submission', $application_state));

        // Is in approvals.
        $application_state = new application_state($form_stage->id, true);
        self::assertFalse($this->resolve_graphql_type(self::TYPE, 'is_in_approvals', $application_state));
        $application_state = new application_state($approval_stage->id, false, $approval_level->id);
        self::assertTrue($this->resolve_graphql_type(self::TYPE, 'is_in_approvals', $application_state));

        // Is finished.
        $application_state = new application_state($form_stage->id, true);
        self::assertFalse($this->resolve_graphql_type(self::TYPE, 'is_finished', $application_state));
        $application_state = new application_state($finished_stage->id);
        self::assertTrue($this->resolve_graphql_type(self::TYPE, 'is_finished', $application_state));
    }
}
