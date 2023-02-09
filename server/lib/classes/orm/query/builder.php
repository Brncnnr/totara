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
use coding_exception;
use core\dml\sql;
use core\orm\collection;
use core\orm\lazy_collection;
use core\orm\paginator;
use core\orm\query\exceptions\multiple_records_found_exception;
use core\orm\query\exceptions\record_not_found_exception;
use core\orm\query\sql\query;
use core\orm\query\sql\where;
use stdClass;

/**
 * This class is the main query builder class. It provides a fluent interface
 * which enables you to create and execute queries on the database without
 * using SQL.
 *
 * Example usage:
 *
 * ```php
 * $records = builder::table('tablename')
 *     ->where('column1', 'value1')
 *     ->where('column2', ['a', 'b', 'c'])
 *     ->order_by('column', 'desc')
 *     ->get();
 *
 * $record = builder::table('tablenname')->find(123);
 * ```
 *
 * There are a lot of possible query methods, please refer to the documentation
 * on the features and usage of the query builder.
 */
final class builder extends builder_base implements interacts_with_query, interacts_with_database {

    /**
     * @var array
     */
    protected $last_executed_queries = [];

    /**
     * Always keep last query regardless of debugging.
     * To have it for throwing moodle-compatible exceptions for example.
     *
     * @var array
     */
    protected $last_executed_query = [];

    /**
     * Add select statement
     *
     * @param string|string[] $what What field are you selecting
     * @return $this
     */
    public function select($what) {
        if (!is_array($what)) {
            $what = [$what];
        }

        // Add alias or prefix if needed
        $what = array_map(
            function ($field) {
                if ($field instanceof sql) {
                    return new field($field, $this);
                }

                if ($field instanceof raw_field) {
                    !$field->get_builder() || $field->set_builder($this);

                    return $field;
                }

                if ($field instanceof builder) {
                    return $field;
                }

                return new field($field, $this);
            },
            $what
        );

        $this->properties->selects = $what;

        return $this;
    }

    /**
     * Add column(s) to selection
     *
     * @param string[]|field[]|string|field $what Column(s) to select
     * @return $this
     */
    public function add_select($what) {
        if (!is_array($what)) {
            $what = [$what];
        }

        return $this->select(array_merge($this->properties->selects, $what));
    }

    /**
     * Reset select statement
     *
     * @return $this
     */
    public function reset_select() {
        $this->properties->selects = [];

        return $this;
    }

    /**
     * Replace current sql selection with a raw string
     *
     * @param string $what
     * @param array $params
     * @return $this
     */
    public function select_raw(string $what, array $params = []) {
        return $this->select(field::raw($what, $params));
    }

    /**
     * Add raw SQL string to selection
     *
     * @param string $what
     * @param array $params
     * @return $this
     */
    public function add_select_raw(string $what, array $params = []) {
        return $this->add_select(field::raw($what, $params));
    }

    /**
     * Add where clause to the builder
     *
     * @param string|closure|field $attribute Attribute to select or a closure which works as a nested builder to create aggregation
     * @param string $condition Select condition =,<,>,<>,
     * @param string $value Value to query by
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where($attribute, $condition = null, $value = null, bool $or = false) {
        [$attribute, $condition, $value] = $this->normalize_where_attributes(...func_get_args());

        return $this->do_where($attribute, $condition, $value, $or);
    }

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
    public function where_field($attribute, $condition =  null, $raw_value = null, bool $or = false) {

        [$attribute, $condition, $raw_value] = $this->normalize_where_attributes(...func_get_args());

        return $this->do_where($attribute, $condition, $raw_value, $or, true);
    }

    /**
     * Add a condition that a column must be a null
     *
     * @param string|field $attribute Column name
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_null($attribute, bool $or = false) {
        return $this->where($attribute, '=', null, $or);
    }

    /**
     * Add a condition that a column must be a null aggregated as OR
     *
     * @param string|field $attribute Column name
     * @return $this
     */
    public function or_where_null($attribute) {
        return $this->where_null($attribute, true);
    }

    /**
     * Add a condition that a column must be not null
     *
     * @param string|field $attribute Column name
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_not_null($attribute, bool $or = false) {
        return $this->where($attribute, '!=', null, $or);
    }

    /**
     * Add a condition that a column must be not null aggregated as OR
     *
     * @param string|field $attribute Column name
     * @return $this
     */
    public function or_where_not_null($attribute) {
        return $this->where_not_null($attribute, true);
    }

    /**
     * A shortcut to add where in condition
     *
     * @param string|field $attribute Column name
     * @param array $values Array of values
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_in($attribute, array $values, bool $or = false) {
        return $this->where($attribute, '=', $values, $or);
    }

    /**
     * A shortcut to add where in condition
     *
     * @param string|field $attribute Column name aggregated as OR
     * @param array $values Array of values
     * @return $this
     */
    public function or_where_in($attribute, array $values) {
        return $this->where_in($attribute, $values, true);
    }

