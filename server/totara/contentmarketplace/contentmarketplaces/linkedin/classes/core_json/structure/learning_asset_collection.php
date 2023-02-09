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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\core_json\structure;

use core\json\structure\structure;
use core\json\type;

/**
 * Json structure for learning asset collection response from
 * linkedin learning's API.
 */
class learning_asset_collection extends structure {
    /**
     * @return array
     */
    public static function get_definition(): array {
        return [
            'type' => type::OBJECT,
            'properties' => [
                'elements' => [
                    'type' => type::ARRAY,
                    'items' => learning_asset_element::get_definition(),
                    structure::ADDITIONAL_ITEMS => false,
                ],
                'paging' => pagination::get_definition(),
                'metadata' => [
                    'type' => type::OBJECT,
                ],
            ],
            structure::ADDITIONAL_PROPERTIES => true,
        ];
    }
}