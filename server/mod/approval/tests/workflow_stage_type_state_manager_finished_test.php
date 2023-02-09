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

use mod_approval\entity\application\application_activity;
use mod_approval\exception\model_exception;
use mod_approval\model\application\activity\finished as finished_activity;
use mod_approval\model\application\application;
use mod_approval\model\application\application_state;
use mod_approval\model\workflow\stage_type\finished as finished_stage_type;
use mod_approval\model\workflow\stage_type\state_manager\finished;
use mod_approval\model\workflow\workflow_stage;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass \mod_approval\model\workflow\stage_type\state_manager\finished
 */
class mod_approval_workflow_stage_type_state_manager_finished_testcase extends mod_approval_testcase {

    private $application;

    private $user;

    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->user = $this->create_user();
        $this->setUser($this->user);
        $this->application = $this->create_application_for_user();
        application_activity::repository()->where('application_id', $this->application->id)->delete();
    }

    protected function tearDown(): void {
        $this->application = null;
        $this->user = null;
        parent::tearDown();
    }

    public function test_get_next_state() {
        /** @var workflow_stage $finished_stage*/
        $finished_stage = $this->application->workflow_version->stages->find('type', finished_stage_type::class);
        $state_manager = new finished($finished_stage);
        $state = $this->createMock(application_state::class);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Finished stages do not have next states');
        $state_manager->get_next_state($state);
    }

    public function test_get_previous_state() {
        /** @var workflow_stage $finished_stage*/
        $finished_stage = $this->application->workflow_version->stages->find('type', finished_stage_type::class);
        $state_manager = new finished($finished_stage);
        $state = $this->createMock(application_state::class);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Finished stages do not have previous stages');
        $state_manager->get_previous_state($state);
    }

    public function test_get_initial_state() {
        $finished_stage = $this->application->workflow_version->stages->find('type', finished_stage_type::class);
        $state_manager = new finished($finished_stage);

        $initial_state = $state_manager->get_initial_state();
        $this->assertEquals($finished_stage->id, $initial_state->get_stage_id());
        $this->assertTrue($initial_state->is_stage_type(finished_stage_type::get_code()));
    }

    public function test_get_start_state() {
        /** @var workflow_stage $finished_stage*/
        $finished_stage = $this->application->workflow_version->stages->find('type', finished_stage_type::class);
        $state_manager = new finished($finished_stage);

        $this->expectException(model_exception::class);
        $this->expectExceptionMessage('An application can not start in a finished stage');
        $state_manager->get_creation_state();
    }

    public function test_on_application_start() {
        /** @var workflow_stage $finished_stage*/
        $finished_stage = $this->application->workflow_version->stages->find('type', finished_stage_type::class);
        $state_manager = new finished($finished_stage);

        $this->expectException(model_exception::class);
        $this->expectExceptionMessage('An application can not start in a finished stage');
        $state_manager->get_creation_state();
    }

    public function test_on_state_entry() {
        /** @var workflow_stage $finished_stage*/
        $finished_stage = $this->application->workflow_version->stages->find('type', finished_stage_type::class);
        $state_manager = new finished($finished_stage);
        $previous_state = $this->createMock(application_state::class);

        $this->assertNull($this->application->completed);
        $state_manager->on_state_entry($this->application, $previous_state, $this->user->id);

        // Check the application is marked as completed.
        $this->assertNotNull($this->application->completed);

        $activities = $this->application->activities->all();
        $this->assertCount(1, $activities);
        $this->assertEquals(finished_activity::get_type(), $activities[0]->activity_type);
    }

    public function test_on_state_exit() {
        /** @var workflow_stage $finished_stage*/
        $finished_stage = $this->application->workflow_version->stages->find('type', finished_stage_type::class);
        $state_manager = new finished($finished_stage);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Exiting a finished state not implemented');
        $state_manager->on_state_exit(
            $this->createMock(application::class),
            $this->createMock(application_state::class),
            null
        );
    }

    public function test_instantiating_state_manager() {
        // With valid stage_type.
        $finished_stage = $this->application->workflow_version->stages->find(function (workflow_stage $stage) {
            return $stage->type === finished_stage_type::class;
        });
        $state_manager = new finished($finished_stage);
        $this->assertInstanceOf(finished::class, $state_manager);

        // With invalid stage_type.
        $non_finished_stage = $this->application->workflow_version->stages->find(function (workflow_stage $stage) {
            return $stage->type !== finished_stage_type::class;
        });

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Application stage is not of type " . finished_stage_type::class);
        new finished($non_finished_stage);
    }
}
