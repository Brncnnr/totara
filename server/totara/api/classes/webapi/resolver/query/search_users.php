<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Arshad Anwer <arshad.anwer@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\webapi\resolver\query;

use context_system;
use context_tenant;
use core\pagination\cursor_paginator;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\query_resolver;
use totara_api\webapi\middleware\require_manage_capability;
use core\entity\user_repository;
use core\entity\user;

/**
 * Class search_users
 * @package totara_api\webapi\resolver\query
 */
class search_users extends query_resolver {
    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        $input = $args['input'];

        $has_tenant_id = false;
        if (isset($input['tenant_id'])) {
            $has_tenant_id = true;
        }
        $collection = user_repository::search(
            $has_tenant_id ? context_tenant::instance($input['tenant_id']) : context_system::instance(),
            $input['pattern'] ?? '',
            cursor_paginator::DEFAULT_ITEMS_PER_PAGE,
            false,
            true
        );

        return [
            'users' => $collection->reduce(function (array $users, user $user) use ($has_tenant_id) {
                // Filter out site admin users.
                if (!is_siteadmin($user->id)) {
                    if ($has_tenant_id) {
                        // Remove tenant participant.
                        if (!is_null($user->tenantid)) {
                            $users[] = $user;
                        }
                    // Make sure only system users if no tenant id is set..
                    } else if (is_null($user->tenantid)) {
                        $users[] = $user;
                    }
                }
                return $users;
            }, [])
        ];
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user(),
            new require_advanced_feature('api'),
            require_manage_capability::by_tenant_id('input.tenant_id', true)
        ];
    }
}