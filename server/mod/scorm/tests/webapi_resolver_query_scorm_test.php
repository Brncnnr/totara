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

use totara_webapi\phpunit\webapi_phpunit_helper;
use mod_scorm\webapi\resolver\query;
use core\format;

/**
 * Tests the mod scorm webapi query.
 */
class mod_scorm_webapi_resolver_query_scorm_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    // The regular (non-admin) user to test with.
    private $learner;

    // The course the user is assigned to with scorms in it.
    private $course;

    // An array of scorms in $course to compare results against.
    private $scorms;

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    public function setUp(): void {
        $this->setAdminUser();

        // Create a spare user to make sure they aren't returned
        $controluser = $this->getDataGenerator()->create_user();

        // Create the target user so we can check the data returned.
        $this->learner = $this->getDataGenerator()->create_user();

        // Set up some courses and enrolments for the last part of the data.
        $this->course = $this->getDataGenerator()->create_course(['shortname' => 'c1', 'fullname' => 'course1', 'summary' => 'first course']);
        $this->getDataGenerator()->enrol_user($this->learner->id, $this->course->id, 'student', 'manual');

        $sc1 = $this->getDataGenerator()->create_module('scorm', ['course' => $this->course, 'name' => 'c1sc1']);
        $sc2 = $this->getDataGenerator()->create_module('scorm', ['course' => $this->course, 'name' => 'c1sc2']);
        $sc3 = $this->getDataGenerator()->create_module('scorm', ['course' => $this->course, 'name' => 'c1sc3']);
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

        parent::tearDown();
    }

    /**
     * Test the results of the query when the current user is not logged in.
     */
    public function test_resolve_no_login() {
        $this->setGuestUser();

        $user = $this->getDataGenerator()->create_user();

        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');

        $scorm = array_pop($this->scorms);
        $this->resolve_graphql_query('mod_scorm_scorm', ['scormid' => $scorm->id]);
    }

    /**
     * Test the results of the query when called with an invalid scorm id
     */
    public function test_resolver_invalid_scormid() {
        $this->setUser($this->learner);

        $invalidid = 1;
        foreach ($this->scorms as $scorm) {
            $invalidid += $scorm->id;
        }

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Invalid course module ID');

        $this->resolve_graphql_query('mod_scorm_scorm', ['scormid' => $invalidid]);
    }

    /**
     * Test the results of the query when called on a valid course by a user not enrolled on said course
     */
    public function test_resolver_user_unenrolled() {
        $newuser = $this->getDataGenerator()->create_user();
        $this->setUser($newuser);

        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('Course or activity not accessible. (Not enrolled)');

        $scorm = array_pop($this->scorms);
        $this->resolve_graphql_query('mod_scorm_scorm', ['scormid' => $scorm->id]);
    }

    /**
     * Test the results of the query when called on a valid course by an enrolled user
     */
    public function test_resolver_valid_usercall() {
        $this->setUser($this->learner);

        $scorm = array_pop($this->scorms);
        $results = $this->resolve_graphql_query('mod_scorm_scorm', ['scormid' => $scorm->id]);

        // Note: This is raw query results, so we can't check calculatedgrade or launchurl.
        $this->assertSame($scorm->id, $results->id);
        $this->assertSame($scorm->name, $results->name);
        $this->assertSame($scorm->intro, $results->intro);
        $this->assertSame($scorm->course, $results->course);
        $this->assertSame($scorm->maxattempt, $results->maxattempt);
    }

    /**
     * Test the results of the query affected by visibility settings when called on a valid course by an enrolled user.
     */
    public function test_resolver_valid_usercall_visibility() {
        global $DB;

        $this->setUser($this->learner);

        // Check the initial result has 3 scorms.
        $scorm = array_pop($this->scorms);
        $results = $this->resolve_graphql_query('mod_scorm_scorm', ['scormid' => $scorm->id]);

        $this->assertSame($scorm->id, $results->id);
        $this->assertSame($scorm->name, $results->name);

        // Now hide one of the scorms and check the results are 2.
        $audvisibility = get_config(null, 'audiencevisibility');
        $this->assertSame('0', $audvisibility); // Make sure audvis is off for ease.

        $cm = get_coursemodule_from_instance("scorm", $scorm->id, $this->course->id, false, MUST_EXIST);
        $DB->set_field('course_modules', 'visible', '0', ['id' => $cm->id]);
        // Reset and re-fetch the cminfo cache.
        get_fast_modinfo(0,0,true);
        $modinfo = get_fast_modinfo($this->course);
        $cminfos = $modinfo->get_instances_of('scorm');

        try {
            $this->resolve_graphql_query('mod_scorm_scorm', ['scormid' => $scorm->id]);
            $this->fail('Expected exception was not thrown');
        } catch (require_login_exception $e) {
            $this->assertStringContainsString('Course or activity not accessible. (Activity is hidden)', $e->getMessage());
        }

        // Now hide the course and check the results are 0.
        $DB->set_field('course', 'visible', '0', ['id' => $this->course->id]);

        // Clear the course visibility cache
        cache_helper::purge_by_definition('totara_core', 'totara_course_is_viewable', ['userid' => $this->learner->id]);

        try {
            $this->resolve_graphql_query('mod_scorm_scorm', ['scormid' => $scorm->id]);
            $this->fail('Expected exception was not thrown');
        } catch (require_login_exception $e) {
            $this->assertStringContainsString('Course or activity not accessible. (Course is hidden)', $e->getMessage());
        }

        // Check the admin can still see everything.
        $this->setAdminUser();
        // Reset and re-fetch the cminfo cache.
        get_fast_modinfo(0,0,true);
        $modinfo = get_fast_modinfo($this->course);
        $cminfos = $modinfo->get_instances_of('scorm');

        $results = $this->resolve_graphql_query('mod_scorm_scorm', ['scormid' => $scorm->id]);
        $this->assertSame($scorm->id, $results->id);
        $this->assertSame($scorm->name, $results->name);
    }
}
