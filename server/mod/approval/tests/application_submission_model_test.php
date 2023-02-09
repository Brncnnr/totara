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

use core\entity\user;
use core\orm\entity\repository;
use core\orm\query\builder;
use mod_approval\entity\application\application_activity;
use mod_approval\entity\application\application_submission as application_submission_entity;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\activity\creation as creation_activity;
use mod_approval\model\application\activity\stage_started as stage_started_activity;
use mod_approval\model\application\activity\stage_submitted as stage_submitted_activity;
use mod_approval\model\application\application;
use mod_approval\model\application\application_action;
use mod_approval\model\application\application_submission;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form_data;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\testing\application_generator_object;
use mod_approval\testing\assignment_generator_object;
use mod_approval\testing\formview_generator_object;
use mod_approval\testing\workflow_generator_object;
use totara_hierarchy\testing\generator as hierarchy_generator;

require_once(__DIR__ . '/testcase.php');

/**
 * @coversDefaultClass \mod_approval\model\application\application_submission
 *
 * @group approval_workflow
 */
class mod_approval_application_submission_model_testcase extends mod_approval_testcase {

    /**
     * Gets the generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * @covers ::create_or_update
     * @return application_submission $application_submission model
     * @throws coding_exception
     */
    public function test_create_or_update(): application_submission {

        $this->setAdminUser();
        $generator = $this->generator();
        $application_repository = application_submission_entity::repository();

        $workflow_type = $generator->create_workflow_type('test');

        // Create a form and version
        $form_version = $generator->create_form_and_version();
        $form = $form_version->form;

        // Create a workflow and version
        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id, status::DRAFT);
        $workflow_version = $generator->create_workflow_and_version($workflow_go);
        $workflow = $workflow_version->workflow;

        $workflow_stage = $generator->create_workflow_stage($workflow_version->id, 'Test Stage', form_submission::get_enum());

        $formview_go = new formview_generator_object('request', $workflow_stage->id);
        $generator->create_formview($formview_go);

        $generator->create_approval_level($workflow_stage->id, 'Level 1', 1);

        // Generate a simple organisation hierarchy
        $hierarchy_generator = hierarchy_generator::instance();
        $framework = $hierarchy_generator->create_framework('organisation');
        $organisation = $hierarchy_generator->create_org(['frameworkid' => $framework->id]);

        // Create an assignment
        $assignment_go = new assignment_generator_object(
            $workflow->course_id,
            assignment_type\organisation::get_code(),
            $organisation->id
        );
        $assignment_go->is_default = true;
        $assignment = $generator->create_assignment($assignment_go);
        $workflow_version->status = status::DRAFT;
        $workflow_version->save();

        // Nothing yet.
        $this->assertEquals(0, $application_repository->count());

        // Create a user
        $user = $this->create_user();
        $this->setUser($user);

        // Create an application generator object
        $application_go = new application_generator_object($workflow_version->id, $form_version->id, $assignment->id);
        $application = $generator->create_application($application_go);

        $workflow_stage = workflow_stage::load_by_entity($workflow_stage);
        $application = application::load_by_entity($application);

        $form_data = form_data::from_json('{"request":"hurray!"}');

