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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_scorm
 */

defined('MOODLE_INTERNAL') || die();

use mod_scorm\webapi\resolver\query;
use core\format;

/**
 * Tests the mod scorm webapi type.
 */
class mod_scorm_webapi_resolver_type_current_status_testcase extends advanced_testcase {
    // The regular (non-admin) user to test with.
    private $learner;

    // The course the user is assigned to with scorms in it.
    private $course;

    // An array of scorms in $course to compare results against.
    private $scorms;

    private $context;

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    private function resolve($field, $scorm, array $args = []) {
        if (!empty($scorm->id) && $cm = get_coursemodule_from_instance("scorm", $scorm->id, $scorm->course, false, IGNORE_MISSING)) {
            $this->context = \context_module::instance($cm->id, IGNORE_MISSING);
        }

        $excontext = $this->get_execution_context();
        $excontext->set_relevant_context($this->context);

        return \mod_scorm\webapi\resolver\type\current_status::resolve(
            $field,
            $scorm,
            $args,
            $excontext
        );
    }

    public function setUp(): void {
        $this->setAdminUser();
        $now = time();

        // Create a spare user to make sure they aren't returned
        $controluser = $this->getDataGenerator()->create_user();

        // Create the target user so we can check the data returned.
        $this->learner = $this->getDataGenerator()->create_user();

        // Set up some courses and enrolments for the last part of the data.
        $this->course = $this->getDataGenerator()->create_course(['shortname' => 'c1', 'fullname' => 'course1', 'summary' => 'first course']);
        $this->getDataGenerator()->enrol_user($this->learner->id, $this->course->id, 'student', 'manual');

        $sc1 = $this->getDataGenerator()->create_module('scorm', ['course' => $this->course, 'name' => 'c1sc1']);
        $sc2 = $this->getDataGenerator()->create_module('scorm', ['course' => $this->course, 'name' => 'c1sc2']);
        $sc3 = $this->getDataGenerator()->create_module('scorm', ['course' => $this->course, 'name' => 'c1sc3', 'maxattempt' => 2, 'timeopen' => $now - HOURSECS, 'timeclose' => $now + HOURSECS]);
        $this->scorms = [$sc1, $sc2, $sc3];

        $controlcourse = $this->getDataGenerator()->create_course(['shortname' => 'c2', 'fullname' => 'course2', 'summary' => 'second course']);
        $this->getDataGenerator()->enrol_user($controluser->id, $controlcourse->id, 'student', 'manual');

        $sc4 = $this->getDataGenerator()->create_module('scorm', ['course' => $controlcourse, 'name' => 'c1sc4']);
        $sc5 = $this->getDataGenerator()->create_module('scorm', ['course' => $controlcourse, 'name' => 'c1sc5']);

        $noenrol = $this->getDataGenerator()->create_course(['shortname' => 'c3', 'fullname' => 'course3', 'summary' => 'third course']);

        $sc6 = $this->getDataGenerator()->create_module('scorm', ['course' => $noenrol, 'name' => 'c1sc6']);
        $sc7 = $this->getDataGenerator()->create_module('scorm', ['course' => $noenrol, 'name' => 'c1sc7']);

        parent::setUp();
    }

    public function tearDown(): void {
        $this->learner = null;
        $this->course = null;
        $this->scorms = null;
        $this->context = null;

        parent::tearDown();
    }

    /**
     * Check that attempting to resolve the type on an empty object or
     * an object with an empty id or course will throw the expected error
     */
    public function test_resolve_invalid_item() {
        // Set up a default context to run these on.
        $scorm = array_pop($this->scorms);
        $cm = get_coursemodule_from_instance("scorm", $scorm->id, $scorm->course, false, IGNORE_MISSING);
        $this->context = \context_module::instance($cm->id);
        $expected = 'Coding error detected, it must be fixed by a programmer: Invalid SCORM current status request';

        // Test out a completely null scorm.
        try {
            $this->resolve('maxattempt', null);
        } catch (\coding_exception $e) {
            $this->assertSame($expected, $e->getMessage());
        }

        // Next try a scorm without an id.
        $sco1 = clone($scorm);
        $sco1->id = null;
        try {
            $this->resolve('maxattempt', $sco1);
        } catch (\coding_exception $e) {
            $this->assertSame($expected, $e->getMessage());
        }

        // After that try a scorm without an course.
        $sco2 = clone($scorm);
        $sco2->course = null;
        try {
            $this->resolve('maxattempt', $sco2);
        } catch (\coding_exception $e) {
            $this->assertSame($expected, $e->getMessage());
        }

        // And finally try a valid scorm to make sure it works.
        try {
            $result = $this->resolve('maxattempt', $scorm);
            $this->assertSame($scorm->maxattempt, $result);
        } catch (\coding_exception $e) {
            $this->fail($e->getMessage());
        }

    }

    /**
     * Test the scorm type resolver for all expected properties
     */
    public function test_scorm_resolver_properties() {
        // Test scorm record properties.
        $db_properties = [
            'maxattempt' => 'Int',
            'completionstatusrequired' => 'Int',
            'completionscorerequired' => 'Int',
            'completionstatusallscos' => 'Boolean!',
        ];

        // Grab the first scorm.
        $scorm = array_pop($this->scorms);

        foreach ($db_properties as $field => $format) {
            try {
                $value = $this->resolve($field, $scorm);
                $this->assertEquals($scorm->{$field}, $value);
            } catch (\coding_exception $e) {
                $this->assertSame("Coding error detected, it must be fixed by a programmer: Invalid format given", $e->getMessage(), "in field {$field} and format {$format}");

                $value = $this->resolve($field, $scorm, ['format' => format::FORMAT_PLAIN]);
                $this->assertEquals($scorm->{$field}, $value);
                $this->assertTrue(is_string($value));
            }
        }

        // Test coursemodule properties
        $cm = get_coursemodule_from_instance("scorm", $scorm->id, $scorm->course, false, MUST_EXIST);
        $value = $this->resolve('completion', $scorm);
        $this->assertEquals(COMPLETION_TRACKING_NONE, $cm->completion);
        $this->assertEquals('tracking_none', $value);

        $value = $this->resolve('completionview', $scorm);
        $this->assertEquals($cm->completionview, $value);
    }

    /**
     * Test the learning item type resolver for the attempts_current field
     */
    public function test_resolve_attempts_current() {
        $this->setUser($this->learner);

        foreach ($this->scorms as $scorm) {
            $value = $this->resolve('attempts_current', $scorm);
            $this->assertEquals(0, $value);
            $this->assertTrue(is_int($value));
        }
    }

    /**
     * Test the learning item type resolver for the completionstatus field
     */
    public function test_resolve_completionstatus() {
        $this->setUser($this->learner);

        foreach ($this->scorms as $scorm) {
            $value = $this->resolve('completionstatus', $scorm);
            $this->assertEquals('incomplete', $value);
        }
    }

    /**
     * Test the learning item type resolver for the attempts_current field
     */
    public function test_resolve_grade_fields() {
        $grade_properties = [
            'gradefinal' => null,
            'grademax' => 100,
            'gradepercentage' => 0,
        ];
        $this->setUser($this->learner);

        foreach ($this->scorms as $scorm) {
            foreach ($grade_properties as $field => $expected) {
                $value = $this->resolve($field, $scorm);
                $this->assertEquals($expected, $value);
            }
        }
    }

}
