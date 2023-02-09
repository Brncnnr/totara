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
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use invalid_parameter_exception;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\assignment\assignment;
use mod_approval\model\assignment\assignment_approval_level;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage;
use mod_approval\webapi\middleware\require_assignment;

/**
 * Fetches the ancestor assignment_approval_levels of a given workflow stage.
 */
class ancestor_assignment_approval_levels extends query_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        /** @var assignment $assignment */
        $assignment = $args['assignment'];

        // Workflow determines access control in this case.
        $workflow = workflow::load_by_course_id($assignment->course_id);
        $ec->set_relevant_context($workflow->get_context());
        if (!$workflow->get_interactor(user::logged_in()->id)->can_manage_workflow_approvers()) {
            throw access_denied_exception::workflow('Cannot manage workflow approvers');
        }

        $input = $args['input'];
        if (empty($input['workflow_stage_id'])) {
            throw new invalid_parameter_exception('workflow_stage_id is required');
        }
        $workflow_stage = workflow_stage::load_by_id($input['workflow_stage_id']);

        // Collect ancestor assignment_approval_levels for each approval_level in this stage.
        $items = [];
        foreach ($workflow_stage->approval_levels as $approval_level) {
            $current_assignment_approval_level = new assignment_approval_level($assignment, $approval_level);
            $items[] = $current_assignment_approval_level->get_ancestor_assignment_approval_level();
        }

        // Filter out null values, and return as an array of items.
        return ['items' => array_filter($items)];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            require_assignment::by_input_assignment_id()
        ];
    }
}