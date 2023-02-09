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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

namespace totara_engage\query\provider;

use core\orm\query\builder;

interface container {

    /**
     * The builder should return the following fields:
     *   - id
     *   - type
     *   - owner_id
     *   - resource_id
     *   - name
     *
     * @return builder
     */
    public function get_container_builder(): builder;

    /**
     * @param int $resourceid
     * @param $component
     * @return array
     */
    public function get_container_details(int $resourceid, $component): array;

}