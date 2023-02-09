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
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\withdraw_before_submission;
use mod_approval\model\application\activity\withdrawn as withdrawn_activity;
use mod_approval\model\application\application_state;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use mod_approval\model\workflow\workflow_stage;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\application\action\withdraw_before_submission
 */
class mod_approval_application_action_withdraw_before_submission_testcase extends mod_approval_testcase {
    /**
     * @covers ::get_code
     */
    public function test_get_code(): void {
        $this->assertEquals(3, withdraw_before_submission::get_code());
    }

    /**
     * @covers ::get_enum
     */
    public function test_get_enum(): void {
        $this->assertEquals('WITHDRAW_BEFORE_SUBMISSION', withdraw_before_submission::get_enum());
    }

    /**
     * @covers ::get_label
     */
    public function test_get_label(): void {
        $this->assertEquals(
            new lang_string('model_application_action_status_withdrawn', 'mod_approval'),
            withdraw_before_submission::get_label()
        );
    }

    public function test_execute(): void {
        $submitter_user = self::getDataGenerator()->create_user();
        $approver_user = self::getDataGenerator()->create_user();
        $withdrawer_user = self::getDataGenerator()->create_user();

        // Create an application.
        $this->setAdminUser();
        $application = $this->create_application_for_user();

        // Last stage was configured as finished.
        $stage_id3 = $application->workflow_version->stages->last()->id;
        // Submit the application.
        $submission = application_submission::create_or_update(
            $application,
            $submitter_user->id,
            form_data::from_json('{"kia":"kaha"}')
        );
        $submission->publish($submitter_user->id);
        submit::execute($application, $submitter_user->id);

        // Reject to set it to BEFORE_SUBMISSION.
        reject::execute($application, $approver_user->id);

        // Start with no activities.
        application_activity_entity::repository()->delete();

        // Run the function.
        withdraw_before_submission::execute($application, $withdrawer_user->id);

        // Check that the action has been recorded.
        $this->assertEquals(withdraw_before_submission::get_code(), $application->last_action->code);

        // Withdraw activity was created.
        self::assertEquals(1, application_activity_entity::repository()
            ->where('activity_type', '=', withdrawn_activity::get_type())
            ->where_null('workflow_stage_approval_level_id')
            ->count()
        );
    }

    public function test_execute_when_state_is_not_draft_or_before_submissions() {
        $approver_user = self::getDataGenerator()->create_user();

        // Create an application.
        $this->setAdminUser();
        $application = $this->create_application_for_user();
        $stage_id1 = $application->current_state->get_stage_id();
        $stage_id2 = $application->get_next_stage()->id;

        // Cannot withdraw_before_submission when draft.
        $application->set_current_state(new application_state(
            $stage_id1,
            true
        ));
        try {
            withdraw_before_submission::execute($application, $approver_user->id);
            $this->fail('Expected exception not thrown');
        } catch (coding_exception $e) {
            $this->assertStringContainsString('Cannot withdraw application before submission because the state is not before submission', $e->getMessage());
        }

        // Cannot withdraw_before_submission when in approvals.
        $application->set_current_state(new application_state(
            $stage_id2,
            false,
            123
        ));
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Cannot withdraw application before submission because the state is not before submission');
        withdraw_before_submission::execute($application, $approver_user->id);
    }
}
