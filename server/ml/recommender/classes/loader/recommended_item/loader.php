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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_recommender
 */

namespace ml_recommender\loader\recommended_item;

use core\orm\pagination\offset_cursor_paginator;
use core\orm\query\builder;
use ml_recommender\query\recommended_item\item_query;
use ml_recommender\query\recommended_item\user_query;

/**
 * Base loader for recommendations
 *
 * @package ml_recommender\loader\recommended_item
 */
abstract class loader {
    /**
     * loader constructor.
     * Preventing this class from construction
     */
    private function __construct() {
    }

    /**
     * Fetch the recommended items.
     *
     * @param item_query $query
     * @param int $actor_id
     * @return offset_cursor_paginator
     */
    abstract public static function get_recommended(item_query $query, int $actor_id = 0): offset_cursor_paginator;

    /**
     * Fetch the recommended items for the user query.
     *
     * @param user_query $query
     * @param int $actor_id
     * @return offset_cursor_paginator
     */
    abstract public static function get_recommended_for_user(user_query $query, int $actor_id = 0): offset_cursor_paginator;

    /**
     * @param builder $builder
     * @param string $item_owner_col
     * @param int $user_id
     */
    protected static function filter_multi_tenancy(builder $builder, string $item_owner_col, int $user_id = 0): void {
        global $CFG, $USER;
        if (!$user_id) {
            $user_id = $USER->id;
        }

        if (!empty($CFG->tenantsenabled) && !is_siteadmin($user_id)) {
            $tenant_id = builder::get_db()->get_field('user', 'tenantid', ['id' => $user_id], MUST_EXIST);
            // User is assigned to a tenant and/or is admin
            if (null !== $tenant_id) {
                $builder->join(['user', 'u'], function (builder $join) use ($CFG, $tenant_id, $item_owner_col): void {
                    $join->where_field($item_owner_col, 'u.id');
                    $join->where('u.suspended', 0);
                    $join->where('u.deleted', 0);

                    if (!empty($CFG->tenantsisolated)) {
                        // Isolation mode is on, hence we are skipping those users that belong
                        // to the system level.
                        $join->where('u.tenantid', $tenant_id);
                    } else {
                        $join->where_raw(
                            '(u.tenantid = :tenant_id OR u.tenantid IS NULL)',
                            ['tenant_id' => $tenant_id]
                        );
                    }
                });
            } else {
                // User is participant
                $tenant_builder = builder::table('tenant', 't');
                $tenant_builder->join(['cohort_members', 'cm'], 't.cohortid', 'cm.cohortid');
                $tenant_builder->select('t.id AS tenant_id');
                $tenant_builder->where('cm.userid', $user_id);

                $collection = $tenant_builder->get();
                $tenant_ids = $collection->pluck('tenant_id');
                if (!empty($tenant_ids)) {
                    [$in_sql, $parameters] = builder::get_db()->sql_in($tenant_ids);
                    $builder->join(['user', 'u'], function (builder $join) use ($in_sql, $parameters, $item_owner_col): void {
                        $join->where_field($item_owner_col, 'u.id');
                        $join->where_raw("(u.tenantid {$in_sql} OR u.tenantid IS NULL)", $parameters);
                    });
                } else {
                    $builder->join(['user', 'u'], $item_owner_col, 'u.id');
                    $builder->where_null('u.tenantid');
                }
                $builder->where('u.suspended', 0);
                $builder->where('u.deleted', 0);
            }
        }
    }
}