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
 * @package @mod_approval
 */

namespace mod_approval\webapi\resolver\query;

use container_approval\approval as container_approval;
use context_user;
use core\entity\user;
use core\pagination\base_paginator;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use mod_approval\data_provider\workflow\workflow as workflow_provider;
use mod_approval\exception\access_denied_exception;
use mod_approval\interactor\category_interactor;

/**
 * Paginated manageable workflows query.
 */
class manageable_workflows extends query_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['query_options'];
        $user = user::logged_in();
        $user_context = context_user::instance($user->id);
        $ec->set_relevant_context($user_context);

        $category_interactor = new category_interactor(
            container_approval::get_default_category_context(),
            $user->id
        );
        if (!$category_interactor->can_manage_workflows()) {
            throw access_denied_exception::manage_workflows();
        }

        $filters = [];
        if (!empty($user_context->tenantid)) {
            $filters['tenant_id'] = $user_context->tenantid;
        }
        $filters = array_merge($input['filters'] ?? [], $filters);

        return (new workflow_provider())
            ->add_filters($filters)
            ->sort_by(strtolower($input['sort_by'] ?? 'updated'))
            ->get_page(
                $input['pagination']['page'] ?? 1,
                $input['pagination']['limit'] ?? base_paginator::DEFAULT_ITEMS_PER_PAGE,
                $user->id
            );
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