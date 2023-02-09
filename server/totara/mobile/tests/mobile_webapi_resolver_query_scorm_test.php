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

/**
use mod_scorm\webapi\resolver\query;
use core\format;
*/

/**
 * Tests the mod scorm mobile webapi query.
 */
class totara_mobile_webapi_resolver_query_scorm_testcase extends advanced_testcase {
    // The regular (non-admin) user to test with.
    private $learner;

    // The course the user is assigned to with scorms in it.
    private $course;

    // An array of scorms in $course to compare results against.
    private $scorms;

    public function setUp(): void {
        $this->setAdminUser();

        set_config('enable', 1, 'totara_mobile');

        // Create a spare user to make sure they aren't returned
        $controluser = $this->getDataGenerator()->create_user();

        // Create the target user so we can check the data returned.
        $this->learner = $this->getDataGenerator()->create_user();

        // Set up some courses and enrolments for the last part of the data.
        $this->course = $this->getDataGenerator()->create_course(['shortname' => 'c1', 'fullname' => 'course1', 'summary' => 'first course']);
        $this->getDataGenerator()->enrol_user($this->learner->id, $this->course->id, 'student', 'manual');

        $sc1 = $this->getDataGenerator()->create_module('scorm', ['course' => $this->course, 'name' => 'c1sc1']);
        $sc2 = $this->getDataGenerator()->create_module('scorm', ['course' => $this->course, 'name' => 'c1sc2', 'allowmobileoffline' => 1]);
        $sc3 = $this->getDataGenerator()->create_module('scorm', ['course' => $this->course, 'name' => 'c1sc3', 'allowmobileoffline' => 0]);
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
     * Test the results of the embedded mobile query through the GraphQL stack.
     */
    public function test_embedded_query() {
        global $CFG;

        $this->setUser($this->learner);

        try {
            // Note: This is raw query results, so we can't check calculated fields like grade or urls.
            foreach ($this->scorms as $scorm) {
                $course = get_course($scorm->course);

                $result = \totara_webapi\graphql::execute_operation(
                    \core\webapi\execution_context::create('mobile', 'totara_mobile_scorm'),
                    ['scormid' => $scorm->id]
                );

                $data = $result->toArray()['data']['scorm'];

                $this->assertSame($scorm->id, $data['id']);
                $this->assertSame($course->id, $data['courseid']);
                $this->assertSame(!empty($course->showgrades), $data['showGrades']);
                $this->assertSame($scorm->name, $data['name']);
                $this->assertSame($scorm->intro, $data['description']);
                $this->assertSame(null, $data['attemptsMax']);
                $this->assertSame(0, $data['attemptsCurrent']);
                $this->assertSame(false, $data['attemptsForceNew']);
                $this->assertSame(false, $data['attemptsLockFinal']);
                $this->assertSame(false, $data['autoContinue']);

                // It's tricky to mimic this data, but lets at least check that its there and not empty.
                $this->assertNotEmpty($data['newAttemptDefaults']);

                $this->assertSame((bool)$scorm->allowmobileoffline, $data['offlineAttemptsAllowed']);
                if ($scorm->allowmobileoffline) {
                    $cm = get_coursemodule_from_instance("scorm", $scorm->id, $this->course->id, false, MUST_EXIST);
                    $packageurl = 'https://www.example.com/moodle/totara/mobile/pluginfile.php/' . context_module::instance($cm->id)->id . '/mod_scorm/package/' . $scorm->revision . '/singlescobasic.zip';
                    $this->assertSame($packageurl, $data['offlinePackageUrl']);
                    $this->assertSame(sha1_file($CFG->dirroot . '/mod/scorm/tests/packages/singlescobasic.zip'), $data['offlinePackageContentHash']);
                    $this->assertSame(['item_1'], $data['offlinePackageScoIdentifiers']);
                } else {
                    $this->assertNull($data['offlinePackageUrl']);
                    $this->assertNull($data['offlinePackageContentHash']);
                    $this->assertNull($data['offlinePackageScoIdentifiers']);
                }

                // Note: running this multiple times in the same test hit's a page context
                // change which triggers a debugging message, looks like a testing only problem.
                $this->resetDebugging();
            }
        } catch (\moodle_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }
}
