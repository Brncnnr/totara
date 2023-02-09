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

use core_container\container as core_container;
use container_approval\approval as approval_container;
use core\orm\query\builder;
use core\entity\user;
use mod_approval\entity\application\application as application_entity;
use mod_approval\entity\application\application_submission as application_submission_entity;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\application\action\action;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\submit;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\action\withdraw_in_approvals;
use mod_approval\model\application\activity\creation as creation_activity;
use mod_approval\model\application\activity\stage_started as stage_started_activity;
use mod_approval\model\application\application;
use mod_approval\model\application\application_state;
use mod_approval\model\application\application_submission;
use mod_approval\model\assignment\approver_type\relationship as relationship_approver_type;
use mod_approval\model\assignment\approver_type\user as user_approver_type;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\form\form_data;
use mod_approval\model\form\form_version;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\finished;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_formview;
use mod_approval\model\workflow\workflow_type;
use mod_approval\model\workflow\workflow_version;
use mod_approval\testing\assignment_approver_generator_object;
use mod_approval\testing\assignment_generator_object;
use mod_approval\testing\generator as mod_approval_generator;
use mod_approval\testing\workflow_generator_object;
use totara_core\relationship\relationship;
use totara_hierarchy\testing\generator as totara_hierarchy_generator;
use totara_job\job_assignment;

require_once(__DIR__ . '/testcase.php');

/**
 * @coversDefaultClass \mod_approval\model\application\application
 *
 * @group approval_workflow
 */
class mod_approval_application_model_testcase extends mod_approval_testcase {

    /**
     * Gets the generator instance
     *
     * @return mod_approval_generator
     */
    protected function generator(): mod_approval_generator {
        return mod_approval_generator::instance();
    }

