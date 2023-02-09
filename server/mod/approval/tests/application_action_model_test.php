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
 * @author Angela Kuznetsova <angela.kuzntetsova@totaralearning.com>
 * @package mod_approval
 */

use core\orm\query\builder;
use core\entity\user as user_entity;
use mod_approval\entity\application\application_action as application_action_entity;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\application_action;
use mod_approval\model\application\application_submission;
use mod_approval\model\form\form_data;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\testing\formview_generator_object;
use mod_approval\testing\workflow_generator_object;

require_once(__DIR__ . '/testcase.php');

/**
 * @coversDefaultClass \mod_approval\model\application\application_action
 *
 * @group approval_workflow
 */
class mod_approval_application_action_model_testcase extends mod_approval_testcase {

    use approval_workflow_test_setup;

    /**
     * Gets the generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * @covers ::create
     * @return application_action $application
     * @throws coding_exception
     */
    public function test_create(): application_action {
        $application_action_repository = application_action_entity::repository();

        // Create a simple workflow and an application for a user
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user = new user_entity($this->getDataGenerator()->create_user()->id);
        $this->setUser($user);
        $application = $this->create_submitted_application($workflow, $assignment, $user);

        $this->assertEquals(0, $application_action_repository->count());

        $form_data = form_data::from_json('{"agency_code":"Astronauts are inherently insane. And really noble."}');
        $time = time();
        application_submission::create_or_update($application, $user->id, $form_data)->publish($user->id);
        $application_action = application_action::create($application, $user->id, new approve());

        // We have a repository
        $this->assertEquals(1, $application_action_repository->count());
        $this->assertInstanceOf(get_class($application_action), $application_action);
        $this->assertEquals($user->id, $application_action->user_id);
        $this->assertEquals($application->id, $application_action->application_id);
        $this->assertEquals($application->current_approval_level->id, $application_action->workflow_stage_approval_level_id);
        $this->assertEquals(approve::get_code(), $application_action->code);
        $this->assertEquals($form_data->to_json(), $application_action->form_data);
        $this->assertGreaterThanOrEqual($time, $application_action->created);
        return $application_action;
    }

    public function test_supercede_actions_for_stage() {
        // Create an application.
        list($workflow, , $assignment) = $this->create_workflow_and_assignment();
        $user = new user_entity($this->getDataGenerator()->create_user()->id);
        $application = $this->create_application($workflow, $assignment, $user);
        $workflow_version = $application->workflow_version;
        $stage1 = $workflow_version->stages->first();
        $stage2 = $workflow_version->get_next_stage($stage1->id);

        // Submit the application.
        $form_data =  form_data::from_json('{"request":"Astronauts are inherently insane. And really noble."}');
        $submission = application_submission::create_or_update($application, $user->id, $form_data);
        $submission->publish($user->id);
        submit::execute($application, $user->id);

        // Then approve the application.
        approve::execute($application, $user->id);

        // Check that there is a recorded action.
        $this->assertCount(1, $application->actions);
        /** @var application_action $action*/
        $action = $application->actions->first();
        $this->assertFalse($action->superseded);

        // Mark the action superseded.
        application_action::supercede_actions_for_stage($application, $stage2);
        $application->refresh(true);

        // Check that there is a superseded action.
        $this->assertCount(1, $application->actions);
        /** @var application_action $action*/
        $action = $application->actions->first();
        $this->assertTrue($action->superseded);
    }

    /**
     * @covers ::refresh
     * @throws coding_exception
     */
    public function test_refresh(): void {
        $application_action = $this->test_create();
        $this->assertFalse($application_action->superseded);
        builder::table(application_action_entity::TABLE)->update(['superseded' => '1']);
        $application_action->refresh();
        $this->assertTrue($application_action->superseded);
    }

    /**
     * @covers ::delete
     * @throws coding_exception
     */
    public function test_delete(): void {
        $application_action = $this->test_create();
        $this->assertNotEmpty($application_action->id);
        $application_action->delete();
        $this->assertEmpty($application_action->id);
    }
}