    /**
     * A shortcut to add where not in condition
     *
     * @param string|field $attribute Column name
     * @param array $values Array of values
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_not_in($attribute, array $values, bool $or = false) {
        return $this->where($attribute, '!=', $values, $or);
    }

    /**
     * A shortcut to add where not in condition
     *
     * @param string|field $attribute Column name aggregated as OR
     * @param array $values Array of values
     * @return $this
     */
    public function or_where_not_in($attribute, array $values) {
        return $this->where_not_in($attribute, $values, true);
    }

    /**
     * Shortcut to add a like condition, value will automatically be wrapped with '%'
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_like($attribute, string $value = '', bool $or = false) {
        return $this->where($attribute, 'like', $value, $or);
    }

    /**
     * Shortcut to add a like condition, value won't be escaped has to be done by the dev
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_like_raw($attribute, string $value, bool $or = false) {
        return $this->where($attribute, 'like_raw', $value, $or);
    }

    /**
     * Shortcut to add a like condition where string starts with value, resulting in 'value%'
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_like_starts_with($attribute, string $value, bool $or = false) {
        return $this->where($attribute, 'like_starts_with', $value, $or);
    }

    /**
     * Shortcut to add a like condition where string ends with value, resulting in '%value'
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_like_ends_with($attribute, string $value, bool $or = false) {
        return $this->where($attribute, 'like_ends_with', $value, $or);
    }

    /**
     * Shortcut to add a EXIST (subquery) condition, pass subquery as instance of builder
     *
     * @param builder|callable $builder
     * @param bool $or
     * @return $this
     */
    public function where_exists($builder, bool $or = false) {
        if (is_callable($builder)) {
            $builder($qb = new builder());
            $builder = $qb;
        }

        if (!$builder instanceof builder) {
            throw new coding_exception('Either a builder instance of a callback should be passed to where_exists.');
        }

        return $this->where('', 'exists', $builder, $or);
    }

    /**
     * Shortcut to add a NOT EXIST (subquery) condition, pass subquery as instance of builder
     *
     * @param builder|callable $builder
     * @param bool $or
     * @return $this
     */
    public function where_not_exists($builder, bool $or = false) {
        return $this->where('', '!exists', $builder, $or);
    }

    /**
     * Add where clause to the builder to compare to another database column with OR aggregation
     * Value won't be escaped has to be done by the dev!
     *
     * @param string|closure|field $attribute Attribute to select or a closure which works as a nested builder to create aggregation
     * @param string $condition Select condition =,<,>,<>,
     * @param string $raw_value column name to compare to, this is a raw value which won't be escaped
     * @return $this
     */
    public function or_where_field($attribute, $condition =  null, $raw_value = null) {

        [$attribute, $condition, $raw_value] = $this->normalize_where_attributes(...func_get_args());

        return $this->do_where($attribute, $condition, $raw_value, true, true);
    }

    /**
     * An alias to add where clause to the builder with OR aggregation
     *
     * @param string|closure|field $attribute Attribute to select or a closure which works as a nested builder to create aggregation
     * @param string $condition Select condition =,<,>,<>,
     * @param string $value Value to query by
     * @return $this
     */
    public function or_where($attribute, $condition = null, $value = null) {

        [$attribute, $condition, $value] = $this->normalize_where_attributes(...func_get_args());

        return $this->where($attribute, $condition, $value, true);
    }

    /**
     * An alias to add where_like clause to the builder with OR aggregation
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @return $this
     */
    public function or_where_like($attribute, string $value = '') {
        return $this->where_like($attribute, $value, true);
    }

    /**
     * Shortcut to add a like condition, value won't be escaped has to be done by the dev
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @return $this
     */
    public function or_where_like_raw($attribute, string $value) {
        return $this->where_like_raw($attribute, $value, true);
    }

    /**
     * Shortcut to add a like condition where string starts with value, resulting in 'value%'
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @return $this
     */
    public function or_where_like_starts_with($attribute, string $value) {
        return $this->where_like_starts_with($attribute, $value, true);
    }

    /**
     * Shortcut to add a like condition where string ends with value, resulting in '%value'
     *
     * @param string|field $attribute Attribute to select
     * @param string $value Value to query by
     * @return $this
     */
    public function or_where_like_ends_with($attribute, string $value) {
        return $this->where_like_ends_with($attribute, $value, true);
    }

    /**
     * Shortcut to add a EXIST (subquery) condition, pass subquery as instance of builder
     *
     * @param builder $builder
     * @return $this
     */
    public function or_where_exists(builder $builder) {
        return $this->where_exists($builder, true);
    }

