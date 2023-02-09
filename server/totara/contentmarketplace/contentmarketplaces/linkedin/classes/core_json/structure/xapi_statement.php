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

use contentmarketplace_linkedin\core_json\data_format\progress_percentage;
use contentmarketplace_linkedin\totara_xapi\handler\handler;
use core\json\structure\structure;
use core\json\type;

/**
 * The json structure for xapi statement from linkedin learning.
 */
class xapi_statement extends structure {
    /**
     * @return array
     */
    public static function get_definition(): array {
        return [
            "type" => type::OBJECT,
            "properties" => [
                "actor" => [
                    "type" => type::OBJECT,
                    "properties" => [
                        "objectType" => [
                            "type" => type::STRING,
                        ],
                        "mbox" => [
                            "type" => type::STRING
                        ]
                        // Missing SSO fields, but it will be added when sso is added.
                    ],
                    "required" => ["objectType"]
                ],
                "result" => [
                    "type" => type::OBJECT,
                    "properties" => [
                        "completion" => [
                            "type" => type::BOOL
                        ],
                        "extensions" => [
                            "type" => type::OBJECT,
                            "properties" => [
                                handler::PROGRESS_RESULT_KEY => [
                                    "type" => [type::INT, type::STRING],
                                    "format" => progress_percentage::get_name()
                                ]
                            ]
                        ],
                        "duration" => [
                            "type" => type::STRING
                        ]
                    ],
                    "required" => ["completion"]
                ],
                "verb" => [
                    "type" => type::OBJECT,
                    "properties" => [
                        "id" => [
                            "type" => type::STRING
                        ],
                        "display" => [
                            "type" => type::OBJECT
                        ]
                    ],
                    "required" => ["id", "display"],
                    structure::ADDITIONAL_PROPERTIES => true
                ],
                "id" => [
                    "type" => type::STRING
                ],
                "timestamp" => [
                    "type" => type::STRING
                ],
                "object" => [
                    "type" => type::OBJECT,
                    "properties" => [
                        "definition" => [
                            "type" => type::OBJECT,
                            "properties" => [
                                "type" => [
                                    "type" => type::STRING
                                ]
                            ],
                            "required" => ["type"]
                        ],
                        "id" => [
                            "type" => type::STRING
                        ],
                        "objectType" => [
                            "type" => type::STRING
                        ]
                    ],
                    "required" => ["id"],
                    structure::ADDITIONAL_PROPERTIES => true
                ]
            ],
            "required" => ["actor", "result", "object", "id", "timestamp", "verb"],
            structure::ADDITIONAL_PROPERTIES => true
        ];
    }
}