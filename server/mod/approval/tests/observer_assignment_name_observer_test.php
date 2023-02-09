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

use container_approval\approval;
use core\testing\generator as core_generator;
use core_phpunit\testcase;
use hierarchy_organisation\entity\organisation as organisation_entity;
use hierarchy_organisation\event\organisation_updated;
use hierarchy_position\entity\position as position_entity;
use hierarchy_position\event\position_updated;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_type\cohort as cohort_assignment_type;
use mod_approval\model\assignment\assignment_type\organisation as organisation_assignment_type;
use mod_approval\model\assignment\assignment_type\position as position_assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_type;

/**
 * @group approval_workflow
 * @covers \mod_approval\observer\assignment_name_observer
 */
class mod_approval_observer_assignment_name_observer_testcase extends testcase {

    public function test_organisation_assignment_names_are_updated() {
        $container = $this->get_workflow_container();
        $hierarchy_generator = core_generator::instance()->get_plugin_generator('totara_hierarchy');
        $framework = $hierarchy_generator->create_framework('organisation');
        $organisation = $hierarchy_generator->create_org(
            [
                'frameworkid' => $framework->id,
                'fullname' => 'Senior executive',
                'shortname' => 'se'
            ]
        );

        $assignment = assignment::create(
            $container,
            organisation_assignment_type::get_code(),
            $organisation->id
        );

        // Update organisation
        organisation_entity::repository()
            ->where('id', $organisation->id)
            ->update(['fullname' => 'New management']);
        $new_organisation = new organisation_entity($organisation->id);
        organisation_updated::create_from_old_and_new($new_organisation->to_record(), $organisation)->trigger();

        $assignment = new mod_approval\entity\assignment\assignment($assignment->id);
        $this->assertEquals('New management', $assignment->name);
    }

    public function test_position_assignment_names_are_updated() {
        $container = $this->get_workflow_container();
        $hierarchy_generator = core_generator::instance()->get_plugin_generator('totara_hierarchy');
        $framework = $hierarchy_generator->create_framework('position');
        $position = $hierarchy_generator->create_pos(
            [
                'frameworkid' => $framework->id,
                'fullname' => 'Senior executive',
                'shortname' => 'se'
            ]
        );

        $assignment = assignment::create(
            $container,
            position_assignment_type::get_code(),
            $position->id
        );

        // Update position
        position_entity::repository()
            ->where('id', $position->id)
            ->update(['fullname' => 'New management']);
        $new_position = new position_entity($position->id);
        position_updated::create_from_old_and_new($new_position->to_record(), $position)->trigger();


        $assignment = new mod_approval\entity\assignment\assignment($assignment->id);
        $this->assertEquals('New management', $assignment->name);
    }

    public function test_cohort_assignment_names_are_updated() {
        $container = $this->get_workflow_container();
        $cohort = $this->getDataGenerator()->create_cohort([
            'name' => 'Senior executive',
            'idnumber' => 'se',
        ]);
        $assignment = assignment::create(
            $container,
            cohort_assignment_type::get_code(),
            $cohort->id
        );

        // Update the cohort name
        $cohort->name = 'New management';
        cohort_update_cohort($cohort);

        $assignment = new mod_approval\entity\assignment\assignment($assignment->id);
        $this->assertEquals('New management', $assignment->name);
    }

    /**
     * @return workflow
     */
    private function get_workflow_container(): approval {
        $this->setAdminUser();
        $form = form::create('simple', 'form');
        $workflow = workflow::create(
            workflow_type::create('type'),
            $form,
            'workflow',
            '',
            cohort_assignment_type::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            '1'
        );

        return $workflow->container;
    }
}
