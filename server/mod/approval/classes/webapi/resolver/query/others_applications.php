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

use container_approval\approval;
use core\entity\user;
use core\pagination\offset_cursor_paginator;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use mod_approval\data_provider\application\applications_for_others;

/**
 * A list of applications the current user can see, which do not pertain to the current user.
 */
class others_applications extends query_resolver {
    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        $context = approval::get_default_category_context();
        $ec->set_relevant_context($context);

        $user_id = user::logged_in()->id;
        $provider = new applications_for_others($user_id);

        $input = $args['query_options'] ?? [];

        $limit =  $input['pagination']['limit'] ?? offset_cursor_paginator::DEFAULT_ITEMS_PER_PAGE;
        $page = $input['pagination']['page'] ?? 1;
        $filters = $input['filters'] ?? [];
        $sort_by = strtolower($input['sort_by'] ?? 'submitted');
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
            new require_authenticated_user()
        ];
    }
}