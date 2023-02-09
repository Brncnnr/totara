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
 * @package mod_scorm
 */

defined('MOODLE_INTERNAL') || die();

use mod_scorm\webapi\resolver\query;
use core\format;

/**
 * Tests the mod scorm webapi type.
 */
class mod_scorm_webapi_resolver_type_scorm_testcase extends advanced_testcase {
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

        return \mod_scorm\webapi\resolver\type\scorm::resolve(
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
        $expected = 'Coding error detected, it must be fixed by a programmer: Invalid SCORM request';

        // Test out a completely null scorm.
        try {
            $this->resolve('id', null);
        } catch (\coding_exception $e) {
            $this->assertSame($expected, $e->getMessage());
        }

        // Next try a scorm without an id.
        $sco1 = clone($scorm);
        $sco1->id = null;
        try {
            $this->resolve('id', $sco1);
        } catch (\coding_exception $e) {
            $this->assertSame($expected, $e->getMessage());
        }

        // After that try a scorm without an course.
        $sco2 = clone($scorm);
        $sco2->course = null;
        try {
            $this->resolve('id', $sco2);
        } catch (\coding_exception $e) {
            $this->assertSame($expected, $e->getMessage());
        }

        // And finally try a valid scorm to make sure it works.
        try {
            $result = $this->resolve('id', $scorm);
            $this->assertSame($scorm->id, $result);
        } catch (\coding_exception $e) {
            $this->fail($e->getMessage());
        }

    }

    /**
     * Test the scorm type resolver for the id field
     */
    public function test_resolve_id() {
        foreach ($this->scorms as $scorm) {
            // Check that each core instance of learning item gets resolved correctly.
            $value = $this->resolve('id', $scorm);
            $this->assertEquals($scorm->id, $value);
            $this->assertTrue(is_string($value));
        }
    }

    /**
     * Test the scorm type resolver for the courseid field
     */
    public function test_resolve_courseid() {
        foreach ($this->scorms as $scorm) {
            // Check that each core instance of learning item gets resolved correctly.
            $value = $this->resolve('courseid', $scorm);
            $this->assertEquals($scorm->course, $value);
            $this->assertTrue(is_string($value));
        }
    }

    /**
     * Test the scorm type resolver for the showgrades field taken from the scorms course
     */
    public function test_resolve_showgrades() {
        global $DB;

        // Force the course showgrades to true (just in case).
        $DB->set_field('course', 'showgrades', 1, ['id' => $this->course->id]);
        foreach ($this->scorms as $scorm) {
            // Check that each core instance of learning item gets resolved correctly.
            $this->asserttrue($this->resolve('showgrades', $scorm));
        }

        // Force the course showgrades to false (to be thorough).
        $DB->set_field('course', 'showgrades', 0, ['id' => $this->course->id]);
        foreach ($this->scorms as $scorm) {
            // Check that each core instance of learning item gets resolved correctly.
            $this->assertFalse($this->resolve('showgrades', $scorm));
        }
    }

