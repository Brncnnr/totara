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
 * @package totara_core
 */

namespace totara_core\local\visibility\course;

defined('MOODLE_INTERNAL') || die();

/**
 * Program visibility capability map.
 */
final class map extends \totara_core\local\visibility\map {

    /**
     * Returns the view hidden capability for the items within this map.
     *
     * @return string
     */
    public function get_view_hidden_capability(): string {
        return 'moodle/course:viewhiddencourses';
    }

    /**
     * Returns the map table name.
     *
     * @return string
     */
    protected function get_map_table_name(): string {
        return 'totara_core_course_vis_map';
    }

    /**
     * Returns the instance id field name.
     *
     * e.g. courseid, programid
     *
     * @return string
     */
    protected function get_instance_field_name(): string {
        return 'courseid';
    }

    /**
     * Returns the context level for this map.
     *
     * @return int
     */
    protected function get_context_level(): int {
        return CONTEXT_COURSE;
    }
}