    /**
     * Shortcut to add a NOT EXIST (subquery) condition, pass subquery as instance of builder
     *
     * @param builder $builder
     * @return $this
     */
    public function or_where_not_exists(builder $builder) {
        return $this->where_not_exists($builder, true);
    }

    /**
     * Add RAW where string to the SQL query
     *
     * @param string $sql SQL strings
     * @param array $params Array of named parameters
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function where_raw(string $sql, array $params = [], bool $or = false) {
        if (trim($sql) == '') {
            $sql = '1 = 1';
        }

        $this->properties->conditions[] = (new condition())
            ->set_raw($sql, $params)
            ->set_aggregation(!$or);

        return $this;
    }

    /**
     * An alias to add RAW where string to the SQL query aggregated as OR
     *
     * @param string $sql SQL strings
     * @param array $params Array of named parameters
     * @return $this
     */
    public function or_where_raw(string $sql, array $params = []) {
        return $this->where_raw($sql, $params, true);
    }

    /**
     * An alias to add a nested aggregation closure to the query
     *
     * @param Closure $closure Closure that receives an instance of a query builder as its argument
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function nested_where(Closure $closure, bool $or = false) {

        $builder = builder::create_nested($this)
            ->from($this->properties->table)
            ->as($this->properties->alias)
            ->set_aggregation(!$or);

        $closure($builder);

        $this->properties->conditions[] = $builder;

        return $this;
    }

    /**
     * Internal function to add where clause to the builder, accepts the raw parameter
     *
     * @param string|closure|field $attribute Attribute to select or a closure which works as a nested builder to create aggregation
     * @param string $condition Select condition =,<,>,<>,
     * @param string $value Value to query by
     * @param bool $or A flag whether it should be aggregated as OR
     * @param bool $is_raw_field A flag whether compare to another database field
     * @return $this
     */
    protected function do_where($attribute, $condition =  null, $value = null, bool $or = false, bool $is_raw_field = false) {
        // Shortcut for nested closures
        if ($attribute instanceof Closure) {
            return $this->nested_where($attribute, $or);
        }

        if ($attribute instanceof sql) {
            /** @var sql $attribute */
            // Let's convert params to named
            $attribute = $attribute->to_named_params('qb_raw_param');

            return $this->where_raw($attribute->get_sql(), $attribute->get_params(), $or);
        }

        [$attribute, $condition, $value] = $this->normalize_where_attributes(...func_get_args());

        $condition = strtolower($condition);

        // There is an exception for exists, where $attribute is not needed
        if ($condition == 'exists' || $condition == '!exists') {
            $attribute = '';
        } else {
            $attribute = ($attribute instanceof raw_field) ? $attribute : new field($attribute, $this);
        }

        $value = ($is_raw_field) ? (($value instanceof raw_field) ? $value : new field($value, $this)) : $value;

        $this->properties->conditions[] = (new condition())
            ->set_field($attribute)
            ->set_operator($condition)
            ->set_is_raw_field($is_raw_field)
            ->set_value($value)
            ->set_aggregation(!$or);

        return $this;
    }

    /**
     * Normalize where attributes.
     *
     * This is a helper method used to normalize the supplied attributes to account for shortcut syntax.
     *
     * @param string|closure $attribute Attribute to select or pass a closure which works as a nested builder to create aggregation
     * @param string $condition Select condition =,<,>,<>,
     * @param string $value Value to query by
     * @return array [$table, $source, $condition, $target]
     */
    protected function normalize_where_attributes($attribute, $condition = null, $value = null): array {
        // Shortcut for equals
        if (func_num_args() == 2) {
            $value = $condition;
            $condition = '=';
        }

        if ($condition == '!=') {
            $condition = '<>';
        }

        return [$attribute, $condition, $value];
    }

