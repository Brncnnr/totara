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
use core_phpunit\testcase;

/**
 * Tests the totara core course section type resolver.
 */
class totara_core_webapi_resolver_type_course_section_testcase extends testcase {
    private $context;

    protected function tearDown(): void {
        $this->context = null;
    }

    private function resolve($field, $item, array $args = []) {
        $excontext = $this->get_execution_context();
        $excontext->set_relevant_context($this->context);

        return \core\webapi\resolver\type\course_section::resolve(
            $field,
            $item,
            $args,
            $excontext
        );
    }

    private function get_execution_context(string $type = 'dev', ?string $operation = null) {
        return \core\webapi\execution_context::create($type, $operation);
    }

    /**
     * Create some courses and assign some users for testing.
     * @return []
     */
    private function create_dataset(array $users = []) {
        $users = [];
        $users[] = $this->getDataGenerator()->create_user();
        $users[] = $this->getDataGenerator()->create_user();

        $courses = [];
        $courses[] = $this->getDataGenerator()->create_course(['title' => 'c1', 'fullname' => 'course1', 'description' => 'first course']);
        $courses[] = $this->getDataGenerator()->create_course(['title' => 'c2', 'fullname' => 'course2', 'description' => 'second course']);

        // Set-up a default context for the resolver.
        $this->context = \context_course::instance($courses[0]->id);

        $this->getDataGenerator()->enrol_user($users[0]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[1]->id, 'student', 'manual');

        return [$users, $courses];
    }

    /**
     * Mimic the code used in the course type to fetch all the sections for a given course.
     *
     * @param int $courseid
     * @return array
     */
    private function fetch_course_sections($courseid) {
        global $USER;

        if (!$coursecontext = \context_course::instance($courseid, IGNORE_MISSING)) {
            // If there is no matching context we have a bad object, ignore missing so we can do our own error.
            $this->fail('can not fetch sections for non-existant course');
        }

        $this->context = $coursecontext;

        $modinfo = \course_modinfo::instance($courseid, $USER->id);
        $rawsections = $modinfo->get_section_info_all();

        // The user can see everything, just return everything.
        if (has_capability('moodle/course:viewhiddensections', $coursecontext, $USER->id)) {
            return $rawsections;
        }

        $sections = [];
        // Quickly loop through all the sections, and remove non-visible ones.
        foreach ($rawsections as $key => $section) {
            if ($section->__get('visible')) {
                $sections[$key] = $section;
            }
        }

        return $sections;
    }

    /**
     * Check that this only works for course sections.
     */
    public function test_resolve_section_info_only() {
        list($users, $courses) = $this->create_dataset();
        $this->setAdminUser();

        try {
            // Attempt to resolve an integer.
            $this->resolve('id', 7);
            $this->fail('Only section_info objects should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only section_info objects are accepted: integer',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve an array.
            $this->resolve('id', ['id' => 7]);
            $this->fail('Only section_info objects should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only section_info objects are accepted: array',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve a user item.
            $this->resolve('id', $users[0]);
            $this->fail('Only section_info objects should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only section_info objects are accepted: object',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve an invalid object.
            $faux = new \stdClass();
            $faux->id = -1;
            $this->resolve('id', $faux);
            $this->fail('Only section_info objects should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only section_info objects are accepted: object',
                $ex->getMessage()
            );
        }

        // Check that each core instance of course section gets resolved.
        $sections = $this->fetch_course_sections($courses[0]->id);
        foreach ($sections as $section) {
            try {
                $value = $this->resolve('id', $section);
                $this->assertEquals($section->id, $value);
            } catch (\coding_exception $ex) {
                $this->fail($ex->getMessage());
            }
        }
    }

    /**
     * Test the course section type resolver for the id field,
     * Already tested by the section_info test above.
     */
    public function test_resolve_id() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $sections = $this->fetch_course_sections($courses[0]->id);

        foreach ($sections as $section) {
            try {
                $value = $this->resolve('id', $section);
                $this->assertEquals($section->id, $value);
                $this->assertTrue(is_string($value));
            } catch (\coding_exception $ex) {
                $this->fail($ex->getMessage());
            }
        }
    }

    /**
     * Test the course section type resolver for the title field
     */
    public function test_resolve_title() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $sections = $this->fetch_course_sections($courses[0]->id);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('title', $sections[0]);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('title', $sections[0], ['format' => $format]);
            $this->assertEquals('', $value);
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('title', $sections[0], ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('title', $sections[0], ['format' => format::FORMAT_RAW]);
        $this->assertEquals('', $value);

        // Test special character encoding.
        $data = new stdClass();
        $data->name = 'Section & more';
        $extra = $sections[0];
        course_update_section($courses[0], $extra, $data);

        $value = $this->resolve('title', $extra, ['format' => format::FORMAT_PLAIN]);
        $this->assertEquals("", $value);

        $value = $this->resolve('title', $extra, ['format' => format::FORMAT_HTML]);
        $this->assertEquals("", $value);
    }

    /**
     * Test the course section type resolver for the summary field
     */
    public function test_resolve_summary() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $sections = $this->fetch_course_sections($courses[0]->id);
        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];

        try {
            $value = $this->resolve('summary', $sections[0]);
            $this->fail('Expected failure on null $format');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        foreach ($formats as $format) {
            $value = $this->resolve('summary', $sections[0], ['format' => $format]);
            $this->assertEquals($sections[0]->summary, $value);
            $this->assertTrue(is_string($value));
        }

        // Check the permissions required for format::FORMAT_RAW
        $value = $this->resolve('summary', $sections[0], ['format' => format::FORMAT_RAW]);
        $this->assertNull($value);

        $this->setAdminUser();
        $value = $this->resolve('summary', $sections[0], ['format' => format::FORMAT_RAW]);
        $this->assertEquals($sections[0]->summary, $value);
    }

    /**
     * Test the course section type resolver for the available field
     */
    public function test_resolve_available() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $sections = $this->fetch_course_sections($courses[0]->id);

        foreach ($sections as $section) {
            try {
                $value = $this->resolve('available', $section);
                $this->assertEquals($section->available, $value);
            } catch (\coding_exception $ex) {
                $this->fail($ex->getMessage());
            }
        }
    }

