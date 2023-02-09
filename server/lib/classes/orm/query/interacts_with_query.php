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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core
 * @group orm
 */

namespace core\orm\query;

use Closure;
use core\dml\sql;

/**
 * These methods are the ones which can be used to influence the query to be built by the query builder.
 * All those can be chained.
 */
interface interacts_with_query {

    /**
     * Select from a sub-query or table name, will check passed table name for sanity check against a simple regex
     *
     * @param builder|string|table $where Table name or an instance of a query builder with a subquery
     * @param string|null $as Alias, must be specified in here or prior to this method call
     * @return $this
     */
    public function from($where, ?string $as = null);

    /**
     * Set base table alias, will check passed alias for sanity check against a simple regex
     *
     * @param string $alias Table alias, empty for no alias.
     * @return $this
     */
    public function as(string $alias);

    /**
     * Add select statement
     *
     * @param string|string[] $what What field are you selecting
     * @return $this
     */
    public function select($what);

    /**
     * Add column(s) to selection
     *
     * @param string[]|field[]|string|field $what Column(s) to select
     * @return $this
     */
    public function add_select($what);

    /**
     * Reset select statement
     *
     * @return $this
     */
    public function reset_select();

    /**
     * Add raw SQL string to selection
     *
     * @param string $what
     * @param array $params
     * @return $this
     */
    public function select_raw(string $what, array $params = []);

    /**
     * Add raw SQL string to selection
     *
     * @param string $what
     * @param array $params
     * @return $this
     */
    public function add_select_raw(string $what, array $params = []);

    /**
     * Add union to this query
     *
     * @param builder|callable|null $builder Instance of another query to add. Pass null to reset unions
     * @param bool $all Flag to indicate whether it's union all
     * @return $this
     */
    public function union($builder, bool $all = false);

    /**
     * Alias to add union all
     *
     * @param builder|callable $builder Query instance to add as union
     * @return $this
     */
    public function union_all($builder);

    /**
     * Join another table
     *
     * @param string|table|array $table Table name to join, or table object or array in form of [table, alias]
     * @param string $source Source column
     * @param string $condition Joining condition =,<,>,<>,
     * @param string $target Target column to join
     * @param string $type Join type
     * @return $this
     */
    public function join($table, $source = null, $condition = null, $target = null, string $type = 'inner');

    /**
     * Alias for left join
     *
     * @param string|table|array $table Table name to join, or table object or array in form of [table, alias]
     * @param string $source Source column
     * @param string $condition Joining condition =,<,>,<>,
     * @param string $target Target column to join
     * @return $this
     */
    public function left_join($table, $source = null, $condition = null, $target = null);

    /**
     * Alias for right join
     *
     * @param string|table|array $table Table name to join, or table object or array in form of [table, alias]
     * @param string $source Source column
     * @param string $condition Joining condition =,<,>,<>,
     * @param string $target Target column to join
     * @return $this
     */
    public function right_join($table, $source = null, $condition = null, $target = null);

    /**
     * Alias for full join
     *
     * @param string|table|array $table Table name to join, or table object or array in form of [table, alias]
     * @param string $source Source column
     * @param string $condition Joining condition =,<,>,<>,
     * @param string $target Target column to join
     * @return $this
     */
    public function full_join($table, $source = null, $condition = null, $target = null);

    /**
     * Alias for cross-join
     *
     * @param string|table|array $table Table name to join, or table object or array in form of [table, alias]
     * @return $this
     */
    public function cross_join($table);

    /**
     * Add where clause to the builder
     *
     * @param string|closure|field $attribute Attribute to select or a closure which works as a nested builder to create aggregation
     * @param string $condition Select condition =,<,>,<>,
     * @param string $value Value to query by
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where($attribute, $condition =  null, $value = null, bool $or = false);

    /**
     * Shortcut to add a like condition, value will automatically be wrappd with '%'
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_like($attribute, string $value = '', bool $or = false);

    /**
     * Shortcut to add a like condition, value won't be escaped has to be done by the dev
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_like_raw($attribute, string $value, bool $or = false);

    /**
     * Shortcut to add a like condition where string starts with value, resulting in 'value%'
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_like_starts_with($attribute, string $value, bool $or = false);

    /**
     * Shortcut to add a like condition where string ends with value, resulting in '%value'
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_like_ends_with($attribute, string $value, bool $or = false);

    /**
     * Add where clause to the builder to compare to another database column
     * Value won't be escaped has to be done by the dev!
     *
     * @param string|closure|field $attribute Attribute to select or a closure which works as a nested builder to create aggregation
     * @param string $condition Select condition =,<,>,<>,
     * @param string $raw_value column name to compare to, this is a raw value which won't be escaped
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_field($attribute, $condition =  null, $raw_value = null, bool $or = false);

    /**
     * Shortcut to add a EXIST (subquery) condition, pass subquery as instance of builder
     *
     * @param builder|callable $builder
     * @param bool $or
     * @return $this
     */
    public function where_exists($builder, bool $or = false);

