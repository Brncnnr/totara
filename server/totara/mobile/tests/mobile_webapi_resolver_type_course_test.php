<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_mobile
 */

defined('MOODLE_INTERNAL') || die();

use core\format;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the core course type resolver.
 */
class totara_mobile_webapi_resolver_type_course_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function resolve($field, $course, array $args = []) {
        return $this->resolve_graphql_type('totara_mobile_course', $field, $course, $args);
    }

    /**
     * Create some courses and assign some users for testing.
     *
     * @return []
     */
    private function create_faux_courses(array $users = []) {
        set_config('enable', true, 'totara_mobile');

        $users = [];
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();

        $courses = [];
        $courses[] = $this->getDataGenerator()->create_course(['shortname' => 'c1', 'fullname' => 'course1', 'summary' => 'first course']);
        $courses[] = $this->getDataGenerator()->create_course(['shortname' => 'c2', 'fullname' => 'course2', 'summary' => 'second course']);
        $courses[] = $this->getDataGenerator()->create_course(['shortname' => 'c3', 'fullname' => 'course3', 'summary' => 'third course', 'visible' => 0]);

        $this->getDataGenerator()->enrol_user($users[0]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[1]->id, 'student', 'manual');

        return [$users, $courses];
    }

    /**
     * Check that this only works for courses.
     */
    public function test_resolve_courses_only() {
        list($users, $courses) = $this->create_faux_courses();
        $this->setAdminUser();

        try {
            // Attempt to resolve an integer.
            $this->resolve('native', 7);
            $this->fail('Course must be pre-resolved');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Course property must be resolved first',
                $ex->getMessage()
            );
        }


        try {
            // Attempt to resolve an integer.
            $this->resolve('native', ['course' => 7]);
            $this->fail('Only course objects should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only course objects are accepted: integer',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve an array.
            $this->resolve('native', ['course' => ['id' => 7]]);
            $this->fail('Only course instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only course objects are accepted: array',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve a user item.
            $this->resolve('native', ['course' => $users[0]]);
            $this->fail('Only course instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only valid course objects are accepted',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve an invalid object.
            $faux = new \stdClass();
            $faux->id = -1;
            $this->resolve('native', ['course' => $faux]);
            $this->fail('Only course instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only valid course objects are accepted',
                $ex->getMessage()
            );
        }

        // Check that each core instance of course gets resolved.
        try {
            $value = $this->resolve('course', ['course' => $courses[0]]);
            $this->assertEquals($courses[0]->id, $value->id);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }

        try {
            $value = $this->resolve('course', ['course' => $courses[1]]);
            $this->assertEquals($courses[1]->id, $value->id);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }

        try {
            $value = $this->resolve('course', ['course' => $courses[2]]);
            $this->assertEquals($courses[2]->id, $value->id);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Check that mobile_coursecompat resolves correctly (passed through from the resolver)
     */
    public function test_resolve_mobile_coursecompat() {
        list($users, $courses) = $this->create_faux_courses();
        $this->setAdminUser();

        try {
            $value = $this->resolve('mobile_coursecompat', ['course' => $courses[0], 'mobile_coursecompat' => true]);
            $this->assertEquals(true, $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Check that mobile_image resolves correctly (passed through from the resolver)
     */
    public function test_resolve_mobile_image() {
        list($users, $courses) = $this->create_faux_courses();
        $this->setAdminUser();

        try {
            $value = $this->resolve('mobile_image', ['course' => $courses[0], 'mobile_image' => '']);
            $this->assertEquals('', $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Check that formatted_grademax resoves correctly.
     */
    public function test_resolve_formatted_grademax() {
        global $CFG;

        list($users, $courses) = $this->create_faux_courses();
        $this->setAdminUser();

        try {
            // The first time it should resolve to the default 2 decimals.
            $value = $this->resolve('formatted_grademax', ['course' => $courses[0], 'mobile_image' => '']);
            $this->assertEquals('100.00', $value);

            // Next update that setting and re-resolve to make sure it matches.
            $CFG->grade_decimalpoints = 0;
            $value = $this->resolve('formatted_grademax', ['course' => $courses[0], 'mobile_image' => '']);
            $this->assertEquals('100', $value);

        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Check that formatted_gradefinal resoves correctly.
     */
    public function test_resolve_formatted_gradefinal() {
        global $CFG;
        require_once($CFG->libdir . '/grade/grade_item.php');


        list($users, $courses) = $this->create_faux_courses();
        $user = $users[0];
        $course = $courses[0];
        $this->setUser($user);

        // Set up a course grade we can fill in for data.
        $grade_generator = $this->getDataGenerator()->get_plugin_generator('core_grades');
        $gradeitem = new \grade_item(['courseid' => $course->id, 'itemtype' => 'course'], false);
        $gradeitem->insert();

        try {
            // The first time it will resolve with no information since the user hasn't even visited the course.
            $value = $this->resolve('formatted_gradefinal', ['course' => $course, 'mobile_image' => '']);
            $this->assertEquals('', $value);

            // Next update the users grade data and re-resove to make sure it matches expectations.
            $grade_generator->new_grade_for_item($gradeitem->id, 73.845, $user->id);

            $value = $this->resolve('formatted_gradefinal', ['course' => $course, 'mobile_image' => '']);
            $this->assertEquals('73.85', $value); // Note: it rounds up rather than cut off.

            // Next update that setting and re-resolve to make sure it matches.
            $CFG->grade_decimalpoints = 1;
            $value = $this->resolve('formatted_gradefinal', ['course' => $course, 'mobile_image' => '']);
            $this->assertEquals('73.8', $value); // Good to check that .045 does't round up further than it should.

        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }
}
