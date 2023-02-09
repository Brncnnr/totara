<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_course
 */
defined('MOODLE_INTERNAL') || die();

use totara_core\hook\manager;
use core_course\hook\format\legacy_course_format_supported;

class core_course_legacy_course_format_hook_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_remove_course_format_from_hook(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/course/lib.php");

        // Set up the hook maps
        manager::phpunit_replace_watchers([
            [
                'hookname' => legacy_course_format_supported::class,
                'callback' => function (legacy_course_format_supported $hook): void {
                    $hook->remove_format('none');
                    $hook->remove_format('weeks');
                }
            ]
        ]);

        // Run the hook and check if the formats are actually being removed from the list.
        $course_formats = get_sorted_course_formats(true);

        $hook = new legacy_course_format_supported($course_formats);
        $hook->execute();

        $result = $hook->get_formats();
        $this->assertNotEmpty($result);

        // Check that if the hook is not adding anything new to the result.
        foreach ($result as $item) {
            $this->assertTrue(in_array($item, $course_formats));
        }

        // Check that if we actually removed some of the formats
        $this->assertFalse(in_array('none', $result));
        $this->assertFalse(in_array('weeks', $result));
    }
}