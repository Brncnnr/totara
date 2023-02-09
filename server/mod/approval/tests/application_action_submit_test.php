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
use mod_approval\model\application\activity\stage_submitted as stage_submitted_activity;
use mod_approval\model\application\application_state;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\application\action\submit
 */
class mod_approval_application_action_submit_testcase extends mod_approval_testcase {
    /**
     * @covers ::get_code
     */
    public function test_get_code(): void {
        $this->assertEquals(4, submit::get_code());
    }

    /**
     * @covers ::get_enum
     */
    public function test_get_enum(): void {
        $this->assertEquals('SUBMIT', submit::get_enum());
    }

    /**
     * @covers ::get_label
     */
    public function test_get_label(): void {
        $this->assertEquals(
            new lang_string('model_application_action_status_submitted', 'mod_approval'),
            submit::get_label()
        );
    }

    public function test_submit(): void {
        $submitter = self::getDataGenerator()->create_user();
        $resubmitter = self::getDataGenerator()->create_user();

        // Create an application.
        $this->setAdminUser();
        $application = $this->create_application_for_user();
        $stage1 = $application->current_state->get_stage();
        $stage2 = $application->workflow_version->get_next_stage($stage1->id);

        // Submitted and submitter are not set initially.
        self::assertNull($application->submitted);
        self::assertNull($application->submitter_id);

        // No stage submitted activities exist.
        self::assertEquals(0, application_activity_entity::repository()
            ->where('activity_type', '=', stage_submitted_activity::get_type())
            ->count()
        );

        // Run the function.
        submit::execute($application, $submitter->id);

        // Stage submitted activity was created.
        self::assertEquals(1, application_activity_entity::repository()
            ->where('activity_type', '=', stage_submitted_activity::get_type())
            ->count()
        );

        // State has been updated to in approvals on the same stage.
        $current_state = $application->current_state;
        self::assertEquals($stage2->id, $current_state->get_stage_id());
        self::assertEquals(false, $current_state->is_draft());
        self::assertEquals($stage2->get_approval_levels()->first()->id, $current_state->get_approval_level_id());

        // Submitted and submitter were updated for first-time call to submit.
        $now = time();
        self::assertGreaterThan(0, $application->submitted);
        self::assertLessThanOrEqual($now, $application->submitted);
        self::assertEquals($submitter->id, $application->submitter_id);

        // Submitted and submitter are not called if an application is re-submitted (even if it is draft).
        $application->set_current_state(new application_state($stage1->id, true));
        self::waitForSecond();
        submit::execute($application, $resubmitter->id);
        self::assertGreaterThan(0, $application->submitted);
        self::assertLessThanOrEqual($now, $application->submitted);
        self::assertEquals($submitter->id, $application->submitter_id);

        // Can be submitted when BEFORE_SUBMISSION.
        $application->set_current_state(new application_state($stage1->id));
        submit::execute($application, $resubmitter->id);

        // Cannot submit when IN_APPROVALS.
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Cannot submit application because the state is not before submission');
        submit::execute($application, $resubmitter->id);
    }
}
