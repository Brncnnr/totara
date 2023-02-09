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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 */

namespace core;

use core\orm\entity\repository;
use core\orm\query\builder;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class to provide user related methods for use in builder / repository instances
 *
 * @package totara_tenant
 */
class user_orm_helper {

    /**
     * Applies a filter for fullnames
     *
     * @param builder|repository $builder
     * @param string $search the search string
     * @param string|null $alias optional alias, defaults to the builder alias
     * @return void
     */
    public static function filter_by_fullname($builder, string $search, string $alias = null): void {
        // Filtering by empty string does not make sense
        if ($search === '') {
            return;
        }

        $db = builder::get_db();
        // If no alias is passed use the one from the builder
        if (empty($alias)) {
            $alias = $builder->get_alias_sql();
        }

        $user_name_fields = totara_get_all_user_name_fields(false, $alias, null, null, true);
        $user_name_fields = array_values($user_name_fields);

        if (count($user_name_fields) === 1) {
            $sql_fullname = reset($user_name_fields);
        } else {
            $search_sql_fields = [];
            foreach ($user_name_fields as $field) {
                $search_sql_fields[] = $field;
                $search_sql_fields[] = "' '";
            }
            array_pop($search_sql_fields);
            $user_name_fields = $search_sql_fields;

            $sql_fullname = $db->sql_concat(...$user_name_fields);
        }

        // Note that not all databases support the use of accentsensitive = false.
        $like_sql = $db->sql_like($sql_fullname, ':fullnamesearch', false, false);
        $like_params = ['fullnamesearch' => '%' . $db->sql_like_escape($search) . '%'];
        $builder->where_raw($like_sql, $like_params);
    }

    /**
     * Applies order by fullname depending on what fields are configured for fullname
     *
     * @param $builder
     * @param string|null $alias
     */
    public static function order_by_fullname($builder, string $alias = null) {
        // If no alias is passed use the one from the builder
        if (empty($alias)) {
            $alias = $builder->get_alias_sql();
        }

        $user_name_fields = totara_get_all_user_name_fields(false, $alias, null, null, true);
        foreach ($user_name_fields as $field) {
            $builder->order_by($field);
        }
    }

}