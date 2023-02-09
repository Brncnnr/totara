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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\entity\assignment\assignment_approver as assignment_approver_entity;
use mod_approval\entity\workflow\workflow_stage_approval_level as workflow_stage_approval_level_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\application\application_state;
use mod_approval\model\assignment\approver_type as approver_type;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approver;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\form\form_version;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\model\workflow\workflow_version;
use mod_approval\model\workflow\workflow_type;
use mod_approval\testing\application_generator_object;
use mod_approval\testing\assignment_generator_object;
use totara_core\relationship\relationship;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\workflow_stage_approval_level
 */
class mod_approval_workflow_stage_approval_level_model_testcase extends testcase {
    /** @var workflow_type */
    private $type;

    /** @var form */
    private $form;

    public function setUp(): void {
        parent::setUp();
        $this->type = workflow_type::create('kia ora');
        $this->form = form::create('simple', 'kia ora');
    }

    public function tearDown(): void {
        $this->form = null;
        $this->type = null;
        parent::tearDown();
    }

    /**
     * Gets the generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    /**
     * @covers ::clone
     */
    public function test_clone(): void {
        $this->setAdminUser();
        $time = time();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        $workflow_stage = workflow_stage::create($workflow_version, 'kia kaha', approvals::get_enum());
        $approval_level_current = $workflow_stage->add_approval_level('Stage 1');

        $approval_level = $approval_level_current->clone($workflow_stage);

        $this->assertNotEmpty($approval_level->id);
        $this->assertEquals($workflow_stage->id, $approval_level->workflow_stage->id);
        $this->assertEquals('Stage 1', $approval_level->name);
        $this->assertEquals(3, $approval_level->ordinal_number);
        $this->assertTrue($approval_level->active);
        $this->assertGreaterThanOrEqual($time, $approval_level->created);
        $this->assertLessThanOrEqual($approval_level->updated, $approval_level->created);
    }

    /**
     * @covers ::activate
     * @covers ::deactivate
     */
    public function test_toggle(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        $workflow_stage = workflow_stage::create($workflow_version, 'kia kaha', approvals::get_enum());
        $approval_level = $workflow_stage->add_approval_level('Stage 1');
        $this->assertTrue($approval_level->active);
        $approval_level->deactivate();
        $this->assertFalse($approval_level->active);
        $approval_level->activate();
        $this->assertTrue($approval_level->active);
    }

    /**
     * @covers ::can_deactivate
     */
    public function test_toggle_with_dependencies(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        $workflow_stage = workflow_stage::create($workflow_version, 'kia kaha', approvals::get_enum());
        $approval_level = $workflow_stage->add_approval_level('Stage 1');

        $assignment_go = new assignment_generator_object(
            $workflow->course_id,
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $assignment = $this->generator()->create_assignment($assignment_go);
        $application_go = new application_generator_object($workflow_version->id, $form_version->id, $assignment->id);
        $application_go->current_state = new application_state(
            $application_go->current_state->get_stage_id(),
            false,
            $approval_level->id
        );
        $application = $this->generator()->create_application($application_go);

        $this->assertTrue($approval_level->active);
        try {
            $approval_level->deactivate();
            $this->fail("No model exception thrown");
        } catch (model_exception $e) {
            $this->assertEquals('Cannot deactivate object with active dependencies', $e->debuginfo);
        }
        $application->completed = time();
        $application->save();
        $approval_level->deactivate();
        $this->assertFalse($approval_level->active);
    }

    /**
     * @covers ::refresh
     */
    public function test_refresh(): void {
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        $workflow_stage = workflow_stage::create($workflow_version, 'kia ora koutou', approvals::get_enum());
        $approval_level = $workflow_stage->add_approval_level('Stage 1');

        $this->assertNotEmpty($approval_level->id);
        $this->assertEquals('Stage 1', $approval_level->name);
        builder::table(workflow_stage_approval_level_entity::TABLE)->update(['name' => 'Stage 1: Fill out the form']);
        $approval_level->refresh();
        $this->assertEquals('Stage 1: Fill out the form', $approval_level->name);
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        $this->setAdminUser();
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $cohort1->id
        );
        $assignment1 = $workflow->default_assignment;
        $assignment2 = assignment::create($workflow->container, assignment_type\cohort::get_code(), $cohort2->id, false)->activate();
        $assignment3 = assignment::create($workflow->container, assignment_type\cohort::get_code(), $cohort3->id, false);
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        $workflow_stage = workflow_stage::create($workflow_version, 'kia ora koutou', approvals::get_enum());
        $approval_level1 = $workflow_stage->add_approval_level('Stage 1');
        $approval_level2 = $workflow_stage->add_approval_level('Stage 2');
        $manager = relationship::load_by_idnumber('manager');
        assignment_approver::create($assignment1, $approval_level1, approver_type\relationship::TYPE_IDENTIFIER, $manager->id);
        assignment_approver::create($assignment1, $approval_level1, approver_type\user::TYPE_IDENTIFIER, $user1->id)->activate();
        assignment_approver::create($assignment1, $approval_level1, approver_type\user::TYPE_IDENTIFIER, $user2->id);
        assignment_approver::create($assignment2, $approval_level1, approver_type\user::TYPE_IDENTIFIER, $user1->id)->activate();
        assignment_approver::create($assignment2, $approval_level1, approver_type\user::TYPE_IDENTIFIER, $user2->id);
        assignment_approver::create($assignment3, $approval_level1, approver_type\user::TYPE_IDENTIFIER, $user1->id)->activate();
        assignment_approver::create($assignment3, $approval_level1, approver_type\user::TYPE_IDENTIFIER, $user2->id);

        $approval_level2->delete();
        $this->assertEmpty($approval_level2->id);
        $approval_level1->delete();
        $this->assertEmpty($approval_level1->id);
        // Default level did not delete
        $this->assertEquals(1, workflow_stage_approval_level_entity::repository()->count());
        $this->assertEquals(0, assignment_approver_entity::repository()->count());
    }

    public function test_is_first_level(): void {
        // Set up workflow.
        $this->setAdminUser();
        $workflow = workflow::create(
            $this->type,
            $this->form,
            'kia ora',
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id
        );
        $form_schema = file_get_contents(__DIR__ . '/fixtures/form/test_form.json');
        $form_version = form_version::create($this->form, '1', $form_schema);
        $workflow_version = workflow_version::create($workflow, $form_version);
        $workflow_stage = workflow_stage::create($workflow_version, 'kia ora koutou', approvals::get_enum());

        $default_level = $workflow_stage->approval_levels->first();
        // Add two approval levels.
        $approval_level1 = $workflow_stage->add_approval_level('Stage 1');
        $approval_level2 = $workflow_stage->add_approval_level('Stage 2');

        // Check that one is first and the others aren't.
        self::assertTrue($default_level->is_first());
        self::assertFalse($approval_level1->is_first());
        self::assertFalse($approval_level2->is_first());
    }
}
