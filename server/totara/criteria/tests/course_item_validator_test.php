<?php
/*
 * This file is part of Totara Learn
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

use totara_criteria\validators\course_item_validator;

/**
 * @group totara_competency
 */
class totara_criteria_course_item_validator_testcase extends advanced_testcase {

    /**
     * Test validate_item
     */
    public function test_validate_item() {
        global $CFG;

        // Course completion only enabled for every second course
        $courses = [];
        for ($i = 1; $i < 5; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course(['enablecompletion' => $i % 2]);
        }

        // With global completion enabled
        $CFG->enablecompletion = true;
        foreach ($courses as $idx => $course) {
            $this->assertEquals($idx % 2, course_item_validator::validate_item($course->id));
        }

        // Non-existent course
        $this->assertFalse(course_item_validator::validate_item(12345));

        // With global completion disabled
        $CFG->enablecompletion = false;
        foreach ($courses as $idx => $course) {
            $this->assertFalse(course_item_validator::validate_item($course->id));
        }
    }

}
