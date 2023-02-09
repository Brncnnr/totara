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
namespace contentmarketplace_linkedin\api\response;

use contentmarketplace_linkedin\exception\json_validation_exception;
use core\json\validation_adapter;
use stdClass;

abstract class collection implements result {
    /**
     * The json data.
     * @var stdClass
     */
    protected $json_data;

    /**
     * collection constructor.
     * @param stdClass $json_data
     */
    protected function __construct(stdClass $json_data) {
        $this->json_data = $json_data;
    }

    /**
     * Returns the json structure class name.
     *
     * @return string
     */
    abstract protected static function get_structure_name(): string;

    /**
     * @param stdClass $json_data
     * @return collection
     */
    public static function create(stdClass $json_data): collection {
        // Clear any pass by reference.
        $json_data = clone $json_data;

        $structure_class_name = static::get_structure_name();
        $validator = validation_adapter::create_default();

        $result = $validator->validate_by_structure_class_name($json_data, $structure_class_name);
        if (!$result->is_valid()) {
            $error_message = $result->get_error_message();
            throw new json_validation_exception($error_message);
        }

        return new static($json_data);
    }

    /**
     * @return element[]
     */
    abstract public function get_elements(): array;

    /**
     * @return pagination
     */
    public function get_paging(): pagination {
        $pagination_data = $this->json_data->paging;
        return pagination::create($pagination_data, true);
    }
}