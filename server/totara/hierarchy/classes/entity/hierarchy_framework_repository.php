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

use core\orm\entity\filter\visible;
use core\orm\entity\traits\has_visible_filter;
use core\orm\query\field;
use core\orm\entity\filter\like;
use core\orm\entity\repository;

abstract class hierarchy_framework_repository extends repository {

    protected $order = 'sortorder';

    use has_visible_filter;

    /**
     * Default set of filters for hierarchy frameworks
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
            'visible' => new visible(),
        ];
    }

    public function order_by(string $column, string $direction = 'asc') {
        $allowed_order_columnns = [
            'id',
            'fullname',
            'shortname',
            'description',
            'idnumber',
            'sortorder',
            'timecreated',
            'timemodified',
            'usermodified'
        ];
        if (!in_array($column, $allowed_order_columnns)) {
            $column = 'sortorder';
        }

        parent::order_by($column, $direction);

        return $this;
    }

}