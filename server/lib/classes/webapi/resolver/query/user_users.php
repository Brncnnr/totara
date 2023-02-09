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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\query;

use core\data_providers\users as users_provider;
use core\entity\user;
use core\pagination\cursor;
use core\webapi\query_resolver;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\execution_context;
use core_user\exception\user_users_exception;
use core_user\external\user_interactor;
use totara_core\data_provider\provider;

/**
 * Handles the "core_user_users" GraphQL query.
 */
class user_users extends query_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $query = $args['query'] ?? [];
        $sort = $query['sort'] ?? [];
        if (!empty($sort) && count($sort) > 1) {
            throw new user_users_exception("Sorting by more than one column is not currently supported.");
        }
        if (!empty($sort) && empty($sort[0]['column'])) {
            throw new user_users_exception("Required parameter 'column' not being passed.");
        }
        $sort = reset($sort);
        $order_by = $sort['column'] ?? '';
        $order_dir = $sort['direction'] ?? 'ASC';
        $result_size = $query['pagination']['limit'] ?? provider::DEFAULT_PAGE_SIZE;

        $enc_cursor = $query['pagination']['cursor'] ?? null;
        $cursor = $enc_cursor ? cursor::decode($enc_cursor) : null;

        $current_user_id = user::logged_in()->id;
        $interactor = new user_interactor($current_user_id);

        if (!$interactor->can_view()) {
            throw new user_users_exception('You do not have capabilities to view users.');
        }

        return users_provider::create_active_users_provider(
            $current_user_id,
            null,
            false,
            false,
            false
        )
            ->set_page_size($result_size)
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