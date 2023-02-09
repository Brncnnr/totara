<?php
/*
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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_program
 */

use totara_program\task\recurrence_history_task;

class totara_program_recurrence_history_task_testcase extends advanced_testcase {

    private $users;

    protected function tearDown(): void {
        $this->users = null;
        parent::tearDown();
    }

    public function test_recurrence_history_task_batch_processing() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/totara/program/program.class.php');

        $generator = $this->getDataGenerator();
        /** @var \totara_program\testing\generator $programgenerator */
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $course = $generator->create_course(['enablecompletion' => 1]);

        $program = $programgenerator->create_program();
        $this->add_recurring_courseset($program, $course);

        // Create users and assign users to the programs as individuals..
        for ($i = 1; $i <= 15; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
            $programgenerator->assign_to_program($program->id, ASSIGNTYPE_INDIVIDUAL, $this->users[$i]->id);
            $this->getDataGenerator()->enrol_user($this->users[$i]->id, $course->id, 'student');
        }

        $program->update_learner_assignments(true);
        $this->assertEquals(15, $DB->count_records('user_enrolments'));
        $this->assertEquals(15, $DB->count_records('course_completions'));

        $this->setAdminUser();

        // copy records to completion history table
        $completion_records_history = $DB->get_recordset('prog_completion', []);
        foreach ($completion_records_history as $completion_record) {
            $completion_record->recurringcourseid = $course->id;
            $DB->insert_record('prog_completion_history', $completion_record);
        }

        // Run recurrence history task with batches
        $task = new recurrence_history_task();
        $task->execute();
        $updated_records = $DB->count_records('prog_completion_history', ['status' => STATUS_PROGRAM_INCOMPLETE]);
        $this->assertEquals(15, $updated_records);

        // Now complete some courses
        $completion = new completion_completion(['userid' => $this->users[5]->id, 'course' => $course->id]);
        $completion->mark_complete(time());
        $completion = new completion_completion(['userid' => $this->users[10]->id, 'course' => $course->id]);
        $completion->mark_complete(time());
        $completion = new completion_completion(['userid' => $this->users[15]->id, 'course' => $course->id]);
        $completion->mark_complete(time());

        // Run recurrence history task with batches
        $task = new recurrence_history_task();
        $task->execute();
        $updated_records = $DB->count_records('prog_completion_history', ['status' => STATUS_PROGRAM_INCOMPLETE]);
        $this->assertEquals(12, $updated_records);

        $completed = $DB->get_records('prog_completion_history', ['status' => STATUS_PROGRAM_COMPLETE]);
        $this->assertCount(3, $completed);

        $user_ids = array_column($completed, 'userid');
        $course_ids = array_unique(array_column($completed, 'recurringcourseid'));

        $this->assertEqualsCanonicalizing([$this->users[5]->id, $this->users[10]->id, $this->users[15]->id], $user_ids);
        $this->assertEqualsCanonicalizing([$course->id], $course_ids);
    }

    /**
     * Adds a recurring course to a program.
     *
     * @param stdClass|program $program
     * @param stdClass         $course
     */
    private function add_recurring_courseset($program, $course) {
        $recurringcourseset = new recurring_course_set($program->id);
        $recurringcourseset->course = $course;
        $recurringcourseset->save_set();
    }
}