    /**
     * Shortcut to add a NOT EXIST (subquery) condition, pass subquery as instance of builder
     *
     * @param builder|callable $builder
     * @param bool $or
     * @return $this
     */
    public function where_not_exists($builder, bool $or = false);

    /**
     * Add a condition that a column must be a null
     *
     * @param string|field $attribute Column name
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_null($attribute, bool $or = false);

    /**
     * Add a condition that a column must be not null
     *
     * @param string|field $attribute Column name
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_not_null($attribute, bool $or = false);

    /**
     * A shortcut to add where in condition
     *
     * @param string|field $attribute Column name
     * @param array $values Array of values
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_in($attribute, array $values, bool $or = false);

    /**
     * A shortcut to add where not in condition
     *
     * @param string|field $attribute Column name
     * @param array $values Array of values
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_not_in($attribute, array $values, bool $or = false);

    /**
     * Add where clause to the builder to compare to another database column with OR aggregation
     * Value won't be escaped has to be done by the dev!
     *
     * @param string|closure|field $attribute Attribute to select or a closure which works as a nested builder to create aggregation
     * @param string $condition Select condition =,<,>,<>,
     * @param string $raw_value column name to compare to, this is a raw value which won't be escaped
     * @return $this
     */
    public function or_where_field($attribute, $condition =  null, $raw_value = null);

    /**
     * An alias to add where clause to the builder with OR aggregation
     *
     * @param string|closure|field $attribute Attribute to select or a closure which works as a nested builder to create aggregation
     * @param string $condition Select condition =,<,>,<>,
     * @param string $value Value to query by
     * @return $this
     */
    public function or_where($attribute, $condition = null, $value = null);

    /**
     * An alias to add where_like clause to the builder with OR aggregation
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @return $this
     */
    public function or_where_like($attribute, string $value = '');

    /**
     * Shortcut to add a like condition, value won't be escaped has to be done by the dev
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @return $this
     */
    public function or_where_like_raw($attribute, string $value);

    /**
     * Shortcut to add a like condition where string starts with value, resulting in 'value%'
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @return $this
     */
    public function or_where_like_starts_with($attribute, string $value);

    /**
     * Shortcut to add a like condition where string ends with value, resulting in '%value'
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @return $this
     */
    public function or_where_like_ends_with($attribute, string $value);

    /**
     * Shortcut to add a EXIST (subquery) condition, pass subquery as instance of builder
     *
     * @param builder $builder
     * @return $this
     */
    public function or_where_exists(builder $builder);

    /**
     * Shortcut to add a NOT EXIST (subquery) condition, pass subquery as instance of builder
     *
     * @param builder $builder
     * @return $this
     */
    public function or_where_not_exists(builder $builder);

    /**
     * Add a condition that a column must be a null aggregated as OR
     *
     * @param string|field $attribute Column name
     * @return $this
     */
    public function or_where_null($attribute);

    /**
     * Add a condition that a column must be not null aggregated as OR
     *
     * @param string|field $attribute Column name
     * @return $this
     */
    public function or_where_not_null($attribute);

    /**
     * A shortcut to add where in condition
     *
     * @param string|field $attribute Column name aggregated as OR
     * @param array $values Array of values
     * @return $this
     */
    public function or_where_in($attribute, array $values);

    /**
     * A shortcut to add where not in condition
     *
     * @param string|field $attribute Column name aggregated as OR
     * @param array $values Array of values
     * @return $this
     */
    public function or_where_not_in($attribute, array $values);

    /**
     * Add RAW where string to the SQL query
     *
     * @param string $sql SQL strings
     * @param array $params Array of named parameters
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_raw(string $sql, array $params = [], bool $or = false);

    /**
     * An alias to add RAW where string to the SQL query aggregated as OR
     *
     * @param string $sql SQL strings
     * @param array $params Array of named parameters
     * @return $this
     */
    public function or_where_raw(string $sql, array $params = []);

    /**
     * An alias to add a nested aggregation closure to the query
     *
     * @param Closure $closure Closure that receives an instance of a query builder as its argument
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function nested_where(Closure $closure, bool $or = false);

    /**
     * Removes all conditions related to the given attribute from the query.
     * This does only work with regular attributes at the moment.
     *
     * @param string|field $attribute Attribute to select or a closure which works as a nested builder to create aggregation
     * @return $this
     */
    public function remove_where($attribute);

