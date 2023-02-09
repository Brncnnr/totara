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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_program
 */

use \totara_program\assignment\helper;

class totara_program_program_assignment_plans_testcase extends advanced_testcase {

    private $generator        = null;
    private $programgenerator = null;
    private $plangenerator    = null;

    public function tearDown(): void {
        $this->generator        = null;
        $this->programgenerator = null;
        $this->plangenerator    = null;

        $this->programs         = [];
        $this->users            = [];
        $this->plans            = [];

        parent::tearDown();
    }

    private function create_plan_data() {
        $this->generator = $this->getDataGenerator();
        $this->programgenerator = $this->generator->get_plugin_generator('totara_program');
        $this->plan_generator = $this->generator->get_plugin_generator('totara_plan');
        $this->programs[1] = $this->programgenerator->create_program();
        $this->programs[2] = $this->programgenerator->create_program();
        $this->users[1] = $this->generator->create_user();
        $this->users[2] = $this->generator->create_user();
        $this->users[3] = $this->generator->create_user();

        // Create plan1 for user1, that contains program1
        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $this->users[1]->id, 'enddate' => time() + DAYSECS));
        $this->plans[1] = new development_plan($planrecord->id);
        $this->plans[1]->initialize_settings();
        $component_program = $this->plans[1]->get_component('program');
        $component_program->assign_new_item($this->programs[1]->id);

        // Create plan2 for user2, that contains program1 and program2
        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $this->users[2]->id, 'enddate' => time() + DAYSECS));
        $this->plans[2] = new development_plan($planrecord->id);
        $this->plans[2]->initialize_settings();
        $component_program = $this->plans[2]->get_component('program');
        $component_program->assign_new_item($this->programs[1]->id);
        $component_program->assign_new_item($this->programs[2]->id);

        // Create plan3 for user3, that contains no programs
        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $this->users[3]->id, 'enddate' => time() + DAYSECS));
        $this->plans[3] = new development_plan($planrecord->id);
    }

    public function test_show_in_ui() {
        self::setAdminUser();
        $this->create_plan_data();
        $assignment = \totara_program\assignment\base::create_from_instance_id($this->programs[1]->id, ASSIGNTYPE_PLAN, $this->plans[1]->id);
        self::assertFalse($assignment::show_in_ui());
        self::assertArrayHasKey($assignment->get_type(), helper::get_types());
        self::assertArrayNotHasKey($assignment->get_type(), helper::get_types_with_ui());
        self::assertArrayHasKey($assignment->get_type(), helper::get_types_with_ui(true));
    }

    public function test_can_be_updated() {
        self::setAdminUser();
        $this->create_plan_data();
        $assignment = \totara_program\assignment\base::create_from_instance_id($this->programs[1]->id, ASSIGNTYPE_PLAN, $this->plans[1]->id);
        self::assertTrue($assignment::can_be_updated($this->programs[1]->id));

        self::setUser($this->users[1]);
        self::assertTrue($assignment::can_be_updated($this->programs[1]->id));
    }

    public function test_get_type() {
        self::setAdminUser();

        $this->create_plan_data();
        $assignment = \totara_program\assignment\base::create_from_instance_id($this->programs[1]->id, ASSIGNTYPE_PLAN, $this->plans[1]->id);
        $this->assertEquals(7, $assignment->get_type($assignment->get_type()));
    }

    public function test_get_type_name() {
        self::setAdminUser();

        $this->create_plan_data();
        $assignment = \totara_program\assignment\base::create_from_instance_id($this->programs[1]->id, ASSIGNTYPE_PLAN, $this->plans[1]->id);
        $this->assertEquals('plan', helper::get_type_name($assignment->get_type()));
    }

    public function test_get_type_string() {
        self::setAdminUser();

        $this->create_plan_data();
        $assignment = \totara_program\assignment\base::create_from_instance_id($this->programs[1]->id, ASSIGNTYPE_PLAN, $this->plans[1]->id);
        $this->assertEquals(get_string('plan', 'totara_program'), helper::get_type_string($assignment->get_type()));
    }

    public function test_create_from_instance_id() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        // There should not be a record, the plan is not approved.
        $this->assertEquals(0, $DB->count_records('prog_assignment'));

        $this->plans[1]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[1]->id, $this->plans[1]->id);

        // There should now be one record as the plan has been approved.
        $this->assertEquals(1, $DB->count_records('prog_assignment'));

        $assignment = \totara_program\assignment\base::create_from_instance_id($this->programs[1]->id, ASSIGNTYPE_PLAN, $this->plans[1]->id);
        $assignment->save();

        self::assertInstanceOf('\totara_program\assignment\plan', $assignment);

        $reflection = new ReflectionClass('\totara_program\assignment\plan');

        $property = $reflection->getProperty('typeid');
        $property->setAccessible(true);
        $this->assertEquals(7, $property->getValue($assignment));

        $property = $reflection->getProperty('instanceid');
        $property->setAccessible(true);
        self::assertEquals($this->plans[1]->id, $property->getValue($assignment));

        $assignment_record = $DB->get_record('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_PLAN, 'assignmenttypeid' => $this->plans[1]->id]);
        self::assertEquals('-1', $assignment_record->completiontime);
        self::assertEquals(0, $assignment_record->completionevent);
        self::assertEquals(0, $assignment_record->completioninstance);

        $completion_records = $DB->get_records('prog_completion', [
            'userid' => $this->users[1]->id,
            'programid' => $this->programs[1]->id,
            'coursesetid' => '0',
            'status' => '0',
            'timedue' => '-1',
        ]);

        self::assertCount(1, $completion_records);
    }

    public function test_create_from_id() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        // There should not be a record, the plan is not approved.
        $this->assertEquals(0, $DB->count_records('prog_assignment'));

        $this->plans[1]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[1]->id, $this->plans[1]->id);

        // There should now be one record as the plan has been approved.
        $this->assertEquals(1, $DB->count_records('prog_assignment'));

        $assignments = $DB->get_records('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        $record = reset($assignments);

        $assignment = \totara_program\assignment\plan::create_from_id($record->id);

        $this->assertInstanceOf(\totara_program\assignment\plan::class, $assignment);

        $reflection = new ReflectionClass('\totara_program\assignment\individual');
        $property = $reflection->getProperty('typeid');
        $property->setAccessible(true);
        $this->assertEquals(7, $property->getValue($assignment));

        $property = $reflection->getProperty('instanceid');
        $property->setAccessible(true);
        $this->assertEquals($this->plans[1]->id, $property->getValue($assignment));

        $completion_records = $DB->get_records('prog_completion', [
            'userid' => $this->users[1]->id,
            'programid' => $this->programs[1]->id,
            'coursesetid' => '0',
            'status' => '0',
            'timedue' => '-1',
        ]);

        self::assertCount(1, $completion_records);
    }

    public function test_get_name() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        $this->plans[1]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[1]->id, $this->plans[1]->id);

        $assignments = $DB->get_records('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        $record = reset($assignments);

        $assignment = \totara_program\assignment\plan::create_from_id($record->id);

        self::assertEquals($this->plans[1]->name, $assignment->get_name());
    }

    public function test_get_programid() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        $this->plans[1]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[1]->id, $this->plans[1]->id);

        $assignments = $DB->get_records('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        $record = reset($assignments);

        $assignment = \totara_program\assignment\plan::create_from_id($record->id);

        self::assertEquals($this->programs[1]->id, $assignment->get_programid());
    }

    public function test_get_instanceid() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        $this->plans[1]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[1]->id, $this->plans[1]->id);

        $assignments = $DB->get_records('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        $record = reset($assignments);

        $assignment = \totara_program\assignment\plan::create_from_id($record->id);

        self::assertEquals($this->plans[1]->id, $assignment->get_instanceid());
    }

    public function test_ensure_program_loaded() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        $this->plans[1]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[1]->id, $this->plans[1]->id);

        $assignments = $DB->get_records('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        $record = reset($assignments);

        $assignment = \totara_program\assignment\plan::create_from_id($record->id);

        $reflection = new ReflectionClass('\totara_program\assignment\plan');
        $property = $reflection->getProperty('program');
        $property->setAccessible(true);
        self::assertNull($property->getValue($assignment));

        $reflection = new ReflectionClass('\totara_program\assignment\plan');
        $method = $reflection->getMethod('ensure_program_loaded');
        $method->setAccessible(true);
        $method->invokeArgs($assignment, []);

        $actual = $property->getValue($assignment);
        self::assertNotNull($actual);
        self::assertEquals($this->programs[1]->id, $actual->id);
        self::assertEquals($this->programs[1]->fullname, $actual->fullname);
    }

    public function test_ensure_category_loaded() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        $this->plans[1]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[1]->id, $this->plans[1]->id);

        $assignments = $DB->get_records('prog_assignment', ['programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        $record = reset($assignments);

        $assignment = \totara_program\assignment\plan::create_from_id($record->id);

        $reflection = new ReflectionClass('\totara_program\assignment\plan');
        $property = $reflection->getProperty('category');
        $property->setAccessible(true);
        self::assertNull($property->getValue($assignment));

        $reflection = new ReflectionClass('\totara_program\assignment\plan');
        $method = $reflection->getMethod('ensure_category_loaded');
        $method->setAccessible(true);
        $method->invokeArgs($assignment, []);

        $actual = $property->getValue($assignment);
        self::assertNotNull($actual);
        self::assertInstanceOf('plans_category', $actual);
    }

    public function test_get_user_count() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        // Plan1 for user1, that contains program1
        $assignments = $DB->get_records('prog_assignment', ['assignmenttypeid' => $this->plans[1]->id, 'programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        self::assertCount(0, $assignments);
        $this->plans[1]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[1]->id, $this->plans[1]->id);
        $assignments = $DB->get_records('prog_assignment', ['assignmenttypeid' => $this->plans[1]->id, 'programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        self::assertCount(1, $assignments);
        $record = reset($assignments);
        $assignment = \totara_program\assignment\plan::create_from_id($record->id);
        self::assertEquals(1, $assignment->get_user_count());
        $assignment = \totara_program\assignment\plan::create_from_instance_id($this->programs[1]->id, ASSIGNTYPE_PLAN, $record->assignmenttypeid);
        self::assertEquals(1, $assignment->get_user_count());

        // Plan2 for user2, that contains program1 and programs2
        $assignments = $DB->get_records('prog_assignment', ['assignmenttypeid' => $this->plans[2]->id, 'programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        self::assertCount(0, $assignments);
        $assignments = $DB->get_records('prog_assignment', ['assignmenttypeid' => $this->plans[2]->id, 'programid' => $this->programs[2]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        self::assertCount(0, $assignments);
        $this->plans[2]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[2]->id, $this->plans[2]->id);
        $assignments = $DB->get_records('prog_assignment', ['assignmenttypeid' => $this->plans[2]->id, 'programid' => $this->programs[1]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        self::assertCount(1, $assignments);
        $record = reset($assignments);
        $assignment = \totara_program\assignment\plan::create_from_id($record->id);
        self::assertEquals(1, $assignment->get_user_count());
        $assignment = \totara_program\assignment\plan::create_from_instance_id($this->programs[1]->id, ASSIGNTYPE_PLAN, $record->assignmenttypeid);
        self::assertEquals(1, $assignment->get_user_count());

        $assignments = $DB->get_records('prog_assignment', ['assignmenttypeid' => $this->plans[2]->id, 'programid' => $this->programs[2]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        self::assertCount(1, $assignments);
        $record = reset($assignments);
        $assignment = \totara_program\assignment\plan::create_from_id($record->id);
        self::assertEquals(1, $assignment->get_user_count());
        $assignment = \totara_program\assignment\plan::create_from_instance_id($this->programs[2]->id, ASSIGNTYPE_PLAN, $record->assignmenttypeid);
        self::assertEquals(1, $assignment->get_user_count());

        // Plan3 for user3, that contains no programs
        $assignments = $DB->get_records('prog_assignment', ['assignmenttypeid' => $this->plans[3]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        self::assertCount(0, $assignments);
        $this->plans[3]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[3]->id, $this->plans[3]->id);
        $assignments = $DB->get_records('prog_assignment', ['assignmenttypeid' => $this->plans[3]->id, 'assignmenttype' => ASSIGNTYPE_PLAN]);
        self::assertCount(0, $assignments);
    }

    public function test_get_users_plan_programs() {
        self::setAdminUser();

        $this->create_plan_data();

        //
        // Plan 1 is for user 1 who has program1 assigned.
        //
        $this->plans[1]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[1]->id, $this->plans[1]->id);

        $users_plan_programs = \totara_program\assignment\plan::get_users_plan_programs($this->users[1]->id, $this->plans[1]->id);
        self::assertCount(1, $users_plan_programs);
        self::assertTrue(in_array($this->programs[1]->id, $users_plan_programs));

        //
        // Plan 2 is for user 2 who has program1 and program2 assigned.
        //
        $this->plans[2]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[2]->id, $this->plans[2]->id);

        $users_plan_programs = \totara_program\assignment\plan::get_users_plan_programs($this->users[2]->id, $this->plans[2]->id);
        self::assertCount(2, $users_plan_programs);
        self::assertTrue(in_array($this->programs[1]->id, $users_plan_programs));
        self::assertTrue(in_array($this->programs[2]->id, $users_plan_programs));

        //
        // Plan 3 is for user 3 who has no programs assigned.
        //
        $this->plans[2]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[3]->id, $this->plans[3]->id);

        $users_plan_programs = \totara_program\assignment\plan::get_users_plan_programs($this->users[3]->id, $this->plans[3]->id);
        self::assertCount(0, $users_plan_programs);
    }

    public function test_get_user_assignments() {
        self::setAdminUser();

        $this->create_plan_data();

        $category = \prog_assignments::factory(ASSIGNTYPE_PLAN);

        //
        // Plan 1 is for user 1 who has program1 assigned.
        //
        $this->plans[1]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[1]->id, $this->plans[1]->id);

        $user_assignments = \totara_program\assignment\plan::get_user_assignments($this->users[1]->id, $this->plans[1]->id);
        self::assertCount(1, $user_assignments);
        self::assertTrue(in_array($this->programs[1]->id, $user_assignments));
        foreach ($user_assignments as $assignmentid => $programid) {
            $assignment = \totara_program\assignment\plan::create_from_id($assignmentid);
            self::assertEquals(ASSIGNTYPE_PLAN, $assignment->get_type());
            self::assertEquals($programid, $assignment->get_programid());
            self::assertEquals($this->plans[1]->id, $assignment->get_instanceid());
            self::assertEquals(1, $assignment->get_user_count());

            // Get effected user.
            $item = new \stdClass();
            $item->id = $assignmentid;
            $item->programid = $programid;
            $item->assignmenttypeid = $assignment->get_instanceid();
            $users = $category->get_affected_users_by_assignment($item);

            self::assertCount(1, $users);
            self::assertEquals($this->users[1]->id, current($users)->id);
        }

        //
        // Plan 2 is for user 2 who has program1 and program2 assigned.
        //
        $this->plans[2]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[2]->id, $this->plans[2]->id);

        $user_assignments = \totara_program\assignment\plan::get_user_assignments($this->users[2]->id, $this->plans[2]->id);
        self::assertCount(2, $user_assignments);
        self::assertTrue(in_array($this->programs[1]->id, $user_assignments));
        self::assertTrue(in_array($this->programs[2]->id, $user_assignments));
        foreach ($user_assignments as $assignmentid => $programid) {
            $assignment = \totara_program\assignment\plan::create_from_id($assignmentid);
            self::assertEquals(ASSIGNTYPE_PLAN, $assignment->get_type());
            self::assertEquals($programid, $assignment->get_programid());
            self::assertEquals($this->plans[2]->id, $assignment->get_instanceid());
            self::assertEquals(1, $assignment->get_user_count());

            // Get effected user.
            $item = new \stdClass();
            $item->id = $assignmentid;
            $item->programid = $programid;
            $item->assignmenttypeid = $assignment->get_instanceid();
            $users = $category->get_affected_users_by_assignment($item);

            self::assertCount(1, $users);
            self::assertEquals($this->users[2]->id, current($users)->id);
        }

        //
        // Plan 3 is for user 3 who has no programs assigned.
        //
        $this->plans[2]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_program\assignment\plan::update_plan_assignments($this->users[3]->id, $this->plans[3]->id);

        $users_plan_programs = \totara_program\assignment\plan::get_users_plan_programs($this->users[3]->id, $this->plans[3]->id);
        self::assertCount(0, $users_plan_programs);
    }

    public function test_event_approval_approved() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        self::assertCount(0, $DB->get_records('prog_assignment'));
        self::assertCount(0, $DB->get_records('prog_user_assignment'));
        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));

        // Plan 1 is for user 1 who has program1 assigned.
        $this->plans[1]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_plan\event\approval_approved::create_from_plan($this->plans[1])->trigger();

        $prog_assignment = $DB->get_records('prog_assignment', [
            'programid' => $this->programs[1]->id,
            'assignmenttype' => ASSIGNTYPE_PLAN,
            'assignmenttypeid' => $this->plans[1]->id
        ]);
        self::assertCount(1, $prog_assignment);

        $prog_user_assignment = $DB->get_records('prog_user_assignment', [
            'userid' => $this->users[1]->id,
            'programid' => $this->programs[1]->id,
            'assignmentid' => current($prog_assignment)->id
        ]);
        self::assertCount(1, $prog_user_assignment);

        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));
    }

    public function test_event_approval_declined() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        self::assertCount(0, $DB->get_records('prog_assignment'));
        self::assertCount(0, $DB->get_records('prog_user_assignment'));
        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));

        // Plan 1 is for user 1 who has program1 assigned.
        $this->plans[1]->set_status(DP_PLAN_STATUS_APPROVED);
        \totara_plan\event\approval_approved::create_from_plan($this->plans[1])->trigger();

        $prog_assignment = $DB->get_records('prog_assignment', [
            'programid' => $this->programs[1]->id,
            'assignmenttype' => ASSIGNTYPE_PLAN,
            'assignmenttypeid' => $this->plans[1]->id
        ]);
        self::assertCount(1, $prog_assignment);

        $prog_user_assignment = $DB->get_records('prog_user_assignment', [
            'userid' => $this->users[1]->id,
            'programid' => $this->programs[1]->id,
            'assignmentid' => current($prog_assignment)->id
        ]);
        self::assertCount(1, $prog_user_assignment);

        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));

        $this->plans[1]->set_status(DP_PLAN_STATUS_UNAPPROVED);
        \totara_plan\event\approval_approved::create_from_plan($this->plans[1])->trigger();

        self::assertCount(0, $DB->get_records('prog_assignment'));
        self::assertCount(0, $DB->get_records('prog_user_assignment'));
        self::assertCount(0, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));
    }

    public function test_event_component_created() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $this->users[1]->id, 'enddate' => time() + DAYSECS));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);

        self::assertCount(0, $DB->get_records('prog_assignment'));
        self::assertCount(0, $DB->get_records('prog_user_assignment'));
        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));

        $plan->initialize_settings();
        $component_program = $plan->get_component('program');
        $component_program->assign_new_item($this->programs[1]->id);

        \totara_plan\event\component_created::create_from_component($plan, 'program', $this->programs[1]->id, 'test program')->trigger();

        $prog_assignment = $DB->get_records('prog_assignment', [
            'programid' => $this->programs[1]->id,
            'assignmenttype' => ASSIGNTYPE_PLAN,
            'assignmenttypeid' => $plan->id
        ]);
        self::assertCount(1, $prog_assignment);

        $prog_user_assignment = $DB->get_records('prog_user_assignment', [
            'userid' => $this->users[1]->id,
            'programid' => $this->programs[1]->id,
            'assignmentid' => current($prog_assignment)->id
        ]);
        self::assertCount(1, $prog_user_assignment);

        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));
    }

    public function test_event_component_deleted() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $this->users[1]->id, 'enddate' => time() + DAYSECS));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);

        self::assertCount(0, $DB->get_records('prog_assignment'));
        self::assertCount(0, $DB->get_records('prog_user_assignment'));
        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));

        $plan->initialize_settings();
        $component_program = $plan->get_component('program');
        $component_program->assign_new_item($this->programs[1]->id);

        \totara_plan\event\component_created::create_from_component($plan, 'program', $this->programs[1]->id, 'test program')->trigger();

        $prog_assignment = $DB->get_records('prog_assignment', [
            'programid' => $this->programs[1]->id,
            'assignmenttype' => ASSIGNTYPE_PLAN,
            'assignmenttypeid' => $plan->id
        ]);
        self::assertCount(1, $prog_assignment);

        $prog_user_assignment = $DB->get_records('prog_user_assignment', [
            'userid' => $this->users[1]->id,
            'programid' => $this->programs[1]->id,
            'assignmentid' => current($prog_assignment)->id
        ]);
        self::assertCount(1, $prog_user_assignment);

        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));

        $item = $DB->get_record('dp_plan_program_assign', ['planid' => $plan->id, 'programid' => $this->programs[1]->id]);
        $component_program->unassign_item($item);

        \totara_plan\event\component_deleted::create_from_component($plan, 'program', $this->programs[1]->id, 'test program')->trigger();

        self::assertCount(0, $DB->get_records('prog_assignment'));
        self::assertCount(0, $DB->get_records('prog_user_assignment'));
        self::assertCount(0, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));
    }

    public function test_event_plan_completed() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $this->users[1]->id, 'enddate' => time() + DAYSECS));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);

        self::assertCount(0, $DB->get_records('prog_assignment'));
        self::assertCount(0, $DB->get_records('prog_user_assignment'));
        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));

        $plan->initialize_settings();
        $component_program = $plan->get_component('program');
        $component_program->assign_new_item($this->programs[1]->id);

        \totara_plan\event\component_created::create_from_component($plan, 'program', $this->programs[1]->id, 'test program')->trigger();

        $prog_assignment = $DB->get_records('prog_assignment', [
            'programid' => $this->programs[1]->id,
            'assignmenttype' => ASSIGNTYPE_PLAN,
            'assignmenttypeid' => $plan->id
        ]);
        self::assertCount(1, $prog_assignment);

        $prog_user_assignment = $DB->get_records('prog_user_assignment', [
            'userid' => $this->users[1]->id,
            'programid' => $this->programs[1]->id,
            'assignmentid' => current($prog_assignment)->id
        ]);
        self::assertCount(1, $prog_user_assignment);

        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));

        $plan->set_status(DP_PLAN_STATUS_COMPLETE, DP_PLAN_REASON_MANUAL_COMPLETE);

        \totara_plan\event\plan_completed::create_from_plan($plan)->trigger();

        self::assertCount(0, $DB->get_records('prog_assignment'));
        self::assertCount(0, $DB->get_records('prog_user_assignment'));
        self::assertCount(0, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));
    }

    public function test_event_plan_reactivated() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $this->users[1]->id, 'enddate' => time() + DAYSECS));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);

        self::assertCount(0, $DB->get_records('prog_assignment'));
        self::assertCount(0, $DB->get_records('prog_user_assignment'));
        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));

        $plan->initialize_settings();
        $component_program = $plan->get_component('program');
        $component_program->assign_new_item($this->programs[1]->id);

        \totara_plan\event\component_created::create_from_component($plan, 'program', $this->programs[1]->id, 'test program')->trigger();

        $prog_assignment = $DB->get_records('prog_assignment', [
            'programid' => $this->programs[1]->id,
            'assignmenttype' => ASSIGNTYPE_PLAN,
            'assignmenttypeid' => $plan->id
        ]);
        self::assertCount(1, $prog_assignment);

        $prog_user_assignment = $DB->get_records('prog_user_assignment', [
            'userid' => $this->users[1]->id,
            'programid' => $this->programs[1]->id,
            'assignmentid' => current($prog_assignment)->id
        ]);
        self::assertCount(1, $prog_user_assignment);

        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));

        $plan->set_status(DP_PLAN_STATUS_COMPLETE, DP_PLAN_REASON_MANUAL_COMPLETE);
        \totara_plan\event\plan_completed::create_from_plan($plan)->trigger();

        self::assertCount(0, $DB->get_records('prog_assignment'));
        self::assertCount(0, $DB->get_records('prog_user_assignment'));
        self::assertCount(0, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));

        $plan->reactivate_plan();
        \totara_plan\event\plan_reactivated::create_from_plan($plan)->trigger();

        $prog_assignment = $DB->get_records('prog_assignment', [
            'programid' => $this->programs[1]->id,
            'assignmenttype' => ASSIGNTYPE_PLAN,
            'assignmenttypeid' => $plan->id
        ]);
        self::assertCount(1, $prog_assignment);

        $prog_user_assignment = $DB->get_records('prog_user_assignment', [
            'userid' => $this->users[1]->id,
            'programid' => $this->programs[1]->id,
            'assignmentid' => current($prog_assignment)->id
        ]);
        self::assertCount(1, $prog_user_assignment);

        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));
    }

    public function test_event_plan_deleted() {
        global $DB;

        self::setAdminUser();

        $this->create_plan_data();

        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $this->users[1]->id, 'enddate' => time() + DAYSECS));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);

        self::assertCount(0, $DB->get_records('prog_assignment'));
        self::assertCount(0, $DB->get_records('prog_user_assignment'));
        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));

        $plan->initialize_settings();
        $component_program = $plan->get_component('program');
        $component_program->assign_new_item($this->programs[1]->id);

        \totara_plan\event\component_created::create_from_component($plan, 'program', $this->programs[1]->id, 'test program')->trigger();

        $prog_assignment = $DB->get_records('prog_assignment', [
            'programid' => $this->programs[1]->id,
            'assignmenttype' => ASSIGNTYPE_PLAN,
            'assignmenttypeid' => $plan->id
        ]);
        self::assertCount(1, $prog_assignment);

        $prog_user_assignment = $DB->get_records('prog_user_assignment', [
            'userid' => $this->users[1]->id,
            'programid' => $this->programs[1]->id,
            'assignmentid' => current($prog_assignment)->id
        ]);
        self::assertCount(1, $prog_user_assignment);

        self::assertCount(1, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));

        $plan->delete();
        \totara_plan\event\plan_deleted::create_from_plan($plan)->trigger();

        self::assertCount(0, $DB->get_records('prog_assignment'));
        self::assertCount(0, $DB->get_records('prog_user_assignment'));
        self::assertCount(0, $DB->get_records('prog_completion', ['userid' => $this->users[1]->id, 'programid' => $this->programs[1]->id]));
    }
}
