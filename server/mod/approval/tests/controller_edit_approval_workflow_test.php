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

use core\entity\user as user_entity;
use core_phpunit\testcase;
use mod_approval\controllers\workflow\edit;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\approval_workflow_test_setup;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\controllers\workflow\edit
 */
class mod_approval_controller_edit_approval_workflow_testcase extends testcase {

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
        $this->setAdminUser();
        $workflow = $this->set_application();
        $workflow_model = workflow::load_by_entity($workflow);
        $_POST['workflow_id'] = $workflow->id;
        self::assertSame($workflow_model->get_context(), (new edit())->get_context());
    }

    public function test_edit_application(): void {
        global $CFG;
        $workflow = $this->set_application();
        $this->setAdminUser();
        $_POST['workflow_id'] = $workflow->id;

        $edit = (new edit())->action();
        $data = $edit->get_data();

        $this->assertArrayHasKey('back-url', $data);
        $this->assertEquals($CFG->wwwroot . '/mod/approval/workflow/index.php', $data['back-url']);

        $this->assertArrayHasKey('stage-types', $data);
        foreach ($data['stage-types'] as $stage_type) {
            $this->assertArrayHasKey('label', $stage_type);
            $this->assertArrayHasKey('enum', $stage_type);
        }
    }

    public function test_invalid_param(): void {
        $workflow = $this->set_application();
        $this->setAdminUser();
        $_POST['workflow_id'] = 9999999;

        $this->expectException(moodle_exception::class);
        (new edit())->process();
    }

    private function set_application() {
        $this->setAdminUser();
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $application = $this->create_application($workflow, $assignment, user_entity::logged_in());

        return $workflow;
    }
}