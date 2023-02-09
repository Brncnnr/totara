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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use invalid_parameter_exception;
use mod_approval\model\application\application;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\webapi\middleware\require_assignment;

/**
 * A list of all approvers on particular workflow_stage and approval_level.
 */
class application_approvers extends query_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];

        if (empty($input['application_id'])) {
            throw new invalid_parameter_exception('application id is required');
        }
        if (empty($input['workflow_stage_approval_level_id'])) {
            throw new invalid_parameter_exception('approval level id is required');
        }
        $application = application::load_by_id($input['application_id']);
        $workflow_stage_approval_level = workflow_stage_approval_level::load_by_id($input['workflow_stage_approval_level_id']);

        // Set-up category context to see full profile tree with fullname, profileimageurl etc.
        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($application->get_context());
        }

        return $application->get_approver_users($workflow_stage_approval_level);
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            require_assignment::by_input_application_id(),
        ];
    }
}
