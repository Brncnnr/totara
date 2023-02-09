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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_course
 */

class core_course_renderer_testcase extends advanced_testcase {

    /**
     * Get's a course renderer.
     * @return core_course_renderer
     */
    private static function get_renderer(): \core_course_renderer {
        global $PAGE;
        /** @var core_course_renderer $renderer */
        $renderer = $PAGE->get_renderer('core_course');
        return $renderer;
    }

    /**
     * Tests that getting the course section cm gets the correct intro, and that it is formatted correctly.
     * Noting that the whole API is a little off as the intro will be formatted twice.
     */
    public function test_course_section_cm() {
        global $CFG, $USER;

        $this->setAdminUser();

        $intro_raw = '<p>I am description</p><style type=\'text/css\'>body {background-color:red !important;}</style>';
        $intro_cleaned = clean_text($intro_raw);

        $course = $this->getDataGenerator()->create_course();
        $section = $this->getDataGenerator()->create_course_section(['course' => $course, 'section' => 1]);
        $forum = $this->getDataGenerator()->create_module('forum', [
            'course' => $course,
            'section' => 1, // '1' here means first section after the '0' section.
            'shortname' => 'Test forum',
            'idnumber' => 'test_forum',
            'introeditor' => [
                'text' => $intro_raw,
                'format' => FORMAT_MOODLE,
                'itemid' => 0,
            ],
            'showdescription' => 1
        ]);

        self::assertSame($intro_raw, $forum->intro);
        self::assertSame(FORMAT_MOODLE, $forum->introformat);
        self::assertSame('0', $CFG->disableconsistentcleaning);

        $renderer = self::get_renderer();
        $expected = '<div class="contentafterlink"><div class="no-overflow"><div class="no-overflow">%s</div></div></div>';

        rebuild_course_cache($course->id, true);
        $modinfo = get_fast_modinfo($course->id, $USER->id);
        $cminfo = $modinfo->get_cm($forum->cmid);
        self::assertSame(sprintf($expected, $intro_cleaned), $renderer->course_section_cm_text($cminfo, []));

        $CFG->disableconsistentcleaning = 1;

        rebuild_course_cache($course->id, true);
        $modinfo = get_fast_modinfo($course->id, $USER->id);
        $cminfo = $modinfo->get_cm($forum->cmid);
        self::assertSame(sprintf($expected, $intro_raw), $renderer->course_section_cm_text($cminfo, []));

        $CFG->disableconsistentcleaning = 0;

        rebuild_course_cache($course->id, true);
        $modinfo = get_fast_modinfo($course->id, $USER->id);
        $cminfo = $modinfo->get_cm($forum->cmid);
        self::assertSame(sprintf($expected, $intro_cleaned), $renderer->course_section_cm_text($cminfo, []));
    }

}