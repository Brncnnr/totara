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
 */

namespace core\orm\query;

use core\orm\query\sql\query;

/**
 * Class condition
 *
 * The class that resolves condition written as php [$field, $operator, $value] to actual [$sql and $params]
 *
 * @package core\orm\query
 */
final class condition implements queryable {

    /**
     * Flag determining aggregation type
     *
     * @var bool
     */
    protected $aggregation = true;

    /**
     * Raw SQL where query condition
     *
     * @var string
     */
    protected $raw = '';

    /**
     * Flag whether this condition was initialized as raw sql string
     *
     * @var bool
     */
    protected $is_raw = false;

    /**
     * Field name
     *
     * @var string
     */
    protected $field = null;

    /**
     * Compare to other database field flag
     *
     * @var bool
     */
    protected $is_raw_field = false;

    /**
     * Operator, e.g. <,>,=,!=
     *
     * @var string|null
     */
    protected $operator = null;

    /**
     * Value to compare to
     *
     * @var mixed|builder
     */
    protected $value = null;

    /**
     * Query parameters
     *
     * @var array
     */
    protected $params = [];

    /**
     * Valid supported operators
     *
     * @var array
     */
    protected $available_operators = [
        '=',
        '<>',
        '!=',
        '>',
        '<',
        '>=',
        '<=',
        'in',
        'exists',
        '!exists',
        'like',
        '!like',
        'ilike',
        '!ilike',
        'like_raw',
        '!like_raw',
        'ilike_raw',
        '!ilike_raw',
        'like_starts_with',
        '!like_starts_with',
        'ilike_starts_with',
        '!ilike_starts_with',
        'like_ends_with',
        '!like_ends_with',
        'ilike_ends_with',
        '!ilike_ends_with',
    ];

    /**
     * Get where SQL for this condition
     *
     * @return array
     */
    public function where_sql(): array {
        if (!$this->is_raw) {
            $this->generate_sql();
        }

        return [$this->raw, $this->params];
    }

    /**
     * Generate a sql query for a given field and condition
     *
     * @return $this
     */
    protected function generate_sql() {
        if ($this->operator == '!=') {
            $this->operator = '<>';
        }

        $this->operator = strtolower($this->operator);

        // Reset raw and params, in case this condition is being built more than once.
        $this->raw = '';
        $this->params = [];

        if (!in_array($this->operator, $this->available_operators)) {
            throw new \coding_exception('Condition must be one of the following: ' . implode(', ', $this->available_operators));
        }

        switch (true) {
            case $this->is_raw_field:
                if (!in_array($this->operator, ['=', '<>', '>', '<', '>=', '<='])) {
                    throw new \coding_exception("Comparing fields supported only with =, !=, <, >, <=, >=.");
                }
                $this->raw = "{$this->field} {$this->operator} {$this->value}";
                break;

            case is_numeric($this->value) && in_array($this->operator, ['=', '<>', '>', '<', '>=', '<=']):
                $param = $this->get_next_param_name();
                $this->params = [$param => $this->value];
                $this->raw = "{$this->field} {$this->operator} :{$param}";
                break;

            case is_bool($this->value):
                if (!in_array($this->operator, ['=', '<>'])) {
                    throw new \coding_exception("Comparing boolean supported only with =, != (<>)");
                }
                // Make sure we end up with 0/1 instead of false/true
                $value = (int) $this->value;
                $this->raw = "{$this->field} {$this->operator} $value";
                break;

            case is_array($this->value):
                if (!in_array($this->operator, ['=', 'in', '<>'])) {
                    throw new \coding_exception("Comparing arrays supported only with in, =, !=");
                }
                $equal = in_array($this->operator, ['=', 'in']);

                // Gracefully handling when empty array is supplied
                if (!empty($this->value)) {
                    [$sql, $this->params] = builder::get_db()->get_in_or_equal($this->value, SQL_PARAMS_NAMED, 'param', $equal);
                    $this->raw = "{$this->field} {$sql}";
                } else {
                    $this->raw = !$equal ? '1 = 1' : '1 = 2';
                }

                break;

            case is_null($this->value):
                if (!in_array($this->operator, ['=', '<>'])) {
                    throw new \coding_exception("Comparing NULLs supported only with = or !=.");
                }

                $operator = ($this->operator == '=') ? 'IS NULL' : 'IS NOT NULL';
                $this->raw = "{$this->field} {$operator}";
                break;

            // boolean was covered above already, same with numeric values with non-like operators
            // here we cover the rest of the scalars
            case is_scalar($this->value):
                // Please not that <, >, <=, >= operators for strings are supported by db systems
                // results depend on collations used
                $accepted_operators = [
                    '=', '<>', '>', '<', '>=', '<=',
                    'like', '!like', 'ilike', '!ilike',
                    'like_raw', '!like_raw', 'ilike_raw', '!ilike_raw',
                    'like_starts_with', '!like_starts_with', 'ilike_starts_with', '!ilike_starts_with',
                    'like_ends_with', '!like_ends_with', 'ilike_ends_with', '!ilike_ends_with',
                ];
                if (!in_array($this->operator, $accepted_operators)) {
                    throw new \coding_exception("Comparing strings supported only with =, != (<>), <, >, <=, >= or (!)(i)like(_[raw|[starts|ends]_with]).");
                }

                $param = $this->get_next_param_name();
                $this->params = [$param => $this->value];

                if (strpos($this->operator, 'like') !== false) {
                    // starts with !
                    $not_like = strpos($this->operator, '!') === 0;
                    // not ilike
                    $cs = strpos($this->operator, 'ilike') === false;

                    // if it's not a raw value escape it
                    if (strpos($this->operator, 'raw') === false) {
                        $this->params[$param] = $this->escape_like_value();
                    }

                    $this->raw = builder::get_db()->sql_like($this->field, ":{$param}", $cs, true, $not_like);
                } else {
                    $this->raw = "{$this->field} {$this->operator} :{$param}";
                }
                break;

            case $this->value instanceof builder:
                if (!in_array($this->operator, ['=', '<>', 'in', 'exists', '!exists', '>', '<', '>=', '<='])) {
                    throw new \coding_exception("Comparing to subqueries is not supported with {$this->operator}");
                }

                $query_builder = query::from_builder($this->value);

                [$subquery, $sq_params, $offset, $limit] = $query_builder->build();

                $this->params = array_merge($this->params, $sq_params);

                if ($offset + $limit != 0) {
                    debugging('Can not use limits in a subquery due to database driver limitations.');
                }

                // EXISTS has a different syntax
                if (strpos($this->operator, 'exists') !== false) {
                    $operator = strpos($this->operator, '!') === 0 ? 'NOT EXISTS' : 'EXISTS';
                    $this->raw = "{$operator} ({$subquery})";
                } else {
                    $this->raw = "{$this->field} {$this->operator} ({$subquery})";
                }
                break;

            default:
                throw new \coding_exception("The combination of operator and value is not supported.");
        }

        return $this;
    }