    /**
     * Removes all conditions related to the given attribute from the query.
     * This does only work with regular attributes at the moment.
     *
     * If the attribute is a field instance and it has an identifier this function
     * will only remove the field with the same identifier, everything else will be ignored.
     * This is to make it possible to safely remove certain fields without removing any unwanted ones.
     *
     * @param string|field $attribute Attribute to select or a closure which works as a nested builder to create aggregation
     * @return $this
     */
    public function remove_where($attribute) {
        if ($attribute instanceof Closure) {
            throw new coding_exception('Removing of an nested where condition is not yet supported.');
        }

        if ($attribute instanceof sql) {
            throw new coding_exception('Removing of a raw sql condition is not yet supported.');
        }

        if ($attribute instanceof field && $attribute->is_raw()) {
            throw new coding_exception('Removing of a raw condition is not yet supported.');
        }

        $identifier = null;
        if ($attribute instanceof field) {
            $identifier = $attribute->get_identifier();
        }

        $search_field = $attribute instanceof field ? (string) $attribute : (string) new field($attribute, $this);

        foreach ($this->properties->conditions as $key => $condition) {
            $field = $condition->get_field();
            // In case the given field has an identifier this takes precedence
            if ($identifier) {
                if ($field instanceof field && $identifier === $field->get_identifier()) {
                    unset($this->properties->conditions[$key]);
                }
            } else if ($search_field === (string) $field) {
                unset($this->properties->conditions[$key]);
            }
        }

        // Reindex the conditions array
        $this->properties->conditions = array_values($this->properties->conditions);

        return $this;
    }

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
    public function join($table, $source = null, $condition = null, $target = null, string $type = 'inner') {
        [$table, $source, $condition, $target]  = $this->normalize_join_attributes(...func_get_args());

        // Alternative syntax
        if (is_array($table)) {
            $table = (new table($table[0]))->as($table[1] ?? '');
        }

        $table = ($table instanceof table) ? $table : (new table($table))->as($table);

        $join = (new join($this))
            ->set_table($table)
            ->set_type($type);

        if (!$table->get_builder()) {
            $table->set_builder($join->get_builder());

            if ($table->has_alias()) {
                $table->as($table->get_alias());
            }
        }

        if ($source instanceof Closure) {
            $source($join->get_builder());
        } else {
            $join->get_builder()->where_field(
                new field($source, $this),
                $condition,
                new field($target, $join->get_builder())
            );
        }

        $this->properties->joins[] = $join;

        return $this;
    }

    /**
     * Alias for left join
     *
     * @param string|table|array $table Table name to join, or table object or array in form of [table, alias]
     * @param string $source Source column
     * @param string $condition Joining condition =,<,>,<>,
     * @param string $target Target column to join
     * @return $this
     */
    public function left_join($table, $source = null, $condition = null, $target = null) {
        [$table, $source, $condition, $target]  = $this->normalize_join_attributes(...func_get_args());

        return $this->join($table, $source, $condition, $target, 'left');
    }

    /**
     * Alias for right join
     *
     * @param string|table|array $table Table name to join, or table object or array in form of [table, alias]
     * @param string $source Source column
     * @param string $condition Joining condition =,<,>,<>,
     * @param string $target Target column to join
     * @return $this
     */
    public function right_join($table, $source = null, $condition = null, $target = null) {
        [$table, $source, $condition, $target]  = $this->normalize_join_attributes(...func_get_args());

        return $this->join($table, $source, $condition, $target, 'right');
    }

    /**
     * Alias for full join
     *
     * @param string|table|array $table Table name to join, or table object or array in form of [table, alias]
     * @param string $source Source column
     * @param string $condition Joining condition =,<,>,<>,
     * @param string $target Target column to join
     * @return $this
     */
    public function full_join($table, $source = null, $condition = null, $target = null) {
        [$table, $source, $condition, $target]  = $this->normalize_join_attributes(...func_get_args());

        return $this->join($table, $source, $condition, $target, 'full');
    }

    /**
     * Alias for cross-join
     *
     * @param string|table|array $table Table name to join, or table object or array in form of [table, alias]
     * @return $this
     */
    public function cross_join($table) {
        return $this->join($table, function () {}, null, null, 'cross');
    }

    /**
     * Normalize join attributes.
     *
     * This is a helper method used to normalize the supplied attributes to account for shortcut syntax.
     *
     * @param string $table Table name to join
     * @param string $source Source column
     * @param string $condition Joining condition =,<,>,<>,
     * @param string $target Target column to join
     * @return array [$table, $source, $condition, $target]
     */
    protected function normalize_join_attributes($table, $source = null, $condition = null, $target = null): array {
        // Shortcut for equals
        if (func_num_args() == 3) {
            $target = $condition;
            $condition = '=';
        }

        if ($condition == '!=') {
            $condition = '<>';
        }

        return [$table, $source, $condition, $target];
    }

    /**
     * Add union to this query
     *
     * @param builder|callable|null $builder Instance of another query to add
     * @param bool $all Flag to indicate whether it's union all
     * @return $this
     */
    public function union($builder, bool $all = false) {
        if (is_null($builder)) {
            return $this->reset_union();
        }

        if (is_callable($builder)) {
            $b = new builder();
            $builder($b, $this);
            if (!$b instanceof builder) {
                throw new coding_exception('The callback must not reset builder');
            }
            $b->mark_as_united();
        } else if ($builder instanceof builder) {
            $b = $builder;
            $b->mark_as_united();
        } else {
            throw new coding_exception('You must pass an instance of builder or a callable to the union functions, ' . gettype($builder) . ' given');
        }

        $this->properties->unions[] = [ $b, $all ];

        return $this;
    }

    /**
     * Alias to add union all
     *
     * @param builder|callable $builder Query instance to add as union
     * @return $this
     */
    public function union_all($builder) {
        return $this->union($builder, true);
    }

