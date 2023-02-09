<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\webapi\resolver\query;

use core_container\factory;
use core\pagination\cursor;
use core\webapi\query_resolver;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\execution_context;
use container_workspace\data_providers\non_members as non_members_provider;
use container_workspace\webapi\middleware\require_workspace_members_access;
use totara_core\data_provider\provider;

/**
 * Handles the "container_workspace_non_members" GraphQL query.
 */
class non_members extends query_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $query = $args['query'];
        $order_by = $query['order_by'] ?? 'firstname,lastname';
        $order_dir = $query['order_dir'] ?? 'ASC';
        $result_size = $query['result_size'] ?? provider::DEFAULT_PAGE_SIZE;

        $enc_cursor = $query['cursor'] ?? null;
        $cursor = $enc_cursor ? cursor::decode($enc_cursor) : null;

        $filters = $query['filters'];
        $workspace_id = $filters['workspace_id'];
        if (!$ec->has_relevant_context()) {
            $context = factory::from_id($workspace_id)->get_context();
            $ec->set_relevant_context($context);
        }

        $core_filters = $filters['core_filters'] ?? [];

        return non_members_provider::create_for_workspace($workspace_id)
            ->set_page_size($result_size)
            ->set_filters($core_filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated($cursor);
    }

    /**
     * @return array
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user(),
            new require_advanced_feature('container_workspace'),
            new require_workspace_members_access('query.filters.workspace_id'),
        ];
    }
}
