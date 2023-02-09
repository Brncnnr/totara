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
 * @author Angela Kuznetsova <angela.Kuznetsova@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\query;

use core\entity\user;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use core\webapi\execution_context;
use core\webapi\middleware\require_authenticated_user;
use invalid_parameter_exception;
use mod_approval\entity\assignment\assignment;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\assignment\assignment as assignment_model;
use mod_approval\model\assignment\assignment_type\provider as assignment_type_provider;
use mod_approval\webapi\middleware\require_workflow;

/**
 * Get the assignment of a given type and identifier in a workflow
 *
 * @package mod_approval\webapi\resolver\query
 */
class override_for_assignment_type extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];
        $user = user::logged_in();

        // Check that user has capability to manage overrides.
        $workflow = $args['workflow'];
        if (!$workflow->get_interactor($user->id)->has_manage_assignment_overrides_capability()) {
            throw access_denied_exception::workflow('Cannot manage assignment overrides');
        }

        if (empty($input['assignment_type'])) {
            throw new invalid_parameter_exception('Invalid assignment parameters, assignment type is required.');
        }
        if (empty($input['assignment_identifier'])) {
            throw new invalid_parameter_exception('Invalid assignment parameters, assignment identifier is required.');
        }

        $assignment_type = assignment_type_provider::get_by_enum($input['assignment_type'])::get_code();
        $assignment_identifier = $input['assignment_identifier'];

         $assignment_entity = assignment::repository()
            ->where('course', '=', $workflow->course_id)
            ->where('assignment_type', '=', $assignment_type)
            ->where('assignment_identifier', '=', $assignment_identifier)
            ->one();

         return $assignment_entity ? assignment_model::load_by_entity($assignment_entity) : null;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            require_workflow::by_input_workflow_id(),
        ];
    }
}