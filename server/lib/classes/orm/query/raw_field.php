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

use core\dml\sql;

/**
 * Class raw_field
 *
 * A wrapper around field string that acts as a base for all fields injected into the query
 * It has all the functionality, but validation is not enforced by default to allow injecting RAW statements into the
 * query, but keep it unified with the cases when it needs to process field before injecting (e.g. prefix)
 * Also holds parameters which are allowed when you are using raw.
 *
 * @package core\orm\query
 */
class raw_field {

    /**
     * @var builder_base
     */
    protected $builder;

    /**
     * Field
     *
     * @var string
     */
    protected $field;

    /**
     * Get as for this field
     *
     * @var string|null
     */
    protected $field_as = null;

    /**
     * Get the aggregation function for the field if used
     *
     * @var string|null
     */
    protected $field_agg = null;

    /**
     * Get alias or table name the field was prepended with
     *
     * @var string|null
     */
    protected $field_alias = null;

    /**
     * Get field itself, no
     *
     * @var string|null
     */
    protected $field_column = null;

    /**
     * A flag to keep this field as raw
     *
     * @var bool
     */
    protected $is_raw = false;

    /**
     * Parameters bag
     *
     * @var array
     */
    protected $params = [];

    /**
     * A flag whether it should prefix the column
     *
     * @var bool
     */
    protected $should_prefix = true;

    /**
     * A flag to indicate whether this field has been validated
     *
     * @var bool
     */
    private $is_validated = false;

    /**
     * An identifier which can be used for modifications
     *
     * @var string
     */
    private $identifier = null;

    /**
     *
     *
     * @param string $field Raw SQL to insert into query
     * @param array $params Array of params
     * @param builder|null $builder
     * @return field|string
     */
    public static function raw($field, array $params = [], ?builder $builder = null): self {
        if ($field instanceof sql) {
            $field = $field->to_named_params('qb_order_raw');

            if (!empty($params)) {
                debugging('When using sql bag, please pass params through it as well, $params ignored.');
            }

            $params = $field->get_params();
            $field = $field->get_sql();
        }

        // We enforce validation in the constructor, so we replace the field with this
        $f = new self('field', $builder);
        $f->is_raw = true;
        $f->field = $field;
        $f->set_params($params);

        return $f;
    }

    /**
     * field constructor.
     * @param string $field
     * @param builder|null $builder
     */
    public function __construct(string $field, ?builder $builder = null) {
        $this->field = $field;
        $this->builder = $builder;
        $this->is_raw = true;
    }

