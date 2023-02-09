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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_job
 */

namespace totara_job\webapi\resolver\mutation;

use \core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\mutation_resolver;
use \totara_job\job_assignment;
use totara_job\webapi\resolver\helper;

/**
 * Mutation to sort job assignments
 */
class sort_assignments extends mutation_resolver {

    use helper;

    /**
     * Sorts job assignments
     *
     * @param array $args
     * @param execution_context $ec
     * @return bool
     */
    public static function resolve(array $args, execution_context $ec) {
        global $CFG;

        require_once($CFG->dirroot . '/totara/job/lib.php');

        $user = self::get_user_from_args($args, 'userid', false);
        if (!\totara_job_can_edit_job_assignments($user->id) || !\totara_job_can_view_job_assignments($user)) {
            throw new \coding_exception('No permission to sort job assignments.');
        }

        $jobsids_current = array_map(
            function (job_assignment $job) {
                return $job->id;
            },
            job_assignment::get_all($user->id)
        );

        if (!empty(array_diff($jobsids_current, $args['assignmentids']))) {
            throw new \moodle_exception('error', 'error', '', null, 'Jobs given do not match existing jobs.');
        }

        job_assignment::resort_all($user->id, $args['assignmentids']);

        return true;
    }

    public static function get_middleware(): array {
        return [
            require_login::class
        ];
    }

}

