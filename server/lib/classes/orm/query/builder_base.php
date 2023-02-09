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

use coding_exception;
use moodle_database;
use stdClass;

/**
 * This class is the base for the builder itself but also for the SQL builders which build
 * the individual SQL parts.
 *
 * It stores most of the properties in a separate property object which can be
 * used to transfer the properties from one instance to another or which
 * can be used by the repository to change the builder properties directly
 *
 * WARNING! WARNING! WARNING! DO NOT EXTEND IT IT'S INTERNAL THE METHODS MIGHT CHANGE WITHOUT DEPRECATION
 *
 * @internal This is not meant to be used as external
 */
abstract class builder_base {

    /** @var string Base table name regex */
    public const BASE_REGEX = '[a-zA-Z][a-zA-Z0-9\_]*';

    /** @var string Regex to check table sanity */
    public const TABLE_REGEX = '/^' . self::BASE_REGEX .'$/';

    /** @var string Regex to check AS sanity */
    public const AS_REGEX = self::TABLE_REGEX;

    /** @var string Regex to check aggregate function sanity */
    public const AGG_REGEX = '/^(avg|count|min|max|sum)\((.*)\)$/i';

    /** @var string Regex to check field sanity */
    public const FIELD_REGEX = '/^(?:' . self::BASE_REGEX . '|\*)$/';

    /** @var string Regex to check prefix, might be "prefix" prefix or {table} */
    public const PREFIX_REGEX = '/^(?:"' . self::BASE_REGEX . '"|' . self::BASE_REGEX . '|{' . self::BASE_REGEX . '})$/';

    /**
     * Parent instance of query builder
     *
     * @var builder|null
     */
    protected $parent = null;

    /**
     * @var properties
     */
    protected $properties;

    /**
     * Report builder constructor.
     *
     * @param properties|null $properties
     * @param bool $nested
     */
    public function __construct(properties $properties = null, bool $nested = false) {
        $this->properties = $properties ?? new properties();
        $this->properties->nested = $nested;
    }

    /**
     * Get database object reference
     *
     * @return moodle_database
     */
    public static function get_db(): moodle_database {
        global $DB;
        return $DB;
    }

    /**
     * Set parent query builder instance
     *
     * @param builder $builder
     * @return $this
     */
    public function set_parent(builder $builder) {
        $this->parent = $builder;

        return $this;
    }

    /**
     * Get parent query builder instance
     *
     * @param bool $topmost
     * @return builder|builder_base
     */
    public function get_parent(bool $topmost = false) {
        if (! $this->parent instanceof builder_base) {
            return $this;
        }

        return $topmost ? $this->parent->get_parent(true) : $this->parent;
    }

    /**
     * Glorified constructor to create a nested instance of the query builder
     *
     * @param builder|null $parent
     * @return builder
     */
    public static function create_nested(?builder $parent): self {
        $builder = new builder(null, true);
        if ($parent) {
            $builder->set_parent($parent);
        }

        return $builder;
    }

    /**
     * Translate aggregation attribute to SQL
     *
     * @param bool $aggregation
     * @return string
     */
    protected function agg_to_sql(bool $aggregation): string {
        return $aggregation ? 'AND' : 'OR';
    }

    /**
     * Aggregation getter
     *
     * @return bool
     */
    protected function get_aggregation(): bool {
        return $this->properties->aggregation;
    }

    /**
     * Aggregation setter
     *
     * @param bool $aggregation
     * @return $this
     */
    public function set_aggregation(bool $aggregation) {
        $this->properties->aggregation = $aggregation;

        return $this;
    }

    /**
     * Select from a sub-query or table name, will check passed table name for sanity check against a simple regex
     *
     * @param builder|string|table $where Table name or an instance of a query builder with a subquery
     * @param string|null $as Alias, must be specified in here or prior to this method call
     * @return $this
     */
    public function from($where, ?string $as = null) {

        if ($where instanceof builder) {
           $this->properties->from = $where;
        } else {
            $where = (string) $where;
            if (!empty($where) && !preg_match(builder_base::TABLE_REGEX, $where)) {
                throw new coding_exception('Table name can only be alpha numeric with underscores');
            }

            $this->properties->table = $where;
        }

        if (!empty($as)) {
            $this->as($as);
        }

        if ($this->properties->having instanceof builder_base) {
            $this->properties->having->from($where, $as);
        }

        return $this;
    }

