<?php
/*
 * This file is part of Totara LMS
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

use core\format;
use totara_webapi\phpunit\webapi_phpunit_helper;

global $CFG;
require_once($CFG->dirroot . '/totara/program/program.class.php');

/**
 * Tests the totara core program type resolver.
 */
class totara_program_webapi_resolver_type_program_completion_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private function resolve($field, $completion, array $args = []) {
        $excontext = $this->get_execution_context();
        if (!empty($completion->programid)) {
            $pcontext = \context_program::instance($completion->programid);
            $excontext->set_relevant_context($pcontext);
        }

        return \totara_program\webapi\resolver\type\completion::resolve(
            $field,
            $completion,
            $args,
            $excontext
        );
    }

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    /**
     * Create some users and various learning items.
     * @return array
     */
    private function create_faux_prog_completions() {
        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $now = time() - 1;

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();

        $c1 = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $c2 = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $c3 = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);

        $program = $prog_gen->create_program(['shortname' => 'prg1', 'fullname' => 'program1', 'summary' => 'first program']);
        $prog_gen->add_courses_and_courseset_to_program($program, [[$c1, $c2], [$c3]], CERTIFPATH_STD);
        $prog_gen->assign_program($program->id, [$u1->id, $u2->id, $u3->id]);

        // Update completions, leave u1 unstarted, but make u2 in progress and u3 complete.
        $compu2 = prog_load_completion($program->id, $u2->id);
        $compu2->timestarted = $now;
        prog_write_completion($compu2);

        $compu3 = prog_load_completion($program->id, $u3->id);
        $compu3->timestarted = $now;
        $compu3->timecompleted = $now;
        $compu3->status = STATUS_PROGRAM_COMPLETE;
        prog_write_completion($compu3);

        $users = [1 => $u1, 2 => $u2, 3 => $u3];
        return [$users, $program];
    }

    /**
     * Check that this only works for std class.
     */
    public function test_resolve_stdclass_only() {
        list($users, $program) = $this->create_faux_prog_completions();
        $this->setUser($users[1]);

        try {
            $this->resolve('id', 7);
            $this->fail('Only program instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Expected \stdClass for $completion, recieved: integer',
                $ex->getMessage()
            );
        }

        try {
            $this->resolve('id', ['id' => 7]);
            $this->fail('Only program instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Expected \stdClass for $completion, recieved: array',
                $ex->getMessage()
            );
        }

        $completion = prog_load_completion($program->id, $users[1]->id, false);
        try {
            $value = $this->resolve('id', $completion);
            $this->assertEquals($completion->id, $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Test the program completion type resolver for the id field
     */
    public function test_resolve_id() {
        list($users, $program) = $this->create_faux_prog_completions();
        $this->setUser($users[1]);

        $completion = prog_load_completion($program->id, $users[1]->id, false);
        $value = $this->resolve('id', $completion);
        $this->assertEquals($completion->id, $value);
        $this->assertTrue(is_numeric($value));
    }

    /**
     * Test the program completion type resolver for the status field
     */
    public function test_resolve_status() {
        list($users, $program) = $this->create_faux_prog_completions();

        $this->setUser($users[1]); // Make sure the user resolving the query matches the record.
        $comp1 = prog_load_completion($program->id, $users[1]->id, false);
        $value = $this->resolve('status', $comp1);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $value);
        $this->assertTrue(is_numeric($value));

        $this->setUser($users[2]); // Make sure the user resolving the query matches the record.
        $comp2 = prog_load_completion($program->id, $users[2]->id, false);
        $value = $this->resolve('status', $comp2);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $value);
        $this->assertTrue(is_numeric($value));

        $this->setUser($users[3]); // Make sure the user resolving the query matches the record.
        $comp3 = prog_load_completion($program->id, $users[3]->id, false);
        $value = $this->resolve('status', $comp3);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $value);
        $this->assertTrue(is_numeric($value));
    }

    /**
     * Test the program completion type resolver for the statuskey field
     */
    public function test_resolve_statuskey() {
        list($users, $program) = $this->create_faux_prog_completions();

        $this->setUser($users[1]); // Make sure the user resolving the query matches the record.
        $comp1 = prog_load_completion($program->id, $users[1]->id, false);
        $value = $this->resolve('statuskey', $comp1);
        $this->assertEquals('incomplete', $value);
        $this->assertTrue(is_string($value));

        $this->setUser($users[2]); // Make sure the user resolving the query matches the record.
        $comp2 = prog_load_completion($program->id, $users[2]->id, false);
        $value = $this->resolve('statuskey', $comp2);
        $this->assertEquals('incomplete', $value);
        $this->assertTrue(is_string($value));

        $this->setUser($users[3]); // Make sure the user resolving the query matches the record.
        $comp3 = prog_load_completion($program->id, $users[3]->id, false);
        $value = $this->resolve('statuskey', $comp3);
        $this->assertEquals('complete', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the program completion type resolver for the timecompleted field
     */
    public function test_resolve_timecompleted() {
        list($users, $program) = $this->create_faux_prog_completions();

        $this->setUser($users[1]); // Make sure the user resolving the query matches the record.
        $comp1 = prog_load_completion($program->id, $users[1]->id, false);
        $value = $this->resolve('timecompleted', $comp1, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals(0, $value);
        $this->assertTrue(is_numeric($value));

        $this->setUser($users[2]); // Make sure the user resolving the query matches the record.
        $comp2 = prog_load_completion($program->id, $users[2]->id, false);
        $value = $this->resolve('timecompleted', $comp2, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals(0, $value);
        $this->assertTrue(is_numeric($value));

        $this->setUser($users[3]); // Make sure the user resolving the query matches the record.
        $comp3 = prog_load_completion($program->id, $users[3]->id, false);
        $value = $this->resolve('timecompleted', $comp3, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertTrue(is_numeric($value));
        $this->assertLessThan(time(), $value); // This was set to now-1 so it is in the past.
        $this->assertGreaterThan(time()-(60*5), $value); // in the last 5 min should be plenty of lee-way.
    }


    /**
     * Test the program completion type resolver for the progress field
     */
    public function test_resolve_progress() {
        list($users, $program) = $this->create_faux_prog_completions();


        $this->setUser($users[1]); // Make sure the user resolving the query matches the record.
        $comp1 = prog_load_completion($program->id, $users[1]->id, false);
        $value = $this->resolve('progress', $comp1);
        $this->assertEquals(0, $value);
        $this->assertTrue(is_numeric($value));

        // Even though they're marked as started at this point, they've actually made no progress
        $this->setUser($users[2]); // Make sure the user resolving the query matches the record.
        $comp2 = prog_load_completion($program->id, $users[2]->id, false);
        $value = $this->resolve('progress', $comp2);
        $this->assertEquals(0, $value);
        $this->assertTrue(is_numeric($value));

        // This is the only test that requires more granular completion data, so set it up.
        $coursesets = $program->content->get_course_sets();

        $cs1 = array_shift($coursesets);
        foreach ($cs1->get_courses() as $course) {
            $ccompletion = new completion_completion(array('course' => $course->id, 'userid' => $users[2]->id));
            $ccompletion->mark_complete();
        }

        $cs1u2 = prog_load_courseset_completion($cs1->id, $users[2]->id);
        $cs1u2->timestarted = time() - (60*60*5);
        $cs1u2->timecompleted = time() - 60;
        $cs1u2->status = STATUS_COURSESET_COMPLETE;
        prog_write_courseset_completion($cs1u2);

        // Make sure their progress has updated.
        $comp2 = prog_load_completion($program->id, $users[2]->id, false);
        $value = $this->resolve('progress', $comp2);
        $this->assertEquals(66, $value); // It's 50% of the coursesets, but 66% of the courses.
        $this->assertTrue(is_numeric($value));

        $this->setUser($users[3]); // Make sure the user resolving the query matches the record.
        $comp3 = prog_load_completion($program->id, $users[3]->id, false);
        $value = $this->resolve('progress', $comp3);
        $this->assertEquals(100, $value); // This was set to now-1 so it is in the past.
        $this->assertTrue(is_numeric($value));
    }

}
