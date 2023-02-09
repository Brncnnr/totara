<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_container
 */
defined('MOODLE_INTERNAL') || die();

class core_container_container_type_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_create_default_course_record(): void {
        global $DB;

        $course_record = new stdClass();
        $course_record->fullname = 'course_dd';
        $course_record->shortname = 'dd';

        $course_id = $DB->insert_record('course', $course_record);
        $this->assertTrue(
            $DB->record_exists('course', ['id' => $course_id, 'containertype' => 'container_course'])
        );
    }
}