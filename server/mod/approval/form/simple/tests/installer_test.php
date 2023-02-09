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
 * @package approvalform_simple
 */

use approvalform_simple\installer;
use core_phpunit\testcase;
use mod_approval\entity\application\application;
use mod_approval\entity\form\form;
use mod_approval\entity\workflow\workflow_stage as workflow_stage_entity;
use mod_approval\entity\workflow\workflow_type;
use mod_approval\entity\workflow\workflow_version;
use mod_approval\model\application\action\approve;
use mod_approval\model\application\action\reject;
use mod_approval\model\application\application as application_model;
use mod_approval\model\workflow\stage_type\approvals;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow_stage;
use totara_core\entity\relationship;

/**
 * @coversDefaultClass \approvalform_simple\installer
 *
 * @group approval_workflow
 */
class approvalform_simple_installer_testcase extends testcase {

    /**
     * @covers ::install_demo_cohort
     */
    public function test_install_demo_cohort() {
        $this->setAdminUser();

        $installer = new installer();
        $cohort = $installer->install_demo_cohort();

        $this->assertInstanceOf(\core\entity\cohort::class, $cohort);
        $this->assertEquals('Simple workflow demo', $cohort->name);
        $this->assertEquals('simpledemo', $cohort->idnumber);

        // Install it again, should be the same.
        $new_cohort = $installer->install_demo_cohort();
        $this->assertEquals($new_cohort->id, $cohort->id);
    }

    /**
     * @covers ::install_demo_workflow
     */
    public function test_install_demo_workflow() {
        global $CFG;
        $this->setAdminUser();

        // Install a testing workflow
        $installer = new installer();
        $cohort = $installer->install_demo_cohort();
        $workflow = $installer->install_demo_workflow($cohort, 'Testing');

        // Check workflow_type
        $workflow_type = new workflow_type($workflow->workflow_type_id);
        $this->assertEquals('Testing', $workflow_type->name);

        // Check form
        $form = new form($workflow->form_id);
        $this->assertEquals('simple', $form->plugin_name);
        $this->assertEquals('Simple Request Form', $form->title);

        // Check form_version
        $this->assertCount(1, $form->versions);
        $form_version = $form->versions->first();
        $json_schema = file_get_contents($CFG->dirroot . '/mod/approval/form/simple/form.json');
        $this->assertEquals($json_schema, $form_version->json_schema);

        // Check workflow
        $this->assertEquals('Default Simple Workflow', $workflow->name);

        // Check workflow_version
        $this->assertCount(1, $workflow->versions);
        $workflow_version = $workflow->versions->first();

        // Check stages
        /* @var workflow_version $workflow_version */
        $this->assertCount(5, $workflow_version->stages);
        $stages = installer::get_default_stages();
        $ix = 0;
        foreach ($workflow_version->stages as $stage) {
            $ix++;
            $expected = array_shift($stages);
            $stage_model = workflow_stage::load_by_entity($stage);
            $this->assertEquals($expected['name'], $stage->name);
            $this->assertEquals($expected['type'], $stage_model->type::get_enum());
            $this->assertEquals($ix, $stage->sortorder);
            ${'stage' . $ix} = $stage;
        }

        // Check stage1 formviews

        // Check stage2 approval levels
        $this->assertCount(2, $stage2->approval_levels);
        $stage2_1 = $stage2->approval_levels->first();

        // Check default assignment & approvers
        $this->assertCount(1, $workflow->assignments);
        $default_assignment = $workflow->assignments->first();
        $this->assertEquals(true, $default_assignment->is_default);
        $this->assertEquals('Simple workflow demo', $default_assignment->name);
        $this->assertCount(3, $default_assignment->approvers);

        $manager = relationship::repository()->where('idnumber', '=', 'manager')->one();
        $manager_approver = $default_assignment->approvers->first();
        $this->assertEquals($stage2_1->id, $manager_approver->workflow_stage_approval_level_id);
        $this->assertEquals($manager->id, $manager_approver->identifier);
    }