    /**
     * Test the scorm type resolver for all expected properties
     */
    public function test_scorm_resolver_properties() {
        // All of the string, int, float, and bool properties.
        $db_properties = [
            'id' => 'core_id!',
            'name' => 'String!',
            'scormtype' => 'String!',
            'reference' => 'String',
            'version' => 'String!',
            'maxgrade' => 'Float!',
            'grademethod' => 'String!',
            'whatgrade' => 'String!',
            'maxattempt' => 'Int!',
            'forcecompleted' => 'Boolean!',
            'forcenewattempt' => 'Boolean!',
            'lastattemptlock' => 'Boolean!',
            'masteryoverride' => 'Boolean!',
            'displaycoursestructure' => 'Boolean!',
            'skipview' => 'Int!',
            'nav' => 'Int!',
            'navpositionleft' => 'Int',
            'navpositiontop' => 'Int',
            'auto' => 'Boolean!',
            'width' => 'Int',
            'height' => 'Int',
            'displayactivityname' => 'Boolean!',
            'autocommit' => 'Boolean!',
            'allowmobileoffline' => 'Boolean!',
            'completionstatusrequired' => 'Int!',
            'completionscorerequired' => 'Boolean!',
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

        $cm_properties = [
            'completion' => 'Int!',
            'completionview' => 'Boolean!'
        ];
        $cm = get_coursemodule_from_instance("scorm", $scorm->id, $scorm->course, false, MUST_EXIST);
        foreach ($cm_properties as $field => $format) {
            $value = $this->resolve($field, $scorm);
            $this->assertEquals($cm->{$field}, $value);
        }
    }

    /**
     * Test the learning item type resolver for the intro field
     */
    public function test_resolve_intro() {
        $scorm = array_pop($this->scorms);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('intro', $scorm);
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            // Check that each core instance of learning item gets resolved correctly.
            $value = $this->resolve('intro', $scorm, ['format' => $format]);
            $this->assertEquals($scorm->intro, $value);
            $this->assertTrue(is_string($value));
        }
    }

    public function test_resolve_package_url() {
        foreach ($this->scorms as $scorm) {
            // Check that each core instance of learning item gets resolved correctly.
            $value = $this->resolve('package_url', $scorm, ['format' => format::FORMAT_PLAIN]);

            // Mimic the internals of the package_url resolver.
            $fs = get_file_storage();
            $files = $fs->get_area_files($this->context->id, 'mod_scorm', 'package', 0, "timemodified DESC", false);
            if ($files) {
                // URL defaults to forcedownload, that's the last boolean in the call below if we need to change that.
                $url = \moodle_url::make_pluginfile_url($this->context->id, 'mod_scorm', 'package', 0, '/', $scorm->reference, true);
                $package_url = $url->out();
            } else {
                $this->fail('Missing SCORM package in tests');
            }

            $this->assertEquals($package_url, $value);
            $this->assertTrue(is_string($value));
        }
    }

    /**
     * Test the learning item type resolver for the attempts_current field
     */
    public function test_resolve_attemps_current() {
        $this->setUser($this->learner);

        foreach ($this->scorms as $scorm) {
            // Check that each core instance of learning item gets resolved correctly.
            $value = $this->resolve('attempts_current', $scorm);
            $this->assertEquals(0, $value);
            $this->assertTrue(is_int($value));
        }
    }


    public function test_resolve_attempt_defaults() {
        global $DB;
        $this->setUser($this->learner);

        foreach ($this->scorms as $scorm) {
            // Check that each core instance of learning item gets resolved correctly.
            $value = $this->resolve('attempt_defaults', $scorm);

            // Build expected object to match against, kind of superfluous as they're generated with the same code.
            $def = new \stdClass();
            if ($scoes = $DB->get_records('scorm_scoes', array('scorm' => $scorm->id), 'sortorder, id')) {
                $userdata = new \stdClass();
                // Drop keys so that it is a simple array.
                $scoes = array_values($scoes);
                foreach ($scoes as $sco) {
                    $def->{($sco->identifier)} = new \stdClass();
                    $userdata->{($sco->identifier)} = new \stdClass();
                    $def->{($sco->identifier)} = \get_scorm_default($userdata->{($sco->identifier)}, $scorm, $sco->id, 0, 'normal');
                }
            }

            $this->assertSame(json_encode($def), $value);
        }
    }


    /**
     * Test timeopen and timeclose.
     */
    public function test_resolve_timeopen_and_close() {
        $scorm = array_pop($this->scorms);
        $format = \core\date_format::FORMAT_TIMESTAMP;

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('timeopen', $scorm, ['format' => $format]);
        $this->assertEquals($scorm->timeopen, $value);
        $this->assertTrue(is_string($value));

        // Check that each core instance of learning item gets resolved correctly.
        $value = $this->resolve('timeclose', $scorm, ['format' => $format]);
        $this->assertEquals($scorm->timeclose, $value);
        $this->assertTrue(is_string($value));
    }

}
