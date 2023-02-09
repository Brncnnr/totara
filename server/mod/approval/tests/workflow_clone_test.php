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

use core_phpunit\testcase;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\status;
use mod_approval\model\workflow\stage_type\form_submission;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_version;
use mod_approval\model\workflow\helper\cloner as workflow_clone_helper;
use mod_approval\testing\approval_workflow_test_setup;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\workflow\helper\cloner
 */
class mod_approval_workflow_clone_testcase extends testcase {
    use approval_workflow_test_setup;

    /**
     * Gets the approval workflow generator instance
     *
     * @return \mod_approval\testing\generator
     */
    protected function generator(): \mod_approval\testing\generator {
        return \mod_approval\testing\generator::instance();
    }

    public function test_clone_workflow(): void {
        global $DB;

        $now = time();
        $workflow = $this->set_application();
        $this->setAdminUser();

        // Change created and modified time for original container
        $container_record = $DB->get_record('course', ['id' => $workflow->course_id]);
        $container_record->timecreated = $now - HOURSECS;
        $container_record->timemodified = $now - HOURSECS;
        $DB->update_record('course', $container_record);

        $new_workflow_name = 'Learning to build';
        $new_default_assignment =  [
            'type' => assignment_type\cohort::get_code(),
            'id' => $this->getDataGenerator()->create_cohort()->id,
        ];

        $updated_workflow = $workflow->refresh();
        $new_workflow = workflow_clone_helper::clone(
            $updated_workflow,
            $new_workflow_name,
            $new_default_assignment['type'],
            $new_default_assignment['id']
        );

        $this->assertNotEmpty($new_workflow);
        $this->assertNotEquals($workflow->id, $new_workflow->id);
        $this->assertGreaterThan($workflow->id, $new_workflow->id);
        $this->assertEquals($new_workflow_name, $new_workflow->name);
        $this->assertEquals($workflow->workflow_type_id, $new_workflow->workflow_type_id);
        $this->assertEquals($workflow->latest_version->stages->first()->name, $new_workflow->latest_version->stages->first()->name);
        $this->assertEquals($workflow->latest_version->stages->first()->get_interactions()->count(), $new_workflow->latest_version->stages->first()->get_interactions()->count());
        $this->assertEquals($workflow->latest_version->stages->first()->get_formviews()->count(), $new_workflow->latest_version->stages->first()->get_formviews()->count());
        $this->assertNotEquals($workflow->course_id, $new_workflow->course_id);
        $this->assertGreaterThan($workflow->course_id, $new_workflow->course_id);
        $this->assertCount(1, $new_workflow->versions);
        $this->assertTrue($new_workflow->active);
        $this->assertEquals(status::DRAFT, $new_workflow->latest_version->status);
        // New course container has different timecreated and timemodified
        $this->assertNotEquals($updated_workflow->container->timecreated, $new_workflow->container->timecreated);
        $this->assertNotEquals($updated_workflow->container->timemodified, $new_workflow->container->timemodified);
        $this->assertEquals($new_default_assignment['type'], $new_workflow->default_assignment->assignment_type);
        $this->assertEquals($new_default_assignment['id'], $new_workflow->default_assignment->assignment_identifier);
    }

    private function set_application() {
        $this->setAdminUser();
        list($workflow) = $this->create_workflow_and_assignment();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Add a second approval level
        /** @var \mod_approval\entity\workflow\workflow $workflow */
        $workflow_version = workflow_version::load_latest_by_workflow_id($workflow->id);
        workflow_version_entity::repository()
            ->where('id', $workflow_version->id)
            ->update([
                'status' => status::DRAFT,
            ]);
        $stage_1 = $workflow_version->stages->first();
        $this->generator()->create_approval_level($workflow_version->get_next_stage($stage_1->id)->id, 'Level 2', 2);

        // Add a second stage
        $this->generator()->create_workflow_stage($workflow_version->id, 'Next Stage', form_submission::get_enum());
        workflow_version_entity::repository()
            ->where('id', $workflow_version->id)
            ->update([
                'status' => status::ACTIVE,
            ]);

        return workflow::load_by_id($workflow->id);
    }
}