    /**
     * Escape value given in the like statement
     *
     * To escape the like value properly
     * We apply the following conversions.
     *
     * 1. like, ilike, !like, !ilike => %value%
     * 2. ..._starts_with            => value%
     * 3. ..._ends_with              => %value
     *
     * @return string
     */
    private function escape_like_value(): string {
        $prefix = '';
        $suffix = '';

        if (strpos($this->operator, 'starts_with') !== false) {
            $suffix = '%';
        } else if (strpos($this->operator, 'ends_with') !== false) {
            $prefix = '%';
        } else {
            $suffix = '%';
            $prefix = '%';
        }

        // Escape the value
        $value = builder::get_db()->sql_like_escape($this->value);
        return $prefix . $value . $suffix;
    }

    /**
     * Get next unique parameter name for the query
     * 
     * @return string
     */
    protected function get_next_param_name(): string {
        return builder::get_db()::get_unique_param('qb_param');
    }

    /**
     * Set raw sql for this query
     *
     * @param string $sql SQL where condition
     * @param array $params SQL params
     * @return $this
     */
    public function set_raw(string $sql, array $params = []) {
        $this->raw = $sql;
        $this->params = $params;
        $this->is_raw = true;

        return $this;
    }

    /**
     * @return string|field|null
     */
    public function get_field() {
        return $this->field;
    }

    /**
     * Set field to query
     *
     * @param string|field $field
     * @return $this
     */
    public function set_field($field) {
        $this->field = $field;

        return $this;
    }

    /**
     * Set operator
     *
     * @param string $operator
     * @return $this
     */
    public function set_operator(string $operator) {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Set raw field flag
     *
     * @param bool $type
     * @return $this
     */
    public function set_is_raw_field(bool $type) {
        $this->is_raw_field = $type;

        return $this;
    }

    /**
     * Set value
     *
     * @param $value
     * @return $this
     */
    public function set_value($value) {
        $this->value = $value;

        return $this;
    }

    /**
     * Set parameters used for raw queries
     *
     * @param array $params
     * @return $this
     */
    public function set_params(array $params) {
        $this->params = $params;

        return $this;
    }

    /**
     * Set aggregation flag
     *
     * @param bool $aggregation
     * @return queryable
     */
    public function set_aggregation(bool $aggregation): queryable {
        $this->aggregation = $aggregation;

        return $this;
    }

    /**
     * Get aggregation type
     *
     * @return bool
     */
    public function get_aggregation(): bool {
        return $this->aggregation;
    }
}