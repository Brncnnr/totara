<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package core
 */

namespace core\entity;

use coding_exception;
use context;
use core\orm\query\builder;
use core\orm\query\field;
use core\tenant_orm_helper;

/**
 * This trait provides functionality to expand a group of users, i.e. a cohort or a position,
 * to the individual users in that group.
 *
 * It can be used by entities and to be able to function the entity needs to provide a few constants:
 *
 * - EXPAND_TABLE
 *   The name of the table to query from, i.e. cohort_members
 *
 * - EXPAND_QUERY_COLUMN
 *   The name of the column which identifies the group, i.e. cohortid
 *
 * - EXPAND_SELECT_COLUMN
 *   The name of the column representing the userid in the table, i.e. userid
 *
 * To be multi tenancy compatible a context has to be provided. Then only the users
 * who are in the same tenant as the given context are being loaded.
 */
trait expand {

    /**
     * Get all records for the given ids which could be only one entry or multiple ones depending on the source or target.
     * For example: For users it could be all members of a cohort/position/organisation or it could be an individual user.
     *
     * @param int[] $ids
     * @param context|null $context pass a context to be to make this multi tenancy aware
     * @return array
     */
    public static function expand_multiple(array $ids, ?context $context = null): array {
        if (empty($ids)) {
            throw new coding_exception('To be able to expand at least one id must be specified');
        }

        if (empty(static::EXPAND_TABLE) || empty(static::EXPAND_QUERY_COLUMN) || empty(static::EXPAND_SELECT_COLUMN)) {
            throw new coding_exception('Please define the EXPAND_TABLE, EXPAND_QUERY_COLUMN and EXPAND_SELECT_COLUMN constants');
        }

        $user_alias = uniqid('user');

        $builder = builder::table(static::EXPAND_TABLE)
            ->select(static::EXPAND_SELECT_COLUMN)
            ->join(['user', $user_alias], static::EXPAND_SELECT_COLUMN, 'id')
            ->where_in(static::EXPAND_QUERY_COLUMN, $ids)
            ->where("{$user_alias}.deleted", 0)
            ->group_by(static::EXPAND_SELECT_COLUMN)
            // Make sure this is multi tenancy compatible.
            ->when(!is_null($context), function (builder $builder) use ($context) {
                tenant_orm_helper::restrict_users(
                    $builder,
                    new field(static::EXPAND_SELECT_COLUMN, $builder),
                    $context
                );
            });

        $result = $builder->get(true);

        return array_unique($result->pluck(static::EXPAND_SELECT_COLUMN));
    }

    /**
     * Get all records for the given id which could be only one entry or multiple ones depending on the source or target.
     * For example: For users it could be all members of a cohort/position/organisation or it could be an individual user.
     *
     * @param context|null $context pass a context to be to make this multi tenancy aware
     * @return array
     */
    public function expand(?context $context = null): array {
        return self::expand_multiple([$this->id], $context);
    }

}