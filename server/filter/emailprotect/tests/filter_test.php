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
 * @package filter_emailprotect
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class filter_emailprotect_filter_testcase
 */
class filter_emailprotect_filter_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/filter/emailprotect/filter.php');
        parent::setUpBeforeClass();
    }

    public function test_filter() {

        // Prep the required structure.
        $learner = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($learner->id, $course->id);
        $coursecontext = context_course::instance($course->id);

        // Prep some data.
        $email = 'admin@example.com';
        $text = "You can email me at {$email} any time you want";
        $mailto = "You can email me at <a href='mailto:{$email}'>{$email}</a> any time you want";

        // Access it like we're the learner.
        $this->setUser($learner);

        // Check it filters at the course context level.
        $filter = new filter_emailprotect($coursecontext, []);
        $this->assertStringNotContainsString($email, $filter->filter($text));
        $this->assertStringNotContainsString($email, $filter->filter($mailto));

        // Check it filters at the system level.
        $filter = new filter_emailprotect(\context_system::instance(), []);
        $this->assertStringNotContainsString($email, $filter->filter($text));
        $this->assertStringNotContainsString($email, $filter->filter($mailto));

        // Confirm that this filter is not compatible with clean_text.
        $filtered = $filter->filter($text);
        $this->assertNotSame($filtered, clean_text($filtered, FORMAT_HTML));
        $filtered = $filter->filter($mailto);
        $this->assertNotSame($filtered, clean_text($filtered, FORMAT_HTML));
    }

    public function test_is_compatible_with_clean_text() {

        $method = new ReflectionMethod('filter_emailprotect', 'is_compatible_with_clean_text');
        $method->setAccessible(true);
        self::assertFalse($method->invoke(null));

    }

}