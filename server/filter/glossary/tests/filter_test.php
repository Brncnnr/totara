<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests.
 *
 * @package filter_glossary
 * @category test
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/glossary/filter.php'); // Include the code to test.

/**
 * Test case for glossary.
 */
class filter_glossary_filter_testcase extends advanced_testcase {

    /**
     * Test ampersands.
     */
    public function test_ampersands() {
        global $CFG;

        // Enable glossary filter at top level.
        filter_set_global_state('glossary', TEXTFILTER_ON);
        $CFG->glossary_linkentries = 1;

        // Create a test course.
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        // Create a glossary.
        $glossary = $this->getDataGenerator()->create_module('glossary',
                array('course' => $course->id, 'mainglossary' => 1));

        // Create two entries with ampersands and one normal entry.
        /** @var \mod_glossary\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_glossary');
        $normal = $generator->create_content($glossary, array('concept' => 'normal'));
        $amp1 = $generator->create_content($glossary, array('concept' => 'A&B'));
        $amp2 = $generator->create_content($glossary, array('concept' => 'C&amp;D'));

        filter_manager::reset_caches();
        \mod_glossary\local\concept_cache::reset_caches();

        // Format text with all three entries in HTML.
        $html = '<p>A&amp;B C&amp;D normal</p>';
        $filtered = format_text($html, FORMAT_HTML, array('context' => $context));

        // Find all the glossary links in the result.
        $matches = array();
        preg_match_all('~eid=([0-9]+).*?title="(.*?)"~', $filtered, $matches);

        // There should be 3 glossary links.
        $this->assertEquals(3, count($matches[1]));
        $this->assertEquals($amp1->id, $matches[1][0]);
        $this->assertEquals($amp2->id, $matches[1][1]);
        $this->assertEquals($normal->id, $matches[1][2]);

        // Check text and escaping of title attribute.
        $this->assertEquals($glossary->name . ': A&amp;B', $matches[2][0]);
        $this->assertEquals($glossary->name . ': C&amp;D', $matches[2][1]);
        $this->assertEquals($glossary->name . ': normal', $matches[2][2]);
    }

    public function test_filter() {
        global $CFG, $DB;

        // Enable glossary filter at top level.
        filter_set_global_state('glossary', TEXTFILTER_ON);
        $CFG->glossary_linkentries = 1;

        // Prep the required structure.
        $learner = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($learner->id, $course->id);
        $coursecontext = context_course::instance($course->id);
        $glossary = 'Test glossary 1';
        $glossary_object = $this->getDataGenerator()->create_module('glossary', ['name' => $glossary, 'course' => $course, 'mainglossary' => 1]);
        $cmcontext = context_module::instance($glossary_object->cmid);
        /** @var \mod_glossary\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_glossary');
        $entry = $generator->create_content($glossary_object, [
            'concept' => 'Fun',
            'definition' => 'Enjoyment',
            'teacherentry' => true,
            'approved' => true,
        ], [
            'exciting'
        ]);

        // Manually reset the caches.
        filter_manager::reset_caches();
        \mod_glossary\local\concept_cache::reset_caches();

        // Prep some data
        $text = 'Hopefully you had fun today and found our course exciting.';
        $filtered = $text;
        foreach (['fun' => 'Fun', 'exciting' => 'exciting'] as $word => $concept) {
            $url = $CFG->wwwroot.'/mod/glossary/showentry.php?eid='.$entry->id.'&amp;displayformat=dictionary';
            $link = '<a href="'.$url.'" title="'.$glossary.': '.$concept.'" class="glossary autolink concept glossaryid'.$glossary_object->id.'">'.$word.'</a>';
            $filtered = str_replace($word, $link, $filtered);
        }

        // Filter at the cm context level. It should be filtered.
        $filter = new filter_glossary($cmcontext, []);
        self::assertSame($filtered, $filter->filter($text));

        // Filter at the course context level. It should be filtered.
        $filter = new filter_glossary($coursecontext, []);
        self::assertSame($filtered, $filter->filter($text));

        // Filter at the system context level. Nothing should change.
        $filter = new filter_glossary(context_system::instance(), []);
        self::assertSame($text, $filter->filter($text));

        // Confirm that this filter is indeed compatible with clean_text.
        $this->assertSame($filtered, clean_text($filtered, FORMAT_HTML));

        // Trigger the horrible hack.
        self::assertTrue(!isset($CFG->embeddedsoforcelinktarget));
        $CFG->embeddedsoforcelinktarget = true;
        $filter = new filter_glossary($cmcontext, []);
        $filtered = $filter->filter($text);
        // Confirm that this filter is no longer compatible with clean_text.
        $this->assertNotSame($filtered, clean_text($filtered, FORMAT_HTML));
    }

    public function test_is_compatible_with_clean_text() {
        global $CFG;

        self::assertTrue(!isset($CFG->embeddedsoforcelinktarget));

        $method = new ReflectionMethod('filter_glossary', 'is_compatible_with_clean_text');
        $method->setAccessible(true);
        self::assertTrue($method->invoke(null));

        $CFG->embeddedsoforcelinktarget = true;
        self::assertFalse($method->invoke(null));

        $CFG->embeddedsoforcelinktarget = false;
        self::assertTrue($method->invoke(null));

        unset($CFG->embeddedsoforcelinktarget);
        self::assertTrue($method->invoke(null));
    }
}
