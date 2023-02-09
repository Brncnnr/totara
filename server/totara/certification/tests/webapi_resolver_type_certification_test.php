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
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/job/lib.php');

use core\format;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the totara core certification type resolver.
 */
class totara_certification_webapi_resolver_type_certification_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function resolve($field, $certification, array $args = []) {
        return $this->resolve_graphql_type('totara_certification_certification', $field, $certification, $args);
    }

    /**
     * Create some certifications and assign some users for testing.
     * @return []
     */
    private function create_faux_certifications(array $users = []) {
        $user = $this->getDataGenerator()->create_user();

        $prog_gen = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $c3 = $this->getDataGenerator()->create_course();

        $certification = $prog_gen->create_certification(['shortname' => 'cert1', 'fullname' => 'certification1', 'summary' => 'first certification']);
        $prog_gen->add_courses_and_courseset_to_program($certification, [[$c1, $c2], [$c3]], CERTIFPATH_CERT);
        $prog_gen->add_courses_and_courseset_to_program($certification, [[$c1], [$c3]], CERTIFPATH_RECERT);
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
            $this->fail('Only program instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only certification program objects are accepted: integer',
                $ex->getMessage()
            );
        }

        try {
            $this->resolve('id', ['id' => 7]);
            $this->fail('Only program instances should be accepted');
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
     * Test the certification type resolver for the idnumber fiel
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
            $this->assertEquals('cert1', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('shortname', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('shortname', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('cert1', $value);
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
    public function test_resolve_summary() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

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
            $this->assertEquals('first certification', $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('summary', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('summary', $certification, ['format' => format::FORMAT_RAW]);
        $this->assertEquals('first certification', $value);
    }

    /**
     * Test the learning item type resolver for the summary_format field
     */
    public function test_resolve_summary_format() {
        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('summaryformat', $certification);
        $this->assertEquals('HTML', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the certification type resolver for the duedate field
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
        global $CFG;

        list($user, $certification) = $this->create_faux_certifications();
        $this->setUser($user);

        $value = $this->resolve('coursesets', $certification, ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals('array', getType($value));
        $this->assertTrue(!empty($value));

        foreach ($value as $cs) {
            $this->assertInstanceOf('course_set', $cs);
        }
    }
}
