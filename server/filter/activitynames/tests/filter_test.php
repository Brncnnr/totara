<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package filter_activitynames
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class filter_activitynames_filter_testcase
 */
class filter_activitynames_filter_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/filter/activitynames/filter.php');
        parent::setUpBeforeClass();
    }

    public function test_filter() {
        global $CFG;

        // Prep the required structure.
        $learner = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $forum1 = 'Test forum 1';
        $forum2 = 'Test forum 2';
        $forum1_object = $this->getDataGenerator()->create_module('forum', ['name' => $forum1, 'course' => $course]);
        $forum2_object = $this->getDataGenerator()->create_module('forum', ['name' => $forum2, 'course' => $course]);
        $this->getDataGenerator()->enrol_user($learner->id, $course->id);
        $coursecontext = context_course::instance($course->id);
        $cmcontext = context_module::instance($forum1_object->cmid);

        // Prep some data.
        $text = 'You should check out the Test forum 1 for the latest news and Test forum 2 for great discussion';
        $forum1_link = '<a class="autolink" title="'.$forum1.'" href="'.$CFG->wwwroot.'/mod/forum/view.php?id='.$forum1_object->cmid.'">'.$forum1.'</a>';
        $forum2_link = '<a class="autolink" title="'.$forum2.'" href="'.$CFG->wwwroot.'/mod/forum/view.php?id='.$forum2_object->cmid.'">'.$forum2.'</a>';

        // Access it like we're the learner.
        $this->setUser($learner);

        // Check it filters at the cm context level.
        $filter = new filter_activitynames($cmcontext, []);
        $expected = str_replace('Test forum 2', $forum2_link, $text);
        $this->assertSame($expected, $filter->filter($text));

        // Check it filters at the course context level.
        $filter = new filter_activitynames($coursecontext, []);
        $expected = str_replace($forum1, $forum1_link, $text);
        $expected = str_replace($forum2, $forum2_link, $expected);
        $this->assertSame($expected, $filter->filter($text));

        // Confirm that this filter is indeed compatible with clean_text.
        $this->assertSame($expected, clean_text($expected, FORMAT_HTML));

        // Check nothing gets filtered at the system level.
        $filter = new filter_activitynames(\context_system::instance(), []);
        $this->assertSame($text, $filter->filter($text));
    }

    public function test_is_compatible_with_clean_text() {

        $method = new ReflectionMethod('filter_activitynames', 'is_compatible_with_clean_text');
        $method->setAccessible(true);
        self::assertTrue($method->invoke(null));

    }

}