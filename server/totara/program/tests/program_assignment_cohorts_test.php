<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_program
 */

class totara_program_program_assignment_cohorts_testcase extends advanced_testcase {

    public function test_show_in_ui() {
        $assignment = "\\totara_program\assignment\cohort";
        self::assertTrue($assignment::show_in_ui());
    }

    public function test_can_be_updated() {
        $assignment = "\\totara_program\assignment\cohort";
        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');
        $program1 = $programgenerator->create_program();
        self::setAdminUser();
        self::assertTrue($assignment::can_be_updated($program1->id));

        self::setUser($generator->create_user());
        self::assertFalse($assignment::can_be_updated($program1->id));
    }

    public function test_get_user_count() {
        global $DB;

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();

        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $audience1 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 1']);
        $cohortgenerator->cohort_assign_users($audience1->id, [$user1->id, $user2->id, $user3->id]);

        $audience2 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 2']);
        $cohortgenerator->cohort_assign_users($audience2->id, [$user1->id, $user4->id]);

        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience1->id);
        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience2->id);

        $assign1id = $DB->get_field('prog_assignment', 'id', ['programid' => $program1->id, 'assignmenttype' => 3, 'assignmenttypeid' => $audience1->id]);
        $assignment1 = \totara_program\assignment\cohort::create_from_id($assign1id);
        $assign2id = $DB->get_field('prog_assignment', 'id', ['programid' => $program1->id, 'assignmenttype' => 3, 'assignmenttypeid' => $audience2->id]);
        $assignment2 = \totara_program\assignment\cohort::create_from_id($assign2id);

        $this->assertEquals(3, $assignment1->get_user_count());
        $this->assertEquals(2, $assignment2->get_user_count());
    }

    public function test_get_name() {
        global $DB;

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();

        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $audience1 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 1']);
        $cohortgenerator->cohort_assign_users($audience1->id, [$user1->id, $user2->id, $user3->id]);

        $audience2 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 2']);
        $cohortgenerator->cohort_assign_users($audience2->id, [$user1->id, $user4->id]);

        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience1->id);

        $assign1id = $DB->get_field('prog_assignment', 'id', ['programid' => $program1->id, 'assignmenttype' => 3, 'assignmenttypeid' => $audience1->id]);
        $assignment1 = \totara_program\assignment\cohort::create_from_id($assign1id);

        // Does the name match?
        $this->assertEquals($audience1->name, $assignment1->get_name());
    }

    /**
     * Test to see if user user_assignment records are created
     * correctly for new assignments
     */
    public function test_create_from_instance_id() {
        global $DB, $CFG;

        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();

        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $audience1 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 1']);
        $cohortgenerator->cohort_assign_users($audience1->id, [$user1->id, $user2->id, $user3->id]);

        $program1 = $programgenerator->create_program();

        $cohorttypeid = 3;
        $assignment = \totara_program\assignment\base::create_from_instance_id($program1->id, $cohorttypeid, $audience1->id);
        $assignment->save();

        $this->assertInstanceOf('\totara_program\assignment\cohort', $assignment);

        $reflection = new ReflectionClass('\totara_program\assignment\cohort');
        $property = $reflection->getProperty('typeid');
        $property->setAccessible(true);
        $this->assertEquals(3, $property->getValue($assignment));

        $property = $reflection->getProperty('instanceid');
        $property->setAccessible(true);
        $this->assertEquals($audience1->id, $property->getValue($assignment));

        // Check all the correct records were created.
        $this->assertEquals(1, $DB->count_records('prog_assignment', ['programid' => $program1->id]));
        //$this->assertEquals(3, $DB->count_records('prog_user_assignment', ['programid' => $program1->id]));
        //$this->assertEquals(3, $DB->count_records('prog_completion', ['programid' => $program1->id]));
    }

    public function test_get_type() {
        global $DB;

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();

        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $audience1 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 1']);
        $cohortgenerator->cohort_assign_users($audience1->id, [$user1->id, $user2->id, $user3->id]);

        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience1->id);

        $assignments = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $record = reset($assignments);
        $assignment = \totara_program\assignment\cohort::create_from_id($record->id);
        $this->assertEquals(3, $assignment->get_type());
    }

    public function test_cohort_assignment_delete() {
        global $DB;

        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();

        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $audience1 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 1']);
        $cohortgenerator->cohort_assign_users($audience1->id, [$user1->id, $user2->id]);

        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience1->id);
        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user3->id);

        // Run cron
        $task = new \totara_program\task\user_assignments_task();
        $task->execute();

        $assignments = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $this->assertCount(2, $assignments);
        $user_assignments = $DB->get_records('prog_user_assignment');
        $this->assertCount(3, $user_assignments);

        // Delete record manually to circumvent any clean up
        $DB->delete_records('cohort', ['id' => $audience1->id]);

        $audience_assignment = $DB->get_record('prog_assignment', ['assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience1->id]);
        $assignment = \totara_program\assignment\cohort::create_from_id($audience_assignment->id);
        $assignment->remove();

        $assignments = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $this->assertCount(1, $assignments);
        $assignment = reset($assignments);
        $this->assertEquals(ASSIGNTYPE_INDIVIDUAL, $assignment->assignmenttype);
        $this->assertEquals($user3->id, $assignment->assignmenttypeid);

        $user_assignments = $DB->get_records('prog_user_assignment');
        $this->assertCount(1, $user_assignments);
        $user_assignment = reset($user_assignments);
        $this->assertEquals($user3->id, $user_assignment->userid);
        $this->assertEquals($assignment->id, $user_assignment->assignmentid);
    }

    public function test_program_assignment_cohort_deleted() {
        global $DB;

        $this->setAdminUser();

        // Generate program and cohort assignments
        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');
        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();

        $audience1 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 1']);
        $audience2 = $this->getDataGenerator()->create_cohort(['name' => 'Audience 2']);

        $cohortgenerator->cohort_assign_users($audience1->id, [$user1->id, $user2->id]);
        $cohortgenerator->cohort_assign_users($audience2->id, [$user3->id, $user4->id]);

        $program1 = $programgenerator->create_program();

        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience1->id);
        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_COHORT, $audience2->id);
        $programgenerator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user5->id);

        // Run cron
        $task = new \totara_program\task\user_assignments_task();
        $task->execute();

        $assignments1 = $DB->get_records_menu('prog_assignment', ['programid' => $program1->id], '', 'assignmenttypeid, id');
        $user_assignments = $DB->get_records('prog_user_assignment', ['programid' => $program1->id]);
        $this->assertCount(3, $assignments1);
        $this->assertCount(5, $user_assignments);

        // Delete cohort using the cohort UI
        cohort_delete_cohort($audience1);

        // Check the correct records remain
        $assignments2 = $DB->get_records('prog_assignment', ['programid' => $program1->id]);
        $user_assignments = $DB->get_records('prog_user_assignment', ['programid' => $program1->id]);
        $this->assertCount(2, $assignments2);
        $this->assertCount(3, $user_assignments);

        $this->assertNotEmpty($assignments2[$assignments1[$audience2->id]]);
        $this->assertNotEmpty($assignments2[$assignments1[$user5->id]]);

        $audience_user_assignments = $DB->get_records('prog_user_assignment', ['assignmentid' => $assignments1[$audience1->id]]);
        $this->assertCount(0, $audience_user_assignments);
    }
}
