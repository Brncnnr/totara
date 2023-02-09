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
use core_tag\entity\tag_collection;
use core_tag\entity\tag_instance;

/**
 * Repository class for tag_instance
 */
final class tag_instance_repository extends repository {
    /**
     * @param int $tagid
     * @return tag_instance[]
     */
    public function get_instances_of_tag(int $tagid): array {
        $builder = builder::table(static::get_table());
        $builder->map_to(tag_instance::class);

        $builder->where('tagid', $tagid);
        return $builder->fetch();
    }

    /**
     * Returns the count of the number of tag instances for a specified collection.
     *
     * @param string $name
     * @param string $component
     * @return int
     */
    public function count_instances_for_collection(string $name, string $component): int {
        $builder = builder::table(static::get_table());
        return $builder
            ->join(['tag', 't'], 'tagid', '=', 'id')
            ->join([tag_collection::TABLE, 'tc'], 'tc.id', '=', 't.tagcollid')
            ->where('tc.name', '=', $name)
            ->where('tc.component', '=', $component)
            ->count();
    }
}