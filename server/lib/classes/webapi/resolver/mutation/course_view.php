<?php
/*
* This file is part of Totara Learn
*
* Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
* @author David Curry <david.curry@totaralearning.com>
* @package core
*/

namespace core\webapi\resolver\mutation;

use core\webapi\execution_context;
use core\webapi\middleware\require_login_course;
use core\webapi\mutation_resolver;

class course_view extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        global $DB;

        // Get course module and course (provided by middleware)
        $courseid = $args['course_id'];
        $sectionid = $args['section_id'] ?? 0;

        // Load course.
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

        // Get the section number from the id, if provided.
        $section = 0;
        if (!empty($sectionid)) {
            $section = $DB->get_field('course_sections', 'section', ['id' => $sectionid, 'course' => $course->id], MUST_EXIST);
        }

        $context = \context_course::instance($course->id);
        $ec->set_relevant_context($context, 0);

        // Trigger course viewed event.
        course_view($context, $section);

        return true;
    }

    public static function get_middleware(): array {
        return [
            new require_login_course('course_id')
        ];
    }
}
