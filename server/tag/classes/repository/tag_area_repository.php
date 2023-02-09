<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_tag
 */
namespace core_tag\repository;

use core\orm\entity\repository;
use core\orm\query\builder;
use core_tag\entity\tag_area;

final class tag_area_repository extends repository {
    /**
     * @param string $component
     * @param string $itemtype
     *
     * @return tag_area|null
     */
    public function find_for_component(string $component, string $itemtype): ?tag_area {
        $builder = builder::table(static::get_table());
        $builder->where('component', $component);
        $builder->where('itemtype', $itemtype);

        $builder->map_to(tag_area::class);

        /** @var tag_area|null $entity */
        $entity = $builder->one();
        return $entity;
    }
}