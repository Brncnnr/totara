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
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

use core\format;
use totara_webapi\phpunit\webapi_phpunit_helper;

global $CFG;
require_once($CFG->dirroot . '/totara/program/program.class.php');

/**
 * Tests the totara certification completion type resolver.
 */
class totara_certification_webapi_resolver_type_certification_completion_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private function resolve($field, $completion, array $args = []) {
        $excontext = $this->get_execution_context();
        if (!empty($completion->programid)) {
            $pcontext = \context_program::instance($completion->programid);
            $excontext->set_relevant_context($pcontext);
        }

        return \totara_certification\webapi\resolver\type\completion::resolve(
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
    private function create_faux_cert_completions() {
        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $now = time() - 1;

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();

        $c1 = $this->getDataGenerator()->create_course(['fullname' => 'Cert Course 1', 'enablecompletion' => COMPLETION_ENABLED]);
        $c2 = $this->getDataGenerator()->create_course(['fullname' => 'Cert Course 2', 'enablecompletion' => COMPLETION_ENABLED]);
        $c3 = $this->getDataGenerator()->create_course(['fullname' => 'Cert Course 3', 'enablecompletion' => COMPLETION_ENABLED]);

        $certification = $prog_gen->create_certification(['shortname' => 'cert1', 'fullname' => 'certification1', 'summary' => 'first certification']);
        $prog_gen->add_courses_and_courseset_to_program($certification, [[$c1, $c2], [$c3]], CERTIFPATH_CERT);
        $prog_gen->add_courses_and_courseset_to_program($certification, [[$c1], [$c3]], CERTIFPATH_RECERT);
        $prog_gen->assign_program($certification->id, [$u1->id, $u2->id, $u3->id]);

        // Update completions, leave u1 unstarted, but make u2 in progress and u3 complete.
        list($ccomp2, $pcomp2) = certif_load_completion($certification->id, $u2->id);
        $pcomp2->timestarted = $now;
        $pcomp2->timedue = $now + (DAYSECS*14);
        certif_write_completion($ccomp2, $pcomp2);

        list($ccomp3, $pcomp3) = certif_load_completion($certification->id, $u3->id);
        $pcomp3->timestarted = $now;
        $pcomp3->timecompleted = $now;
        $pcomp3->timedue = $now + (DAYSECS*21);
        $pcomp3->timemodified = $now;
        $pcomp3->status = STATUS_PROGRAM_COMPLETE;

        $ccomp3->certifpath = CERTIFPATH_RECERT;
        $ccomp3->status = CERTIFSTATUS_COMPLETED;
        $ccomp3->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $ccomp3->timecompleted = $now;
        $ccomp3->timemodified = $now;
        $ccomp3->timewindowopens = $now + (DAYSECS*7);
        $ccomp3->timeexpires = $now + (DAYSECS*21);
        $ccomp3->baselinetimeexpires = $now + (DAYSECS*21);

        certif_write_completion($ccomp3, $pcomp3);

        $users = [1 => $u1, 2 => $u2, 3 => $u3];
        return [$users, $certification];
    }

    /**
     * Check that this only works for array.
     * expected input to match [$certcompletion, $progcompletion]
     */
    public function test_resolve_array_only() {
        list($users, $certification) = $this->create_faux_cert_completions();
        $this->setUser($users[1]);

        try {
            $this->resolve('id', 7);
            $this->fail('Only certif_load_completion results should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Expected $completion to match certif_load_completion() output, recieved: integer',
                $ex->getMessage()
            );
        }

        try {
            $item = new \stdClass();
            $item->id = 7;
            $this->resolve('id', $item);
            $this->fail('Only certif_load_completion results should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Expected $completion to match certif_load_completion() output, recieved: object',
                $ex->getMessage()
            );
        }

        $completion = certif_load_completion($certification->id, $users[1]->id, false);
        try {
            $value = $this->resolve('id', $completion);
            $this->assertEquals($completion[0]->id, $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Test the certification completion type resolver for the id field
     */
    public function test_resolve_id() {
        list($users, $certification) = $this->create_faux_cert_completions();
        $this->setUser($users[1]);

        $completion = certif_load_completion($certification->id, $users[1]->id, false);
        $value = $this->resolve('id', $completion);
        $this->assertTrue(is_numeric($value));
        $this->assertEquals($completion[0]->id, $value);
    }

    /**
     * Test the certification completion type resolver for the status field
     */
    public function test_resolve_status() {
        list($users, $certification) = $this->create_faux_cert_completions();

        $this->setUser($users[1]); // Make sure the user resolving the query matches the record.
        $comp1 = certif_load_completion($certification->id, $users[1]->id, false);
        $value = $this->resolve('status', $comp1);
        $this->assertTrue(is_numeric($value));
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $value);

        $this->setUser($users[2]); // Make sure the user resolving the query matches the record.
        $comp2 = certif_load_completion($certification->id, $users[2]->id, false);
        $value = $this->resolve('status', $comp2);
        $this->assertTrue(is_numeric($value));
        $this->assertEquals(CERTIFSTATUS_ASSIGNED, $value);

        $this->setUser($users[3]); // Make sure the user resolving the query matches the record.
        $comp3 = certif_load_completion($certification->id, $users[3]->id, false);
        $value = $this->resolve('status', $comp3);
        $this->assertTrue(is_numeric($value));
        $this->assertEquals(CERTIFSTATUS_COMPLETED, $value);
    }

    /**
     * Test the certification completion type resolver for the statuskey field
     */
    public function test_resolve_statuskey() {
        list($users, $certification) = $this->create_faux_cert_completions();

        $this->setUser($users[1]); // Make sure the user resolving the query matches the record.
        $comp1 = certif_load_completion($certification->id, $users[1]->id, false);
        $value = $this->resolve('statuskey', $comp1);
        $this->assertTrue(is_string($value));
        $this->assertEquals('assigned', $value);

        $this->setUser($users[2]); // Make sure the user resolving the query matches the record.
        $comp2 = certif_load_completion($certification->id, $users[2]->id, false);
        $value = $this->resolve('statuskey', $comp2);
        $this->assertTrue(is_string($value));
        $this->assertEquals('assigned', $value);

        $this->setUser($users[3]); // Make sure the user resolving the query matches the record.
        $comp3 = certif_load_completion($certification->id, $users[3]->id, false);
        $value = $this->resolve('statuskey', $comp3);
        $this->assertTrue(is_string($value));
        $this->assertEquals('completed', $value);
    }

    /**
     * Test the certification completion type resolver for the renewalstatus field
     */
    public function test_resolve_renewalstatus() {
        list($users, $certification) = $this->create_faux_cert_completions();

        $this->setUser($users[1]); // Make sure the user resolving the query matches the record.
        $comp1 = certif_load_completion($certification->id, $users[1]->id, false);
        $value = $this->resolve('renewalstatus', $comp1);
        $this->assertTrue(is_numeric($value));
        $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $value);

        $this->setUser($users[2]); // Make sure the user resolving the query matches the record.
        $comp2 = certif_load_completion($certification->id, $users[2]->id, false);
        $value = $this->resolve('renewalstatus', $comp2);
        $this->assertTrue(is_numeric($value));
        $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $value); // Note: even though this is due, its not due for renewal.

        $this->setUser($users[3]); // Make sure the user resolving the query matches the record.
        $comp3 = certif_load_completion($certification->id, $users[3]->id, false);
        $value = $this->resolve('renewalstatus', $comp3);
        $this->assertTrue(is_numeric($value));
        $this->assertEquals(CERTIFRENEWALSTATUS_NOTDUE, $value); // Note: This isn't due till the window opens.
    }

    /**
     * Test the certification completion type resolver for the renewalstatuskey field
     */
    public function test_resolve_renewalstatuskey() {
        list($users, $certification) = $this->create_faux_cert_completions();

        $this->setUser($users[1]); // Make sure the user resolving the query matches the record.
        $comp1 = certif_load_completion($certification->id, $users[1]->id, false);
        $value = $this->resolve('renewalstatuskey', $comp1);
        $this->assertTrue(is_string($value));
        $this->assertEquals('notdue', $value);

        $this->setUser($users[2]); // Make sure the user resolving the query matches the record.
        $comp2 = certif_load_completion($certification->id, $users[2]->id, false);
        $value = $this->resolve('renewalstatuskey', $comp2);
        $this->assertTrue(is_string($value));
        $this->assertEquals('notdue', $value);

        $this->setUser($users[3]); // Make sure the user resolving the query matches the record.
        $comp3 = certif_load_completion($certification->id, $users[3]->id, false);
        $value = $this->resolve('renewalstatuskey', $comp3);
        $this->assertTrue(is_string($value));
        $this->assertEquals('notdue', $value);
    }

    /**
     * Test the certification completion type resolver for the timecompleted field
     */
    public function test_resolve_timecompleted() {
        list($users, $certification) = $this->create_faux_cert_completions();

        $this->setUser($users[1]); // Make sure the user resolving the query matches the record.
        $comp1 = certif_load_completion($certification->id, $users[1]->id, false);
        $value = $this->resolve('timecompleted', $comp1, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertTrue(is_numeric($value));
        $this->assertEquals(0, $value);

        $this->setUser($users[2]); // Make sure the user resolving the query matches the record.
        $comp2 = certif_load_completion($certification->id, $users[2]->id, false);
        $value = $this->resolve('timecompleted', $comp2, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertTrue(is_numeric($value));
        $this->assertEquals(0, $value);

        $this->setUser($users[3]); // Make sure the user resolving the query matches the record.
        $comp3 = certif_load_completion($certification->id, $users[3]->id, false);
        $value = $this->resolve('timecompleted', $comp3, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertTrue(is_numeric($value));
        $this->assertLessThan(time(), $value); // This was set to now-1 so it is in the past.
        $this->assertGreaterThan(time()-(60*5), $value); // in the last 5 min should be plenty of lee-way.
    }


    /**
     * Test the certification completion type resolver for the progress field
     */
    public function test_resolve_progress() {
        list($users, $certification) = $this->create_faux_cert_completions();

        $this->setUser($users[1]); // Make sure the user resolving the query matches the record.
        $comp1 = certif_load_completion($certification->id, $users[1]->id, false);
        $value = $this->resolve('progress', $comp1);
        $this->assertEquals(0, $value);
        $this->assertTrue(is_numeric($value));

        // Even though they're marked as started at this point, they've actually made no progress
        $this->setUser($users[2]); // Make sure the user resolving the query matches the record.
        $comp2 = certif_load_completion($certification->id, $users[2]->id, false);
        $value = $this->resolve('progress', $comp2);
        $this->assertTrue(is_numeric($value));
        $this->assertEquals(0, $value);

        // This is the only test that requires more granular completion data, so set it up.
        $prog = new \program($certification->id); // Have to reload the object because of generators...
        $coursesets = $prog->content->get_course_sets_path(CERTIFPATH_CERT);

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
        $comp2 = certif_load_completion($certification->id, $users[2]->id, false);
        $value = $this->resolve('progress', $comp2);
        $this->assertTrue(is_numeric($value));
        $this->assertEquals(66, $value); // It's 50% of the coursesets, but 66% of the courses.

        $this->setUser($users[3]); // Make sure the user resolving the query matches the record.
        $comp3 = certif_load_completion($certification->id, $users[3]->id, false);
        $value = $this->resolve('progress', $comp3);
        $this->assertTrue(is_numeric($value));
        $this->assertEquals(100, $value); // They haven't actually made any progress, but they are complete so its 100%.
    }
}
