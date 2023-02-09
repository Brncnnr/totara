<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package core_container
 */
namespace core_container\facade;

/**
 * Returning a unique value which will be saved within table {course_categories}.
 *
 * The child container to implement this interface in order to have the ability to provide the category
 * id number for the category API when create new default category or when the API is performing
 * a record look up.
 *
 *
 * Notes:
 * + If the child container does not implement the interface then the default value for id number
 * will be container_type concat with the parent category's id.
 */
interface category_id_number_provider {
    /**
     * @return string
     */
    public static function get_container_category_id_number(): string;
}