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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use core_phpunit\testcase;
use mod_approval\controllers\application\edit;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\application\application_action;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\model\assignment\assignment as assignment_model;
use totara_core\advanced_feature;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\controllers\application\edit
 */
class mod_approval_controller_edit_application_testcase extends testcase {

    use approval_workflow_test_setup;

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    public function test_context(): void {
        [$application, $assignment] = $this->set_application();

        $assignment_model = assignment_model::load_by_id($assignment->id);

        $_POST['application_id'] = $application->id;

        self::assertSame($assignment_model->get_context(), (new edit())->get_context());
    }

    public function test_access(): void {
        advanced_feature::enable('approval_workflows');
        list($application) = $this->set_application();

        $_POST['application_id'] = $application->id;

        // User can edit application in DRAFT state
        ob_start();
        (new edit())->process();
        ob_get_clean();

        // Submit form as applicant.
        $form_data = form_data::from_json('{"agency_code":"what?"}');
        $submission = application_submission::create_or_update($application, $application->user->id, $form_data);
        $submission->publish(user::logged_in()->id);
        submit::execute($application, user::logged_in()->id);

        // User cannot edit submitted application
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Unsupported redirect detected, script execution terminated');
        (new edit())->process();

        // User can edit application in WITHDRAWN state
        application_action::create(
            $application,
            $application->user_id,
            new withdraw_in_approvals()
        );

        ob_start();
        (new edit())->process();
        ob_get_clean();
    }

    public function test_invalid_param(): void {
        $this->set_application();

        $_POST['application_id'] = 9999999;

        $this->expectException(moodle_exception::class);
        (new edit())->process();
    }

    private function set_application(): array {
        $this->setAdminUser();
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $application = $this->create_application($workflow, $assignment, user::logged_in());

        return [$application, $assignment];
    }
}
