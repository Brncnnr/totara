<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_job
 */

namespace totara_job\webapi\resolver\mutation;

use coding_exception;
use core\exception\unresolved_record_reference;
use core\reference\user_record_reference;
use core\webapi\execution_context;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use totara_job\exception\job_assignment_create_exception;
use totara_job\job_assignment;
use totara_job\webapi\resolver\helper;

/**
 * Mutation to create a job assignment
 */
class create_job_assignment extends mutation_resolver {

    use helper;

    /**
     * Creates a job assignment.
     *
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec) {
        global $CFG;

        require_once($CFG->dirroot . '/totara/job/lib.php');

        $input = $args['input'] ?? [];

        $not_found_message = 'The user does not exist or you do not have permission to create a job assignment.';
        $user_record_reference = new user_record_reference();
        try {
            $user_reference = $user_record_reference->get_record($input['user'] ?? []);
        } catch (unresolved_record_reference $exception) {
            throw new job_assignment_create_exception($not_found_message);
        }

        $user = self::get_user_from_args(['userid' => $user_reference->id], 'userid', false);

        // They have to be able to view and edit the job assignments in order to create one.
        if (!\totara_job_can_edit_job_assignments($user->id) || !\totara_job_can_view_job_assignments($user)) {
            throw new job_assignment_create_exception($not_found_message);
        }

        $params = self::get_job_assignment_params_from_input($user, $input, job_assignment_create_exception::class);

        $params['userid'] = $user->id;

        try {
            $job_assignment_model = job_assignment::create($params);
        } catch (coding_exception $exception) {
            throw new job_assignment_create_exception($exception->a);
        }

        return [
            'job_assignment' => $job_assignment_model
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user()
        ];
    }

}