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

use core\orm\query\sql\query;
use core\orm\query\sql\where;

/**
 * Class join holds the join details provides a wrapper around builder that has join conditions in it to keep extra
 * stuff that's needed to build a proper join condition.
 *
 * @package core\orm\query
 */
final class join {

    /**
     * Table name \ builder instance if joining subquery
     *
     * @var table
     */
    protected $table;

    /**
     * @var string
     */
    protected $type = 'inner';

    /**
     * Builder instance
     *
     * @var builder
     */
    protected $builder;

    protected $allowed_types = ['inner', 'left', 'right', 'cross', 'full'];

    /**
     * Create join instance
     *
     * @param builder|null $parent A link to the parent builder
     */
    public function __construct(?builder $parent = null) {
        $this->builder = new builder();

        if ($parent) {
            $this->builder->set_parent($parent);
        }
    }

    /**
     * Set table to join
     *
     * @param string|table $table
     * @return $this
     */
    public function set_table($table) {

        if (!$table instanceof table) {
            $table = (new table($table, $this->builder))->as($table);
        }

        $this->table = $table;

        // Table can be an instance of builder as well which holds a sub-query to join.
        if ($table->get_name() instanceof builder) {
            $this->builder->from($this->table->get_name())
                ->as($table->get_alias());
        } else {
            // Update builder table and alias
            $this->builder->from($table->get_name())
                ->as($table->get_alias());
        }

        return $this;
    }

    /**
     * Get table
     *
     * @return table|null
     */
    public function get_table(): ?table {
        return $this->table;
    }

    /**
     * Set Join type
     *
     * @param string $type ['inner', 'left', 'right', 'cross', 'full']
     *
     * @return $this
     */
    public function set_type(string $type) {
        if (!in_array(strtolower($type), $this->allowed_types)) {
            $allowed_types = implode(", ", $this->allowed_types);
            throw new \coding_exception("Join type must be in the following subset: " . $allowed_types . "\"{$type}\" given!");
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Get builder instance holding join ON ... conditions
     *
     * @return builder
     */
    public function get_builder(): builder {
        return $this->builder;
    }

    /**
     * Return the actual [$sql, $params] for the join
     *
     * @return array
     */
    public function join_sql(): array {

        if ($this->builder->has_conditions()) {
            [$where, $params] = where::from_builder($this->builder)->build();

            $where = "ON {$where}";
        } else {
            $where = '';
            $params = [];
        }

        $type = strtoupper($this->type);

        // Sub-query check
        // Table can be an instance of builder as well which holds a sub-query to join.
        if ($this->table->get_name() instanceof builder) {
            $subquery = $this->table->get_name();
            [$sub_sql, $sub_params] = query::from_builder($subquery)->build();

            if (!empty($sub_sql = trim($sub_sql))) {
                $sub_sql = "({$sub_sql})";

                if (!empty($as = $this->get_builder()->get_alias())) {
                    $sub_sql = "{$sub_sql} \"{$as}\"";
                }

                $table_name = $sub_sql;
                $params = array_merge($params, $sub_params);
            } else {
                throw new \coding_exception('You can not join an empty sub-query');
            }
        } else {
            $table_name = $this->table->sql();
        }

        return [trim("{$type} JOIN {$table_name} {$where}"), $params];
    }
}