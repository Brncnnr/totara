<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\mutation;

use core\entity\user as user_entity;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\mutation_resolver;
use mod_approval\exception\access_denied_exception;
use mod_approval\exception\model_exception;
use mod_approval\model\application\application;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_resolver;
use mod_approval\webapi\middleware\require_assignment;
use totara_job\entity\job_assignment;

/**
 * Create a new application
 */
class create_application extends mutation_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];

        $creator = user_entity::logged_in();
        if (empty($input['applicant_id'])) {
            $applicant = clone $creator;
        } else {
            $applicant = new user_entity((int)$input['applicant_id']);
        }

        if (!isset($input['job_assignment_id'])) {
            $job_assignment = null;
        } else {
            $job_assignment = new job_assignment($input['job_assignment_id']);
        }

        /** @var assignment $assignment */
        $assignment = $args['assignment'];

        if (!$assignment->get_interactor($applicant->id, $creator->id)->can_create_application()) {
            throw access_denied_exception::application('User cannot create application for the given applicant');
        }

        $workflow_version = $assignment->workflow->active_version;

        if(is_null($workflow_version)) {
            throw new model_exception ('Workflow is not active');
        }
        // Check that applicant is allowed to create an application at this assignment.
        $assignment_resolver = new assignment_resolver($applicant, $creator);
        $assignment_resolver->resolve();
        $allowed_assignments = $assignment_resolver->get_assignments();
        if (!$allowed_assignments->has('id', $assignment->id)) {
            throw access_denied_exception::assignment('assignment not available for new application by this applicant');
        }

        $application = application::create(
            $workflow_version,
            $assignment,
            $creator->id,
            $applicant->id,
            $job_assignment
        );

        return ['application_id' => $application->refresh()->id];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            require_assignment::by_input_assignment_id(),
        ];
    }
}