    /**
     * Add order to the SQL query
     *
     * @param string|order|sql|null $column Order column. Pass null to reset order by
     * @param string $direction Order direction: ASC or DESC
     * @return $this
     */
    public function order_by($column, string $direction = order::DIRECTION_ASC);

    /**
     * Add order by raw field to the SQL query
     * Input needs to be sanitized manually here
     *
     * @param string $what raw order string, i.e. 'my_custom_column_alias DESC' or 'col1 ASC, col2 DESC'
     * @return $this
     */
    public function order_by_raw($what);

    /**
     * Bring disorder to this query
     *
     * @return $this
     */
    public function reset_order_by();

    /**
     * Add group by statement
     *
     * @param string|string[]|null $fields Field(s) to group by. Pass null to reset group by
     * @return $this
     */
    public function group_by($fields);

    /**
     * Group by raw sql field
     *
     * @param string $field_sql Field to group by
     * @return $this
     */
    public function group_by_raw(string $field_sql);

    /**
     * Reset group by conditions
     *
     * @return $this
     */
    public function reset_group_by();

    /**
     * Add HAVING condition to the SQL query
     *
     * @param string|closure $attribute Attribute to add or closure for creating complex conditions
     * @param string $condition Select condition =,<,>,<>,
     * @param string $value Query value
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function having($attribute, $condition =  null, $value = null, bool $or = false);

    /**
     * An alias to add HAVING condition to the SQL query aggregated with OR
     *
     * @param string|closure $attribute Attribute to add or closure for creating complex conditions
     * @param string $condition Select condition =,<,>,<>,
     * @param string $value Query value
     * @return $this
     */
    public function or_having($attribute, $condition = null, $value = null);

    /**
     * Add raw HAVING string to the SQL query
     *
     * @param string $sql SQL string
     * @param array $params Array of named parameters
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function having_raw(string $sql, array $params = [], bool $or = false);

    /**
     * An alias to add raw HAVING string to the SQL query aggregated with OR
     *
     * @param string $sql SQL string
     * @param array $params Array of named parameters
     * @return $this
     */
    public function or_having_raw(string $sql, array $params = []);

    /**
     * Limit the number of selected records
     *
     * @param int $limit Limit, 0 resets the limit
     * @return $this
     */
    public function limit(int $limit);

    /**
     * Offset the selected records
     *
     * @param int $offset Offset, 0 resets the limit
     * @return $this
     */
    public function offset(int $offset);

    /**
     * Conditionally execute a closure without braking a fluent flow
     * Note, this is a syntactic sugar for simple cases do not abuse it with monstrous logic
     * For example to have something like:
     * ->...
     * ->when($visible_only, function (builder $builder) { $builder->where('visible', true) })
     * ->...
     *
     * @param bool $condition Condition to check
     * @param callable $true Closure to run if condition is true
     * @param callable|null $false Closure to run if condition is false
     * @return $this
     */
    public function when(bool $condition, callable $true, ?callable $false = null);

    /**
     * Conditionally execute a closure without braking a fluent flow
     * The opposite of when
     * Note, this is a syntactic sugar for simple cases do not abuse it with monstrous logic
     * For example to have something like:
     * ->...
     * ->unless($hidden, function (builder $builder) { $builder->where('visible', true) }; )
     * ->...
     *
     * @param bool $condition Condition to check
     * @param callable $true Closure to run if condition is true
     * @param callable|null $false Closure to run if condition is false
     * @return $this
     */
    public function unless(bool $condition, callable $true, ?callable $false = null);

    /**
     * Map results to a class or to a given callback
     *
     * @param callable|string|null $what Something to map results to
     * @return $this
     */
    public function map_to($what);

    /**
     * Check whether a query has a join already and return it
     *
     * @param string|table $table Table name
     * @param string|null $alias Table alias, if not specified will return a first join for a given table
     * @return join|null
     */
    public function get_join($table, ?string $alias = null): ?join;

    /**
     * Check whether a query has a join already and return a boolean
     *
     * @param string|table $table Table name
     * @param string|null $alias Table alias, if not specified will return a first join for a given table
     * @return bool
     */
    public function has_join($table, ?string $alias = null);

    /**
     * Return each record as array
     *
     * @param bool $as_array
     * @return $this
     */
    public function results_as_arrays(bool $as_array = true);

    /**
     * Return results as array of objects
     *
     * @return $this
     */
    public function results_as_objects();

    /**
     * Return whether this builder has any WHERE conditions added.
     *
     * @return bool
     */
    public function has_conditions();

    /**
     * Get SQL for table alias
     *
     * @return string
     */
    public function get_alias_sql();

    /**
     * Is alias used when generating the query
     *
     * @return bool
     */
    public function is_alias_used();

    /**
     * Get default table alias
     *
     * @return string
     */
    public function get_alias();
}
