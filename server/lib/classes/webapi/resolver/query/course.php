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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @author David Curry <david.curry@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\query;

use context_course;
use core\webapi\execution_context;
use core\webapi\middleware\require_login_course;
use core\webapi\query_resolver;

class course extends query_resolver {

    public static function resolve(array $args, execution_context $ec) {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        // Course visibility and access is already covered in the course_require_login middleware
        $course = get_course($args['courseid']);
        $course->image = course_get_image($course);

        $context = context_course::instance($course->id);
        $ec->set_relevant_context($context);
        // Trigger course viewed event.
        course_view($context);

        return (object)$course;
    }

    public static function get_middleware(): array {
        return [
            new require_login_course('courseid')
        ];
    }

}
