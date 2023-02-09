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
use mod_approval\entity\application\application_activity as application_activity_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\application\activity\creation;
use mod_approval\model\application\activity\stage_submitted;
use mod_approval\model\application\application_activity;
use mod_approval\model\application\application_state;
use mod_approval\testing\approval_workflow_test_setup;

require_once(__DIR__ . '/testcase.php');

/**
 * @coversDefaultClass \mod_approval\model\application\application_activity
 *
 * @group approval_workflow
 */
class mod_approval_application_activity_model_testcase extends mod_approval_testcase {

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
     * @return application_activity $application
     * @throws coding_exception
     */
    public function test_create(): application_activity {
        // WARNING: Do not use application_model::create() for testing this!!
        $application_activity_repository = application_activity_entity::repository();

        // Create a simple workflow and an application for a user
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user = new user_entity($this->getDataGenerator()->create_user()->id);
        $this->setUser($user);
        $application = $this->create_submitted_application($workflow, $assignment, $user);

        // Nothing yet.
        $this->assertEquals(0, $application_activity_repository->count());

        $activity_info = [];
        $time = time();
        $application_activity = application_activity::create(
            $application,
            $user->id,
            stage_submitted::class,
            $activity_info
        );

        // We have a repository
        $this->assertEquals(1, $application_activity_repository->count());
        $this->assertInstanceOf(application_activity::class, $application_activity);
        $this->assertEquals($user->id, $application_activity->user_id);
        $this->assertEquals($application->id, $application_activity->application_id);
        $this->assertEquals($application->current_stage->id, $application_activity->workflow_stage_id);
        $this->assertEquals($application->current_approval_level->id, $application_activity->workflow_stage_approval_level_id);
        $this->assertEquals(stage_submitted::get_type(), $application_activity->activity_type);
        $this->assertEquals('[]', $application_activity->activity_info);
        $this->assertGreaterThanOrEqual($time, $application_activity->timestamp);
        return $application_activity;
    }

    /**
     * @covers ::create
     */
    public function test_create_with_completed_application() {
        // Create a simple workflow and an application for a user
        $user = new user_entity($this->getDataGenerator()->create_user());
        $this->setUser($user);
        $application = $this->create_application_for_user();
        $this->fake_state_application($application, "FINISHED");

        $activity_info = [];
        $activity = application_activity::create(
            $application,
            $user->id,
            stage_submitted::class
        );

        $this->assertEquals($application->current_state->get_stage_id(), $activity->stage->id);
        $this->assertNull($activity->approval_level);
    }

    /**
     * @covers ::create
     */
    public function test_create_with_draft_application() {
        // Create a simple workflow and an application for a user
        $user = new user_entity($this->getDataGenerator()->create_user());
        $this->setUser($user);
        $application = $this->create_application_for_user();

        $activity = application_activity::create(
            $application,
            $user->id,
            stage_submitted::class
        );

        $this->assertEquals($application->current_state->get_stage_id(), $activity->stage->id);
        $this->assertNull($activity->approval_level);
    }

    /**
     * @covers ::create
     */
    public function test_create_with_invalid_activity_type() {
        // Create a simple workflow and an application for a user
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user = new user_entity($this->getDataGenerator()->create_user()->id);
        $this->setUser($user);
        $application = $this->create_submitted_application($workflow, $assignment, $user);

        self::expectException(model_exception::class);
        self::expectExceptionMessage('Invalid activity type');

        application_activity::create(
            $application,
            $user->id,
            user_entity::class
        );
    }

    /**
     * @covers ::create
     */
    public function test_create_with_invalid_activity_info() {
        // Create a simple workflow and an application for a user
        list($workflow, $framework, $assignment) = $this->create_workflow_and_assignment();
        $user = new user_entity($this->getDataGenerator()->create_user()->id);
        $this->setUser($user);
        $application = $this->create_submitted_application($workflow, $assignment, $user);

        self::expectException(model_exception::class);
        self::expectExceptionMessage('Invalid activity info');

        application_activity::create(
            $application,
            $user->id,
            stage_submitted::class,
            ['I am invalid']
        );
    }

    /**
     * @covers ::refresh
     * @throws coding_exception
     */
    public function test_refresh(): void {
        $application_activity = $this->test_create();
        $this->assertEquals(stage_submitted::get_type(), $application_activity->activity_type);
        builder::table(application_activity_entity::TABLE)->update(['activity_type' => creation::get_type()]);
        $application_activity->refresh();
        $this->assertEquals(creation::get_type(), $application_activity->activity_type);
    }

    /**
     * @covers ::delete
     * @throws coding_exception
     */
    public function test_delete(): void {
        $application_activity = $this->test_create();
        $this->assertNotEmpty($application_activity->id);
        $application_activity->delete();
        $this->assertEmpty($application_activity->id);
    }
}
