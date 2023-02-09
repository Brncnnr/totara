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
use core\webapi\middleware\require_login_course_via_coursemodule;
use core\webapi\mutation_resolver;

/**
 * Mutation to move a job assignment to a new position.
 */
class completion_activity_self_complete extends mutation_resolver {

    /**
     * Self-completes a course as the current user.
     *
     * @param array $args
     * @param execution_context $ec
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec) {
        global $CFG;

        // Get course module and course (provided by middleware)
        $cm = $args['cm'];
        $course = $args['course'];

        require_once($CFG->libdir . '/completionlib.php');

        // Completion status must be provided
        if (!isset($args['complete'])) {
            throw new \invalid_parameter_exception('complete required');
        }
        $complete = $args['complete'];

        // The following closely follows course/togglecompletion.php.

        $targetstate = $complete ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;

        // Check if course completion is enabled and available to user.
        $completion = new \completion_info($course);
        if (!$completion->is_enabled()) {
            throw new \moodle_exception('completionnotenabled', 'completion');
        }

        // NOTE: All users are allowed to toggle their completion state, including
        // users for whom completion information is not directly tracked. (I.e. even
        // if you are a teacher, or admin who is not enrolled, you can still toggle
        // your own completion state. You just don't appear on the reports.)

        if ($cm->completion == COMPLETION_TRACKING_NONE) {
            throw new \moodle_exception('completionnotenabled', 'completion');
        }

        // Check completion state is manual
        if ($cm->completion != COMPLETION_TRACKING_MANUAL) {
            throw new \moodle_exception('noselfcompletioncriteria', 'completion');
        }

        $completion->update_state($cm, $targetstate);

        return true;
    }

    public static function get_middleware(): array {
        return [
            new require_login_course_via_coursemodule('cmid')
        ];
    }
}
