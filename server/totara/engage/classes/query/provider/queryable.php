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
use totara_engage\query\query;

interface queryable {

    /**
     * Returning the fields as following, in order to make union works for multiple database type:
     *
     * + uniqueid       -> Where it is like a concatinate string of the card type.
     * + instanceid     -> The instanceid of the card.
     * + name           -> The name to be displayed on the card.
     * + summary        -> Card's summary.
     * + userid         -> The owner's id of the card.
     * + access         -> Access setting.
     * + timecreated
     * + timemodified
     * + extra          -> Extra data of the card, this is more likely to be a JSON string.
     * + component      -> Card's component
     *
     * @param query $query
     * @return builder|null
     */
    public function get_builder(query $query): ?builder;

    /**
     * Provider can get filtered by type.
     *
     * @param query $query
     * @return bool
     */
    public static function provide_query_type(query $query): bool;

    /**
     * Get section filter options.
     *
     * @param query $query
     * @return array
     */
    public function get_section_options(query $query): array;

    /**
     * Create a builder to get resources linked to other resources.
     *
     * @param query $query
     * @param bool $sub_query
     * @return resource_builder|null
     */
    public function get_linked_builder(query $query, bool $sub_query = true): ?resource_builder;

}