    /**
     * Test the course section type resolver for the available field with a single basic restriction.
     */
    public function test_resolve_availablereason_basic() {
        global $DB;

        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = $courses[0];
        $sections = $this->fetch_course_sections($course->id);
        $section = array_shift($sections);

        // First check it fails without a format.
        try {
            $value = $this->resolve('availablereason', $section);
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Invalid format given',
                $ex->getMessage()
            );
        }

        $value = $this->resolve('availablereason', $section, ['format' => format::FORMAT_PLAIN]);
        $this->assertIsArray($value);
        $this->assertEmpty($value);

        $record = $DB->get_record('course_sections', ['id' => $section->id]);

        $specialgroup = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $availability = json_encode(\core_availability\tree::get_root_json(
            [\availability_group\condition::get_json($specialgroup->id)]
        ));
        $updates = new \stdClass();
        $updates->availability = $availability;

        // Update the section and refresh the section_info objects.
        course_update_section($course->id, $record, $updates);
        $sections = $this->fetch_course_sections($course->id);
        $section = array_shift($sections);

        $formats = [format::FORMAT_HTML, format::FORMAT_PLAIN];
        foreach ($formats as $format) {
            $value = $this->resolve('availablereason', $section, ['format' => format::FORMAT_HTML]);
            $this->assertIsArray($value);
            $this->assertCount(1, $value);

            $reason = array_pop($value);
            if ($format == format::FORMAT_RAW) {
                // Check with regex to handle changing group ids.
                $this->assertMatchesRegularExpression('/Not available unless: You belong to <strong>group-[0-9]*</strong>/', $reason);
            } else {
                $this->assertMatchesRegularExpression('/Not available unless: You belong to group-[0-9]*/', $reason);
            }
        }
    }

    /**
     * Test the course section type resolver for the available field with multiple more complicated restrictions
     */
    public function test_resolve_availablereason_complex() {
        global $DB;

        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = $courses[0];

        $sections = $this->fetch_course_sections($course->id);
        $section = array_shift($sections);
        $record = $DB->get_record('course_sections', ['id' => $section->id]);

        $mods = [];
        $quiz_generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $mods[0] = $quiz_generator->create_instance([
            'name' => 'RestrictionQuiz1',
            'course' => $course->id,
            'intro' => 'QuizDesc1'
        ]);
        $mods[1] = $quiz_generator->create_instance([
            'name' => 'RestrictionQuiz2',
            'course' => $course->id,
            'intro' => 'QuizDesc2'
        ]);
        $mods[2] = $quiz_generator->create_instance([
            'name' => 'RestrictionQuiz3',
            'course' => $course->id,
            'intro' => 'QuizDesc3'
        ]);

        $availability = json_encode(\core_availability\tree::get_root_json(
            [
                \availability_completion\condition::get_json($mods[0]->cmid, COMPLETION_COMPLETE),
                \core_availability\tree::get_root_json([
                    \availability_completion\condition::get_json($mods[1]->cmid, COMPLETION_COMPLETE),
                    \availability_completion\condition::get_json($mods[2]->cmid, COMPLETION_COMPLETE),
                ])
            ]
        ));
        $updates = new \stdClass();
        $updates->availability = $availability;

        // Update the section and refresh the section_info objects.
        course_update_section($course->id, $record, $updates);
        $sections = $this->fetch_course_sections($course->id);
        $section = array_shift($sections);

        $value = $this->resolve('availablereason', $section, ['format' => format::FORMAT_HTML]);
        $this->assertIsArray($value);
        $this->assertCount(2, $value);

        $reason = array_shift($value);
        $expected = "The activity RestrictionQuiz1 is marked complete";
        $this->assertSame($expected, $reason);

        $reason = array_shift($value);
        $expected = "The activity RestrictionQuiz2 is marked complete and The activity RestrictionQuiz3 is marked complete";
        $this->assertSame($expected, $reason);
    }
}
