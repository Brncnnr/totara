<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\repository;

use core\orm\collection;
use core\orm\entity\repository;
use core\orm\query\builder;
use totara_contentmarketplace\entity\course_module_source;
use totara_contentmarketplace\learning_object\factory;

/**
 * @method course_module_source one(bool $strict = false)
 * @method collection|course_module_source[] get(bool $unkeyed = false)
 */
class course_module_source_repository extends repository {

    /**
     * Get the course module sources for a particular marketplace and internallearning object ID.
     *
     * @param int $id E.g. 123
     * @param string $component
     * @return self
     */
    public function filter_by_id_and_component(int $id, string $component): self {
        return $this
            ->where('learning_object_id', $id)
            ->where('marketplace_component', $component);
    }

    /**
     * Get the course module sources for a particular marketplace and external learning object ID.
     *
     * @param string $external_id E.g. 'urn:linkedin:123'
     * @param string $component
     * @return $this
     */
    public function filter_by_external_id_and_component(string $external_id, string $component): self {
        $resolver = factory::get_resolver($component);
        $table = $resolver::get_entity_class()::TABLE;
        $external_id_field = $resolver::get_external_id_field();

        return $this
            ->join($table, function (builder $builder) use ($table, $component) {
                $base_alias = '"' . $this->get_table() . '"';
                $builder
                    ->where_field("$base_alias.learning_object_id", "$table.id")
                    ->where("$base_alias.marketplace_component", $component);
            })
            ->where("$table.$external_id_field", $external_id);
    }

}
