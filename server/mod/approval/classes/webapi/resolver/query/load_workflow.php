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
use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use mod_approval\exception\access_denied_exception;
use mod_approval\interactor\category_interactor;
use mod_approval\interactor\workflow_interactor;
use mod_approval\model\workflow\workflow;

/**
 * Query for loading a workflow.
 *
 * @package mod_approval\webapi\resolver\query
 */
class load_workflow extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];
        $user = user::logged_in();

        $category_interactor = new category_interactor(
            container_approval::get_default_category_context(),
            $user->id
        );
        if (!$category_interactor->can_manage_workflows()) {
            throw access_denied_exception::manage_workflows();
        }

        $workflow = workflow::load_by_id($input['workflow_id']);
        $ec->set_relevant_context($workflow->get_context());

        return [
            'workflow' => $workflow
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