    /**
     * Get table (Table getter)
     *
     * @return string
     */
    public function get_table(): ?string {
        return $this->properties->table;
    }

    /**
     * Set base table alias, will check passed alias for sanity check against a simple regex
     *
     * @param string $alias Table alias, empty for no alias.
     * @return $this
     */
    public function as(string $alias) {
        if (!empty($alias) && !preg_match(builder_base::AS_REGEX, $alias)) {
            throw new coding_exception('Table aliases can only be alpha numeric with underscores');
        }

        if ($this->properties->alias && $this->properties->alias != $this->properties->table && $this->properties->alias !== $alias) {
            throw new coding_exception('Can not reset an alias which has already been set');
        }

        $this->properties->alias = $alias;

        if ($this->properties->having instanceof builder_base) {
            $this->properties->having->as($this->properties->alias);
        }

        return $this;
    }

    /**
     * Get default table alias
     *
     * @return string
     */
    public function get_alias(): ?string {
        return $this->properties->alias;
    }

    /**
     * Is alias used when generating the query
     *
     * @return bool
     */
    public function is_alias_used(): bool {
        return $this->properties->use_alias;
    }

    /**
     * Use table alias when generating the query
     *
     * @param bool $use
     * @return $this
     */
    protected function use_alias(bool $use = true) {
        $this->properties->use_alias = $use;

        return $this;
    }

    /**
     * Do not use table alias when generating the query
     *
     * @return $this
     */
    protected function do_not_use_alias() {
        return $this->use_alias(false);
    }

    /**
     * Get SQL for table alias
     *
     * @return string
     */
    public function get_alias_sql(): string {
        if (empty($this->properties->alias) || !$this->is_alias_used()) {
            return '';
        }

        return "\"{$this->properties->alias}\"";
    }

    /**
     * Return whether this builder has any WHERE conditions added.
     *
     * @return bool
     */
    public function has_conditions(): bool {
        return !empty($this->properties->conditions);
    }

    /**
     * @return array|condition[]|queryable[]
     */
    public function get_conditions(): array {
        return $this->properties->conditions;
    }

    /**
     * Prettifying a bits of sql query by removing extra unnecessary spaces
     *
     * @param array $bits
     * @param bool $implode_only
     * @return string
     */
    protected function prettify_sql(array $bits, bool $implode_only = false): string {
        // We don't want to waste resources
        // if debugging is not activated
        if (!debugging() || $implode_only) {
            return implode(' ', $bits);
        }
        return implode(
            ' ',
            array_filter(
                array_map('trim', $bits),
                function ($condition) {
                    return $condition != '';
                }
            )
        );
    }

    /**
     * Set the instance of this query as being nested
     *
     * @param bool $nested
     * @return $this
     */
    protected function mark_as_nested(bool $nested = true) {
        $this->properties->nested = $nested;

        return $this;
    }

    /**
     * Set the instance of this query as being used as union part in a query
     *
     * @param bool $united Used in a union or not
     * @return $this
     */
    protected function mark_as_united(bool $united = true) {
        $this->properties->united = $united;

        return $this;
    }

    /**
     * Return a new builder instance for the same table
     *
     * @return builder
     */
    public function new(): builder {
        return builder::table($this->get_table())
            ->as($this->properties->alias);
    }

    /**
     * Return each record as array
     *
     * @param bool $as_array
     * @return $this
     */
    public function results_as_arrays(bool $as_array = true) {
        $this->properties->return_type = $as_array ? $this->properties::AS_ARRAY : $this->properties::AS_OBJECT;

        return $this;
    }

    /**
     * Return results as array of objects
     *
     * @return $this
     */
    public function results_as_objects() {
        return $this->results_as_arrays(false);
    }

    /**
     * Should this builder return arrays?
     *
     * @return bool
     */
    protected function should_return_array(): bool {
        return $this->properties->return_type == properties::AS_ARRAY;
    }

