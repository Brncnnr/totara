<?php
/**
 * This file is part of Totara Core
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

use contentmarketplace_linkedin\core_json\data_format\classification_type;
use core\json\structure\structure;
use core\json\type;

class classification_element extends structure {
    /**
     * @return array
     */
    public static function get_definition(): array {
        return [
            'type' => type::OBJECT,
            'properties' => [
                'name' => locale_string::get_definition(),
                'urn' => [
                    'type' => type::STRING
                ],
                'type' => [
                    'type' => type::STRING,
                    'format' => classification_type::get_name(),
                ],
                'owner' => named_party::get_definition(),
            ],
            'required' => ['urn', 'name', 'type'],
            structure::ADDITIONAL_PROPERTIES => true
        ];
    }
}