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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\query;

use core\entity\user;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use core\webapi\execution_context;
use core\webapi\middleware\require_authenticated_user;
use mod_approval\entity\assignment\assignment;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\assignment\assignment_type\provider as assignment_type_provider;
use mod_approval\model\status;
use mod_approval\webapi\middleware\require_workflow;

/**
 * Get the list of assignment_identifiers for assignments of a given type in a workflow
 *
 * @package mod_approval\webapi\resolver\query
 */
class assignment_identifiers extends query_resolver {

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

        $assignment_type = assignment_type_provider::get_by_enum($input['assignment_type'])::get_code();

        return assignment::repository()
            ->select('assignment_identifier')
            ->where('course', '=', $workflow->course_id)
            ->where('assignment_type', '=', $assignment_type)
            ->where('status', '!=', status::ARCHIVED)
            ->get()
            ->map(function ($item){
                return $item->assignment_identifier;
            });
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
