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

use contentmarketplace_linkedin\core_json\data_format\availability;
use contentmarketplace_linkedin\core_json\data_format\difficulty_level;
use core\json\data_format\param_url;
use core\json\structure\structure;
use core\json\type;

/**
 * Json schema for asset details
 */
class asset_details extends structure {
    /**
     * @return array
     */
    public static function get_definition(): array {
        $locale_structure = locale::get_definition();
        $locale_string_structure = locale_string::get_definition();

        return [
            'type' => type::OBJECT,
            'properties' => [
                'availability' => [
                    'type' => type::STRING,
                    'format' => availability::get_name(),
                ],
                'availableLocales' => [
                    'type' => type::ARRAY,
                    'items' => $locale_structure,
                ],
                'classifications' => [
                    'type' => type::ARRAY,
                ],
                'contributors' => [
                    'type' => type::ARRAY,
                    'items' => contributor::get_definition(),
                ],
                'description' => $locale_string_structure,
                'descriptionIncludingHtml' => $locale_string_structure,
                'images' => [
                    'type' => type::OBJECT,
                    'properties' => [
                        'primary' => [
                            'type' => type::STRING,
                            'format' => 'param_url',
                        ],
                    ],
                    structure::ADDITIONAL_PROPERTIES => true,
                ],
                'lastUpdatedAt' => [
                    'type' => type::INT,
                ],
                'level' => [
                    'type' => type::STRING,
                    'format' => difficulty_level::get_name(),
                ],
                'publishedAt' => [
                    'type' => type::INT,
                ],
                'relationships' => [
                    // From linkedin:
                    // The value of this field is currently always an empty array. Future versions of the API may use
                    // this field to indicate relationships the learning asset has to other learning assets.
                    'type' => type::ARRAY,
                ],
                'retiredAt' => [
                    'type' => type::INT,
                ],
                'shortDescription' => $locale_string_structure,
                'shortDescriptionIncludingHtml' => $locale_string_structure,
                'timeToComplete' => time_span::get_definition(),
                'urls' => [
                    'type' => type::OBJECT,
                    'properties' => [
                        'aiccLaunch' => [
                            'type' => type::STRING,
                            'format' => param_url::get_name(),
                        ],
                        'ssoLaunch' => [
                            'type' => type::STRING,
                            'format' => param_url::get_name(),
                        ],
                        'webLaunch' => [
                            'type' => type::STRING,
                            'format' => param_url::get_name(),
                        ],
                    ],
                    structure::ADDITIONAL_PROPERTIES => true,
                ],
                'discoverableBy' => [
                    'type' => type::ARRAY,
                ]
            ],
            structure::ADDITIONAL_PROPERTIES => true,
        ];
    }
}