    /**
     * Remove all the added unions to this query
     *
     * @return $this
     */
    public function reset_union() {
        $this->properties->unions = [];

        return $this;
    }

    /**
     * Add order to the SQL query
     *
     * @param string $column Order column
     * @param string $direction Order direction: ASC or DESC
     * @return $this
     */
    public function order_by($column, string $direction = order::DIRECTION_ASC) {

        switch (true) {
            case is_null($column):
                return $this->reset_order_by();

            case $column instanceof sql:
                return $this->order_by_raw($column);

            case $column instanceof raw_field:
                $column->get_builder() || $column->set_builder($this);
                break;

            default:
                $column = new order($column, $direction, $this);
                break;
        }

        if ($this->can_add_order_by($column)) {
            $this->properties->orders[] = $column;
        }

        return $this;
    }

    /**
     * Add order by raw field to the SQL query. SQL-injection prone function
     *
     * @param string|order|sql $what
     * @return $this
     */
    public function order_by_raw($what) {

        switch (true) {
            case is_string($what):
                $what = order::raw($what, [], $this);
                break;

            case $what instanceof raw_field:
                $what->get_builder() || $what->set_builder($this);
                break;

            default:
                // We'll try to convert it to string, just in case it's passed as an object convertible to string
                // Or explode if that's not the case
                $what = (string) $what;
                $what = order::raw($what, [], $this);
                break;
        }

        if ($this->can_add_order_by($what)) {
            $this->properties->orders[] = $what;
        }

        return $this;
    }

    /**
     * Returns true if the order does not exist yet and can be safely added.
     * Will throw an exception if the same field was set before but with a different direction.
     *
     * @param raw_field $new_order
     * @return bool
     * @throws coding_exception
     */
    private function can_add_order_by(raw_field $new_order): bool {
        foreach ($this->properties->orders as $order) {
            if ($order->get_field_column() === $new_order->get_field_column()) {
                if ($order->sql() === $new_order->sql()) {
                    // In case the same order is already set on the builder
                    // do not set it twice to avoid issues with Mssql later on
                    return false;
                } else if (!$new_order->is_raw()) {
                    throw new coding_exception(
                        "Order for field '{$new_order->get_field_column()}' is already set with a different configuration."
                    );
                }
            }
        }
        return true;
    }

    /**
     * Bring disorder to this query
     *
     * @return $this
     */
    public function reset_order_by() {
        $this->properties->orders = [];

        return $this;
    }

    /**
     * Return whether this query has order enforced
     *
     * @return bool
     */
    public function has_order_by(): bool {
        return !empty($this->properties->orders);
    }

    /**
     * @return array|order[]
     */
    public function get_orders(): array {
        return $this->properties->orders;
    }

    /**
     * Add group by statement
     *
     * @param string|string[] $fields Field(s) to group by
     * @return $this
     */
    public function group_by($fields) {
        if (is_null($fields)) {
            return $this->reset_group_by();
        }

        if (!is_array($fields)) {
            $fields = [$fields];
        }
        $fields = array_map(
            function ($value) {
                return new field($value, $this);
            },
            $fields
        );

        $this->properties->group_by = array_merge($this->properties->group_by, $fields);

        return $this;
    }

    /**
     * Group by raw sql field
     *
     * @param string $field_sql Field to group by
     * @return $this
     */
    public function group_by_raw(string $field_sql) {
        if (!empty($field_sql)) {
            $this->properties->group_by[] = $field_sql;
        }

        return $this;
    }

    /**
     * Reset group by conditions
     *
     * @return $this
     */
    public function reset_group_by() {
        $this->properties->group_by = [];

        return $this;
    }

    /**
     * Add HAVING condition to the SQL query
     *
     * @param string|field|sql|closure $attribute Attribute to add or closure for creating complex conditions
     * @param string $condition Select condition =,<,>,<>,
     * @param string $value Query value
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function having($attribute, $condition =  null, $value = null, bool $or = false) {
        $this->get_having()->where(...func_get_args());

        return $this;
    }

    /**
     * An alias to add HAVING condition to the SQL query aggregated with OR
     *
     * @param string|field|sql|closure $attribute Attribute to add or closure for creating complex conditions
     * @param string $condition Select condition =,<,>,<>,
     * @param string $value Query value
     * @return $this
     */
    public function or_having($attribute, $condition = null, $value = null) {
        $this->get_having()->or_where(...func_get_args());

        return $this;
    }

    /**
     * Add raw HAVING string to the SQL query
     *
     * @param string $sql SQL string
     * @param array $params Array of named parameters
     * @param bool $or A flag whether it should be aggregated as OR
     * @return $this
     */
    public function having_raw(string $sql, array $params = [], bool $or = false) {
        $this->get_having()->where_raw($sql, $params, $or);

        return $this;
    }

