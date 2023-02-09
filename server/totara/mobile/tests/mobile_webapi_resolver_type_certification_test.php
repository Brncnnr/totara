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
use totara_certification\user_learning\courseset as item_courseset;

/**
 * Tests the totara core certification type resolver.
 */
class totara_mobile_webapi_resolver_type_certification_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function resolve($field, $certification, array $args = []) {
        return $this->resolve_graphql_type('totara_mobile_certification', $field, $certification, $args);
    }

    /**
     * Create some users and various learning items.
     * @return array
     */
    private function create_faux_certifications($format = 'html') {
        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $user = $this->getDataGenerator()->create_user();

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();

        $summary = 'first certification';
        $endnote = 'Congratulations on completing the certification';
        if ($format == 'json') {
            $summary = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"first certification"}]}]}';
            $endnote = '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Congratulations on completing the certification"}]}]}';
        }

        $certification = $prog_gen->create_certification([
            'shortname' => 'prg1',
            'fullname' => 'certification1',
            'summary' => $summary,
            'endnote' => $endnote
        ]);
        $prog_gen->add_courses_and_courseset_to_program($certification, [[$c1, $c2], [$c3]], CERTIFPATH_STD);
        $prog_gen->assign_program($certification->id, [$user->id]);

        return [$user, $certification];
    }

    /**
     * Check that this only works for learning items.
     */
    public function test_resolve_certifications_only() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        try {
            $this->resolve('id', 7);
            $this->fail('Only certification instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only certification program objects are accepted: integer',
                $ex->getMessage()
            );
        }

        try {
            $this->resolve('id', ['id' => 7]);
            $this->fail('Only certification instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only certification program objects are accepted: array',
                $ex->getMessage()
            );
        }

        try {
            $value = $this->resolve('id', $certification);
            $this->assertEquals($certification->id, $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Test the certification type resolver for the id field
     */
    public function test_resolve_id() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        $value = $this->resolve('id', $certification);
        $this->assertEquals($certification->id, $value);
        $this->assertTrue(is_numeric($value));
    }

    /**
     * Test the certification type resolver for the certifid field
     */
    public function test_resolve_certifid() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        $value = $this->resolve('certifid', $certification);
        $this->assertTrue(is_numeric($value));
        $this->assertEquals($certification->certifid, $value);
    }

    /**
     * Test the certification type resolver for the idnumber field
     */
    public function test_resolve_idnumber() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        $value = $this->resolve('idnumber', $certification);
        $this->assertEquals($certification->idnumber, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the certification type resolver for the shortname field
     */
    public function test_resolve_shortname() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('shortname', $certification);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('shortname', $certification, ['format' => $format]);
            $this->assertEquals('prg1', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('shortname', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('shortname', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('prg1', $value);
    }

    /**
     * Test the certification type resolver for the fullname field
     */
    public function test_resolve_fullname() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('fullname', $certification);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('fullname', $certification, ['format' => $format]);
            $this->assertEquals('certification1', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('fullname', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('fullname', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('certification1', $value);
    }

    /**
     * Test the certification type resolver for the summary field
     */
    public function test_resolve_summary_html() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN, format::FORMAT_MOBILE];

        try {
            $value = $this->resolve('summary', $certification);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('summary', $certification, ['format' => $format]);
            $this->assertEquals('first certification', $value, "Format {$format}");
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('summary', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('summary', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('first certification', $value);
    }

    public function test_resolve_summary_json() {
        list($user, $certification) = $this->create_faux_certifications('json');
        $this->setUser($user);
        $formats = [
            // TODO TL-27575 should convert from JSON_EDITOR to other formats
            //format::FORMAT_HTML => '<p>first certification</p>',
            //format::FORMAT_PLAIN => 'first certification',
            format::FORMAT_MOBILE => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"first certification"}]}]}',
        ];

        try {
            $value = $this->resolve('summary', $certification);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format => $expected) {
            $value = $this->resolve('summary', $certification, ['format' => $format]);
            $this->assertEquals($expected, $value, "Format {$format}");
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('summary', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('summary', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"first certification"}]}]}', $value);
    }

    /**
     * Test the learning item type resolver for the summary_format field
     */
    public function test_resolve_summary_format_html() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('summaryformat', $certification);
        $this->assertEquals('HTML', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the learning item type resolver for the summary_format field
     */
    public function test_resolve_summary_format_json() {
        list($user, $certification) = $this->create_faux_certifications('json');
        $this->setUser($user);

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('summaryformat', $certification);
        $this->assertEquals('JSON_EDITOR', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the certification type resolver for the endnote field
     */
    public function test_resolve_endnote_html() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN, format::FORMAT_MOBILE];

        try {
            $value = $this->resolve('endnote', $certification);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('endnote', $certification, ['format' => $format]);
            $this->assertEquals('Congratulations on completing the certification', $value, "Format {$format}");
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('endnote', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('endnote', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('Congratulations on completing the certification', $value);
    }

    public function test_resolve_endnote_json() {
        list($user, $certification) = $this->create_faux_certifications('json');
        $this->setUser($user);
        $formats = [
            // TODO TL-27575 should convert from JSON_EDITOR to other formats
            //format::FORMAT_HTML => '<p>first certification</p>',
            //format::FORMAT_PLAIN => 'first certification',
            format::FORMAT_MOBILE => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Congratulations on completing the certification"}]}]}',
        ];

        try {
            $value = $this->resolve('endnote', $certification);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format => $expected) {
            $value = $this->resolve('endnote', $certification, ['format' => $format]);
            $this->assertEquals($expected, $value, "Format {$format}");
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('endnote', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('endnote', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Congratulations on completing the certification"}]}]}', $value);
    }

    /**
     * Test the learning item type resolver for the endnote_format field
     */
    public function test_resolve_endnote_format_html() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('endnoteformat', $certification);
        $this->assertEquals('HTML', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the learning item type resolver for the endnote_format field
     */
    public function test_resolve_endnote_format_json() {
        list($user, $certification) = $this->create_faux_certifications('json');
        $this->setUser($user);

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('endnoteformat', $certification);
        $this->assertEquals('JSON_EDITOR', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the certification type resolver for the availablefrom field
     */
    public function test_resolve_availablefrom() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('availablefrom', $certification, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertSame(null, $value);

        $certification->availablefrom = time();
        $value = $this->resolve('availablefrom', $certification, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals($certification->availablefrom, $value);
        $this->assertTrue(is_string($value));
    }

    public function test_resolve_availableuntil() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        $value = $this->resolve('availableuntil', $certification, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertSame(null, $value);

        $certification->availableuntil = time();
        $value = $this->resolve('availableuntil', $certification, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals($certification->availableuntil, $value);
        $this->assertTrue(is_string($value));
    }

    public function test_resolve_coursesets() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        $value = $this->resolve('coursesets', $certification);
        $this->assertEquals('array', getType($value));
        $this->assertTrue(!empty($value));

        foreach ($value as $cs) {
            $this->assertInstanceOf('course_set', $cs);
        }
    }

    /**
     * Set up a certification with a more complicated set of coursesets
     * Note: If you want to use this anywhere else, might be best to move it to the prog_generator
     *
     * Courseset 1 AND Courseset 2
     * OR
     * Courseset 3
     * THEN
     * Courseset 4
     *
     * @param array $users - A list of users to be assigned to the certification
     * @return object      - The certification item
     */
    private function setup_complex_coursesets($users = []) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/program/program.class.php');
        require_once($CFG->dirroot . '/totara/program/program_courseset.class.php');

        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $certification = $prog_gen->create_certification([
            'shortname' => 'cmpcrt',
            'fullname' => 'cmp certification',
            'summary' => 'Complex certification',
            'endnote' => 'Congratulations on completing the certification'
        ]);

        $cs1data = [
            'programid' => $certification->id,
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
            'certifpath' => CERTIFPATH_CERT
        ];
        $cs1 = $DB->insert_record('prog_courseset', (object)$cs1data);
        $c1 = $this->getDataGenerator()->create_course(['fullname' => 'cs1course']);
        $DB->insert_record('prog_courseset_course', (object)['coursesetid' => $cs1, 'courseid' => $c1->id, 'sortorder' => 1]);

        $cs2data = [
            'programid' => $certification->id,
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
            'certifpath' => CERTIFPATH_CERT
        ];
        $cs2 = $DB->insert_record('prog_courseset', (object)$cs2data);
        $c2 = $this->getDataGenerator()->create_course(['fullname' => 'cs2course']);
        $DB->insert_record('prog_courseset_course', (object)['coursesetid' => $cs2, 'courseid' => $c2->id, 'sortorder' => 1]);

        $cs3data = [
            'programid' => $certification->id,
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
            'certifpath' => CERTIFPATH_CERT
        ];
        $cs3 = $DB->insert_record('prog_courseset', (object)$cs3data);
        $c3 = $this->getDataGenerator()->create_course(['fullname' => 'cs3course']);
        $DB->insert_record('prog_courseset_course', (object)['coursesetid' => $cs3, 'courseid' => $c3->id, 'sortorder' => 1]);

        $cs4data = [
            'programid' => $certification->id,
            'sortorder' => 4,
            'label' => 'Courseset FOUR',
            'nextsetoperator' => 0, // The last courseset sets nextsetoperator to 0, for reasons unknown.
            'completiontype' => 1,
            'mincourses' => 0,
            'coursesumfield' => 0,
            'coursesumfieldtotal' => 0,
            'timeallowed' => 86400,
            'recurrancetime' => 0,
            'recurrancecreatetime' => 0,
            'contenttype' => 1,
            'certifpath' => CERTIFPATH_CERT
        ];
        $cs4 = $DB->insert_record('prog_courseset', (object)$cs4data);
        $c4 = $this->getDataGenerator()->create_course(['fullname' => 'cs4course']);
        $DB->insert_record('prog_courseset_course', (object)['coursesetid' => $cs4, 'courseid' => $c4->id, 'sortorder' => 1]);

        $prog_gen->assign_program($certification->id, $users);

        return $certification;
    }

    /**
     * Set up a simple certification with nothing but a single course in it.
     * Note: If you want to use this anywhere else, micertification->idbe best to move it to the prog_generator
     *
     * @param array $users - A list of users to be assigned to the certification
     * @return object      - The certification item
     */
    private function setup_simple_coursesets($users = []) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/program/program.class.php');
        require_once($CFG->dirroot . '/totara/program/program_courseset.class.php');

        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $certification = $prog_gen->create_certification([
            'shortname' => 'smpcrt',
            'fullname' => 'smp certification',
            'summary' => 'Simple certification',
            'endnote' => 'Congratulations on completing the certification'
        ]);

        $cs1data = [
            'programid' => $certification->id,
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

        $prog_gen->assign_program($certification->id, $users);

        return $certification;
    }

    public function test_resolve_current_coursesets() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        // First test the courseset we're using everywhere else.
        $value = $this->resolve('current_coursesets', $certification);
        $this->assertEquals('array', getType($value));
        $this->assertCount(1, $value);

        $csets = array_shift($value);
        $this->assertEquals('array', getType($value));
        $this->assertCount(1, $csets);

        // Note: the mobile certification courseset type has been updated to handle either type of courseset.
        $courseset = array_shift($csets);
        $this->assertInstanceOf(item_courseset::class, $courseset);
        $this->assertEquals('Course Set 1', $courseset->name);

        // Double check the second non-accessible courseset isn't included.
        $this->assertTrue(empty($value));

        // Secondly set up a certification with a more complicated set of coursesets and make sure it works.
        $prog = $this->setup_complex_coursesets([$user->id]);
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

        // Finally set up a certification with nothing but a single course.
        $prog = $this->setup_simple_coursesets([$user->id]);
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
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        // Do a quick test for null when timedue is not set.
        $value = $this->resolve('duedate', $certification);
        $this->assertNull($value);

        $timedue = time() + (DAYSECS * 12); // Due in 12 days.

        // Set the timedue for the user so we can test it.
        list($ccomp, $pcomp) = certif_load_completion($certification->id, $user->id);
        $pcomp->timestarted = time() - 1;
        $pcomp->timedue = $timedue;
        certif_write_completion($ccomp, $pcomp);

        $date = new DateTime('@' . $timedue);
        $date->setTimezone(core_date::get_user_timezone_object());

        $formats = [
             \core\date_format::FORMAT_TIMESTAMP => $timedue,
             \core\date_format::FORMAT_ISO8601 => $date->format(DateTime::ISO8601),
        ];

        foreach ($formats as $format => $expected) {
            $value = $this->resolve('duedate', $certification, ['format' => $format]);
            $this->assertEquals($expected, $value);
        }
    }

    public function test_resolve_duedate_state() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        $value = $this->resolve('duedate', $certification);
        $this->assertNull($value);

        $timedue_long = time() + (DAYSECS * 31); // Due in 31 days.
        $timedue_short = time() + (DAYSECS * 12); // Due in 12 days.
        $timedue_overdue = time() - DAYSECS; // Was due yesterday.

        // Set the timedue for the user so we can test it.
        list($ccomp, $pcomp) = certif_load_completion($certification->id, $user->id);
        $pcomp->timestarted = time() - 1;
        $pcomp->timedue = $timedue_long;
        certif_write_completion($ccomp, $pcomp);

        $value = $this->resolve('duedate_state', $certification, ['format' => format::FORMAT_PLAIN]);;
        $this->assertEquals('info', $value);

        // Set the timedue for the user to quite a while away.
        list($ccomp, $pcomp) = certif_load_completion($certification->id, $user->id);
        $pcomp->timestarted = time() - 1;
        $pcomp->timedue = $timedue_short;
        certif_write_completion($ccomp, $pcomp);

        $value = $this->resolve('duedate_state', $certification, ['format' => format::FORMAT_PLAIN]);;
        $this->assertEquals('warning', $value);

        // Set the timedue for the user to quite a while away.
        list($ccomp, $pcomp) = certif_load_completion($certification->id, $user->id);
        $pcomp->timestarted = time() - 1;
        $pcomp->timedue = $timedue_overdue;
        certif_write_completion($ccomp, $pcomp);

        $value = $this->resolve('duedate_state', $certification, ['format' => format::FORMAT_PLAIN]);;
        $this->assertEquals('danger', $value);

    }

    public function test_resolve_count_unavailablesets() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $certification = $this->setup_complex_coursesets([$user->id]);
        $this->setUser($user);

        $value = $this->resolve('count_unavailablesets', $certification);
        $this->assertIsInt($value);
        $this->assertEquals(1, $value);

        // Complete the course in courseset 3.
        $course = $DB->get_record('course', ['fullname' => 'cs3course']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course->id));
        $completion->mark_complete();

        $value = $this->resolve('count_unavailablesets', $certification);
        $this->assertIsInt($value);
        $this->assertEquals(0, $value);
    }

    public function test_resolve_count_optionalsets() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $certification = $this->setup_complex_coursesets([$user->id]);
        $this->setUser($user);

        $value = $this->resolve('count_optionalsets', $certification);
        $this->assertIsInt($value);
        $this->assertEquals(0, $value);

        // Complete the course in courseset 3.
        $course = $DB->get_record('course', ['fullname' => 'cs3course']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course->id));
        $completion->mark_complete();

        $value = $this->resolve('count_optionalsets', $certification);
        $this->assertIsInt($value);
        $this->assertEquals(2, $value);
    }

    public function test_resolve_count_completedsets() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $certification = $this->setup_complex_coursesets([$user->id]);
        $this->setUser($user);

        $value = $this->resolve('count_completedsets', $certification);
        $this->assertIsInt($value);
        $this->assertEquals(0, $value);

        // Complete the course in courseset 3.
        $course = $DB->get_record('course', ['fullname' => 'cs3course']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course->id));
        $completion->mark_complete();

        $value = $this->resolve('count_completedsets', $certification);
        $this->assertIsInt($value);
        $this->assertEquals(1, $value);
    }

    public function test_resolve_courseset_header() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $certification = $this->setup_complex_coursesets([$user->id]);
        $this->setUser($user);

        $value = $this->resolve('courseset_header', $certification);
        $this->assertIsString($value);
        $this->assertEquals('', $value);

        // Complete the course in courseset 3.
        $course = $DB->get_record('course', ['fullname' => 'cs3course']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $completion = new completion_completion(array('userid' => $user->id, 'course' => $course->id));
        $completion->mark_complete();

        $value = $this->resolve('courseset_header', $certification);
        $this->assertIsString($value);
        $this->assertEquals('1 completed set and 2 optional sets', $value);
    }


    public function test_resolve_mobileimage() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        // If the image matches the default this should be empty.
        $value = $this->resolve('mobile_image', $certification);
        $this->assertEquals('', $value);
    }
}
