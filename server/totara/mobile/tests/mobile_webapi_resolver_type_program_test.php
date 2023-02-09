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
 * @package totara_mobile
 */

defined('MOODLE_INTERNAL') || die();

use core\format;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_program\user_learning\courseset as item_courseset;

/**
 * Tests the totara core program type resolver.
 */
class totara_mobile_webapi_resolver_type_program_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function resolve($field, $program, array $args = []) {
        return $this->resolve_graphql_type('totara_mobile_program', $field, $program, $args);
    }

    /**
     * Create some users and various learning items.
     * @return array
     */
    private function create_faux_programs($format = 'html') {
        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $user = $this->getDataGenerator()->create_user();

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();

        $summary = 'first program';
        $endnote = 'Congratulations on completing the program';
        if ($format == 'json') {
            $summary = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"first program"}]}]}';
            $endnote = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Congratulations on completing the program"}]}]}';
        }

        $program = $prog_gen->create_program([
            'shortname' => 'prg1',
            'fullname' => 'program1',
            'summary' => $summary,
            'endnote' => $endnote,
        ]);
        $prog_gen->add_courses_and_courseset_to_program($program, [[$c1, $c2], [$c3]], CERTIFPATH_STD);
        $prog_gen->assign_program($program->id, [$user->id]);

        return [$user, $program];
    }

    /**
     * Check that this only works for learning items.
     */
    public function test_resolve_programs_only() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        try {
            $this->resolve('id', 7);
            $this->fail('Only program instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only program objects are accepted: integer',
                $ex->getMessage()
            );
        }

        try {
            $this->resolve('id', ['id' => 7]);
            $this->fail('Only program instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only program objects are accepted: array',
                $ex->getMessage()
            );
        }

        try {
            $value = $this->resolve('id', $program);
            $this->assertEquals($program->id, $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Test the program type resolver for the id field
     */
    public function test_resolve_id() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        $value = $this->resolve('id', $program);
        $this->assertEquals($program->id, $value);
        $this->assertTrue(is_numeric($value));
    }

    /**
     * Test the program type resolver for the idnumber field
     */
    public function test_resolve_idnumber() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        $value = $this->resolve('idnumber', $program);
        $this->assertEquals($program->idnumber, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the program type resolver for the shortname field
     */
    public function test_resolve_shortname() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('shortname', $program);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('shortname', $program, ['format' => $format]);
            $this->assertEquals('prg1', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('shortname', $program, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('shortname', $program, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('prg1', $value);
    }

    /**
     * Test the program type resolver for the fullname field
     */
    public function test_resolve_fullname() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('fullname', $program);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('fullname', $program, ['format' => $format]);
            $this->assertEquals('program1', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('fullname', $program, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('fullname', $program, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('program1', $value);
    }

    /**
     * Test the program type resolver for the summary field
     */
    public function test_resolve_summary_html() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('summary', $program);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('summary', $program, ['format' => $format]);
            $this->assertEquals('first program', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('summary', $program, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('summary', $program, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('first program', $value);
    }

    public function test_resolve_summary_json() {
        list($user, $program) = $this->create_faux_programs('json');
        $this->setUser($user);
        $formats = [
            // TODO TL-27575 should convert from JSON_EDITOR to other formats
            //format::FORMAT_HTML => '<p>first program</p>',
            //format::FORMAT_PLAIN => 'first program',
            format::FORMAT_MOBILE => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"first program"}]}]}',
        ];

        try {
            $value = $this->resolve('summary', $program);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format => $expected) {
            $value = $this->resolve('summary', $program, ['format' => $format]);
            $this->assertEquals($expected, $value, "Format {$format}");
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('summary', $program, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('summary', $program, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"first program"}]}]}', $value);
    }

    /**
     * Test the learning item type resolver for the summary_format field
     */
    public function test_resolve_summary_format_html() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('summaryformat', $program);
        $this->assertEquals('HTML', $value);
        $this->assertTrue(is_string($value));
    }

    public function test_resolve_summary_format_json() {
        list($user, $program) = $this->create_faux_programs('json');
        $this->setUser($user);

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('summaryformat', $program);
        $this->assertEquals('JSON_EDITOR', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the program type resolver for the endnote field
     */
    public function test_resolve_endnote_html() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('endnote', $program);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('endnote', $program, ['format' => $format]);
            $this->assertEquals('Congratulations on completing the program', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('endnote', $program, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('endnote', $program, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('Congratulations on completing the program', $value);
    }

    public function test_resolve_endnote_json() {
        list($user, $program) = $this->create_faux_programs('json');
        $this->setUser($user);
        $formats = [
            // TODO TL-27575 should convert from JSON_EDITOR to other formats
            //format::FORMAT_HTML => '<p>Congratulations on completing the program</p>',
            //format::FORMAT_PLAIN => 'Congratulations on completing the program',
            format::FORMAT_MOBILE => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Congratulations on completing the program"}]}]}',
        ];

        try {
            $value = $this->resolve('endnote', $program);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format => $expected) {
            $value = $this->resolve('endnote', $program, ['format' => $format]);
            $this->assertEquals($expected, $value, "Format {$format}");
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('endnote', $program, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('endnote', $program, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Congratulations on completing the program"}]}]}', $value);
    }

    /**
     * Test the learning item type resolver for the endnote_format field
     */
    public function test_resolve_endnote_format_html() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('endnoteformat', $program);
        $this->assertEquals('HTML', $value);
        $this->assertTrue(is_string($value));
    }

    public function test_resolve_endnote_format_json() {
        list($user, $program) = $this->create_faux_programs('json');
        $this->setUser($user);

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('endnoteformat', $program);
        $this->assertEquals('JSON_EDITOR', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the program type resolver for the availablefrom field
     */
    public function test_resolve_availablefrom() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('availablefrom', $program, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertSame(null, $value);

        $program->availablefrom = time();
        $value = $this->resolve('availablefrom', $program, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals($program->availablefrom, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the program type resolver for the availableuntil field
     */
    public function test_resolve_availableuntil() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        $value = $this->resolve('availableuntil', $program, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertSame(null, $value);

        $program->availableuntil = time();
        $value = $this->resolve('availableuntil', $program, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals($program->availableuntil, $value);
        $this->assertTrue(is_string($value));
    }

    public function test_resolve_coursesets() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        $value = $this->resolve('coursesets', $program);
        $this->assertEquals('array', getType($value));
        $this->assertTrue(!empty($value));

        foreach ($value as $cs) {
            $this->assertInstanceOf('course_set', $cs);
        }
    }

    /**
     * Set up a program with a more complicated set of coursesets
     * Note: If you want to use this anywhere else, might be best to move it to the prog_generator
     *
     * Courseset 1 AND Courseset 2
     * OR
     * Courseset 3
     * THEN
     * Courseset 4
     *
     * @param array $users - A list of users to be assigned to the program
     * @return object      - The program item
     */
    private function setup_complex_coursesets($users = []) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/program/program.class.php');
        require_once($CFG->dirroot . '/totara/program/program_courseset.class.php');

        $programdata = [
            'fullname' => 'Complex Program',
        ];
        $prog = $DB->insert_record('prog', (object)$programdata);

        $cs1data = [
            'programid' => $prog,
            'sortorder' => 1,
            'label' => 'Courseset One',
            'nextsetoperator' => NEXTSETOPERATOR_AND,
            'completiontype' => 1,
            'mincourses' => 0,
            'coursesumfield' => 0,
            'coursesumfieldtotal' => 0,
            'timeallowed' => 86400,
            'recurrancetime' => 0,
            'recurrancecreatetime' => 0,
            'contenttype' => 1,
            'certifpath' => 1
        ];
        $cs1 = $DB->insert_record('prog_courseset', (object)$cs1data);
        $c1 = $this->getDataGenerator()->create_course(['fullname' => 'cs1course']);
        $DB->insert_record('prog_courseset_course', (object)['coursesetid' => $cs1, 'courseid' => $c1->id, 'sortorder' => 1]);

        $cs2data = [
            'programid' => $prog,
            'sortorder' => 2,
            'label' => 'Courseset Two',
            'nextsetoperator' => NEXTSETOPERATOR_OR,
            'completiontype' => 1,
            'mincourses' => 0,
            'coursesumfield' => 0,
            'coursesumfieldtotal' => 0,
            'timeallowed' => 86400,
            'recurrancetime' => 0,
            'recurrancecreatetime' => 0,
            'contenttype' => 1,
            'certifpath' => 1
        ];
        $cs2 = $DB->insert_record('prog_courseset', (object)$cs2data);
        $c2 = $this->getDataGenerator()->create_course(['fullname' => 'cs2course']);
        $DB->insert_record('prog_courseset_course', (object)['coursesetid' => $cs2, 'courseid' => $c2->id, 'sortorder' => 1]);

        $cs3data = [
            'programid' => $prog,
            'sortorder' => 3,
            'label' => 'Courseset Three',
            'nextsetoperator' => NEXTSETOPERATOR_THEN,
            'completiontype' => 1,
            'mincourses' => 0,
            'coursesumfield' => 0,
            'coursesumfieldtotal' => 0,
            'timeallowed' => 86400,
            'recurrancetime' => 0,
            'recurrancecreatetime' => 0,
            'contenttype' => 1,
            'certifpath' => 1
        ];
        $cs3 = $DB->insert_record('prog_courseset', (object)$cs3data);
        $c3 = $this->getDataGenerator()->create_course(['fullname' => 'cs3course']);
        $DB->insert_record('prog_courseset_course', (object)['coursesetid' => $cs3, 'courseid' => $c3->id, 'sortorder' => 1]);

        $cs4data = [
            'programid' => $prog,
            'sortorder' => 4,
            'label' => 'Courseset One',
            'nextsetoperator' => 0, // The last courseset sets nextsetoperator to 0, for reasons unknown.
            'completiontype' => 1,
            'mincourses' => 0,
            'coursesumfield' => 0,
            'coursesumfieldtotal' => 0,
            'timeallowed' => 86400,
            'recurrancetime' => 0,
            'recurrancecreatetime' => 0,
            'contenttype' => 1,
            'certifpath' => 1
        ];
        $cs4 = $DB->insert_record('prog_courseset', (object)$cs4data);
        $c4 = $this->getDataGenerator()->create_course(['fullname' => 'cs4course']);
        $DB->insert_record('prog_courseset_course', (object)['coursesetid' => $cs4, 'courseid' => $c4->id, 'sortorder' => 1]);

         $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');
         $prog_gen->assign_program($prog, $users);

        return $prog;
    }

    /**
     * Set up a program with a more complicated set of coursesets
     * Note: If you want to use this anywhere else, might be best to move it to the prog_generator
     *
     * Courseset 1 AND Courseset 2
     * Second courset is marked as completed
     *
     * @param array $users - A list of users to be assigned to the program
     * @return object      - The program item
     */
    private function setup_complex_coursesets_for_completion($users = []) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/program/program.class.php');
        require_once($CFG->dirroot . '/totara/program/program_courseset.class.php');

        $programdata = [
            'fullname' => 'Complex Program',
        ];
        $prog = $DB->insert_record('prog', (object)$programdata);

        $cs1data = [
            'programid' => $prog,
            'sortorder' => 1,
            'label' => 'Courseset One',
            'nextsetoperator' => NEXTSETOPERATOR_AND,
            'completiontype' => 1,
            'mincourses' => 0,
            'coursesumfield' => 0,
            'coursesumfieldtotal' => 0,
            'timeallowed' => 86400,
            'recurrancetime' => 0,
            'recurrancecreatetime' => 0,
            'contenttype' => 1,
            'certifpath' => 1
        ];
        $cs1 = $DB->insert_record('prog_courseset', (object)$cs1data);
        $c1 = $this->getDataGenerator()->create_course(['fullname' => 'cs1course']);
        $DB->insert_record('prog_courseset_course', (object)['coursesetid' => $cs1, 'courseid' => $c1->id, 'sortorder' => 1]);

        $cs2data = [
            'programid' => $prog,
            'sortorder' => 2,
            'label' => 'Courseset Two',
            'nextsetoperator' => 0,
            'completiontype' => 1,
            'mincourses' => 0,
            'coursesumfield' => 0,
            'coursesumfieldtotal' => 0,
            'timeallowed' => 86400,
            'recurrancetime' => 0,
            'recurrancecreatetime' => 0,
            'contenttype' => 1,
            'certifpath' => 1
        ];
        $cs2 = $DB->insert_record('prog_courseset', (object)$cs2data);
        $c2 = $this->getDataGenerator()->create_course(['fullname' => 'cs2course']);
        $DB->insert_record('prog_courseset_course', (object)['coursesetid' => $cs2, 'courseid' => $c2->id, 'sortorder' => 1]);

        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $prog_gen->assign_program($prog, $users);

        return [$prog, $c2];
    }

    /**
     * Set up a simple program with nothing but a single course in it.
     * Note: If you want to use this anywhere else, might be best to move it to the prog_generator
     *
     * @param array $users - A list of users to be assigned to the program
     * @return object      - The program item
     */
    private function setup_simple_coursesets($users = []) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/program/program.class.php');
        require_once($CFG->dirroot . '/totara/program/program_courseset.class.php');

        $programdata = [
            'fullname' => 'Simple Program',
        ];
        $prog = $DB->insert_record('prog', (object)$programdata);

        $cs1data = [
            'programid' => $prog,
            'sortorder' => 1,
            'label' => 'Courseset One',
            'nextsetoperator' =>  0, // The last courseset sets nextsetoperator to 0, for reasons unknown.
            'completiontype' => 1,
            'mincourses' => 0,
            'coursesumfield' => 0,
            'coursesumfieldtotal' => 0,
            'timeallowed' => 86400,
            'recurrancetime' => 0,
            'recurrancecreatetime' => 0,
            'contenttype' => 1,
            'certifpath' => 1
        ];
        $cs1 = $DB->insert_record('prog_courseset', (object)$cs1data);
        $c1 = $this->getDataGenerator()->create_course();
        $DB->insert_record('prog_courseset_course', (object)['coursesetid' => $cs1, 'courseid' => $c1->id, 'sortorder' => 1]);

        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $prog_gen->assign_program($prog, $users);

        return $prog;
    }

    public function test_resolve_current_coursesets() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        // First test the courseset we're using everywhere else.
        $value = $this->resolve('current_coursesets', $program);
        $this->assertEquals('array', getType($value));
        $this->assertCount(1, $value);

        $csets = array_shift($value);
        $this->assertEquals('array', getType($value));
        $this->assertCount(1, $csets);

        // Note: the mobile program courseset type has been updated to handle either type of courseset.
        $courseset = array_shift($csets);
        $this->assertInstanceOf(item_courseset::class, $courseset);
        $this->assertEquals('Course Set 1', $courseset->name);

        // Double check the second non-accessible courseset isn't included.
        $this->assertTrue(empty($value));

        // Secondly set up a program with a more complicated set of coursesets and make sure it works.
        $progid = $this->setup_complex_coursesets([$user->id]);
        $prog = new \program($progid);
        $value = $this->resolve('current_coursesets', $prog);
        $this->assertEquals('array', getType($value));
        $this->assertCount(2, $value); // There is an OR in the first group so we expect 2 arrays.

        // Check that "cs1 AND cs2" are in the first array.
        $csets = array_shift($value);
        $this->assertEquals('array', getType($value));
        $this->assertCount(2, $csets);

        $cs1 = array_shift($csets);
        $this->assertEquals('Courseset One', $cs1->name);
        $cs2 = array_shift($csets);
        $this->assertEquals('Courseset Two', $cs2->name);

        // And that "OR cs3" is by iteself in the second array.
        $csets = array_shift($value);
        $this->assertEquals('array', getType($value));
        $this->assertCount(1, $csets);

        $cs3 = array_shift($csets);
        $this->assertEquals('Courseset Three', $cs3->name);

        // And that "THEN cs4" doesn't show up at all.
        $this->assertEmpty($value);

        // Check if we can see sourcesets if one of them is completed.
        [$progid, $c2] = $this->setup_complex_coursesets_for_completion([$user->id]);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $c2->id));
        $completion->mark_complete();

        $prog = new \program($progid);

        $value = $this->resolve('current_coursesets', $prog);
        $this->assertEquals('array', getType($value));
        $this->assertCount(1, $value); // There is an OR in the first group but second set is complete so we expect 1 array.

        // Check that "cs1 AND" are in the first array.
        $csets = array_shift($value);
        $this->assertEquals('array', getType($value));
        $this->assertCount(1, $csets);

        $cs1 = array_shift($csets);
        $this->assertEquals('Courseset One', $cs1->name);

        // Finally set up a program with nothing but a single course.
        $progid = $this->setup_simple_coursesets([$user->id]);
        $prog = new \program($progid);
        $value = $this->resolve('current_coursesets', $prog);
        $this->assertEquals('array', getType($value));
        $this->assertCount(1, $value);
        $csets = array_shift($value);
        $this->assertEquals('array', getType($csets));
        $this->assertCount(1, $csets);
        $cs1 = array_shift($csets);
        $this->assertEquals('Courseset One', $cs1->name);
        $this->assertEmpty($value);
    }

    public function test_resolve_duedate() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        // Do a quick test for null when timedue is not set.
        $value = $this->resolve('duedate', $program);
        $this->assertNull($value);

        $timedue = time() + (DAYSECS * 12); // Due in 12 days.

        // Set the timedue for the user so we can test it.
        $pcomp = prog_load_completion($program->id, $user->id);
        $pcomp->timestarted = time() - 1;
        $pcomp->timedue = $timedue;
        prog_write_completion($pcomp);

        $date = new DateTime('@' . $timedue);
        $date->setTimezone(core_date::get_user_timezone_object());

        $formats = [
            \core\date_format::FORMAT_TIMESTAMP => $timedue,
            \core\date_format::FORMAT_ISO8601 => $date->format(DateTime::ISO8601),
        ];

        foreach ($formats as $format => $expected) {
            $value = $this->resolve('duedate', $program, ['format' => $format]);
            $this->assertEquals($expected, $value);
        }
    }

    public function test_resolve_duedate_state() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        $timedue_long = time() + (DAYSECS * 31); // Due in 31 days.
        $timedue_short = time() + (DAYSECS * 12); // Due in 12 days.
        $timedue_overdue = time() - DAYSECS; // Was due yesterday.

        $value = $this->resolve('duedate_state', $program);
        $this->assertNull($value);

        // Set the timedue for the user to quite a while away.
        $pcomp = prog_load_completion($program->id, $user->id);
        $pcomp->timestarted = time() - 1;
        $pcomp->timedue = $timedue_long;
        prog_write_completion($pcomp);

        $value = $this->resolve('duedate_state', $program, ['format' => format::FORMAT_PLAIN]);;
        $this->assertEquals('info', $value);

        // Set the timedue for the user to quite a while away.
        $pcomp = prog_load_completion($program->id, $user->id);
        $pcomp->timestarted = time() - 1;
        $pcomp->timedue = $timedue_short;
        prog_write_completion($pcomp);

        $value = $this->resolve('duedate_state', $program, ['format' => format::FORMAT_PLAIN]);
        $this->assertEquals('warning', $value);

        // Set the timedue for the user to quite a while away.
        $pcomp = prog_load_completion($program->id, $user->id);
        $pcomp->timestarted = time() - 1;
        $pcomp->timedue = $timedue_overdue;
        prog_write_completion($pcomp);

        $value = $this->resolve('duedate_state', $program, ['format' => format::FORMAT_PLAIN]);
        $this->assertEquals('danger', $value);
    }

    public function test_resolve_count_unavailablesets() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $progid = $this->setup_complex_coursesets([$user->id]);
        $program = new \program($progid);
        $this->setUser($user);

        $value = $this->resolve('count_unavailablesets', $program);
        $this->assertIsInt($value);
        $this->assertEquals(1, $value);

        // Complete the course in courseset 3.
        $course = $DB->get_record('course', ['fullname' => 'cs3course']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course->id));
        $completion->mark_complete();

        $value = $this->resolve('count_unavailablesets', $program);
        $this->assertIsInt($value);
        $this->assertEquals(0, $value);
    }

    public function test_resolve_count_optionalsets() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $progid = $this->setup_complex_coursesets([$user->id]);
        $program = new \program($progid);
        $this->setUser($user);

        $value = $this->resolve('count_optionalsets', $program);
        $this->assertIsInt($value);
        $this->assertEquals(0, $value);

        // Complete the course in courseset 3.
        $course = $DB->get_record('course', ['fullname' => 'cs3course']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course->id));
        $completion->mark_complete();

        $value = $this->resolve('count_optionalsets', $program);
        $this->assertIsInt($value);
        $this->assertEquals(2, $value);
    }

    public function test_resolve_count_completedsets() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $progid = $this->setup_complex_coursesets([$user->id]);
        $program = new \program($progid);
        $this->setUser($user);

        $value = $this->resolve('count_completedsets', $program);
        $this->assertIsInt($value);
        $this->assertEquals(0, $value);

        // Complete the course in courseset 3.
        $course = $DB->get_record('course', ['fullname' => 'cs3course']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course->id));
        $completion->mark_complete();

        $value = $this->resolve('count_completedsets', $program);
        $this->assertIsInt($value);
        $this->assertEquals(1, $value);
    }

    public function test_resolve_courseset_header() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $progid = $this->setup_complex_coursesets([$user->id]);
        $program = new \program($progid);
        $this->setUser($user);

        $value = $this->resolve('courseset_header', $program);
        $this->assertIsString($value);
        $this->assertEquals('', $value);

        // Complete the course in courseset 3.
        $course = $DB->get_record('course', ['fullname' => 'cs3course']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course->id));
        $completion->mark_complete();

        $value = $this->resolve('courseset_header', $program);
        $this->assertIsString($value);
        $this->assertEquals('1 completed set and 2 optional sets', $value);
    }

    public function test_resolve_mobileimage() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        // If the image matches the default this should be empty.
        $value = $this->resolve('mobile_image', $program);
        $this->assertEquals('', $value);
    }
}
