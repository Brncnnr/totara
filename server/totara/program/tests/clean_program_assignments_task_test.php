<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_program
 */

class totara_program_clean_program_assignments_task_testcase extends advanced_testcase {

    private function create_test_data() {
        $data = new \stdClass();

        $data->generator = $this->getDataGenerator();
        $data->programgenerator = $data->generator->get_plugin_generator('totara_program');

        $data->programs[1] = $data->programgenerator->create_program();

        $data->users[1] = $data->generator->create_user();
        $data->users[2] = $data->generator->create_user();
        $data->users[3] = $data->generator->create_user();
        $data->users[4] = $data->generator->create_user();
        $data->users[5] = $data->generator->create_user();

        return $data;
    }

    public function test_missing_cohort() {
        global $DB;

        $this->setAdminUser();
        $data = $this->create_test_data();

        $cohortgenerator = $data->generator->get_plugin_generator('totara_cohort');

        $audience1 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 1']);
        $audience2 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 2']);

        $cohortgenerator->cohort_assign_users($audience1->id, [$data->users[1]->id, $data->users[2]->id]);
        $cohortgenerator->cohort_assign_users($audience2->id, [$data->users[3]->id, $data->users[4]->id]);

        $program1 = $data->programgenerator->create_program();

        $data->programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience1->id);
        $data->programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience2->id);
        $data->programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $data->users[5]->id);

        // Run user assignment task
        $task = new \totara_program\task\user_assignments_task();
        $task->execute();

        $assignments1 = $DB->get_records_menu('prog_assignment', ['programid' => $program1->id], '', 'assignmenttypeid, id');
        $user_assignments = $DB->get_records('prog_user_assignment', ['programid' => $program1->id]);
        $this->assertCount(3, $assignments1);
        $this->assertCount(5, $user_assignments);

        // Directly delete the record to circumvent any nice cleanup
        $DB->delete_records('cohort', ['id' => $audience1->id]);

        // Run the task
        $task = new \totara_program\task\clean_program_assignments_task();
        $task->execute();

        // Ensure the cleanup worked as expected
        $assignments2 = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $user_assignments = $DB->get_records('prog_user_assignment', ['programid' => $program1->id]);
        $this->assertCount(2, $assignments2);
        $this->assertCount(3, $user_assignments);

        $this->assertNotEmpty($assignments2[$assignments1[$audience2->id]]);
        $this->assertNotEmpty($assignments2[$assignments1[$data->users[5]->id]]);

        $audience_user_assignments = $DB->get_records('prog_user_assignment', ['assignmentid' => $assignments1[$audience1->id]]);
        $this->assertCount(0, $audience_user_assignments);
    }

    public function test_missing_position() {
        global $DB;

        $this->setAdminUser();
        $data = $this->create_test_data();

        $hierarchygenerator = $data->generator->get_plugin_generator('totara_hierarchy');

        $posfw = $hierarchygenerator->create_framework('position');
        $pos1record = ['fullname' => 'Pos 1'];
        $positions[1] = $hierarchygenerator->create_hierarchy($posfw->id, 'position', $pos1record);
        $positions[2] = $hierarchygenerator->create_hierarchy($posfw->id, 'position');
        $pos3record = ['fullname' => 'Pos 2', 'parentid' => $positions[1]->id];
        $positions[3] = $hierarchygenerator->create_hierarchy($posfw->id, 'position', $pos3record);

        // Set up job assignments
        $user1ja1 = \totara_job\job_assignment::create_default($data->users[1]->id, array('positionid' => $positions[1]->id));
        $user2ja1 = \totara_job\job_assignment::create_default($data->users[2]->id, array('positionid' => $positions[2]->id));
        $user3ja1 = \totara_job\job_assignment::create_default($data->users[3]->id, array('positionid' => $positions[1]->id));
        $user4ja1 = \totara_job\job_assignment::create_default($data->users[4]->id, array('positionid' => $positions[3]->id));
        $user5ja1 = \totara_job\job_assignment::create_default($data->users[5]->id, array('positionid' => $positions[3]->id));

        $data->programgenerator->assign_to_program($data->programs[1]->id, ASSIGNTYPE_POSITION, $positions[1]->id);
        $data->programgenerator->assign_to_program($data->programs[1]->id, ASSIGNTYPE_POSITION, $positions[2]->id);
        $data->programgenerator->assign_to_program($data->programs[1]->id, ASSIGNTYPE_INDIVIDUAL, $data->users[5]->id);

        // Run user assignment task
        $task = new \totara_program\task\user_assignments_task();
        $task->execute();

        $assignments1 = $DB->get_records_menu('prog_assignment', ['programid' => $data->programs[1]->id], '', 'assignmenttypeid, id');
        $user_assignments = $DB->get_records('prog_user_assignment', ['programid' => $data->programs[1]->id]);
        $this->assertCount(3, $assignments1);
        $this->assertCount(4, $user_assignments);

        $DB->delete_records('pos', ['id' => $positions[1]->id]);

        $task = new \totara_program\task\clean_program_assignments_task();
        $task->execute();

        $assignments2 = $DB->get_records('prog_assignment', ['programid' => $data->programs[1]->id]);
        $user_assignments = $DB->get_records('prog_user_assignment', ['programid' => $data->programs[1]->id]);
        $this->assertCount(2, $assignments2);
        $this->assertCount(2, $user_assignments);

        $this->assertNotEmpty($assignments2[$assignments1[$positions[2]->id]]);
        $this->assertNotEmpty($assignments2[$assignments1[$data->users[5]->id]]);

        $position_user_assignments = $DB->get_records('prog_user_assignment', ['assignmentid' => $assignments1[$positions[1]->id]]);
        $this->assertCount(0, $position_user_assignments);
    }

    public function test_missing_organisation() {
        global $DB;

        $this->setAdminUser();
        $data = $this->create_test_data();

        $hierarchygenerator = $data->generator->get_plugin_generator('totara_hierarchy');

        $orgfw = $hierarchygenerator->create_framework('organisation');
        $org1record = ['fullname' => 'Org 1'];
        $organisations[1] = $hierarchygenerator->create_hierarchy($orgfw->id, 'organisation', $org1record);
        $organisations[2] = $hierarchygenerator->create_hierarchy($orgfw->id, 'organisation');
        $org3record = ['fullname' => 'Org 2', 'parentid' => $organisations[1]->id];
        $organisations[3] = $hierarchygenerator->create_hierarchy($orgfw->id, 'organisation', $org3record);

        // Set up job assignments
        $user1ja1 = \totara_job\job_assignment::create_default($data->users[1]->id, array('organisationid' => $organisations[1]->id));
        $user2ja1 = \totara_job\job_assignment::create_default($data->users[2]->id, array('organisationid' => $organisations[2]->id));
        $user3ja1 = \totara_job\job_assignment::create_default($data->users[3]->id, array('organisationid' => $organisations[1]->id));
        $user4ja1 = \totara_job\job_assignment::create_default($data->users[4]->id, array('organisationid' => $organisations[3]->id));
        $user5ja1 = \totara_job\job_assignment::create_default($data->users[5]->id, array('organisationid' => $organisations[3]->id));

        $data->programgenerator->assign_to_program($data->programs[1]->id, ASSIGNTYPE_ORGANISATION, $organisations[1]->id);
        $data->programgenerator->assign_to_program($data->programs[1]->id, ASSIGNTYPE_ORGANISATION, $organisations[2]->id);
        $data->programgenerator->assign_to_program($data->programs[1]->id, ASSIGNTYPE_INDIVIDUAL, $data->users[5]->id);

        // Run user assignment task
        $task = new \totara_program\task\user_assignments_task();
        $task->execute();

        $assignments1 = $DB->get_records_menu('prog_assignment', ['programid' => $data->programs[1]->id], '', 'assignmenttypeid, id');
        $user_assignments = $DB->get_records('prog_user_assignment', ['programid' => $data->programs[1]->id]);
        $this->assertCount(3, $assignments1);
        $this->assertCount(4, $user_assignments);

        $DB->delete_records('org', ['id' => $organisations[1]->id]);

        $task = new \totara_program\task\clean_program_assignments_task();
        $task->execute();

        $assignments2 = $DB->get_records('prog_assignment', ['programid' => $data->programs[1]->id]);
        $user_assignments = $DB->get_records('prog_user_assignment', ['programid' => $data->programs[1]->id]);
        $this->assertCount(2, $assignments2);
        $this->assertCount(2, $user_assignments);

        $this->assertNotEmpty($assignments2[$assignments1[$organisations[2]->id]]);
        $this->assertNotEmpty($assignments2[$assignments1[$data->users[5]->id]]);

        $organisation_user_assignments = $DB->get_records('prog_user_assignment', ['assignmentid' => $assignments1[$organisations[1]->id]]);
        $this->assertCount(0, $organisation_user_assignments);
    }

    public function test_missing_manager_hierarchy() {
        global $DB;

        $this->setAdminUser();
        $data = $this->create_test_data();

        // Create some managers
        $managers[1] = $data->generator->create_user(['firstname' => 'Manager', 'lastname' => 'One']);
        $managers[2] = $data->generator->create_user(['firstname' => 'Manager', 'lastname' => 'Two']);
        $managers[3] = $data->generator->create_user(['firstname' => 'Manager', 'lastname' => 'Three']);

        // Set manager for managers[1] to be managers[2]
        $managerjas[1] = \totara_job\job_assignment::create_default($managers[1]->id, ['fullname' => 'Main Job']);
        $managerjas[2] = \totara_job\job_assignment::create_default($managers[2]->id, ['managerjaid' => $managerjas[1]->id]);

        // Set up job assignments
        $user1ja1 = \totara_job\job_assignment::create_default($data->users[1]->id, ['managerjaid' => $managerjas[1]->id]);
        $user2ja1 = \totara_job\job_assignment::create_default($data->users[2]->id, ['managerjaid' => $managerjas[1]->id]);
        $user3ja1 = \totara_job\job_assignment::create_default($data->users[3]->id, ['managerjaid' => $managerjas[2]->id]);
        $user4ja1 = \totara_job\job_assignment::create_default($data->users[4]->id, ['managerjaid' => $managerjas[2]->id]);

        $data->programgenerator->assign_to_program($data->programs[1]->id, ASSIGNTYPE_MANAGERJA, $managerjas[1]->id);
        $data->programgenerator->assign_to_program($data->programs[1]->id, ASSIGNTYPE_MANAGERJA, $managerjas[2]->id);
        $data->programgenerator->assign_to_program($data->programs[1]->id, ASSIGNTYPE_INDIVIDUAL, $data->users[5]->id);

        // Run user assignment task
        $task = new \totara_program\task\user_assignments_task();
        $task->execute();

        $assignments1 = $DB->get_records_menu('prog_assignment', ['programid' => $data->programs[1]->id], '', 'assignmenttypeid, id');
        $user_assignments = $DB->get_records('prog_user_assignment', ['programid' => $data->programs[1]->id]);
        $this->assertCount(3, $assignments1);
        $this->assertCount(6, $user_assignments);

        $DB->delete_records('job_assignment', ['id' => $managerjas[1]->id]);

        $task = new \totara_program\task\clean_program_assignments_task();
        $task->execute();

        $assignments2 = $DB->get_records('prog_assignment', ['programid' => $data->programs[1]->id]);
        $user_assignments = $DB->get_records('prog_user_assignment', ['programid' => $data->programs[1]->id]);
        $this->assertCount(2, $assignments2);
        $this->assertCount(3, $user_assignments);

        $this->assertNotEmpty($assignments2[$assignments1[$managerjas[2]->id]]);
        $this->assertNotEmpty($assignments2[$assignments1[$data->users[5]->id]]);

        $manager_user_assignments = $DB->get_records('prog_user_assignment', ['assignmentid' => $assignments1[$managerjas[1]->id]]);
        $this->assertCount(0, $manager_user_assignments);
    }

    public function test_missing_user() {
        global $DB;

        $this->setAdminUser();
        $data = $this->create_test_data();

        $data->programgenerator->assign_to_program($data->programs[1]->id, ASSIGNTYPE_INDIVIDUAL, $data->users[1]->id);
        $data->programgenerator->assign_to_program($data->programs[1]->id, ASSIGNTYPE_INDIVIDUAL, $data->users[2]->id);
        $data->programgenerator->assign_to_program($data->programs[1]->id, ASSIGNTYPE_INDIVIDUAL, $data->users[3]->id);
        $data->programgenerator->assign_to_program($data->programs[1]->id, ASSIGNTYPE_INDIVIDUAL, $data->users[4]->id);

        // Run user assignment task
        $task = new \totara_program\task\user_assignments_task();
        $task->execute();

        $assignments1 = $DB->get_records_menu('prog_assignment', ['programid' => $data->programs[1]->id], '', 'assignmenttypeid, id');
        $user_assignments = $DB->get_records('prog_user_assignment', ['programid' => $data->programs[1]->id]);
        $this->assertCount(4, $assignments1);
        $this->assertCount(4, $user_assignments);

        // Mark user record as deleted
        $todb = new \stdClass();
        $todb->id = $data->users[4]->id;
        $todb->deleted = 1;
        $DB->update_record('user', $todb);

        // Delete user directly
        $DB->delete_records('user', ['id' => $data->users[3]->id]);

        $task = new \totara_program\task\clean_program_assignments_task();
        $task->execute();

        $assignments2 = $DB->get_records('prog_assignment', ['programid' => $data->programs[1]->id]);
        $user_assignments = $DB->get_records('prog_user_assignment', ['programid' => $data->programs[1]->id]);
        $this->assertCount(2, $assignments2);
        $this->assertCount(2, $user_assignments);

        $this->assertNotEmpty($assignments2[$assignments1[$data->users[1]->id]]);
        $this->assertNotEmpty($assignments2[$assignments1[$data->users[2]->id]]);

        $individual_user_assignments = $DB->get_records('prog_user_assignment', ['assignmentid' => $assignments1[$data->users[4]->id]]);
        $this->assertCount(0, $individual_user_assignments);

        $individual_user_assignments = $DB->get_records('prog_user_assignment', ['assignmentid' => $assignments1[$data->users[3]->id]]);
        $this->assertCount(0, $individual_user_assignments);
    }

    public function test_missing_plan() {
        global $DB;

        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');
        $plan_generator = $generator->get_plugin_generator('totara_plan');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Create plan1 for user1, that contains program1 and program2
        $planrecord = $plan_generator->create_learning_plan(array('userid' => $user1->id, 'enddate' => time() + DAYSECS));
        $plan1 = new development_plan($planrecord->id);
        $plan1->initialize_settings();
        $component_program = $plan1->get_component('program');
        $component_program->assign_new_item($program1->id);
        $component_program->assign_new_item($program2->id);
        $plan1->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_plan\event\approval_approved::create_from_plan($plan1)->trigger();

        // Create plan2 for user2, that contains program1 and program2
        $planrecord = $plan_generator->create_learning_plan(array('userid' => $user2->id, 'enddate' => time() + DAYSECS));
        $plan2 = new development_plan($planrecord->id);
        $plan2->initialize_settings();
        $component_program = $plan2->get_component('program');
        $component_program->assign_new_item($program1->id);
        $component_program->assign_new_item($program2->id);
        $plan2->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_plan\event\approval_approved::create_from_plan($plan2)->trigger();

        self::assertCount(4, $DB->get_records('prog_assignment'));
        self::assertCount(4, $DB->get_records('prog_user_assignment'));
        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $user1->id, 'programid' => $program1->id]));
        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $user1->id, 'programid' => $program2->id]));
        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $user2->id, 'programid' => $program1->id]));
        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $user2->id, 'programid' => $program2->id]));

        // Delete plan1 directly.
        $DB->delete_records('dp_plan', ['id' => $plan1->id]);

        $task = new \totara_program\task\clean_program_assignments_task();
        $task->execute();

        self::assertCount(2, $DB->get_records('prog_assignment'));
        self::assertCount(2, $DB->get_records('prog_user_assignment'));
        self::assertCount(2, $DB->get_records('prog_user_assignment', ['userid' => $user2->id]));
        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $user2->id, 'programid' => $program1->id]));
        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $user2->id, 'programid' => $program2->id]));
    }
}