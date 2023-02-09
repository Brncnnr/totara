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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use core\orm\collection;
use mod_approval\model\application\activity\comment_created;
use mod_approval\model\application\activity\comment_deleted;
use mod_approval\model\application\activity\comment_replied;
use mod_approval\model\application\activity\comment_updated;
use mod_approval\model\application\activity\finished;
use mod_approval\model\application\activity\creation;
use mod_approval\model\application\activity\edited;
use mod_approval\model\application\activity\level_approved;
use mod_approval\model\application\activity\level_ended;
use mod_approval\model\application\activity\level_rejected;
use mod_approval\model\application\activity\level_started;
use mod_approval\model\application\activity\notification_sent;
use mod_approval\model\application\activity\stage_all_approved;
use mod_approval\model\application\activity\stage_ended;
use mod_approval\model\application\activity\stage_started;
use mod_approval\model\application\activity\stage_submitted;
use mod_approval\model\application\activity\uploaded;
use mod_approval\model\application\activity\withdrawn;
use mod_approval\model\application\application;
use mod_approval\model\application\application_activity;
use mod_approval\model\application\application_state;
use mod_approval\model\application\application_workflow_stage;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\testing\application_generator_object;
use mod_approval\testing\assignment_generator_object;
use mod_approval\testing\workflow_generator_object;
use mod_approval\totara_notification\resolver\stage_base;
use mod_approval\totara_notification\recipient\applicant;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/testcase.php');

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\webapi\resolver\type\application_workflow_stage
 */
class mod_approval_webapi_type_application_workflow_stage_testcase extends mod_approval_testcase {

    use webapi_phpunit_helper;

