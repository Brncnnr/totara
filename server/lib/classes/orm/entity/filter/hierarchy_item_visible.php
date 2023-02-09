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

namespace core\orm\entity\filter;

use core\orm\query\builder;
use core\orm\query\field;

class hierarchy_item_visible extends filter {

    public function apply() {
        if (is_bool($this->value)) {
            // Complex. If the item is visible it should be visible in a framework and on an item level,
            // However if it's invisible either of these conditions should be met.
            $this->builder->where(function (builder $builder) {
                $visible = $this->value;

                $builder->where('visible', $visible)
                    ->where(new field('visible', $this->get_framework_join()->get_builder()), '=', $visible, !$visible);
            });
        }
    }

    protected function get_framework_join() {
        // We are getting framework class name and from it getting its table name
        $table = $this->entity_class::get_framework_class()::TABLE;
        /** @var $fw_join \core\orm\query\join */
        if (!$fw_join = $this->builder->get_join($table)) {
            $this->builder->join($table, 'frameworkid', 'id');
            $fw_join = $this->builder->get_join($table);
        }

        return $fw_join;
    }

}