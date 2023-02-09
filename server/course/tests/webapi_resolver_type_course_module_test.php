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

use \totara_core\webapi\resolver\type;
use core\format;

/**
 * Tests the totara core course module type resolver.
 */
class totara_core_webapi_resolver_type_course_module_testcase extends advanced_testcase {
    private $context;

    protected function tearDown(): void {
        $this->context = null;
    }

    private function resolve($field, $item, array $args = []) {
        if (!empty($item->id) && $context = \context_module::instance($item->id, IGNORE_MISSING)) {
            $this->context = $context;
        }

        $excontext = $this->get_execution_context();
        $excontext->set_relevant_context($this->context);

        return \core\webapi\resolver\type\course_module::resolve(
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
        global $CFG;

        $CFG->enableavailability = true;

        $courses = [];
        $courses[0] = $this->getDataGenerator()->create_course([
            'shortname' => 'c1',
            'fullname' => 'course1',
            'format' => 'topics',
            'summary' => 'first course'
        ]);
        $courses[1] = $this->getDataGenerator()->create_course([
            'shortname' => 'c2',
            'fullname' => 'course2',
            'format' => 'topics',
            'summary' => 'second course'
        ]);

        $completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');
        $completion_generator->enable_completion_tracking($courses[0]);
        $completion_generator->enable_completion_tracking($courses[1]);


        // Basic quiz settings
        $quiz_generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $mods = [];
        $mods[0] = $quiz_generator->create_instance([
            'name' => 'quizzical1',
            'course' => $courses[0]->id,
            'intro' => 'QuizDesc'
        ]);
        $mods[1] = $quiz_generator->create_instance([
            'name' => 'quizzical2',
            'course' => $courses[0]->id,
            'intro' => 'QuizDesc'
        ]);
        $mods[2] = $quiz_generator->create_instance([
            'name' => 'quizzical3',
            'course' => $courses[0]->id,
            'intro' => 'QuizDesc'
        ]);

        $specialgroup = $this->getDataGenerator()->create_group(['courseid' => $courses[1]->id]);
        $availability = json_encode(\core_availability\tree::get_root_json([
                \availability_group\condition::get_json($specialgroup->id)
            ])
        );
        $mods[3] = $quiz_generator->create_instance([
            'name' => 'impossible',
            'course' => $courses[1]->id,
            'completion' => 2,
            'completionview' => 1,
            'availability' => $availability,
            'showdescription' => 1,
            'intro' => '<p>A great description<br />for the ages</p>'
        ]);

        $users = [];
        $users[0] = $this->getDataGenerator()->create_user();
        $users[1] = $this->getDataGenerator()->create_user();
        $users[2] = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($users[0]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[0]->id, $courses[1]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[0]->id, 'student', 'manual');
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[1]->id, 'student', 'manual');
        groups_add_member($specialgroup, $users[0]);

        return [$users, $courses];
    }

    /**
     * Mimic the code used in the course type resolver to fetch all the cm_info objects
     * Note: Simply fetching all of a courses modules instead of limiting to sections
     *
     * @param int $courseid
     * @return []
     */
    private function fetch_course_modules($courseid) {
        global $USER;

        $modinfo = \course_modinfo::instance($courseid, $USER->id);
        $mods = $modinfo->get_cms();

        // Set-up a default context for the resolver (only really matters for description).
        $info = current($mods)->get_course_module_record(true);
        $this->context = \context_module::instance($info->id);

        return $mods;
    }

    /**
     * Check that this only works for cm_info objects.
     */
    public function test_resolve_modules_only() {
        list($users, $courses) = $this->create_dataset();
        $this->setAdminUser();
        $mods = $this->fetch_course_modules($courses[0]->id);

        try {
            // Attempt to resolve an integer.
            $this->resolve('id', 7);
            $this->fail('Only cm_info objects should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only cm_info objects are accepted: integer',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve an array.
            $this->resolve('id', ['id' => 7]);
            $this->fail('Only cm_info instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only cm_info objects are accepted: array',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve a user item.
            $this->resolve('id', $users[0]);
            $this->fail('Only cm_info instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only cm_info objects are accepted: object',
                $ex->getMessage()
            );
        }

        try {
            // Attempt to resolve an invalid object.
            $faux = new \stdClass();
            $faux->id = -1;
            $this->resolve('id', $faux);
            $this->fail('Only cm_info instances should be accepted');
        } catch (\coding_exception $ex) {
            $this->assertSame(
                'Coding error detected, it must be fixed by a programmer: Only cm_info objects are accepted: object',
                $ex->getMessage()
            );
        }

        // Check that each core instance of course module gets resolved.
        foreach ($mods as $mod) {
            try {
                $value = $this->resolve('id', $mod);
                $this->assertEquals($mod->id, $value);
            } catch (\coding_exception $ex) {
                $this->fail($ex->getMessage());
            }
        }
    }

    /**
     * Test the course module type resolver for the id field
     */
    public function test_resolve_id() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);

        // Check that each core instance of course module gets resolved correctly.
        foreach ($mods as $mod) {
            $value = $this->resolve('id', $mod);
            $this->assertEquals($mod->id, $value);
        }
    }

