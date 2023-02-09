<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package core_user
 */

namespace core\webapi\resolver\query;

use context_user;
use core\data_providers\users as users_provider;
use core\entity\user;
use core\pagination\cursor;
use core\webapi\query_resolver;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\execution_context;
use totara_core\data_provider\provider;

/**
 * Handles the "core_users" GraphQL query.
 */
class users extends query_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $query = $args['query'] ?? [];
        $order_by = $query['order_by'] ?? 'firstname,lastname';
        $order_dir = $query['order_dir'] ?? 'ASC';
        $result_size = $query['result_size'] ?? provider::DEFAULT_PAGE_SIZE;

        $enc_cursor = $query['cursor'] ?? null;
        $cursor = $enc_cursor ? cursor::decode($enc_cursor) : null;
        $filters = $query['filters'] ?? [];

        $current_user_id = user::logged_in()->id;
        if (!$ec->has_relevant_context()) {
            $context = context_user::instance($current_user_id);
            $ec->set_relevant_context($context);
        }

        return users_provider::create_active_users_provider($current_user_id)
            ->set_page_size($result_size)
            ->set_filters($filters)
            ->set_order($order_by, $order_dir)
            ->fetch_paginated($cursor);
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            require_authenticated_user::class
        ];
    }

}
