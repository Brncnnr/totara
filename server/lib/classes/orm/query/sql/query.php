<?php
/*
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core
 * @group orm
 */

namespace core\orm\query\sql;

use core\orm\query\builder;

/**
 * Class query
 *
 * This is where it happens and all the query parts come together to get the complete sql statement and parameters
 * before being passed to the DML layer. Cheers.
 *
 * @internal This is not meant to be used as external API
 * @package core\orm\query\sql
 */
final class query extends sql {

    /**
     * Generate SQL for the query itself. Woo-hoo.
     *
     * @param bool $count_only
     * @return array [SQL, [Params], $limit_from, $limit_to]
     */
    public function build(bool $count_only = false): array {

        // Take care of filters, conditions and sorting (possible account for joins and other stuff)
        $from_subquery = $this->properties->from instanceof builder;

        // Sanity check.
        // If we are selecting from subquery we are ignoring the table name
        if (!$from_subquery && trim($this->properties->table) == '') {
            throw new \coding_exception('Table name can not be empty');
        }

        [$select_sql, $select_params] = select::from_builder($this)->build();

        // Getting bits of the query.
        [$join_sql, $join_params] = join::from_builder($this)->build();
        [$where_sql, $where_params] = where::from_builder($this)->build(false);
        [$union_sql, $union_params] = union::from_builder($this)->build();
        [$having_sql, $having_params] = having::from_builder($this)->build();
        [$order_sql, $order_params] = order::from_builder($this)->build($count_only);
        [$group_by_sql, $group_by_params] = group_by::from_builder($this)->build();

        $query_params = array_merge(
            $select_params,
            $where_params,
            $join_params,
            $union_params,
            $group_by_params,
            $having_params,
            $order_params
        );

        $alias = $this->get_alias_sql();

        // If the query is set to request items from subquery, let's do that.
        if ($from_subquery) {
            if (empty($alias)) {
                throw new \coding_exception('It is required to set an alias to select from a subquery');
            }

            $query_builder = query::from_builder($this->properties->from);

            [$subquery, $sq_params, $offset, $limit] = $query_builder->build();

            if ($offset + $limit != 0) {
                debugging('Can not use limits in a subquery due to database driver limitations.');
            }

            $query_params = array_merge($query_params, $sq_params);
            $first = "($subquery)";
        } else {
            $first = "{{$this->properties->table}}";
        }

        // Prettify the sql
        $first = $this->prettify_sql([$first, $alias, $join_sql]);
        $second = $this->prettify_sql([$where_sql, $group_by_sql, $having_sql, $union_sql, $order_sql]);

        $sql = "SELECT {$select_sql} FROM {$first} WHERE {$second}";

        // Special case for grouped/count distinct queries: wrap it in a another query to get the real count.
        // We wrap the actual sql in another query because we want to also taking account of the DISTINCT query.
        if ($count_only) {
            $sql = "SELECT COUNT(*) FROM ($sql) cnt";
        }

        return [
            $sql,
            $query_params,
            $this->properties->offset,
            $this->properties->limit
        ];
    }

}