    /**
     * Test the course module type resolver for the idnumber field
     */
    public function test_resolve_idnumber() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);

        // Check that each core instance of course module gets resolved correctly.
        foreach ($mods as $mod) {
            $value = $this->resolve('idnumber', $mod);
            $this->assertEquals($mod->idnumber, $value);
        }
    }

    /**
     * Test the course module type resolver for the modtype field
     */
    public function test_resolve_modtype() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);

        // Check that each core instance of course module gets resolved correctly.
        foreach ($mods as $mod) {
            $value = $this->resolve('modtype', $mod, ['format' => format::FORMAT_PLAIN]);
            $this->assertEquals('quiz', $value);
        }
    }

    /**
     * Test the course module type resolver for the name field
     */
    public function test_resolve_name() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);
        $formats = [format::FORMAT_PLAIN, format::FORMAT_HTML];

        $mod = array_pop($mods);
        foreach ($formats as $format) {
            $info = $mod->get_course_module_record(true);
            $value = $this->resolve('name', $mod, ['format' => $format]);
            $this->assertEquals($info->name, $value);
        }
    }

    /**
     * Test the course module type resolver for the viewurl field
     */
    public function test_resolve_viewurl() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);
        $formats = [format::FORMAT_PLAIN, format::FORMAT_HTML];

        $mod = array_pop($mods);
        foreach ($formats as $format) {
            $value = $this->resolve('viewurl', $mod, ['format' => $format]);
            $this->assertEquals($mod->url, $value);
        }
    }

    /**
     * Test the course module type resolver for the completion field
     */
    public function test_resolve_completion() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('completion', $mod);
        $this->assertEquals('tracking_none', $value);

        $mods = $this->fetch_course_modules($courses[1]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('completion', $mod);
        $this->assertEquals('tracking_automatic', $value);
    }

    /**
     * Test the course module type resolver for the completionenabled field
     */
    public function test_resolve_completionenabled() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);

        // Completion disabled
        $mod = array_pop($mods);
        $value = $this->resolve('completionenabled', $mod);
        $this->assertFalse($value);

        $this->setUser($users[1]);
        $mods = $this->fetch_course_modules($courses[1]->id);

        // Completion enabled
        $mod = array_pop($mods);
        $value = $this->resolve('completionenabled', $mod);
        $this->assertTrue($value);
    }

    /**
     * Test the course module type resolver for the completionstatus field
     */
    public function test_resolve_completionstatus() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('completionstatus', $mod);
        $this->assertEquals('incomplete', $value);

        // Also fetch an unavailable module for testing.
        $this->setUser($users[1]);
        $mods = $this->fetch_course_modules($courses[1]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('completionstatus', $mod);
        $this->assertEquals('unknown', $value);
    }

    /**
     * Test the course module type resolver for the rpl field
     */
    public function test_resolve_rpl() {
        [$users, $courses] = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('rpl', $mod);
        $this->assertFalse($value);

        // Also fetch an unavailable module for testing.
        $this->setUser($users[1]);
        $mods = $this->fetch_course_modules($courses[1]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('rpl', $mod);
        $this->assertFalse($value);
    }

    /**
     * Test the course module type resolver for the progress field
     */
    public function test_resolve_progress() {
        global $DB;
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);

        $mods = $this->fetch_course_modules($courses[0]->id);
        $mod1 = array_pop($mods);
        $mod2 = array_pop($mods);
        $mod3 = array_pop($mods);

        $DB->insert_record('course_modules_completion', [
            'coursemoduleid' => $mod2->id,
            'userid' => $users[0]->id,
            'completionstate' => COMPLETION_INCOMPLETE,
            'timemodified' => time(),
            'progress' => 42,
        ]);
        $DB->insert_record('course_modules_completion', [
            'coursemoduleid' => $mod3->id,
            'userid' => $users[0]->id,
            'completionstate' => COMPLETION_COMPLETE,
            'timemodified' => time(),
            'progress' => 100,
        ]);

        $this->assertNull($this->resolve('progress', $mod1));
        $this->assertEquals('42', $this->resolve('progress', $mod2));
        $this->assertEquals('100', $this->resolve('progress', $mod3));
    }

    /**
     * Test the course module type resolver for the available field
     */
    public function test_resolve_available() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[1]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('available', $mod);
        $this->assertEquals(true, $value);

        $this->setUser($users[1]);
        $mods = $this->fetch_course_modules($courses[1]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('available', $mod);
        $this->assertEquals(false, $value);
    }

    /**
     * Test the course module type resolver for the availablereason field with a single basic restriction
     */
    public function test_resolve_availablereason_basic() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[1]->id);
        $formats = [format::FORMAT_PLAIN, format::FORMAT_HTML];

        $mod = array_pop($mods);
        $value = $this->resolve('availablereason', $mod, ['format' => format::FORMAT_PLAIN]);
        $this->assertEquals([], $value);

        $this->setUser($users[1]);
        $mods = $this->fetch_course_modules($courses[1]->id);

        $mod = array_pop($mods);
        foreach ($formats as $format) {
            $value = $this->resolve('availablereason', $mod, ['format' => $format]);
            $this->assertIsArray($value);
            $this->assertCount(1, $value);
            $reason = array_shift($value);

            // Check with regex to handle changing group ids.
            $this->assertMatchesRegularExpression('/Not available unless: You belong to group-[0-9]*/', $reason);
        }
    }

    /**
     * Test the course module type resolver for the available field with multiple more complicated restrictions
     */
    public function test_resolve_availablereason_complex() {
        global $DB, $USER;

        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $course = $courses[0];

        // Set up some modules to use as restrictions.
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

        $availability = json_encode(\core_availability\tree::get_root_json([
                \availability_completion\condition::get_json($mods[0]->cmid, COMPLETION_COMPLETE),
                \core_availability\tree::get_root_json([
                    \availability_completion\condition::get_json($mods[1]->cmid, COMPLETION_COMPLETE),
                    \availability_completion\condition::get_json($mods[2]->cmid, COMPLETION_COMPLETE),
                ])
            ])
        );

        // Now set up the test module
        $mods[3] = $quiz_generator->create_instance([
            'name' => 'RestrictedAccess',
            'course' => $course->id,
            'completion' => 2,
            'completionview' => 1,
            'availability' => $availability,
            'showdescription' => 1,
            'intro' => '<p>A great description<br />for the ages</p>'
        ]);

        $this->context = \context_module::instance($mods[3]->cmid);
        $modinfo = \course_modinfo::instance($course->id, $USER->id);
        $mod = $modinfo->get_cm($mods[3]->cmid);

        $value = $this->resolve('availablereason', $mod, ['format' => format::FORMAT_HTML]);
        $this->assertIsArray($value);
        $this->assertCount(2, $value);

        $reason = array_shift($value);
        $expected = "The activity RestrictionQuiz1 is marked complete";
        $this->assertSame($expected, $reason);

        $reason = array_shift($value);
        $expected = "The activity RestrictionQuiz2 is marked complete and The activity RestrictionQuiz3 is marked complete";
        $this->assertSame($expected, $reason);
    }

    /**
     * Test the course module type resolver for the showdescription field
     */
    public function test_resolve_showdescription() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('showdescription', $mod);
        $this->assertEquals(false, $value);

        $mods = $this->fetch_course_modules($courses[1]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('showdescription', $mod);
        $this->assertEquals(true, $value);
    }

    /**
     * Test the course module type resolver for the description field
     */
    public function test_resolve_description() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('description', $mod, ['format' => format::FORMAT_PLAIN]);
        $this->assertEquals('QuizDesc', $value);

        $mods = $this->fetch_course_modules($courses[1]->id);
        $formats = [format::FORMAT_PLAIN, format::FORMAT_HTML];

        $mod = array_pop($mods);
        $value = $this->resolve('description', $mod, ['format' => format::FORMAT_HTML]);
        $this->assertEquals('<p>A great description<br />for the ages</p>', $value);

        $value = $this->resolve('description', $mod, ['format' => format::FORMAT_PLAIN]);
        $this->assertEquals("A great description\nfor the ages\n", $value);
    }

    /**
     * Test the course module type resolver for the gradefinal field
     */
    public function test_resolve_gradefinal() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('gradefinal', $mod);
        $this->assertEquals(0, $value);
    }

    /**
     * Test the course module type resolver for the grademax field
     */
    public function test_resolve_grademax() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('grademax', $mod);
        $this->assertEquals(100, $value);
    }

    /**
     * Test the course module type resolver for the gradepercentage field
     */
    public function test_resolve_gradepercentage() {
        list($users, $courses) = $this->create_dataset();
        $this->setUser($users[0]);
        $mods = $this->fetch_course_modules($courses[0]->id);

        $mod = array_pop($mods);
        $value = $this->resolve('gradepercentage', $mod);
        $this->assertEquals(0.0, $value);
    }

    /**
     * Make sure that the description returns null rather than throwing an exception if a module doesn't support having an intro.
     * @return void
     */
    public function test_resolve_description_of_module_does_not_support_intro(): void {
        global $USER;
        self::setAdminUser();

        $generator = self::getDataGenerator();
        $course = \container_perform\perform::create((object) [
            'container_name' => 'Container',
            'category' => \mod_perform\util::get_default_category_id(),
        ]);

        /** @var \container_perform\module\perform_module $module */
        $module = $generator->create_module("perform", ["course" => $course->id]);

        // Start resolving description of this content marketplace module.
        $mod_info = get_fast_modinfo($course->id, $USER->id);
        $cm_info = $mod_info->get_cm($module->get_id());

        self::assertNull(
            $this->resolve("description", $cm_info, ["format" => format::FORMAT_HTML])
        );
    }
}
