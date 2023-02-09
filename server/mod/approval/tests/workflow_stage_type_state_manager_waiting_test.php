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
 */

use mod_approval\entity\application\application_activity;
use mod_approval\exception\model_exception;
use mod_approval\model\application\activity\stage_ended;
use mod_approval\model\application\activity\stage_started;
use mod_approval\model\application\application;
use mod_approval\model\application\application_activity as application_activity_model;
use mod_approval\model\application\application_state;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\stage_type\waiting;
use mod_approval\model\workflow\stage_type\state_manager\waiting as waiting_state_manager;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_version;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\stage_type\state_manager\waiting
 */
class mod_approval_workflow_stage_type_state_manager_waiting_testcase extends mod_approval_testcase {

    private $application;

    private $user;

    private $form_stage_3;

    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->user = $this->create_user();
        $form_stage3 = null;

        $this->setUser($this->user);
        $this->application = $this->create_application_for_user(
            null,
            function (workflow_version $workflow_version) use (&$form_stage3) {
                $form_stage = workflow_stage::create($workflow_version, 'stage 1', form_submission::get_enum());

                workflow_stage_formview::create($form_stage, 'kia', true, false, 'KIA');
                workflow_stage_formview::create($form_stage, 'ora', false, false, 'ORA');

                $approval_stage = workflow_stage::create($workflow_version, 'stage 2', approvals::get_enum());

                workflow_stage_formview::create($approval_stage, 'kia', true, false, 'KIA');
                workflow_stage_formview::create($approval_stage, 'ora', false, false, 'ORA');

                $form_stage3 = workflow_stage::create($workflow_version, 'stage 3', waiting::get_enum());
                workflow_stage_formview::create($form_stage3, 'kia', true, false, 'KIA');
                workflow_stage_formview::create($form_stage3, 'ora', false, false, 'ORA');

                workflow_stage::create($workflow_version, 'stage 4', finished::get_enum());
            }
        );
        $this->form_stage_3 = $form_stage3;
        application_activity::repository()->where('application_id', $this->application->id)->delete();
    }

    protected function tearDown(): void {
        $this->application = null;
        $this->user = null;
        $this->form_stage_3 = null;
        parent::tearDown();
    }

    public function test_get_next_state() {
        // set to stage 3
        $stage_3_state = $this->form_stage_3->state_manager->get_initial_state();
        $state_manager = new waiting_state_manager($this->form_stage_3);
        $this->application->set_current_state($stage_3_state);
        $next_state = $state_manager->get_next_state($this->application->current_state);

        // Goes to next stage.
        $current_stage_id = $this->application->current_state->get_stage_id();
        $next_stage = $this->application->workflow_version->get_next_stage($current_stage_id);
        $this->assertEquals($next_stage->id, $next_state->get_stage_id());
    }

    public function test_get_previous_state() {
        // set to stage 3
        $stage_3_state = $this->form_stage_3->state_manager->get_initial_state();
        $state_manager = new waiting_state_manager($this->form_stage_3);
        $this->application->set_current_state($stage_3_state);
        $previous_state = $state_manager->get_previous_state($this->application->current_state);

        $stage_2 = $this->application->workflow_version->get_previous_stage($this->form_stage_3->id);
        $this->assertEquals($stage_2->id, $previous_state->get_stage_id());
    }

    public function test_get_initial_state() {
        // set to stage 3
        $stage_3_state = $this->form_stage_3->state_manager->get_initial_state();
        $state_manager = new waiting_state_manager($this->form_stage_3);
        $this->application->set_current_state($stage_3_state);
        $initial_state = $state_manager->get_initial_state();

        $this->assertNull($initial_state->get_approval_level_id());
        $this->assertTrue($initial_state->is_stage_type(waiting::get_code()));
        $this->assertEquals($this->form_stage_3->id, $initial_state->get_stage_id());
    }

    public function test_get_start_state() {
        $approvals_stage = $this->application->workflow_version->stages->find(function (workflow_stage $stage) {
            return $stage->type === waiting::class;
        });
        $state_manager = new waiting_state_manager($approvals_stage);

        $this->expectException(model_exception::class);
        $this->expectExceptionMessage('An application can not start in a waiting stage');
        $state_manager->get_creation_state();
    }

    public function test_on_application_start() {
        $state_manager = new waiting_state_manager($this->form_stage_3);

        $this->expectException(model_exception::class);
        $this->expectExceptionMessage('An application can not start in a waiting stage');
        $state_manager->on_application_start($this->createMock(application::class), $this->user->id);
    }

    public function test_on_state_entry() {
        // set to stage 3
        $stage_3_state = $this->form_stage_3->state_manager->get_initial_state();
        $state_manager = new waiting_state_manager($this->form_stage_3);
        $this->application->set_current_state($stage_3_state);
        $previous_state = $this->createMock(application_state::class);

        $state_manager->on_state_entry($this->application, $previous_state, $this->user->id);
        $this->application->refresh();

        $this->assertCount(1, $this->application->activities);

        /** @var application_activity_model $activity_created */
        $activity_created = $this->application->activities->first();
        $this->assertEquals(stage_started::get_type(), $activity_created->activity_type);
    }

    public function test_on_state_exit() {
        // set to stage 3
        $stage_3_state = $this->form_stage_3->state_manager->get_initial_state();
        $state_manager = new waiting_state_manager($this->form_stage_3);
        $this->application->set_current_state($stage_3_state);
        $previous_state = $this->createMock(application_state::class);

        $state_manager->on_state_exit($this->application, $previous_state, $this->user->id);
        $this->application->refresh();

        $this->assertCount(1, $this->application->activities);

        /** @var application_activity_model $activity_created */
        $activity_created = $this->application->activities->first();
        $this->assertEquals(stage_ended::get_type(), $activity_created->activity_type);
    }

    public function test_instantiating_state_manager() {
        // With valid stage_type.
        $waiting_stage = $this->application->workflow_version->stages->find('type', waiting::class);
        $state_manager = new waiting_state_manager($waiting_stage);
        $this->assertInstanceOf(waiting_state_manager::class, $state_manager);

        // With invalid stage_type.
        $non_waiting_stage = $this->application->workflow_version->stages->find(function (workflow_stage $stage) {
            return $stage->type !== waiting::class;
        });

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Application stage is not of type " . waiting::class);
        new waiting_state_manager($non_waiting_stage);
    }
}
