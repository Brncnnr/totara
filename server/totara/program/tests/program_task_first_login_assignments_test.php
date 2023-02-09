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

class totara_program_program_task_first_login_assignments_testcase extends advanced_testcase {

    public function test_execute() {
        global $DB;

        $generator = $this->getDataGenerator();
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        $individualtype = \totara_program\assignment\individual::ASSIGNTYPE_INDIVIDUAL;

        $user1 = $generator->create_user();
        $programgenerator->assign_to_program($program1->id, $individualtype, $user1->id);
        $programgenerator->assign_to_program($program2->id, $individualtype, $user1->id);

        $completionevent = COMPLETION_EVENT_FIRST_LOGIN;
        // This format is totally crazy but means 14 days / 2 weeks
        $completiontime = '14 ' . \totara_program\utils::TIME_SELECTOR_DAYS;

        $assignmenttoprog = prog_assignments::factory($individualtype);

        // Update assignment 1 with first login event
        $data = new \stdClass();
        $data->id = $program1->id;
        $data->item = array($individualtype => array($user1->id => 1));
        $data->completiontime = array($individualtype => array($user1->id => $completiontime));
        $data->completionevent = array($individualtype => array($user1->id => $completionevent));

        $assignmenttoprog->update_assignments($data, false);

        // Update assignment for program 2 as well
        $completiontime = '21 ' . \totara_program\utils::TIME_SELECTOR_DAYS;
        $data = new \stdClass();
        $data->id = $program2->id;
        $data->item = array($individualtype => array($user1->id => 1));
        $data->completiontime = array($individualtype => array($user1->id => $completiontime));
        $data->completionevent = array($individualtype => array($user1->id => $completionevent));

        $assignmenttoprog->update_assignments($data, false);

        // Update program 1
        $program1 = new \program($program1->id);
        $program1->update_learner_assignments(true);

        // Update program 2
        $program2 = new \program($program2->id);
        $program2->update_learner_assignments(true);

        // Fake user login by updating firstaccess for the user
        $todb = new \stdClass();
        $todb->id = $user1->id;
        $firstaccess = time() - 60;
        $todb->firstaccess = $firstaccess;
        $DB->update_record('user', $todb);

        $this->assertEquals(2, $DB->count_records('prog_future_user_assignment'));

        $task = new \totara_program\task\first_login_assignments_task();
        $task->execute();

        $this->assertEquals(0, $DB->count_records('prog_future_user_assignment'));

        $prog1_completion_record = $DB->get_record('prog_completion', ['programid' => $program1->id, 'userid' => $user1->id, 'coursesetid' => 0]);
        // Use 70 here instead of 60 to prevent random unit test failures
        $expected_timedue = strtotime('+2 weeks') - 70;
        $this->assertGreaterThanOrEqual($expected_timedue, $prog1_completion_record->timedue);

        $prog2_completion_record = $DB->get_record('prog_completion', ['programid' => $program2->id, 'userid' => $user1->id, 'coursesetid' => 0]);
        // Use 70 here instead of 60 to prevent random unit test failures
        $expected_timedue = strtotime('+3 weeks') - 70;
        $this->assertGreaterThanOrEqual($expected_timedue, $prog2_completion_record->timedue);
    }
}
