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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\webapi\resolver\query;

use container_approval\approval;
use core\entity\user;
use core\pagination\offset_cursor_paginator;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use mod_approval\data_provider\assignment\override_assignments_for_workflow_stage;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\workflow\workflow;
use mod_approval\webapi\middleware\require_workflow;

/**
 * A list of all override assignments in a workflow.
 */
class override_assignments extends query_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $context = approval::get_default_category_context();
        $ec->set_relevant_context($context);

        /** @var workflow $workflow */
        $workflow = $args['workflow'];

        if (!$workflow->get_interactor(user::logged_in()->id)->can_manage_workflow_approvers()) {
            throw access_denied_exception::workflow('Cannot manage workflow approvers');
        }

        $input = $args['input'];

        $provider = new override_assignments_for_workflow_stage($input['workflow_stage_id']);

        $limit =  $input['pagination']['limit'] ?? offset_cursor_paginator::DEFAULT_ITEMS_PER_PAGE;
        $page = $input['pagination']['page'] ?? 1;
        $filters = $input['filters'] ?? [];
        $sort_by = strtolower($input['sort_by'] ?? 'name_asc');
        return $provider
            ->add_filters($filters)
            ->sort_by($sort_by)
            ->get_page($limit, $page);
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
            require_workflow::by_input_workflow_stage_id(),
        ];
    }
}