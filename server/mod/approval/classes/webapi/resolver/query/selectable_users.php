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

namespace mod_approval\webapi\resolver\query;

use core\entity\user;
use core\pagination\base_paginator;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use core\webapi\execution_context;
use core\webapi\middleware\require_authenticated_user;
use mod_approval\data_provider\user\selectable_users as selectable_users_provider;
use mod_approval\exception\access_denied_exception;
use mod_approval\model\workflow\workflow;
use mod_approval\webapi\middleware\require_workflow;

/**
 * Get a list of approver users
 *
 * @package mod_approval\webapi\resolver\query
 */
class selectable_users extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'];
        $user = user::logged_in();

        /** @var workflow $workflow */
        $workflow = $args['workflow'];
        if (!$workflow->get_interactor($user->id)->has_manage_individual_approvers_capability()) {
            throw access_denied_exception::workflow('Cannot manage approvers');
        }

        $cursor = $input['pagination']['cursor'] ?? null;
        $limit =  $input['pagination']['limit'] ?? base_paginator::DEFAULT_ITEMS_PER_PAGE;
        $filters = $input['filters'] ?? [];

        return (new selectable_users_provider($user))
            ->add_filters($filters)
            ->get_page($cursor, $limit);
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