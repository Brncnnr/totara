<?php
/*
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package core_course
 */

namespace core\webapi\resolver\mutation;

use \core\webapi\execution_context;
use core\webapi\middleware\require_login_course;
use core\webapi\mutation_resolver;

/**
 * Mutation to move a job assignment to a new position.
 */
class completion_course_self_complete extends mutation_resolver {

    /**
     * Self-completes a course as the current user.
     *
     * @param array $args
     * @param execution_context $ec
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec) {
        global $CFG, $USER, $DB;

        require_once($CFG->libdir . '/completionlib.php');

        // Courseid must be provided
        if (empty($args['courseid'])) {
            throw new \invalid_parameter_exception('courseid required');
        }
        $courseid = $args['courseid'];

        // The following closely follows course/togglecompletion.php

        // Load course.
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

        // Check if completion is enabled and available to user.
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            throw new \moodle_exception('completionnotenabled', 'completion');
        } else if (!$completion->is_tracked_user($USER->id)) {
            throw new \moodle_exception('nottracked', 'completion');
        }

        // Get course completion.
        $completion = $completion->get_completion($USER->id, COMPLETION_CRITERIA_TYPE_SELF);
        if (!$completion) {
            throw new \moodle_exception('noselfcompletioncriteria', 'completion');
        }

        // Check if the user has already marked themselves as complete.
        if ($completion->is_complete()) {
            throw new \moodle_exception('useralreadymarkedcomplete', 'completion');
        }

        $completion->mark_complete();
        return true;
    }

    public static function get_middleware(): array {
        return [
            new require_login_course('courseid')
        ];
    }
}

