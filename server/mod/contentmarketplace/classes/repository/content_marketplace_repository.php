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
 * @package mod_contentmarketplace
 */
namespace mod_contentmarketplace\repository;

use core\orm\collection;
use core\orm\entity\repository;
use core\orm\lazy_collection;
use mod_contentmarketplace\entity\content_marketplace;

/**
 * Class content_marketplace_repository
 */
class content_marketplace_repository extends repository {
    /**
     * @param int    $learning_object_id
     * @param string $marketplace_component
     *
     * @return lazy_collection
     */
    public function fetch_by_id_and_component(int $learning_object_id, string $marketplace_component): lazy_collection {
        $repository = content_marketplace::repository();
        $repository->where("learning_object_id", $learning_object_id);
        $repository->where("learning_object_marketplace_component", $marketplace_component);

        return $repository->get_lazy();
    }

    /**
     * @param int $course_id
     * @return content_marketplace|null
     */
    public function find_by_course_id(int $course_id): ?content_marketplace {
        /** @var content_marketplace $entity */
        $entity = content_marketplace::repository()->where('course', $course_id)->one();

        return $entity;
    }

    /**
     * @param int $course_id
     * @param string $component
     *
     * @return collection
     */
    public function find_by_course_id_and_component(int $course_id, string $component): collection {
        return content_marketplace::repository()
            ->where('course', $course_id)
            ->where('learning_object_marketplace_component', $component)
            ->get();
    }

}