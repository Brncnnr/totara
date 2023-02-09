<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package mod_perform
 */

namespace mod_perform\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use core\pagination\base_paginator;
use mod_perform\data_providers\activity\activity as activity_data_provider;
use mod_perform\util;

class paginated_activities extends query_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $context = util::get_default_context();
        $ec->set_relevant_context($context);

        if (!empty($args['query_options']['sort_by'])) {
            activity_data_provider::validate_sort_by($args['query_options']['sort_by']);
        }

        require_capability('mod/perform:view_manage_activities', $context);
        $cursor = $args['query_options']['pagination']['cursor'] ?? null;
        $limit =  $args['query_options']['pagination']['limit'] ?? base_paginator::DEFAULT_ITEMS_PER_PAGE;
        $filters = $args['query_options']['filters'] ?? [];
        $sort_by = $args['query_options']['sort_by'] ?? activity_data_provider::SORT_BY_CREATION_DATE;

        return (new activity_data_provider())
            ->add_filters($filters)
            ->sort_by($sort_by)
            ->get_activities_page($cursor, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('performance_activities'),
            new require_login()
        ];
    }
}