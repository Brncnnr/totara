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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace mod_approval\webapi\resolver\query;

use container_approval\approval as container_approval;
use context_user;
use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use mod_approval\entity\workflow\workflow;
use mod_approval\entity\workflow\workflow_type;
use mod_approval\entity\workflow\workflow_version;
use mod_approval\exception\access_denied_exception;
use mod_approval\interactor\category_interactor;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow_type as workflow_type_model;

/**
 * Query for loading all workflow types.
 *
 * @package mod_approval\webapi\resolver\query
 */
class load_workflow_types extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $user = user::logged_in();
        $user_context = context_user::instance($user->id);

        if (!$ec->has_relevant_context()) {
            $ec->set_relevant_context($user_context);
        }

        // Require_active_workflow flag
        $require_active_workflow = $args['input']['require_active_workflow'] ?? false;

        // If user does not have manage_workflows capability, always set require_active_workflow flag to true.
        if (!$require_active_workflow) {
            $category_interactor = new category_interactor(
                container_approval::get_default_category_context(),
                $user->id
            );
            if (!$category_interactor->can_manage_workflows()) {
                $require_active_workflow = true;
            }
        }

        $workflow_types = workflow_type::repository()
            ->order_by('name');

        if ($require_active_workflow) {
            $workflow_types->join([workflow::TABLE, 'workflow'], 'id', '=', 'workflow_type_id')
                ->join([workflow_version::TABLE, 'workflow_version'], 'workflow_version.workflow_id', '=', 'workflow.id')
                ->where('workflow_version.status', '=', status::ACTIVE);
        }

        return [
            'workflow_types' => $workflow_types->get()->map_to(workflow_type_model::class),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('approval_workflows'),
            new require_authenticated_user(),
        ];
    }

}