    /**
     * An alias to add raw HAVING string to the SQL query aggregated with OR
     *
     * @param string $sql SQL string
     * @param array $params Array of named parameters
     * @return $this
     */
    public function or_having_raw(string $sql, array $params = []) {
        return $this->having_raw($sql, $params, true);
    }

    /**
     * Limit the number of selected records
     *
     * @param int $limit Limit, null resets the limit
     * @return $this
     */
    public function limit(?int $limit) {
        if (!is_null($limit) && $limit < 0) {
            throw new coding_exception('Builder limit cannot be less than 0. If you want to remove the limit pass null or 0.', $limit);
        }

        $this->properties->limit = $limit;

        return $this;
    }

    /**
     * Offset the selected records
     *
     * @param int $offset Offset, null resets the limit
     * @return $this
     */
    public function offset(?int $offset) {
        if (!is_null($offset) && $offset < 0) {
            throw new coding_exception('Builder offset cannot be less than 0. If you want to remove the offset pass null or 0.', $offset);
        }

        $this->properties->offset = $offset;

        return $this;
    }

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
    public function when(bool $condition, callable $true, ?callable $false = null) {
        if ($condition) {
            $true($this);
        } else if ($false) {
            $false($this);
        }

        return $this;
    }

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
    public function unless(bool $condition, callable $true, ?callable $false = null) {
        return $this->when(!$condition, $true, $false);
    }

    /**
     * Internal
     * Find item by ID, will only select the full row identified via the primary key, won't take anny conditions into account
     *
     * @param int $id
     * @return stdClass|null
     */
    protected function find_by_id(int $id): ?stdClass {
        $record = self::get_db()->get_record($this->get_table(), ['id' => $id], '*', IGNORE_MISSING);
        return $record !== false ? $record : null;
    }

    /**
     * Find item by ID, will only select the full row identified via the primary key, won't take anny conditions into account.
     * Result will be mapped if map_to was used.
     *
     * @param int $id
     * @return array|stdClass|null
     */
    public function find(int $id) {
        return $this->map_result($this->find_by_id($id));
    }

    /**
     * Same as find() but throws an exception if record does not exist
     *
     * @param int $id
     * @return array|stdClass
     */
    public function find_or_fail(int $id) {
        $record = $this->find_by_id($id);
        if (!$record) {
            throw new record_not_found_exception(
                $this->get_table(),
                $this->real_table_names("SELECT * FROM {{$this->get_table()}} WHERE id = ?"),
                [$id]
            );
        }
        return $this->map_result($record);
    }

    /**
     * Return the first item matching the search criteria, you mus specify an order_by to be able to use this function
     * Will fail if a limit was previously set.
     *
     * @param bool $strict Fail if not found
     * @return array|null
     */
    public function first(bool $strict = false) {
        $this->restrict(__FUNCTION__, ['limit'])->expect(__FUNCTION__, ['orders']);

        $offset = $this->properties->offset;

        $items = $this->offset(0)->limit(1)->fetch();

        // reset limit, restore offset
        $this->properties->offset = $offset;
        $this->properties->limit = null;

        if ($strict && empty($items)) {
            $query = $this->get_last_executed_query();
            throw new record_not_found_exception($this->get_table(), $query['sql'], $query['params']);
        }

        return array_shift($items);
    }

    /**
     * Return the first item matching the search criteria or throw an Exception if not found
     *
     * @return array|null
     */
    public function first_or_fail() {
        return $this->first(true);
    }

    /**
     * Get exactly one record from the database
     * And fail if there are more than one
     * Also fail if strict is specified and there is no records
     *
     * @param bool $strict Blow up if a record not found
     * @return array|stdClass|null
     */
    public function one(bool $strict = false) {
        $this->restrict(__FUNCTION__, ['limit', 'offset']);

        $item = $this->limit(2)->offset(0)->fetch();

        // reset limit / offset
        $this->properties->offset = null;
        $this->properties->limit = null;

        switch (count($item)) {
            case 1:
                return array_shift($item);
            case 0:
                if ($strict) {
                    $query = $this->get_last_executed_query();
                    throw new record_not_found_exception($this->get_table(), $query['sql'], $query['params']);
                }
                return null;
            default:
                $query = $this->get_last_executed_query();
                throw new multiple_records_found_exception($query['sql'], $query['params']);
        }
    }

    /**
     * Returns paginated results, this is a shortcut to be able to keep the fluent interface
     *
     * Example:
     * $paginator = builder::table('users')
     *     ->where('deleted', 0)
     *     ->paginate(1, 20);
     *
     * // Alternative use
     * $query = builder::table('users')
     *     ->where('deleted', 0);
     *
     * $paginator = new paginator($query, 1, 20);
     *
     * @param int $page
     * @param int $per_page
     * @return paginator
     */
    public function paginate(int $page = 1, int $per_page = 0): paginator {
        return new paginator($this, $page, $per_page, false);
    }