    /**
     * Map results to a class or to a given callback
     *
     * @param callable|string|null $what Something to map results to
     * @return $this
     */
    public function map_to($what) {

        if (!is_null($what) && !is_callable($what) && !class_exists($what)) {
            throw new coding_exception('The object you map to must be a callable or a valid class name or null to cancel mapping');
        }

        $this->properties->map_to = $what;

        return $this;
    }

    /**
     * Perform mapping of a returned record to a class or a callable
     *
     * @param mixed $item returned from the database
     * @return array|stdClass|null
     */
    protected function map_result($item) {
        $map = $this->properties->map_to;

        if ($this->should_return_array()) {
            $item = (array) $item;
        }

        if (is_callable($map)) {
            return $map($item);
        }

        if ($map && class_exists($map)) {
            return new $map($item);
        }

        // No mapping at all
        return $item;
    }

    /**
     * A function to map results to a given callback and convert it to array if needed
     *
     * @param array $results Fresh juicy results fetched from a database
     * @return array
     */
    protected function map_results($results): array {
        return array_map([$this, 'map_result'], $results);
    }

    /**
     * Factory builder constructor
     *
     * @return builder
     */
    public static function create(): self {
        $builder = new builder();
        $builder->properties->nested = false;

        return $builder;
    }

    /**
     * Check if given properties are set and throw exception if it is.
     * This is used to limit certain actions. For example update cannot be used with joins, unions, etc.
     *
     * @param string $action
     * @param array|string[] $properties
     * @return $this
     */
    protected function restrict(string $action, array $properties) {
        foreach ($properties as $property) {
            if (!empty($this->properties->$property)) {
                throw new coding_exception($property.' cannot be used with action '.$action.'()');
            }
        }
        return $this;
    }

    /**
     * Check if given properties are set and throw exception if it is not.
     * This is used to make sure properties are set for certain actions. For example first needs order to be set.
     *
     * @param string $action
     * @param array $properties
     * @return $this
     */
    protected function expect(string $action, array $properties) {
        foreach ($properties as $property) {
            if (empty($this->properties->$property)) {
                throw new coding_exception($property.' has to be set for action '.$action.'()');
            }
        }
        return $this;
    }

    /**
     * Check whether a query has a join already and return it
     *
     * @param string|table $table Table name
     * @param string|null $alias Table alias, if not specified will return a first join for a given table
     * @return join|null
     */
    public function get_join($table, ?string $alias = null): ?join {
        if ($table instanceof table) {
            if (empty($alias) && $table->has_alias()) {
                $alias = $table->get_alias();
            }

            $table = $table->get_name();
        } else {
            $table = (string) $table;
        }

        foreach ($this->properties->joins as $join) {
            if ($join->get_table()->get_name() === $table) {
                if (empty($alias) || $alias == $join->get_table()->get_alias()) {
                    return $join;
                }
            }
        }

        return null;
    }

    /**
     * Check whether a query has a join already and return a boolean
     *
     * @param string|table $table Table name
     * @param string|null $alias Table alias, if not specified will return a first join for a given table
     * @return bool
     */
    public function has_join($table, ?string $alias = null): bool {
        return !is_null($this->get_join($table, $alias));
    }

    /**
     * Clone builder object
     * Builder is a complex object that doesn't support shallow cloning
     * We'll explicitly throw an exception now if an attempt of cloning builder has been made
     * @throws coding_exception
     */
    public function __clone() {
        throw new coding_exception(
            'Builder is a complex object with multiple references inside,' .
            'cloning is not supported. Please consider creating a new instance instead of cloning'
        );
    }

    /**
     * Update table names from {table} to prefix_table
     * Copied from DML, it's protected there
     *
     * @param string $sql
     * @return string|string[]|null
     */
    protected function real_table_names(string $sql): ?string {
        // TODO is used only to make db exceptions compatible with moodle_database exceptions
        // TODO as these require you to pass generated query and since we use those for get_record(s) (strict)
        // TODO we don't have a direct way of getting the query executed, so it's "mocked"
        // TODO it isn't used in any actual code. Is here a better way?
        return preg_replace('/\{([a-z][a-z0-9\_]*)\}/', self::get_db()->get_prefix().'$1', $sql);
    }

}
