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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
namespace totara_notification\placeholder\abstraction;

/**
 * Interface collection_placeholder is used for representing a collection of items placeholder,
 * for example: a list of users, or a list of courses.
 */
interface collection_placeholder extends placeholder {
    /**
     * Return a collection of hash map, where hash-map is the map of key and value that key
     * may or may not present in the template.
     *
     * @param array $load_only_keys     This parameter is in place to help the implementation on optimising the
     *                                  performance that it would not try to load all the unecessary data.
     * @return array
     */
    public function get_collection_map(array $load_only_keys = []): array;
}