    /**
     * Returns simple paginated results, this is a shortcut to be able to keep the fluent interface
     *
     * Example:
     * $paginator = builder::table('users')
     *     ->where('deleted', 0)
     *     ->load_more(1, 20);
     *
     * // Alternative use
     * $query = builder::table('users')
     *     ->where('deleted', 0);
     *
     * $paginator = new paginator($query, 1, 20, true);
     *
     * @param int $page
     * @param int $per_page
     * @return paginator
     */
    public function load_more(int $page, int $per_page = 0): paginator {
        return new paginator($this, $page, $per_page, true);
    }

    /**
     * Fetch raw records from the database
     *
     * @param bool $unkeyed Do not key the results by the first column.
     * @return array[]
     */
    public function fetch(bool $unkeyed = false): array {
        $query_builder = query::from_builder($this);
        $query_parts = $this->log_query(...$query_builder->build());
        $function = $unkeyed ? 'get_records_sql_unkeyed' : 'get_records_sql';

        return $this->map_results(self::get_db()->{$function}(...$query_parts));
    }

    /**
     * Fetch raw records from the database and at the same time returning the count.
     *
     * @return array|[array records, int count]
     */
    public function fetch_counted(): array {
        $query_builder = query::from_builder($this);
        $query_parts = $this->log_query(...$query_builder->build());

        $sql = $query_parts[0] ?? '';
        $params = $query_parts[1] ?? null;
        $limit_from = $query_parts[2] ?? 0;
        $limit_to = $query_parts[3] ?? 0;

        $count = $this->count();

        $records = $this->map_results(
            self::get_db()->get_records_sql($sql, $params, $limit_from, $limit_to)
        );

        return [$records, $count];
    }

    /**
     * Fetch raw recordset from the database
     *
     * @return lazy_collection
     */
    public function fetch_recordset(): lazy_collection {
        $query_builder = query::from_builder($this);
        $query_parts = $this->log_query(...$query_builder->build());

        return lazy_collection::create(self::get_db()->get_recordset_sql(...$query_parts))
            ->map_to($this->properties->map_to)
            ->as_array($this->should_return_array());
    }

    /**
     * Get items from the database
     *
     * @param bool $unkeyed Do not key the results by the first column.
     * @return collection
     */
    public function get(bool $unkeyed = false): collection {
        return new collection($this->fetch($unkeyed));
    }

    /**
     * Get a lazy loading collection utilising the recordset
     *
     * @return lazy_collection
     */
    public function get_lazy(): lazy_collection {
        // It's both now
        return $this->fetch_recordset();
    }

    /**
     * Count the number of results in the query
     *
     * @return int
     */
    public function count(): int {
        /** @var query $query_builder */
        $query_builder = query::from_builder($this);
        $query_parts = $this->log_query(...$query_builder->build(true));

        return self::get_db()->count_records_sql(...$query_parts);
    }

    /**
     * Return internal instance for having method, instantiating it if it hadn't been.
     *
     * @return builder
     */
    protected function get_having(): self {
        if ($this->properties->nested) {
            throw new coding_exception('Cannot have having on a nested builder');
        }

        if (!$this->properties->having) {
            $this->properties->having = (new builder(null, true))
                ->from($this->properties->table)
                ->as($this->properties->alias);
        }

        return $this->properties->having;
    }

    /**
     * Glorified constructor to create a query builder for a specific table or sub-query
     *
     * @param string|builder $table Table or sub-query to select from
     * @param string|null $as Select table as
     * @return builder
     */
    public static function table($table, ?string $as = null): self {

        $builder = new builder();

        if (!$table instanceof builder && empty($as)) {
            $as = $table;
        }

        $builder->from($table, $as);

        $builder->properties->nested = false;

        return $builder;
    }

    /**
     * Generate SQL to concatenate strings
     * If you pass objects that implement __toString method they will be converted to string
     *
     * @param null[]|string[] $params String to concatenate
     * @return string
     */
    public static function concat(?string ...$params): string {
        return self::get_db()->sql_concat(...$params);
    }

    /**
     * Generate SQL to to group concatenation
     *
     * @param string|field $field
     * @param string $separator
     * @param string|field $order_by
     * @return string
     */
    public static function group_concat(string $field, string $separator = ', ', string $order_by = ''): string {
        return self::get_db()->sql_group_concat($field, $separator, $order_by);
    }

    /**
     * Create a new record in the database. Neither takes conditions into account nor joins, unions of offset/limit.
     * This is a shortcut to moodle_database::insert_record()
     *
     * @param array|stdClass $attributes Data object
     * @return int
     */
    public function insert($attributes): int {
        $this->restrict(__FUNCTION__, ['conditions', 'joins', 'unions', 'offset', 'limit', 'orders', 'group_by', 'selects', 'having']);

        return self::get_db()->insert_record($this->get_table(), $attributes);
    }

