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
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\api\response;

use contentmarketplace_linkedin\exception\json_validation_exception;
use core\json\validation_adapter;
use stdClass;

abstract class element implements result {
    /**
     * The cleaned and validated json data.
     * @var stdClass
     */
    protected $json_data;

    /**
     * element constructor.
     * @param stdClass $json_data
     */
    public function __construct(stdClass $json_data) {
        $this->json_data = $json_data;
    }

    /**
     * Return the URN from element data.
     * @return string
     */
    abstract public function get_urn(): string;

    /**
     * Returns the json structure class name.
     * @return string
     */
    abstract protected static function get_json_structure(): string;

    /**
     * @param stdClass $json_data
     * @param bool     $skip_validation This parameter is here, because when we create the collection from
     *                                  the json response, the schema structure should be checked against the
     *                                  element within that collection schema already. Hence we can turn off the
     *                                  validation to make it faster.
     * @return element
     */
    public static function create(stdClass $json_data, bool $skip_validation = false): element {
        // Clear any pass by reference.
        $json_data = clone $json_data;

        if (!$skip_validation) {
            $structure_class_name = static::get_json_structure();
            $validator = validation_adapter::create_default();

            $result = $validator->validate_by_structure_class_name($json_data, $structure_class_name);
            if (!$result->is_valid()) {
                $error_message = $result->get_error_message();
                throw new json_validation_exception($error_message);
            }

        }

        return new static($json_data);
    }
}