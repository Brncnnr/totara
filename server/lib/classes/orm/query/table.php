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

/**
 * This class represents a table and includes the table name and the alias.
 * It can also contain a builder instance as the table, which represents a subquery.
 */
final class table {

    /**
     * @var builder
     */
    protected $builder;

    /**
     * Table name or builder\sub-query instance to join a sub-query
     *
     * @var string|builder|subquery
     */
    protected $table;

    /**
     * Table alias
     *
     * @var string
     */
    protected $as = '';

    /**
     * @param string|builder|subquery $table Table name
     * @param builder|null $builder
     */
    public function __construct($table, ?builder $builder = null) {
        switch (true) {
            case is_string($table):
                if (!preg_match(builder_base::TABLE_REGEX, $table)) {
                    throw new coding_exception('Table name can only be alpha numeric with underscores');
                }
                $this->table = $table;
                break;

            case $table instanceof builder:
                $this->table = $table;
                break;

            case $table instanceof subquery:
                $this->table = $table->get_subquery();
                $this->as($table->get_field_as());
                break;
        }

        if ($builder) {
            $this->set_builder($builder);
        }
    }

    /**
     * Associate a query builder instance
     *
     * @param builder $builder
     * @return table
     */
    public function set_builder(builder $builder) {
        $this->builder = $builder;

        return $this;
    }

    /**
     * Returns associated builder instance
     *
     * @return builder
     */
    public function get_builder(): ?builder {
        return $this->builder;
    }

    /**
     * Table alias will check passed alias for sanity check against a simple regex
     *
     * @param string $as
     * @return $this
     */
    public function as(string $as) {
        if (!preg_match(builder_base::AS_REGEX, $as)) {
            throw new coding_exception('Table aliases can only be alpha numeric with underscores');
        }
        $this->as = $as;

        if ($this->builder) {
            $this->builder->as($as);
        }

        return $this;
    }

    /**
     * Get table alias
     *
     * @return string
     */
    public function get_alias(): string {
        return $this->as;
    }

    /**
     * Return whether a table has an alias
     *
     * @return bool
     */
    public function has_alias(): bool {
        return !empty($this->as);
    }

    /**
     * Get sql for the table
     *
     * @return string
     */
    public function sql(): ?string {
        // Checking for sub-query here
        if ($this->table instanceof builder) {
            // Maybe throwing exception would do here, however it leads to annoying fatal errors when inside toString...
            return null;
        } else {
            if (!empty($this->as)) {
                // We wrap the alias into double quotes to make sure we don't get conflicts with reserved words
                return "{{$this->table}} \"{$this->as}\"";
            }

            return "{{$this->table}}";
        }
    }

    /**
     * Get original table name
     *
     * @return string|builder
     */
    public function get_name() {
        return $this->table;
    }

    /**
     * Convert class to string
     *
     * @return string
     */
    public function __toString() {
        return $this->sql();
    }
}