    /**
     * Updates attributes of all rows affected by the query. Where conditions can be used
     *
     * @param array|stdClass $attributes
     * @return $this
     */
    public function update($attributes) {
        $this->restrict(__FUNCTION__, ['joins', 'unions', 'offset', 'limit', 'orders', 'group_by', 'selects', 'having']);

        $record = (array) $attributes;

        if (isset($record['id'])) {
            throw new \coding_exception('You cannot supply an id here. To update a single record please use \core\orm\query\builder::update_record() instead');
        }

        $use_alias = $this->is_alias_used();
        $this->do_not_use_alias();

        $query_builder = where::from_builder($this);

        self::get_db()->set_fields_select($this->get_table(),  $record, ...$query_builder->build());

        $this->use_alias($use_alias);

        return $this;
    }

    /**
     * Update a single record based on the supplied id attribute
     *
     * @param stdClass|array $record Record array\object, must contain id of the record updated
     * @return $this
     */
    public function update_record($record) {
        $this->restrict(__FUNCTION__, ['joins', 'unions', 'offset', 'limit', 'orders', 'group_by', 'selects', 'having', 'where']);

        $attributes = (array) $record;

        if (!isset($attributes['id'])) {
            throw new \coding_exception('Id is required to update a single record. Please use \core\orm\query\builder::update() instead');
        }

        self::get_db()->update_record($this->get_table(),  $attributes);

        return $this;
    }

    /**
     *  Delete record(s) from the database
     *
     * @return $this
     */
    public function delete() {
        $this->restrict(__FUNCTION__, ['joins', 'unions', 'offset', 'limit', 'orders', 'group_by', 'selects', 'having']);

        $use_alias = $this->is_alias_used();
        $this->do_not_use_alias();

        $query_builder = where::from_builder($this);

        self::get_db()->delete_records_select($this->get_table(), ...$query_builder->build());

        $this->use_alias($use_alias);

        return $this;
    }

    /**
     * Retrieve a value of a single column
     *
     * @param string|field $column Column name to select
     * @param bool $strict Throw an exception if not found
     * @return string|null
     */
    public function value(string $column, bool $strict = false) {
        $limit = $this->properties->limit;
        $offset = $this->properties->offset;
        $map_to = $this->properties->map_to;
        $select = $this->properties->selects;

        $result = $this->limit(1)
            ->offset(0)
            ->map_to(null)
            ->select($column)
            ->limit(1)
            ->fetch();

        $this->properties->limit = $limit;
        $this->properties->offset = $offset;
        $this->properties->selects = $select;
        $this->properties->map_to = $map_to;

        if (empty($result) && $strict) {
            $query = $this->get_last_executed_query();
            throw new record_not_found_exception($this->get_table(), $query['sql'], $query['params']);
        }

        $value = reset($result) ?: null;

        if (!is_null($value)) {
            $value = (array) $value;
            $value = reset($value);
        }

        return $value;
    }

    /**
     * Return whether record(s) matching given where conditions exist
     *
     * @return bool
     */
    public function exists(): bool {
        $offset = $this->properties->offset;
        $limit = $this->properties->limit;
        $selects = $this->properties->selects;

        $result = $this->select('id')
            ->offset(0)
            ->limit(1)
            ->fetch();

        // Restore offset / limit
        $this->properties->offset = $offset;
        $this->properties->limit = $limit;
        $this->properties->selects = $selects;

        return !empty($result);
    }

    /**
     * Return whether record(s) matching given where conditions does not exist
     *
     * @return bool
     */
    public function does_not_exist(): bool {
        return !$this->exists();
    }

    /**
     * If debugging mode is activated we'll log the query
     * in the last_executed_query property
     *
     * @param string $sql
     * @param array $params
     * @param int|null $offset
     * @param int|null $limit
     * @return array
     */
    protected function log_query(string $sql, array $params = [], int $offset = null, int $limit = null): array {
        $this->last_executed_query = [
            'sql' => $sql,
            'params' => $params,
            'offset' => $offset,
            'limit' => $limit
        ];

        if (debugging()) {
            $this->last_executed_queries[]  = $this->last_executed_query;
        }

        return [$sql, $params, $offset, $limit];
    }

    /**
     * Last executed query, contains the query and the params used for it.
     *
     * @return array
     */
    public function get_last_executed_queries(): array {
        return $this->last_executed_queries;
    }

    /**
     * Last executed query, contains the query and the params used for it.
     *
     * @return array
     */
    public function get_last_executed_query(): array {
        return $this->last_executed_query;
    }

}
