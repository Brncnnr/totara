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

use core\format;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Tests the core course type resolver.
 */
class totara_core_webapi_resolver_type_course_testcase extends advanced_testcase {

    use webapi_phpunit_helper;

    private function resolve($field, $course, array $args = []) {
        return $this->resolve_graphql_type('core_course', $field, $course, $args);
    }

    /**
     * Create some courses and assign some users for testing.
     * @return []
     */
    private function create_faux_courses(array $users = []) {
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
        [$users, $courses] = $this->create_faux_courses();
        $this->setAdminUser();

        try {
            // Attempt to resolve an integer.
            $this->resolve('id', 7);
            $this->fail('Only course objects should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only course objects are accepted: integer',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve an array.
            $this->resolve('id', ['id' => 7]);
            $this->fail('Only course instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only course objects are accepted: array',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve a user item.
            $this->resolve('id', $users[0]);
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
            $this->resolve('id', $faux);
            $this->fail('Only course instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only valid course objects are accepted',
                $ex->getMessage()
            );
        }

        // Check that each core instance of course gets resolved.
        try {
            $value = $this->resolve('id', $courses[0]);
            $this->assertEquals($courses[0]->id, $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }

        try {
            $value = $this->resolve('id', $courses[1]);
            $this->assertEquals($courses[1]->id, $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }

        try {
            $value = $this->resolve('id', $courses[2]);
            $this->assertEquals($courses[2]->id, $value);
        } catch (\coding_exception $ex) {
            $this->fail($ex->getMessage());
        }
    }

    /**
     * Test the course type resolver for the id field
     */
    public function test_resolve_id() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);

        // Check that each core instance of course gets resolved correctly.
        $value = $this->resolve('id', $course);
        $this->assertEquals($course->id, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course type resolver for the idnumber field
     */
    public function test_resolve_idnumber() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);

        // Check that each core instance of course gets resolved correctly.
        $value = $this->resolve('idnumber', $course);
        $this->assertEquals($course->idnumber, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course type resolver for the shortname field
     */
    public function test_resolve_shortname() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('shortname', $courses[0]);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('shortname', $courses[0], ['format' => $format]);
            $this->assertEquals($courses[0]->shortname, $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('shortname', $courses[0], ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('shortname', $courses[0], ['format' => format::FORMAT_RAW]);
        $this->assertEquals($courses[0]->shortname, $value);

        // Test special character encoding.
        $extra = $this->getDataGenerator()->create_course([
            'shortname' => 'c&more',
            'fullname' => 'Course & more',
            'summary' => 'Extra course'
        ]);

        $value = $this->resolve('shortname', $extra, ['format' => format::FORMAT_PLAIN]);
        $this->assertEquals('c&more', $value);

        $value = $this->resolve('shortname', $extra, ['format' => format::FORMAT_HTML]);
        $this->assertEquals('c&#38;more', $value);
    }

    /**
     * Test the course type resolver for the fullname field
     */
    public function test_resolve_fullname() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('fullname', $courses[0]);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('fullname', $courses[0], ['format' => $format]);
            $this->assertEquals($courses[0]->fullname, $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('fullname', $courses[0], ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('fullname', $courses[0], ['format' => format::FORMAT_RAW]);
        $this->assertEquals($courses[0]->fullname, $value);

        // Test special character encoding.
        $extra = $this->getDataGenerator()->create_course([
            'shortname' => 'c&more',
            'fullname' => 'Course & more',
            'summary' => 'Extra course'
        ]);

        $value = $this->resolve('fullname', $extra, ['format' => format::FORMAT_PLAIN]);
        $this->assertEquals('Course & more', $value);

        $value = $this->resolve('fullname', $extra, ['format' => format::FORMAT_HTML]);
        $this->assertEquals('Course &#38; more', $value);
    }

    /**
     * Test the course type resolver for the summary field
     */
    public function test_resolve_summary() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('summary', $courses[0]);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('summary', $courses[0], ['format' => $format]);
            if ($format == format::FORMAT_PLAIN) {
                $this->assertEquals($courses[0]->summary, $value);
            }
            if ($format == format::FORMAT_HTML) {
                $this->assertEquals('<div class="text_to_html">'.$courses[0]->summary.'</div>', $value);
            }
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('summary', $courses[0], ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('summary', $courses[0], ['format' => format::FORMAT_RAW]);
        $this->assertEquals($courses[0]->summary, $value);
    }

    /**
     * Test the course type resolver for the summaryformat field
     */
    public function test_resolve_summaryformat() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);
        $formats = [ // Note: HTML is default so not included here, and RAW is not a saved format.
            FORMAT_PLAIN => format::FORMAT_PLAIN,
            FORMAT_MARKDOWN => format::FORMAT_MARKDOWN,
            FORMAT_JSON_EDITOR => format::FORMAT_JSON_EDITOR,
        ];

        // Check that each core instance of course gets resolved correctly.
        $course = $courses[0];
        $value = $this->resolve('summaryformat', $course);
        $this->assertEquals('HTML', $value);
        $this->assertTrue(is_string($value));

        // Also check all non default values.
        foreach ($formats as $format => $expected) {
            $course->summaryformat = $format;
            $value = $this->resolve('summaryformat', $course);
            $this->assertTrue(is_string($value));
            $this->assertEquals($expected, $value);
        }
    }

    /**
     * Test the course type resolver for the timecreated field
     */
    public function test_resolve_timecreated() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);

        // Check that each core instance of course gets resolved correctly.
        $value = $this->resolve('timecreated', $courses[0], ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals($courses[0]->timecreated, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course type resolver for the timemodified field
     */
    public function test_resolve_timemodified() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);

        // Check that each core instance of course gets resolved correctly.
        $value = $this->resolve('timemodified', $courses[0], ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals($courses[0]->timemodified, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course type resolver for the timemodified field
     */
    public function test_resolve_startdate() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);

        // Check that each core instance of course gets resolved correctly.
        $value = $this->resolve('startdate', $courses[0], ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals($courses[0]->startdate, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course type resolver for the timemodified field
     */
    public function test_resolve_enddate() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);

        // Check that each core instance of course gets resolved correctly.
        $value = $this->resolve('enddate', $courses[0], ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertSame(null, $value);

        $courses[0]->enddate = time();
        $value = $this->resolve('enddate', $courses[0], ['format' => \core\date_format::FORMAT_TIMESTAMP]);
        $this->assertEquals($courses[0]->enddate, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course type resolver for the theme field
     */
    public function test_resolve_theme() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);

        // Check that each core instance of course gets resolved correctly.
        $value = $this->resolve('theme', $course);
        $this->assertEquals($course->theme, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course type resolver for the lang field
     */
    public function test_resolve_lang() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);

        // Check that each core instance of course gets resolved correctly.
        $value = $this->resolve('lang', $course);
        $this->assertEquals($course->lang, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course type resolver for the format field
     */
    public function test_resolve_format() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);

        // Check that each core instance of course gets resolved correctly.
        $value = $this->resolve('format', $course);
        $this->assertEquals($course->format, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course type resolver for the coursetype field
     */
    public function test_resolve_coursetype() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);

        // Check that each core instance of course gets resolved correctly.
        $value = $this->resolve('coursetype', $course);
        $this->assertEquals($course->coursetype, $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course type resolver for the icon field
     */
    public function test_resolve_icon() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);

        // Check that each core instance of course gets resolved correctly.
        $this->assertSame(null, $course->icon);
        $value = $this->resolve('icon', $course);
        $this->assertEquals('default', $value);
        $this->assertTrue(is_string($value));

        $course->icon = 'law-of-business-entities';
        $value = $this->resolve('icon', $course);
        $this->assertEquals('law-of-business-entities', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course type resolver for the image (url) and mobile_image fields
     */
    public function test_resolve_image() {
        [$users, $courses] = $this->create_faux_courses();
        $this->setUser($users[0]);
        $course = get_course($courses[0]->id);
        $course->image = course_get_image($course);

        // Check that each core instance of course gets resolved correctly.
        $value = $this->resolve('image', $course);
        $this->assertEquals('https://www.example.com/moodle/theme/image.php/_s/ventura/core/1/course_defaultimage', $value);
        $this->assertTrue(is_string($value));
    }

    /**
     * Test the course type resolver for the completionenabled field.
     */
    public function test_resolve_completionenabled() {
        $c1 = $this->getDataGenerator()->create_course([
            'shortname' => 'c1',
            'fullname' => 'course1',
            'summary' => 'first course',
            'enablecompletion' => 1
        ]);
        $c2 = $this->getDataGenerator()->create_course([
            'shortname' => 'c2',
            'fullname' => 'course2',
            'summary' => 'second course',
            'enablecompletion' => 0
        ]);

        $this->setAdminUser();
        $this->assertTrue($this->resolve('completionenabled', $c1));
        $this->assertfalse($this->resolve('completionenabled', $c2));
    }

    /**
     * Test the course type resolver for the showgrades field.
     */
    public function test_resolve_showgrades() {
        $c1 = $this->getDataGenerator()->create_course([
            'shortname' => 'c1',
            'fullname' => 'course1',
            'summary' => 'first course',
            'showgrades' => 1
        ]);
        $c2 = $this->getDataGenerator()->create_course([
            'shortname' => 'c2',
            'fullname' => 'course2',
            'summary' => 'second course',
            'showgrades' => 0
        ]);

        $this->setAdminUser();
        $this->assertTrue($this->resolve('showgrades', $c1));
        $this->assertfalse($this->resolve('showgrades', $c2));
    }

    /**
     * @return void
     */
    public function test_resolve_course_view_url(): void {
        $course = self::getDataGenerator()->create_course();
        self::setAdminUser();

        self::assertEquals(
            course_get_url($course)->out(),
            $this->resolve("url", $course)
        );
    }

    /**
     * @return void
     */
    public function test_resolve_course_format(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/course/format/lib.php");

        $course = self::getDataGenerator()->create_course(["format" => "singleactivity"]);
        self::setAdminUser();

        $format = $this->resolve("course_format", $course);
        self::assertInstanceOf(format_base::class, $format);
        self::assertInstanceOf(format_singleactivity::class, $format);
    }
}