    /**
     * Sets an identifier which can be used for modifying operations
     *
     * @param string|null $identifier
     * @return $this
     */
    public function set_identifier(?string $identifier) {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Returns an identifier if one is set or null
     *
     * @return string|null
     */
    public function get_identifier(): ?string {
        return $this->identifier;
    }

    /**
     * Do not prefix the field, it would allow to use aliases in there
     *
     * @param bool $sure
     * @return $this
     */
    public function do_not_prefix(bool $sure = true) {
        $this->should_prefix = !$sure;

        return $this;
    }

    /**
     * Associate a query builder instance
     *
     * @param builder $builder
     * @return $this
     */
    public function set_builder(builder $builder) {
        $this->builder = $builder;

        return $this;
    }

    /**
     * Returns associated builder instance
     *
     * @return builder_base
     */
    public function get_builder(): ?builder {
        return $this->builder;
    }

    /**
     * Set parameters (only for raw fields)
     *
     * @param array $params
     * @return $this
     */
    public function set_params(array $params) {
        $this->params = $params;

        return $this;
    }

    /**
     * Get parameters
     *
     * @return array
     */
    public function get_params(): array {
        return $this->params;
    }

    /**
     * Get sql ready to be inserted for this field
     *
     * @return string
     */
    public function sql(): string {
        return $this->is_raw ? $this->get_field_as_is() : $this->get_field_prefixed();
    }

    /**
     * Return whether
     *
     * @return bool
     */
    public function is_raw(): bool {
        return $this->is_raw;
    }

    /**
     * Return raw field as it was passed originally
     *
     * @return string
     */
    public function get_field_as_is(): string {
        return $this->field;
    }

    /**
     * Get parsed AS for the field
     *
     * @return string|null
     */
    public function get_field_as(): ?string {
        return $this->field_as;
    }

    /**
     * Get parsed aggregate function for the field
     *
     * @return string|null
     */
    public function get_field_agg(): ?string {
        return $this->field_agg;
    }

    /**
     * Get parsed ALIAS for the field
     *
     * @return string|null
     */
    public function get_field_alias(): ?string {
        return $this->field_alias;
    }

    /**
     * Get parsed COLUMN for the field
     *
     * @return string|null
     */
    public function get_field_column(): ?string {
        return $this->field_column;
    }

    /**
     * Validate the field against the following set of constructs and parse the given bits into class properties
     * to rebuild it again with correct prefixes at the end, when not supplied.
     *
     * table_alias - optional
     * table_name - optional
     * agg function - optional, e.g. (sum(field))
     * field as - optional
     * column - required
     *
     * @return bool
     */
    public function validate(): bool {
        // TODO add DISTINCT support

        // Let's get the field
        $field = $this->get_field_as_is();

        // Let's account for as:
        if (stripos($field, ' as ')) {
            $bits = preg_split('/ as /i', $field);

            if (count($bits) > 2) {
                return false;
            }

            $field = $bits[0];
            $as = $bits[1] ?? '';

            if (preg_match(builder_base::AS_REGEX, $as) !== 1) {
                return false;
            }

            $this->field_as = $as;
        }

        // Let's account for aggregate functions
        // Let's find what agg function is used
        if (preg_match(builder_base::AGG_REGEX, $field, $agg) === 1) {
            $this->field_agg = $agg[1];
        }

        // And get rid of it
        $field = preg_replace(builder_base::AGG_REGEX, '${2}', $field, 1, $count);

        // Normal case when a field name is given.
        if (preg_match(builder_base::FIELD_REGEX, $field) === 1) {
            $this->field_column = $field;
            $this->is_validated = true;

            return true;
        }

        // The case when it's prefixed with a table name or an alias.
        if (count($bits = explode('.', $field)) == 2) {
            // Let's validate prefix, it can be: a_word or "a word" or {a_word}
            if (preg_match(builder_base::PREFIX_REGEX, $bits[0]) !== 1) {
                return false;
            }

            $this->field_alias = $bits[0];

            // Let's validate field itself
            if (preg_match(builder_base::FIELD_REGEX, $bits[1]) !== 1) {
                return false;
            }

            $this->field_column = $bits[1];
            $this->is_validated = true;
            return true;
        }

        return false;
    }

    /**
     * Get prefixed sql field if available
     *
     * @param string|null $field Field name
     * @return string
     */
    protected function get_field_prefixed(?string $field = null): string {
        // Okay, this may look unconventional, but having this eases life a lot.
        // The necessity for this comes from the fact that for complex queries we can not just refer to the table
        // name due to the db adding a prefix. Always manually adding a prefix or figure braces is annoying.

        if ($field || !$this->is_validated()) {
            if (!$field) {
                debugging('Trying to get prefix for an unvalidated field, that should not happen');
                $field = $this->get_field_as_is();
            }

            if (!$this->should_prefix || preg_match(builder_base::FIELD_REGEX, $field) !== 1) {
                return $field;
            }

            return $this->get_prefix() . $field;
        }

        // We can't prefix raw sql
        if ($this->is_raw()) {
            return $field;
        }

        // Let's rebuild field from bits we split it into earlier
        if ($alias = $this->get_field_alias()) {
            $field = "{$alias}.{$this->get_field_column()}";
        } else {
            $field = ($this->should_prefix ? $this->get_prefix() : '') . $this->get_field_column();
        }

        // Let's apply given aggregate function
        if ($agg = $this->get_field_agg()) {
            $field = "{$agg}({$field})";
        }

        // Let's add as if it was specified
        if ($as = $this->get_field_as()) {
            $field = "{$field} as {$as}";
        }

        return $field;
    }

    /**
     * Get appropriate prefix for the field
     *
     * The logic is the following:
     * This is invoked from where, select or join simple clauses to add prefix for a field
     * unless the field already has a dot or parenthesis or spaces assuming that it already includes a prefix.
     *
     * If the prefix is empty it will attempt to use the table, if the table is empty it will
     * return the passed field.
     *
     * @return string
     */
    public function get_prefix(): string {
        if ($this->builder instanceof builder_base) {
            if (!empty($alias = $this->builder->get_alias()) && $this->builder->get_parent(true)->is_alias_used()) {
                // We wrap the alias into double quotes to make sure we don't get conflicts with reserved words
                return "\"{$alias}\".";
            }

            if (!empty($table = $this->builder->get_table())) {
                return "{{$table}}.";
            }
        }

        return '';
    }

    /**
     * Return whether the field is validated
     *
     * @return bool
     */
    final protected function is_validated(): bool {
        return $this->is_validated;
    }

    /**
     * Get sql when converting to string
     *
     * @return string
     */
    public function __toString() {
        return $this->sql();
    }
}
