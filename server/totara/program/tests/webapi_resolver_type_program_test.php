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

/**
 * Tests the totara core program type resolver.
 */
class totara_program_webapi_resolver_type_program_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function resolve($field, $program, array $args = []) {
        return $this->resolve_graphql_type('totara_program_program', $field, $program, $args);
    }

    /**
     * Create some users and various learning items.
     * @return array
     */
    private function create_faux_programs() {
        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $user = $this->getDataGenerator()->create_user();

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();

        $program = $prog_gen->create_program([
            'shortname' => 'prg1',
            'fullname' => 'program1',
            'summary' => 'first program',
            'endnote' => 'Congratulations on completing the program'
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
    public function test_resolve_summary() {
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

    /**
     * Test the learning item type resolver for the summary_format field
     */
    public function test_resolve_summary_format() {
        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('summaryformat', $program);
        $this->assertEquals('HTML', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the program type resolver for the endnote field
     */
    public function test_resolve_endnote() {
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

    /**
     * Test the program type resolver for the duedate field
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
        global $CFG;

        require_once($CFG->dirroot . '/totara/program/program_courseset.class.php');

        list($user, $program) = $this->create_faux_programs();
        $this->setUser($user);

        $value = $this->resolve('coursesets', $program, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals('array', getType($value));
        $this->assertTrue(!empty($value));

        foreach ($value as $cs) {
            $this->assertInstanceOf('course_set', $cs);
        }
    }
}