    private const TYPE = 'mod_approval_application_workflow_stage';
    private const TYPE_STAGE = 'mod_approval_workflow_stage';
    private const TYPE_APP = 'mod_approval_application';

    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_for_application(): void {
        $workflow_type = $this->generator()->create_workflow_type('Type');
        $form_version = $this->generator()->create_form_and_version();
        $workflow_version1 = $this->generator()->create_workflow_and_version(
            new workflow_generator_object(
                $workflow_type->id,
                $form_version->form_id,
                $form_version->id,
                status::DRAFT
            )
        );
        $assignment = $this->generator()->create_assignment(
            new assignment_generator_object(
                $workflow_version1->workflow->course_id,
                assignment_type\cohort::get_code(),
                $this->getDataGenerator()->create_cohort()->id
            )
        );
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'Sammy', 'lastname' => 'Sam', 'middlename' => '']);
        $stage1 = $this->generator()->create_workflow_stage($workflow_version1->id, 'First Season', form_submission::get_enum());
        $stage2 = $this->generator()->create_workflow_stage($workflow_version1->id, 'Second Season', form_submission::get_enum());
        $workflow_version1->status = status::ACTIVE;
        $workflow_version1->save();

        $workflow_version2 = $this->generator()->create_workflow_and_version(
            new workflow_generator_object(
                $workflow_type->id,
                $form_version->form_id,
                $form_version->id,
                status::DRAFT
            )
        );
        $stage3 = $this->generator()->create_workflow_stage($workflow_version2->id, 'Final Season', form_submission::get_enum());
        $workflow_version2->status = status::ACTIVE;
        $workflow_version2->save();

        $this->setUser($user1);
        $application = application::load_by_entity($this->generator()->create_application(
            new application_generator_object(
                $workflow_version1->id,
                $workflow_version1->form_version_id,
                $assignment->id
            )
        ));
        $context = $application->get_context();

        $this->application_update_stage_and_level_silently($application, $stage1->id, null);
        $app_state_value = $this->resolve_graphql_type(self::TYPE_APP, 'current_state', $application, [], $context);
        $this->assertInstanceOf(application_state::class, $app_state_value);
        $this->assertEquals($stage1->id, $app_state_value->get_stage_id());

        $this->application_update_stage_and_level_silently($application, $stage2->id, null);
        $app_state_value = $this->resolve_graphql_type(self::TYPE_APP, 'current_state', $application, [], $context);
        $this->assertInstanceOf(application_state::class, $app_state_value);
        $this->assertEquals($stage2->id, $app_state_value->get_stage_id());
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_for_activities(): void {
        $g = $this->generator();
        $workflow_type = $g->create_workflow_type('Type');
        $form_version = $g->create_form_and_version();
        $workflow_version1 = $g->create_workflow_and_version(
            new workflow_generator_object(
                $workflow_type->id,
                $form_version->form_id,
                $form_version->id,
                status::DRAFT
            )
        );
        $assignment = $g->create_assignment(
            new assignment_generator_object(
                $workflow_version1->workflow->course_id,
                assignment_type\cohort::get_code(),
                $this->getDataGenerator()->create_cohort()->id
            )
        );
        $user1 = new user($this->getDataGenerator()->create_user(
            ['firstname' => 'Sammy', 'lastname' => 'Sam', 'middlename' => '']
        ));
        $user2 = new user($this->getDataGenerator()->create_user(
            ['firstname' => 'Molly', 'lastname' => 'Mol', 'middlename' => '']
        ));
        // randomly add stages and levels
        $stage1 = workflow_stage::load_by_entity($g->create_workflow_stage(
            $workflow_version1->id,
            'First Season',
            form_submission::get_enum()
        ));
        $stage2 = workflow_stage::load_by_entity($g->create_workflow_stage(
            $workflow_version1->id,
            'Second Season',
            form_submission::get_enum()
        ));
        $stage3 = workflow_stage::load_by_entity($g->create_workflow_stage(
            $workflow_version1->id,
            'Final Season',
            form_submission::get_enum()
        ));
        $level21 = workflow_stage_approval_level::load_by_entity($g->create_approval_level($stage2->id, 'Episode Uno', 1));
        $level11 = workflow_stage_approval_level::load_by_entity($g->create_approval_level($stage1->id, 'Episode Une', 1));
        $level31 = workflow_stage_approval_level::load_by_entity($g->create_approval_level($stage3->id, 'Final Episode', 1));
        $level13 = workflow_stage_approval_level::load_by_entity($g->create_approval_level($stage1->id, 'Season Finale', 3));
        $level12 = workflow_stage_approval_level::load_by_entity($g->create_approval_level($stage1->id, 'Episode Deux', 2));
        $level22 = workflow_stage_approval_level::load_by_entity($g->create_approval_level($stage2->id, 'Season Final', 2));
        $workflow_version1->status = status::ACTIVE;
        $workflow_version1->save();

        $this->setUser($user1);
        $application = application::load_by_entity($g->create_application(
            new application_generator_object(
                $workflow_version1->id,
                $workflow_version1->form_version_id,
                $assignment->id
            )
        ));
        $this->application_update_stage_and_level_silently($application, $stage1->id, $level11->id);
        application_activity::create($application, $user2->id, comment_created::class);
        application_activity::create($application, $user2->id, comment_deleted::class);
        $this->application_update_stage_and_level_silently($application, $stage1->id, $level12->id);
        application_activity::create($application, $user2->id, comment_replied::class);
        application_activity::create($application, $user2->id, comment_updated::class);
        application_activity::create($application, $user2->id, finished::class);
        application_activity::create($application, $user2->id, edited::class);
        $this->application_update_stage_and_level_silently($application, $stage1->id, $level13->id);
        application_activity::create($application, $user2->id, creation::class, []);
        application_activity::create($application, $user2->id, creation::class, ['source' => 42]);
        application_activity::create($application, $user2->id, notification_sent::class, [
            'resolver_class_name' => stage_base::class,
            'recipient_class_name' => applicant::class,
        ]);
        $this->application_update_stage_and_level_silently($application, $stage3->id, $level31->id);
        application_activity::create($application, $user2->id, level_approved::class);
        application_activity::create($application, $user2->id, level_ended::class);
        application_activity::create($application, $user2->id, level_rejected::class);
        application_activity::create($application, $user2->id, level_started::class);
        application_activity::create($application, $user2->id, stage_all_approved::class);
        application_activity::create($application, $user2->id, stage_ended::class);
        application_activity::create($application, $user2->id, stage_started::class);
        application_activity::create($application, $user2->id, stage_submitted::class);
        application_activity::create($application, $user2->id, uploaded::class);
        application_activity::create($application, $user2->id, withdrawn::class);

        // Mark the application finished.
        $application->set_current_state(new application_state($application->current_state->get_stage_id()));

        $context = $application->get_context();
        $stages = $this->resolve_graphql_type(self::TYPE_APP, 'workflow_stages', $application, [], $context);
        $this->assertInstanceOf(collection::class, $stages);
        $this->assertCount(3, $stages);

        $first_stage = $stages->shift();
        $second_stage = $stages->shift();
        $third_stage = $stages->shift();
        $this->assertInstanceOf(application_workflow_stage::class, $first_stage);
        $this->assertInstanceOf(application_workflow_stage::class, $second_stage);
        $this->assertInstanceOf(application_workflow_stage::class, $third_stage);

        $activities1 = $this->resolve_graphql_type(self::TYPE, 'activities', $first_stage, [], $context);
        $activities2 = $this->resolve_graphql_type(self::TYPE, 'activities', $second_stage, [], $context);
        $activities3 = $this->resolve_graphql_type(self::TYPE, 'activities', $third_stage, [], $context);
        $this->assertInstanceOf(collection::class, $activities1);
        $this->assertInstanceOf(collection::class, $activities2);
        $this->assertInstanceOf(collection::class, $activities3);
        $this->assertCount(9, $activities1);
        $this->assertCount(0, $activities2);
        $this->assertCount(10, $activities3);
    }
}
