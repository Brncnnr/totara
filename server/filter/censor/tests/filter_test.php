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
 * @package filter_censor
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class filter_censor_filter_testcase
 */
class filter_censor_filter_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/filter/censor/filter.php');
        parent::setUpBeforeClass();
    }

    public function test_filter() {

        set_config('filter_censor_badwords', 'banana,biscuit');

        // Prep the required structure.
        $learner = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($learner->id, $course->id);
        $coursecontext = context_course::instance($course->id);

        // Prep some data.
        $text = 'My favourite biscuits are banana flavoured! Would you like a biscuit?';
        $filtered = 'My favourite <span class="censoredtext" title="biscuit">****</span>s are <span class="censoredtext" title="banana">***</span> flavoured! Would you like a <span class="censoredtext" title="biscuit">****</span>?';

        // Access it like we're the learner.
        $this->setUser($learner);

        // Check it filters at the course context level.
        $filter = new filter_censor($coursecontext, []);
        $this->assertSame($filtered, $filter->filter($text));

        // Check it filters at the system level.
        $filter = new filter_censor(\context_system::instance(), []);
        $this->assertSame($filtered, $filter->filter($text));

        // Confirm that this filter is indeed compatible with clean_text.
        $this->assertSame($filtered, clean_text($filtered, FORMAT_HTML));
    }

    public function test_is_compatible_with_clean_text() {

        $method = new ReflectionMethod('filter_censor', 'is_compatible_with_clean_text');
        $method->setAccessible(true);
        self::assertTrue($method->invoke(null));

    }

}