        $time = time();
        $application_submission = application_submission::create_or_update($application, $user->id, $form_data);
        // We have a repository
        $this->assertEquals(1, $application_repository->count());
        $this->assertInstanceOf(get_class($application_submission), $application_submission);
        $this->assertEquals($user->id, $application_submission->user_id);
        $this->assertEquals($application->id, $application_submission->application_id);
        $this->assertEquals($workflow_stage->id, $application_submission->workflow_stage_id);
        $this->assertEquals($form_data->to_json(), $application_submission->form_data);
        $this->assertGreaterThanOrEqual($time, $application_submission->created);
        $this->assertLessThanOrEqual($application_submission->updated, $application_submission->created);
        return $application_submission;
    }

    /**
     * @covers ::refresh
     * @throws coding_exception
     */
    public function test_refresh(): void {
        $application_submission = $this->test_create_or_update();
        $this->assertFalse($application_submission->superseded);
        builder::table(application_submission_entity::TABLE)->update(['superseded' => '1']);
        $application_submission->refresh();
        $this->assertTrue($application_submission->superseded);
    }

    /**
     * @covers ::delete
     * @throws coding_exception
     */
    public function test_delete(): void {
        $application_submission = $this->test_create_or_update();
        $this->assertNotEmpty($application_submission->id);
        $application_submission->delete();
        $this->assertEmpty($application_submission->id);
    }

    public function data_clone(): array {
        return [[false], [true]];
    }

    /**
     * @param bool $superseded
     * @dataProvider data_clone
     * @covers ::clone
     */
    public function test_clone(bool $superseded): void {
        $source = $this->test_create_or_update();
        builder::table(application_submission_entity::TABLE)->update(['superseded' => $superseded, 'submitted' => 1234]);
        $application = application::load_by_entity($this->generator()->create_application(new application_generator_object(
            $source->application->workflow_version->id,
            $source->application->form_version_id,
            $source->application->approval_id
        )));
        $this->waitForSecond();
        $destination = $source->clone($application);
        $this->assertEquals($source->user_id, $destination->user_id);
        $this->assertEquals($source->workflow_stage_id, $destination->workflow_stage_id);
        $this->assertNull($destination->submitted);
        $this->assertFalse($destination->superseded);
        $this->assertEquals($source->form_data, $destination->form_data);
        $this->assertGreaterThan($source->created, $destination->created);
        $this->assertGreaterThan($source->updated, $destination->updated);
    }

    public function test_submit_supersedes_reject_actions(): void {
        $submitter_user = new user($this->getDataGenerator()->create_user());

        // Create application
        $this->setAdminUser();
        $application = $this->create_application_for_user();

        // Submit the application.
        $submission = application_submission::create_or_update(
            $application,
            $submitter_user->id,
            form_data::from_json('{"kia":"kaha"}')
        );
        $submission->publish($submitter_user->id);
        submit::execute($application, $submitter_user->id);

        // Reject application.
        reject::execute($application, user::logged_in()->id);
        $application->refresh(true);

        // Submit again.
        $submission = application_submission::create_or_update(
            $application,
            $submitter_user->id,
            form_data::from_json('{"kia":"kaha"}')
        );
        $submission->publish($submitter_user->id);
        submit::execute($application, $submitter_user->id);
        $application->refresh();

        // Check that there is a superseded reject action.
        $this->assertCount(1, $application->actions);
        /** @var application_action $action*/
        $action = $application->actions->first();
        $this->assertEquals(reject::get_code(), $action->code);
        $this->assertTrue($action->superseded);
    }

    /**
     * @covers ::publish
     */
    public function test_publish(): void {
        $this->setAdminUser();
        $application_submission = $this->create_submission_for_user_input();

        // Submission and application are not submitted.
        $this->assertFalse($application_submission->is_published());
        $this->assertFalse($application_submission->application->current_state->is_stage_type(approvals::get_code()));

        $activity_repository = function () use ($application_submission): repository {
            return application_activity::repository()->where('application_id', $application_submission->application_id);
        };

        // Submit submission without transition (no stage_submitted activity).
        $this->assertEquals(2, $activity_repository()->count());
        $this->assertEquals(1, $activity_repository()->where('activity_type', creation_activity::get_type())->count());
        $this->assertEquals(1, $activity_repository()->where('activity_type', stage_started_activity::get_type())->count());
        $this->assertEquals(0, $activity_repository()->where('activity_type', stage_submitted_activity::get_type())->count());
        $application_submission->publish(user::logged_in()->id);
        $this->assertEquals(2, $activity_repository()->count());
        $this->assertEquals(1, $activity_repository()->where('activity_type', creation_activity::get_type())->count());
        $this->assertEquals(1, $activity_repository()->where('activity_type', stage_started_activity::get_type())->count());
        $this->assertEquals(0, $activity_repository()->where('activity_type', stage_submitted_activity::get_type())->count());

        // Submission is submitted.
        $this->assertTrue($application_submission->is_published());

        // Application is submitted.
        $this->assertFalse($application_submission->application->current_state->is_stage_type(approvals::get_code()));
    }

    /**
     * @return application_submission
     */
    private function create_submission_for_user_input(): application_submission {
        $application = $this->create_application_for_user();
        return application_submission::create_or_update(
            $application,
            user::logged_in()->id,
            form_data::from_json('{"kia":"ora"}')
        );
    }

    public function test_supersede_submissions_for_stage(): void {
        $mod_approval_generator = $this->generator();
        $submitter_user = self::getDataGenerator()->create_user();
        $approver_user = self::getDataGenerator()->create_user();
        $resetter_user = self::getDataGenerator()->create_user();

        // Create a workflow with two stages containing one approval level each.
        $workflow_type = $mod_approval_generator->create_workflow_type('test workflow type');

        $form_version = $mod_approval_generator->create_form_and_version();
        $form = $form_version->form;

        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id, status::DRAFT);
        $workflow_version = $mod_approval_generator->create_workflow_and_version($workflow_go);

        // Stage 1
        $workflow_stage1 = $mod_approval_generator->create_workflow_stage(
            $workflow_version->id,
            'Stage 1',
            form_submission::get_enum()
        );
        $formview_go = new formview_generator_object('agency_code', $workflow_stage1->id);
        $mod_approval_generator->create_formview($formview_go);

        // Stage 2
        $workflow_stage2 = $mod_approval_generator->create_workflow_stage(
            $workflow_version->id,
            'Stage 2',
            approvals::get_enum()
        );

        // Stage 3
        $workflow_stage3 = $mod_approval_generator->create_workflow_stage(
            $workflow_version->id,
            'Stage 3',
            form_submission::get_enum()
        );
        $formview_go = new formview_generator_object('request_status', $workflow_stage3->id);
        $mod_approval_generator->create_formview($formview_go);

        // Stage 4
        $workflow_stage4 = $mod_approval_generator->create_workflow_stage(
            $workflow_version->id,
            'Stage 4',
            approvals::get_enum()
        );

        $workflow_stage5 = $mod_approval_generator->create_workflow_stage(
            $workflow_version->id,
            'Stage 5',
            form_submission::get_enum()
        );
        $formview_go = new formview_generator_object('request_status', $workflow_stage5->id);
        $mod_approval_generator->create_formview($formview_go);

        // End stage.
        $mod_approval_generator->create_workflow_stage(
            $workflow_version->id,
            'End',
            finished::get_enum()
        );
        $workflow = workflow::load_by_entity($workflow_version->workflow);
        $workflow->publish($workflow->get_latest_version());

        // Create applications.
        $this->setAdminUser();
        $application = $this->create_application_for_user_on($workflow);
        $control_application = $this->create_application_for_user_on($workflow);

        // Submit stage 1 - creates submissions.
        $form_data = form_data::from_json('{"agency_code":"hurray!"}');
        $submission = application_submission::create_or_update(
            $application,
            $submitter_user->id,
            $form_data
        );
        $submission->publish($submitter_user->id);
        submit::execute($application, $submitter_user->id);

        $control_form_data = form_data::from_json('{"agency_code":"oh no!"}');
        $control_submission = application_submission::create_or_update(
            $control_application,
            $submitter_user->id,
            $control_form_data
        );
        $control_submission->publish($submitter_user->id);
        submit::execute($control_application, $submitter_user->id);

        // Approve the first level of stage 2 - creates an action.
        approve::execute($application, $approver_user->id);
        approve::execute($control_application, $approver_user->id);

        // Submit stage 3 - creates submissions.
        $form_data = form_data::from_json('{"request_status":"yeah okay!"}');
        $submission = application_submission::create_or_update(
            $application,
            $submitter_user->id,
            $form_data
        );
        $submission->publish($submitter_user->id);
        submit::execute($application, $submitter_user->id);

        $control_form_data = form_data::from_json('{"request_status":"woops!"}');
        $control_submission = application_submission::create_or_update(
            $control_application,
            $submitter_user->id,
            $control_form_data
        );
        $control_submission->publish($submitter_user->id);
        submit::execute($control_application, $submitter_user->id);

        // Approve the first level of stage 4 - creates an action.
        approve::execute($application, $approver_user->id);
        approve::execute($control_application, $approver_user->id);

        // Verify that we have non-superseded submissions.
        $submissions_stage1 = application_submission_entity::repository()
            ->where('superseded', '=', false)
            ->where('application_id', '=', $application->id)
            ->where('workflow_stage_id', '=', $workflow_stage1->id)
            ->get();
        self::assertCount(1, $submissions_stage1);
        $submissions_stage3 = application_submission_entity::repository()
            ->where('superseded', '=', false)
            ->where('application_id', '=', $application->id)
            ->where('workflow_stage_id', '=', $workflow_stage3->id)
            ->get();
        self::assertCount(1, $submissions_stage3);

        $control_submissions_stage1 = application_submission_entity::repository()
            ->where('superseded', '=', false)
            ->where('application_id', '=', $control_application->id)
            ->where('workflow_stage_id', '=', $workflow_stage1->id)
            ->get();
        self::assertCount(1, $control_submissions_stage1);
        $control_submissions_stage3 = application_submission_entity::repository()
            ->where('superseded', '=', false)
            ->where('application_id', '=', $control_application->id)
            ->where('workflow_stage_id', '=', $workflow_stage3->id)
            ->get();
        self::assertCount(1, $control_submissions_stage3);

        self::assertEquals(4, application_submission_entity::repository()->count());

        // Supersede submissions for test application on third stage.
        application_submission::supersede_submissions_for_stage(
            $application,
            workflow_stage::load_by_entity($workflow_stage3),
            $resetter_user->id
        );

        // See that the stage 2 submission has been superseded, and the control is untouched.
        self::assertEquals(1, application_submission_entity::repository()
            ->where('superseded', '=', false)
            ->where('id', '=', $submissions_stage1->first()->id)
            ->count()
        );
        self::assertEquals(1, application_submission_entity::repository()
            ->where('superseded', '=', true) // Only submission superseded.
            ->where('id', '=', $submissions_stage3->first()->id)
            ->count()
        );
        self::assertEquals(1, application_submission_entity::repository()
            ->where('superseded', '=', false)
            ->where('id', '=', $control_submissions_stage1->first()->id)
            ->count()
        );
        self::assertEquals(1, application_submission_entity::repository()
            ->where('superseded', '=', false)
            ->where('id', '=', $control_submissions_stage3->first()->id)
            ->count()
        );

        // See that there is a new submission containing the previous submission form data.
        self::assertEquals(5, application_submission_entity::repository()->count());

        /** @var application_submission_entity $new_submission */
        $new_submission = application_submission_entity::repository()
            ->order_by('id', 'DESC')
            ->first();
        self::assertFalse($new_submission->superseded);
        self::assertEquals($resetter_user->id, $new_submission->user_id);
        self::assertEquals($form_data->to_json(), $new_submission->form_data);
    }
}