    /**
     * @covers ::install_demo_workflow
     */
    public function test_install_demo_workflow_twice() {
        $this->setAdminUser();

        // Install a testing workflow
        $installer = new installer();
        $cohort = $installer->install_demo_cohort();
        $workflow1 = $installer->install_demo_workflow($cohort, 'Testing');
        $workflow2 = $installer->install_demo_workflow($cohort, 'Testing');

        // Same type, same form, different workflows
        $this->assertEquals($workflow1->workflow_type_id, $workflow2->workflow_type_id);
        $this->assertEquals($workflow1->form_id, $workflow2->form_id);
        $this->assertNotEquals($workflow1->id, $workflow2->id);

        // Different workflow versions, same form version
        $this->assertNotEquals($workflow1->versions->first()->id, $workflow2->versions->first()->id);
        $this->assertEquals($workflow1->versions->first()->form_version_id, $workflow2->versions->first()->form_version_id);

        // Default assignment should be to same cohort
        $this->assertNotEquals($workflow1->assignments->first()->id, $workflow2->assignments->first()->id);
        $this->assertEquals($workflow1->assignments->first()->name, $workflow2->assignments->first()->name);
        $this->assertEquals(
            $workflow1->assignments->first()->assignment_identifier,
            $workflow2->assignments->first()->assignment_identifier
        );
    }

    public function test_install_demo_assignment() {
        // Install a testing workflow
        $installer = new installer();
        $cohort = $installer->install_demo_cohort();
        $installer->install_demo_workflow($cohort, 'Testing');

        // Install demo assignment
        list($applicant) = $installer->install_demo_assignment($cohort);
        $this->assertTrue(cohort_is_member($cohort->id, $applicant->id));
    }

    public function test_install_demo_applications() {
        $this->setAdminUser();

        // Install a testing workflow
        $installer = new installer();
        $cohort = $installer->install_demo_cohort();
        $workflow = $installer->install_demo_workflow($cohort, 'Testing');
        list($applicant, $ja) = $installer->install_demo_assignment($cohort);

        // Install some applications
        $installer->install_demo_applications($workflow, $applicant, $ja);
        $this->assertEquals(7, application::repository()->count());

        $draft_application = application::repository()
            ->where('is_draft', '=', 1)
            ->order_by('id', 'DESC')
            ->get();
        $before_submission = application::repository()
            ->join([workflow_stage_entity::TABLE, 'stage'], 'current_stage_id', '=', 'stage.id')
            ->where('stage.type_code', form_submission::get_code())
            ->where('is_draft', '=', 0) // Exclude draft.
            ->order_by('id', 'DESC')
            ->get();
        $in_approvals = application::repository()
            ->join([workflow_stage_entity::TABLE, 'stage'], 'current_stage_id', '=', 'stage.id')
            ->where('stage.type_code', approvals::get_code())
            ->order_by('id', 'DESC')
            ->get();
        self::assertCount(3, $draft_application);
        self::assertCount(2, $before_submission);
        self::assertCount(2, $in_approvals);

        $models = [];
        foreach ($in_approvals as $entity) {
            $models[] = application_model::load_by_entity($entity);
        }
        foreach ($before_submission as $entity) {
            $models[] = application_model::load_by_entity($entity);
        }

        $this->assertEquals('Approval', $models[0]->current_stage->name);
        $this->assertEquals('Level 1', $models[0]->current_approval_level->name);
        $this->assertEquals('Level 2', $models[1]->current_approval_level->name);
        $this->assertEquals(approve::get_code(), $models[1]->last_action->code);
        $this->assertEquals(reject::get_code(), $models[2]->last_action->code);
        $this->assertEquals('Followup', $models[3]->current_stage->name);
        $this->assertEquals(approve::get_code(), $models[3]->last_action->code);
    }
}