    /**
     * @covers ::create
     * @return application $application model
     */
    public function test_create(): application {

        $this->setAdminUser();
        $generator = $this->generator();
        $application_repository = application_entity::repository();

        $workflow_type = $generator->create_workflow_type('test workflow type');

        // Create a form and version
        $form_version = $generator->create_form_and_version();
        $form = $form_version->form;

        // Create a workflow and version
        $workflow_go = new workflow_generator_object($workflow_type->id, $form->id, $form_version->id, status::DRAFT);
        $workflow_version = $generator->create_workflow_and_version($workflow_go);
        $workflow = $workflow_version->workflow;

        // Create two workflow stages
        $workflow_stage1 = $generator->create_workflow_stage($workflow_version->id, 'Stage 1', form_submission::get_enum());
        $workflow_stage2 = $generator->create_workflow_stage($workflow_version->id, 'Stage 2', approvals::get_enum());
        $generator->create_workflow_stage($workflow_version->id, 'Stage 3', finished::get_enum());

        // Create two approval levels for Stage 1
        $generator->create_approval_level($workflow_stage2->id, 'Level 1', 1);
        $generator->create_approval_level($workflow_stage2->id, 'Level 2', 2);

        // Generate a simple organisation hierarchy
        /** @var totara_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $framework = $hierarchy_generator->create_framework('organisation');
        $agency = $hierarchy_generator->create_org(
            [
                'frameworkid' => $framework->id,
                'fullname' => 'Agency',
                'idnumber' => '001',
                'shortname' => 'org'
            ]
        );

        // Create an assignment
        $assignment_go = new assignment_generator_object(
            $workflow->course_id,
            assignment_type\organisation::get_code(),
            $agency->id
        );
        $assignment_go->is_default = true;
        $assignment = $generator->create_assignment($assignment_go);

        // Nothing yet.
        $this->assertEquals(0, $application_repository->count());
        $workflow_version->status = status::ACTIVE;
        $workflow_version->save();

        $workflow_version = workflow_version::load_by_entity($workflow_version);
        $form_version = form_version::load_by_entity($form_version);
        $assignment = assignment::load_by_entity($assignment);

        // Create a user
        $user = new user($this->getDataGenerator()->create_user()->id);
        $this->setUser($user);

        // Assign user to agency
        job_assignment::create([
            'userid' => $user->id,
            'idnumber' => '001',
            'organisationid' => $agency->id,
            'fullname' => 'Test Job Assignment'
        ]);

        $time = time();
        $application = application::create($workflow_version, $assignment, $user->id);

        // We have a repository
        $this->assertEquals(1, $application_repository->count());
        $this->assertInstanceOf(get_class($application), $application);
        $this->assertEquals('test workflow type', $application->title);
        $this->assertEquals($user->id, $application->user_id);
        $this->assertEquals(null, $application->job_assignment_id);
        $this->assertEquals($workflow_version->id, $application->workflow_version_id);
        $this->assertEquals($form_version->id, $application->form_version_id);
        $this->assertEquals($assignment->id, $application->approval_id);
        $this->assertEquals($user->id, $application->creator_id);
        $this->assertEquals($user->id, $application->owner_id);
        $this->assertEquals($workflow_stage1->id, $application->current_state->get_stage_id());
        self::assertTrue($application->current_state->is_stage_type(form_submission::get_code()));
        self::assertTrue($application->current_state->is_draft());
        $this->assertNull($application->current_state->get_approval_level_id());
        $this->assertGreaterThanOrEqual($time, $application->created);
        $this->assertLessThanOrEqual($application->updated, $application->created);
        $this->assertNull($application->submitted);
        $this->assertNull($application->completed);
        $this->assertCount(2, $application->activities);
        foreach ($application->activities as $activity) {
            switch ($activity->activity_type) {
                case creation_activity::get_type():
                    $this->assertArrayNotHasKey('source', $activity->activity_info_parsed);
                    break;
                case stage_started_activity::get_type():
                    break;
                default:
                    self::fail('Unexpected activity type detected');
            }
        }
        return $application;
    }

    /**
     * @covers ::update_id_number
     */
    public function test_update_id_number(): void {
        $method = new ReflectionMethod(application::class, 'update_id_number');
        $method->setAccessible(true);
        $entity = new application_entity(['id' => 216051, 'title' => str_repeat('?', 255)]);
        $method->invoke(null, $entity, str_repeat('!', 255), 981144306);
        $expected = str_repeat('!', 237) . '20010203040506RSVP';
        $this->assertEquals($expected, $entity->id_number);
    }

    /**
     * @covers ::refresh
     * @throws coding_exception
     */
    public function test_refresh(): void {
        $application = $this->test_create();
        $this->assertEquals(null, $application->completed);
        builder::table(application_entity::TABLE)->update(['completed' => '1616393343']);
        $application->refresh();
        $this->assertEquals(1616393343, $application->completed);
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        $application = $this->test_create();
        self::assertNotEmpty($application->id);
        self::assertTrue($application->current_state->is_draft());
        $application->delete();
        self::assertEmpty($application->id);
    }

    /**
     * @covers ::delete
     */
    public function test_delete_with_foreign_keys(): void {
        $application = $this->test_create();
        $application_submission = application_submission::create_or_update(
            $application,
            $application->user_id,
            form_data::from_json('{}')
        );
        $this->assertNotEmpty($application_submission->id);
        $this->assertGreaterThan(0, $application->activities->count());
        $application->delete();
        $this->assertEmpty($application->id);
    }

    /**
     * @covers ::get_last_submission
     */
    public function test_get_last_submission(): void {
        $data = form_data::from_json('{"request":"comprehensivity"}');
        $application = $this->test_create();
        $sub1 = $this->generator()->create_application_submission(
            $application->id,
            $application->user_id,
            $application->current_state->get_stage_id(),
            $data
        );

        $last_submission = $application->get_last_submission();
        $this->assertEquals($sub1->id, $last_submission->id);
        $this->assertInstanceOf(application_submission::class, $last_submission);

        $approver = $this->getDataGenerator()->create_user();
        $sub2 = $this->generator()->create_application_submission(
            $application->id,
            $approver->id,
            $application->current_state->get_stage_id(),
            $data
        );

        $last_approver_submission = $application->get_last_submission();
        $this->assertEquals($sub2->id, $last_approver_submission->id);
    }

    /**
     * @covers ::get_last_submission
     * @covers ::get_last_submission_for
     */
    public function test_get_last_stage_submission(): void {
        $this->setAdminUser();

        // New application.
        $application = $this->create_application_for_user(null, [$this, 'setup_stages_for_last_stage_submission']);
        $stages = $application->workflow_version->stages->all();
        $stage1 = array_shift($stages);
        $stage2 = array_shift($stages);
        $stage3 = array_shift($stages);

        // Submit Stage 1.
        $submission1 = application_submission::create_or_update(
            $application,
            user::logged_in()->id,
            form_data::from_json('{"kia":"kaha"}')
        );
        $submission1->publish(user::logged_in()->id);
        submit::execute($application, user::logged_in()->id);

        // Check that last submission is Stage 1.
        $this->assertEquals($submission1->id, $application->get_last_submission()->id);
        $this->assertEquals($submission1->id, $application->get_last_submission_for($stage1->id)->id);
        $this->assertNull($application->get_last_submission_for($stage2->id));

        // Approve Stage 2.
        approve::execute($application, user::logged_in()->id);
        $this->assertEquals($stage3->id, $application->current_state->get_stage_id());

        // Check that last submission is still Stage 1.
        $this->assertEquals($submission1->id, $application->get_last_submission()->id);
        $this->assertEquals($submission1->id, $application->get_last_submission_for($stage1->id)->id);
        $this->assertNull($application->get_last_submission_for($stage3->id));

        // Submit Stage 3
        $submission2 = application_submission::create_or_update(
            $application,
            user::logged_in()->id,
            form_data::from_json('{"kia": "kia tau", "ora":"noho ora mai"}')
        );
        $submission2->publish(user::logged_in()->id);
        submit::execute($application, user::logged_in()->id);

        // Check that last submission is still Stage 1.
        $this->assertEquals($submission2->id, $application->get_last_submission()->id);
        $this->assertEquals($submission1->id, $application->get_last_submission_for($stage1->id)->id);
        $this->assertEquals($submission2->id, $application->get_last_submission_for($stage3->id)->id);
    }

    public function setup_stages_for_last_stage_submission(workflow_version $workflow_version) {
        $stages = [];
        $stages[1] = workflow_stage::create($workflow_version, 'stage 1', form_submission::get_enum());
        workflow_stage_formview::create($stages[1], 'kia', true, false, 'KIA');
        workflow_stage_formview::create($stages[1], 'ora', false, false, 'ORA');

        $stages[2] = workflow_stage::create($workflow_version, 'stage 2', approvals::get_enum());
        workflow_stage_formview::create($stages[2], 'kia', true, false, 'KIA');
        workflow_stage_formview::create($stages[2], 'ora', false, false, 'ORA');

        $stages[3] = workflow_stage::create($workflow_version, 'stage 3', form_submission::get_enum());
        workflow_stage_formview::create($stages[3], 'kia', true, false, 'KIA');
        workflow_stage_formview::create($stages[3], 'ora', false, false, 'ORA');

        workflow_stage::create($workflow_version, 'stage 4', finished::get_enum());
    }

    /**
     * @covers ::get_last_action
     */
    public function test_get_last_action(): void {
        $this->setAdminUser();
        $admin = user::logged_in();

        // New application.
        $application = $this->create_application_for_user();
        $stage1 = $application->current_state->get_stage();
        $stage2 = $application->workflow_version->get_next_stage($stage1->id);
        $level1 = $stage2->approval_levels->first();
        assignment_approver::create($application->assignment, $level1, user_approver_type::TYPE_IDENTIFIER, $admin->id);
        $this->assertNull($application->get_last_action());

        // Save as draft.
        application_submission::create_or_update($application, $admin->id, form_data::from_json('{}'));
        $application->refresh(true);

        // Check that there is still no last action.
        $this->assertTrue($application->current_state->is_draft());
        $this->assertNull($application->get_last_action());

        // Submit.
        $submission = application_submission::create_or_update(
            $application,
            $admin->id,
            form_data::from_json('{"kia":"kaha"}')
        );
        $submission->publish($admin->id);
        submit::execute($application, $admin->id);

        // Check that there is still no last action.
        $this->assertTrue($application->current_state->is_stage_type(approvals::get_code()));
        $this->assertNull($application->get_last_action());

        // Approve the application level.
        approve::execute($application, $admin->id);
        $application->refresh(true);

        // Check that last action is now Approved.
        $last_action = $application->get_last_action();
        $this->assertNotNull($last_action);
        $this->assertEquals(approve::get_code(), $last_action->code);
        $this->assertEquals($admin->id, $last_action->user_id);
    }

    public function data_get_last_action_of_action(): array {
        return [
            'APPROVE' => [new approve()],
            'REJECT' => [new reject()],
            'WITHDRAW_IN_APPROVALS' => [new withdraw_in_approvals()],
        ];
    }

    /**
     * @covers ::get_last_action
     * @param action $action
     * @dataProvider data_get_last_action_of_action
     */
    public function test_get_last_action_of_action(action $action): void {
        $this->setAdminUser();
        $approver = new user($this->getDataGenerator()->create_user());

        // New application
        $application = $this->create_application_for_user();
        workflow_version_entity::repository()->where('id', $application->workflow_version_id)
            ->update([
                'status' => status::DRAFT
            ]);
        $application->workflow_version->refresh();
        $application->workflow_version->stages->first();
        $stage2 = workflow_stage::create($application->workflow_version, 'stage 2', approvals::get_enum());
        $stage2->add_approval_level('level 2');
        $application->workflow_version->activate();

        $application->refresh(true);

        // Submit the application, so it is ready for approval, rejection or withdrawl.
        $submission = application_submission::create_or_update(
            $application,
            user::logged_in()->id,
            form_data::from_json('{"kia":"kaha"}')
        );
        $submission->publish($approver->id);
        submit::execute($application, $approver->id);

        // Perform the action.
        $action->execute($application, $approver->id);

        // Check the last action.
        $last_action = $application->get_last_action();
        $this->assertNotNull($last_action);
        $this->assertEquals($action->get_code(), $last_action->code);
        $this->assertEquals($approver->id, $last_action->user_id);
    }

    /**
     * @return approval_container|core_container
     */
    protected function create_container_for_user(): approval_container {
        global $USER;
        if (empty($USER->id)) {
            throw new coding_exception('user not logged in');
        }
        $data = new stdClass();
        $data->category = approval_container::get_default_category_id();
        return approval_container::create($data);
    }

    /**
     * @covers ::get_approver_users
     */
    public function test_get_approver_users_for_relationships(): void {
        $user1 = $this->getDataGenerator()
            ->create_user(['username' => 'applicant1', 'firstname' => 'Applicant', 'lastname' => 'One', 'middlename' => '']);
        $user2 = $this->getDataGenerator()
            ->create_user(['username' => 'applicant2', 'firstname' => 'Applicant', 'lastname' => 'Two', 'middlename' => '']);
        $boss1 = $this->getDataGenerator()
            ->create_user(['username' => 'manager1', 'firstname' => 'Manager', 'lastname' => 'One', 'middlename' => '']);
        $boss1boss1 = $this->getDataGenerator()
            ->create_user(['username' => 'supervisor1', 'firstname' => 'Manager', 'lastname' => 'Two', 'middlename' => '']);
        $boss1boss2 = $this->getDataGenerator()
            ->create_user(['username' => 'supervisor2', 'firstname' => 'Manager', 'lastname' => 'Three', 'middlename' => '']);
        $appraiser = $this->getDataGenerator()
            ->create_user(['username' => 'appraiser', 'firstname' => 'Appraiser', 'lastname' => 'Appraiser', 'middlename' => '']);
        $approver = $this->getDataGenerator()
            ->create_user(['username' => 'approver', 'firstname' => 'Approver', 'lastname' => 'Approver', 'middlename' => '']);
        // add approver as user1's approver, boss1 as user1's manager, boss1boss1 as user1's manager's manager
        job_assignment::create_default(
            $user1->id,
            [
                'managerjaid' => job_assignment::create_default(
                    $boss1->id,
                    [
                        'managerjaid' => job_assignment::create_default($boss1boss1->id)->id
                    ]
                )->id
            ]
        );
        job_assignment::create_default($user1->id, ['appraiserid' => $appraiser->id]);
        // add boss1boss2 as boss1's manager (NOT user1's manager's manager)
        job_assignment::create_default($boss1->id, ['managerjaid' => job_assignment::create_default($boss1boss2->id)->id]);
        // add approver as user2's approver, manager, manager's manager
        job_assignment::create_default(
            $user2->id,
            [
                'appraiserid' => $approver->id,
                'managerjaid' => job_assignment::create_default(
                    $approver->id, // NOTE: impossible in reality because one cannot be their own manager
                    [
                        'managerjaid' => job_assignment::create_default($approver->id)->id
                    ]
                )->id
            ]
        );
        $this->setAdminUser();
        $form = form::create('simple', 'form');
        $workflow = workflow::create(
            workflow_type::create('type'),
            $form,
            'workflow',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            '1'
        );
        $stage = workflow_stage::create(
            workflow_version::create($workflow, form_version::create($form, '1', '{}')),
            'stage',
            approvals::get_enum()
        );
        $level = $stage->add_approval_level('level');
        $assignment = $workflow->get_default_assignment()->activate();
        assignment_approver::create(
            $assignment,
            $level,
            relationship_approver_type::TYPE_IDENTIFIER,
            relationship::load_by_idnumber('manager')->id
        );
        assignment_approver::create($assignment, $level, user_approver_type::TYPE_IDENTIFIER, $approver->id);
        $get_application_approvers_for = function ($user) use ($stage, $level, $assignment) {
            $this->setUser($user);
            $entity = new application_entity();
            $entity->user_id = $user->id;
            $entity->job_assignment_id = null;
            $entity->workflow_version_id = $stage->workflow_version_id;
            $entity->form_version_id = $stage->workflow_version->form_version_id;
            $entity->approval_id = $assignment->id;
            $entity->creator_id = $user->id;
            $entity->owner_id = $user->id;
            $entity->current_stage_id = $stage->id;
            $entity->is_draft = false;
            $entity->current_approval_level_id = $level->id;
            $entity->save();
            $application = application::load_by_entity($entity);
            $approvers = $application->get_approver_users($level);
            $current_approvers = $application->approver_users->keys();
            self::assertEqualsCanonicalizing($current_approvers, $approvers->keys());
            return $approvers
                ->map(function (user $user) {
                    return $user->username;
                })
                ->all(true);
        };
        // approver for user1
        $approvers1 = $get_application_approvers_for($user1);
        $expected = [$boss1->id => 'manager1', $approver->id => 'approver'];
        $this->assertEquals($expected, $approvers1);
        // approver for user2
        $approvers2 = $get_application_approvers_for($user2);
        $expected = [$approver->id => 'approver'];
        $this->assertEquals($expected, $approvers2);
        // no current stage level no approvers
        $app = application::load_by_id(builder::table(application_entity::TABLE)->insert([
            'user_id' => $user1->id,
            'job_assignment_id' => null,
            'workflow_version_id' => $stage->workflow_version_id,
            'form_version_id' => $stage->workflow_version->form_version_id,
            'approval_id' => $assignment->id,
            'creator_id' => $user1->id,
            'owner_id' => $user1->id,
            'current_stage_id' => $stage->id,
            'is_draft' => 0,
            'current_approval_level_id' => null,
            'created' => time(),
            'updated' => time(),
        ]));
        $this->assertEquals([], $app->approver_users->all(true));
    }

    /**
     * @covers ::get_approver_users
     */
    public function test_get_inherited_approver_users_for_relationships(): void {
        $user1 = $this->getDataGenerator()
            ->create_user(['username' => 'applicant1', 'firstname' => 'Applicant', 'lastname' => 'One', 'middlename' => '']);
        $boss1 = $this->getDataGenerator()
            ->create_user(['username' => 'manager1', 'firstname' => 'Manager', 'lastname' => 'One', 'middlename' => '']);
        $boss1boss1 = $this->getDataGenerator()
            ->create_user(['username' => 'supervisor1', 'firstname' => 'Manager', 'lastname' => 'Two', 'middlename' => '']);
        $boss1boss2 = $this->getDataGenerator()
            ->create_user(['username' => 'supervisor2', 'firstname' => 'Manager', 'lastname' => 'Three', 'middlename' => '']);
        $appraiser = $this->getDataGenerator()
            ->create_user(['username' => 'appraiser', 'firstname' => 'Appraiser', 'lastname' => 'Appraiser', 'middlename' => '']);
        $approver = $this->getDataGenerator()
            ->create_user(['username' => 'approver', 'firstname' => 'Approver', 'lastname' => 'Approver', 'middlename' => '']);
        // add approver as user1's approver, boss1 as user1's manager, boss1boss1 as user1's manager's manager
        job_assignment::create_default(
            $user1->id,
            [
                'managerjaid' => job_assignment::create_default(
                    $boss1->id,
                    [
                        'managerjaid' => job_assignment::create_default($boss1boss1->id)->id
                    ]
                )->id
            ]
        );
        job_assignment::create_default($user1->id, ['appraiserid' => $appraiser->id]);
        // add boss1boss2 as boss1's manager (NOT user1's manager's manager)
        job_assignment::create_default($boss1->id, ['managerjaid' => job_assignment::create_default($boss1boss2->id)->id]);

        $this->setAdminUser();
        $form = form::create('simple', 'form');
        $workflow = workflow::create(
            workflow_type::create('type'),
            $form,
            'workflow',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            '1'
        );
        $stage = workflow_stage::create(
            workflow_version::create($workflow, form_version::create($form, '1', '{}')),
            'stage',
            approvals::get_enum()
        );
        $level = $stage->add_approval_level('level');
        $default_assignment = $workflow->get_default_assignment()->activate();
        $override_assignment = assignment::create(
            $workflow->container,
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            false
        )->activate();
        assignment_approver::create(
            $default_assignment,
            $level,
            relationship_approver_type::TYPE_IDENTIFIER,
            relationship::load_by_idnumber('manager')->id
        );
        assignment_approver::create($default_assignment, $level, user_approver_type::TYPE_IDENTIFIER, $approver->id);
        $get_application_approvers_for = function ($user) use ($stage, $level, $override_assignment) {
            $this->setUser($user);
            $entity = new application_entity();
            $entity->user_id = $user->id;
            $entity->job_assignment_id = null;
            $entity->workflow_version_id = $stage->workflow_version_id;
            $entity->form_version_id = $stage->workflow_version->form_version_id;
            $entity->approval_id = $override_assignment->id;
            $entity->creator_id = $user->id;
            $entity->owner_id = $user->id;
            $entity->current_stage_id = $stage->id;
            $entity->is_draft = false;
            $entity->current_approval_level_id = $level->id;
            $entity->save();
            $application = application::load_by_entity($entity);
            $approvers = $application->get_approver_users($level);
            $current_approvers = $application->approver_users->keys();
            self::assertEqualsCanonicalizing($current_approvers, $approvers->keys());
            return $approvers
                ->map(function (user $user) {
                    return $user->username;
                })
                ->all(true);
        };
        // approver for user1
        $approvers1 = $get_application_approvers_for($user1);
        $expected = [$boss1->id => 'manager1', $approver->id => 'approver'];
        $this->assertEquals($expected, $approvers1);
        // no current stage level no approvers
        $app = application::load_by_id(builder::table(application_entity::TABLE)->insert([
            'user_id' => $user1->id,
            'job_assignment_id' => null,
            'workflow_version_id' => $stage->workflow_version_id,
            'form_version_id' => $stage->workflow_version->form_version_id,
            'approval_id' => $override_assignment->id,
            'creator_id' => $user1->id,
            'owner_id' => $user1->id,
            'current_stage_id' => $stage->id,
            'is_draft' => 0,
            'current_approval_level_id' => null,
            'created' => time(),
            'updated' => time(),
        ]));
        $this->assertEquals([], $app->approver_users->all(true));
    }

    /**
     * @covers ::clone
     */
    public function test_clone_without_submissions(): void {
        $user = $this->create_user();
        $source = $this->test_create();
        $source->mark_submitted($user->id);
        $source->refresh();
        $this->waitForSecond();
        $cloner = $this->getDataGenerator()->create_user();
        $this->setUser($cloner);
        $destination = $source->clone($cloner->id);
        $this->assertEquals($source->user_id, $destination->user_id);
        $this->assertSame($source->job_assignment_id, $destination->job_assignment_id);
        $this->assertEquals($source->workflow_version_id, $destination->workflow_version_id);
        $this->assertEquals($source->form_version_id, $destination->form_version_id);
        $this->assertEquals($source->approval_id, $destination->approval_id);
        $this->assertEquals($cloner->id, $destination->creator_id);
        $this->assertEquals($cloner->id, $destination->owner_id);
        $this->assertEquals($source->current_state->get_stage_id(), $destination->current_state->get_stage_id());
        $this->assertTrue($destination->current_state->is_stage_type(form_submission::get_code()));
        $this->assertTrue($destination->current_state->is_draft());
        $this->assertNull($destination->current_state->get_approval_level_id());
        $this->assertNull($destination->submitted);
        $this->assertNull($destination->completed);
        $this->assertGreaterThan($source->created, $destination->created);
        $this->assertGreaterThan($source->updated, $destination->updated);
        foreach ($destination->activities as $activity) {
            switch ($activity->activity_type) {
                case creation_activity::get_type():
                    $this->assertEquals($source->id, $activity->activity_info_parsed['source']);
                    break;
                case stage_started_activity::get_type():
                    break;
                default:
                    self::fail('Unexpected activity type detected');
            }
        }
    }

    /**
     * @covers ::clone
     */
    public function test_clone_with_submissions(): void {
        $source = $this->test_create();
        // Proactively load kids.
        $source->get_current_stage();
        $source->get_submissions();
        /** @var workflow_stage $stage1 */
        $stage1 = $source->workflow_version->stages->find('ordinal_number', 1);
        /** @var workflow_stage $stage2 */
        $stage2 = $source->workflow_version->stages->find('ordinal_number', 2);
        builder::table(application_entity::TABLE)->update(['current_stage_id' => $stage2->id]);
        $fd = form_data::from_json('{}');
        $me = user::logged_in();
        $someone = new user($this->getDataGenerator()->create_user());
        $this->generator()->create_application_submission($source->id, $me->id, $stage1->id, $fd);
        $this->generator()->create_application_submission($source->id, $someone->id, $stage1->id, $fd);
        $this->generator()->create_application_submission($source->id, $me->id, $stage2->id, $fd);
        builder::table(application_submission_entity::TABLE)->update(['submitted' => 1, 'superseded' => true]);
        $this->assertCount(0, $source->submissions);
        $this->assertCount(3, application::load_by_id($source->id)->submissions);
        $destination = $source->clone($me->id);
        $this->assertCount(1, $destination->submissions);
        $this->assertCount(1, $destination->submissions->filter('workflow_stage_id', $stage1->id));
        $filter = function (application_submission $submission) {
            return $submission->superseded || $submission->submitted;
        };
        $this->assertCount(0, $destination->submissions->filter($filter));
    }

    /**
     * @covers ::clone
     */
    public function test_clone_with_latest_version(): void {
        $source = $this->test_create();
        /** @var workflow_stage $stage1 */
        $stage1 = $source->workflow_version->stages->find('ordinal_number', 1);

        // Create additional version for workflow in DRAFT state and different stage.
        $new_workflow_version = $this->generator()->create_workflow_version(
            $source->workflow_version->workflow_id,
            $source->form_version_id
        );
        $this->generator()->create_workflow_stage($new_workflow_version->id, 'New Stage', form_submission::get_enum());

        // Archived the old version
        $old_workflow_version_model = workflow_version::load_by_id($source->workflow_version_id);
        $old_workflow_version_model->archive();

        // Submission
        $fd = form_data::from_json('{}');
        $me = user::logged_in();
        $this->generator()->create_application_submission($source->id, $me->id, $stage1->id, $fd);

        // Cannot clone if the latest workflow version not active
        try {
            $source->clone($me->id);
            $this->fail('model_exception expected');
        } catch (model_exception $e) {
            $this->assertEquals('Workflow version must be active for clone application', $e->debuginfo);
        }

        // Activate the latest workflow_version and try clone again
        $workflow_version_model = workflow_version::load_by_entity($new_workflow_version);
        $workflow_version_model->activate();
        $destination = $source->clone($me->id);
        $this->assertEquals($workflow_version_model->id, $destination->workflow_version->id);
        $this->assertEquals($workflow_version_model->stages->first()->id, $destination->current_stage->id);
        $this->assertEquals('New Stage', $destination->current_stage->name);

        // Archive all workflow_version for this workflow
        $workflow_version_model->archive();

        // We cannot perform clone as we don't have any active workflow_versions
        try {
            $source->clone($me->id);
            $this->fail('model_exception expected');
        } catch (model_exception $e) {
            $this->assertEquals('Workflow version must be active for clone application', $e->debuginfo);
        }
    }

    /**
     * @covers ::get_your_progress
     */
    public function test_your_progress(): void {
        $application = $this->test_create();
        $stage1 = $application->current_stage;
        $stage2 = $application->workflow_version->get_next_stage($stage1->id);
        $level1 = $stage2->approval_levels->first();
        $level2 = $stage2->approval_levels->last();
        $assignment = $application->assignment;

        // Create another application.
        $this->setUser($application->user);
        $application2 = application::create($application->workflow_version, $assignment, $application->user_id);

        // Create three approvers.
        $approver1 = $this->create_user();
        $approver2 = $this->create_user();
        $approver3 = $this->create_user();

        // Approver 1 for Level 1.
        $approver_go = new assignment_approver_generator_object(
            $assignment->id,
            $level1->id,
            user_approver_type::TYPE_IDENTIFIER,
            $approver1->id
        );
        $this->generator()->create_assignment_approver($approver_go);

        // Approver 2 for Level 2.
        $approver_go->identifier = $approver2->id;
        $approver_go->workflow_stage_approval_level_id = $level2->id;
        $this->generator()->create_assignment_approver($approver_go);

        // Approver 3 for Level 2 also.
        $approver_go->identifier = $approver3->id;
        $this->generator()->create_assignment_approver($approver_go);

        // Draft applications are NA for both approvers
        $this->setUser($approver1);
        $this->assertEquals('NA', $application->your_progress);
        $this->assertEquals('NA', $application2->your_progress);
        $this->setUser($approver2);
        $this->assertEquals('NA', $application->your_progress);
        $this->assertEquals('NA', $application2->your_progress);
        $this->setUser($approver3);
        $this->assertEquals('NA', $application->your_progress);
        $this->assertEquals('NA', $application2->your_progress);

        // Submitted applications are pending for approver1, still n/a for approver2.
        $form_data = form_data::from_json('{}');
        $this->setUser($application->user);
        $application_submission1 = application_submission::create_or_update($application, $application->user_id, $form_data);
        $application_submission1->publish(user::logged_in()->id);
        submit::execute($application, user::logged_in()->id);
        $application_submission2 = application_submission::create_or_update($application2, $application2->user_id, $form_data);
        $application_submission2->publish(user::logged_in()->id);
        submit::execute($application2, user::logged_in()->id);

        $this->setUser($approver1);
        $this->assertEquals('PENDING', $application->your_progress);
        $this->assertEquals('PENDING', $application2->your_progress);
        $this->setUser($approver2);
        $this->assertEquals('NA', $application->your_progress);
        $this->assertEquals('NA', $application2->your_progress);
        $this->setUser($approver3);
        $this->assertEquals('NA', $application->your_progress);
        $this->assertEquals('NA', $application2->your_progress);

        // Approver1 approves one application and rejects the other.
        approve::execute($application, $approver1->id);
        reject::execute($application2, $approver1->id);
        $application->refresh(true);
        $application2->refresh(true);
        $this->setUser($approver1);
        $this->assertEquals('APPROVED', $application->your_progress);
        $this->assertEquals('REJECTED', $application2->your_progress);
        $this->setUser($approver2);
        $this->assertEquals('PENDING', $application->your_progress);
        $this->assertEquals('NA', $application2->your_progress);
        $this->setUser($approver3);
        $this->assertEquals('PENDING', $application->your_progress);
        $this->assertEquals('NA', $application2->your_progress);

        // Approver2 approves the one application still in flight.
        approve::execute($application, $approver2->id);
        $application->refresh(true);
        $this->setUser($approver1);
        $this->assertEquals('APPROVED', $application->your_progress);
        $this->setUser($approver2);
        $this->assertEquals('APPROVED', $application->your_progress);
        $this->setUser($approver3);
        $this->assertEquals('NA', $application->your_progress);
    }

    public function test_set_current_state(): void {
        // Create an application.
        $this->setAdminUser();
        $application = $this->create_application_for_user();
        $next_state = $application->current_stage->state_manager->get_next_state($application->current_state);
        $approval_level_id = $next_state->get_approval_level_id();

        // Check the starting configuration.
        self::assertGreaterThan(0, $application->current_state->get_stage_id());
        self::assertTrue($application->current_state->is_stage_type(form_submission::get_code()));
        self::assertTrue($application->current_state->is_draft());
        self::assertNull($application->current_state->get_approval_level_id());

        // Change the state.
        $application->set_current_state(new application_state(
            123,
            false,
            $approval_level_id
        ));

        // Check the results.
        self::assertEquals(123, $application->current_state->get_stage_id());
        self::assertFalse($application->current_state->is_draft());
        self::assertGreaterThan(0, $application->current_state->get_approval_level_id());
    }

    public function test_mark_finished() {
        $this->setAdminUser();
        $application = $this->create_application_for_user();
        $this->assertNull($application->completed);
        $application->mark_completed();
        $this->assertNotNull($application->completed);
    }

    public function test_change_state(): void {
        // Create an application.
        $this->setAdminUser();
        $actor = $this->create_user();
        $this->setUser($actor);
        $application = $this->create_application_for_user();

        // Check that nothing happens if change_state is called with the current state.
        $mock_application = $this->getMockBuilder(application::class)
            ->setConstructorArgs([new application_entity($application->id)])
            ->onlyMethods(['get_current_stage', 'set_current_state'])
            ->getMock();
        $mock_application->expects($this->never())->method('get_current_stage');
        $mock_application->expects($this->never())->method('set_current_state');
        $mock_application->change_state($application->current_state, $actor->id);

        // Check that change_within_stage is called if change_state is called with the current stage.
        // Set up the mock application.
        $mock_application = $this->getMockBuilder(application::class)
            ->setConstructorArgs([new application_entity($application->id)])
            ->onlyMethods(['get_current_stage', 'set_current_state'])
            ->getMock();
        $mock_application->method('get_current_stage')->willReturn($application->current_stage);
        self::assertTrue($application->current_state->is_stage_type(form_submission::get_code()));
        self::assertTrue($application->current_state->is_draft());
        $mock_application->expects($this->atLeast(2))->method('get_current_stage');
        $mock_application->expects($this->once())->method('set_current_state');
        $mock_application->change_state(new application_state($mock_application->current_state->get_stage_id()), $actor->id);
    }
}
