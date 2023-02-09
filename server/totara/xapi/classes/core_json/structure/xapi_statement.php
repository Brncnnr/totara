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
 * @author  Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_xapi
 */
namespace totara_xapi\core_json\structure;

use core\json\structure\structure;
use core\json\type;

/**
 * The json structure for a basic generic xapi statement.
 *
 * See https://github.com/adlnet/xAPI-Spec/blob/master/xAPI-Data.md#22-formatting-requirements
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
                    ]
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
                    "required" => ["id"]
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
                            ]
                        ],
                        "id" => [
                            "type" => type::STRING
                        ],
                        "objectType" => [
                            "type" => type::STRING
                        ]
                    ],
                    "required" => ["id"]
                ]
            ],
            "required" => ["actor", "object", "verb"]
        ];
    }
}