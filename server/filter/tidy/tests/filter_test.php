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
 * @package filter_tidy
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class filter_tidy_filter_testcase
 */
class filter_tidy_filter_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/filter/tidy/filter.php');
        parent::setUpBeforeClass();
    }

    public function test_filter() {

        if (!extension_loaded('tidy') || !function_exists('tidy_repair_string')) {
            // TL-20808 will see this requirement shown to the user if this filter is enabled.
            $this->markTestSkipped('The tidy extension is not currently loaded, the filter will not do anything.');
        }


        // Prep the required structure.
        $learner = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($learner->id, $course->id);
        $coursecontext = context_course::instance($course->id);

        // Prep some data.
        $text =     '<p>You should <a href="#" target="_blank" rel="noreferrer noopener">check out the Test forum</a> for the latest news <ul><li>One</li><li>Two<li>Three</ul> now.';
        $expected = '<p>You should <a href="#" target="_blank" rel="noreferrer noopener">check out the Test forum</a> for the latest news</p><ul><li>One</li><li>Two</li><li>Three</li></ul>now.';

        // Check it filters at the course context level.
        $filter = new filter_tidy($coursecontext, []);
        $actual = str_replace("\n", '', $filter->filter($text));
        $this->assertSame($expected, $actual);

        // Check nothing gets filtered at the system level.
        $filter = new filter_tidy(\context_system::instance(), []);
        $actual = str_replace("\n", '', $filter->filter($text));
        $this->assertSame($expected, $actual);
    }

    public function test_is_compatible_with_clean_text() {

        $method = new ReflectionMethod('filter_tidy', 'is_compatible_with_clean_text');
        $method->setAccessible(true);
        self::assertTrue($method->invoke(null));

    }

}