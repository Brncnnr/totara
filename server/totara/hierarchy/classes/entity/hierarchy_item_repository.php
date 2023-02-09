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
 * @package totara_hierarchy
 */

namespace totara_hierarchy\entity;

use coding_exception;
use core\orm\entity\filter\basket;
use core\orm\entity\filter\equal;
use core\orm\entity\filter\hierarchy_item_visible;
use core\orm\entity\filter\in;
use core\orm\entity\filter\like;
use core\orm\entity\repository;
use core\orm\entity\traits\has_visible_filter;
use core\orm\query\builder;
use core\orm\query\field;
use core\orm\query\subquery;
use totara_competency\entity\filters\path;

abstract class hierarchy_item_repository extends repository {

    use has_visible_filter;

    /**
     * Add the children count
     *
     * @return $this
     */
    public function with_children_count(): hierarchy_item_repository {
        $this->add_select((new subquery(function (builder $builder) {
            $builder->from($this->builder->get_table())
                ->as('sq_ha')
                ->select('count(id)')
                ->where_field('parentid', new field('id', $this->builder));
        }))->as('children_count'));

        return $this;
    }

    /**
     * Select only fields a small subset of columns from the database which is relevant for a picker dialogue
     *
     * @return $this
     */
    public function select_only_fields_for_picker() {
        $this->add_select([
            'id',
            'shortname',
            'fullname',
            'frameworkid',
            'description',
            'idnumber',
        ]);

        return $this;
    }

    /**
     * Define available default filters
     *
     * @return array
     */
    protected function get_default_filters(): array {
        return [
            'text' => new like([
                new field('fullname', $this->builder),
                new field('idnumber', $this->builder),
                new field('shortname', $this->builder),
                new field('description', $this->builder)
            ]),
            'framework' => new equal(new field('frameworkid', $this->builder)),
            'parent' => new equal(new field('parentid', $this->builder)),
            'path' => new path(),
            'basket' => new basket(),
            'visible' => new hierarchy_item_visible(),
            'type' => new in('typeid'),
            'ids' => new in('id')
        ];
    }

    /**
     * @param string $column
     * @param string $direction
     * @return $this|repository
     */
    public function order_by(string $column, string $direction = 'asc') {
        if (empty($column)) {
            $column = 'sortthread';
        }

        $disallowed_order_columns = [
            'contextid',
            'descriptionformat',
            'scaleconfiguration',
            'taxonomies',
        ];
        if (in_array($column, $disallowed_order_columns)) {
            throw new coding_exception('Not allowed to sort by column ' . $column);
        }

        parent::order_by($column, $direction);

        return $this;
    }

}
