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
 * @package core
 */
namespace core\json;

use coding_exception;
use core\json\abstraction\data_format_aware;
use core\json\abstraction\validation_result;
use core\json\abstraction\validator;
use core\json\data_format\factory as format_factory;
use core\json\structure\factory as structure_factory;
use core\json\structure\structure;
use core\json\validator\opis\validator as opis_validator;
use stdClass;

class validation_adapter {
    /**
     * @var validator
     */
    protected $validator;

    /**
     * validation_adapter constructor.
     * @param validator $validator
     */
    public function __construct(validator $validator) {
        $this->validator = $validator;

        if ($this->validator instanceof data_format_aware) {
            $formats = format_factory::get_all_formats();
            $this->validator->set_format(...$formats);
        }
    }

    /**
     * Create a an instance of validation_adapter with opis json validator under the hood.
     *
     * @return validation_adapter
     */
    public static function create_default(): validation_adapter {
        $validator = new opis_validator();
        return new static($validator);
    }

    /**
     * Returns the decoded json document as dummy data object, or array
     * if the json data is an actual array.
     *
     * @param string $json_document
     * @return stdClass|array
     */
    protected function decode_json(string $json_document) {
        $json_data = json_decode($json_document);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $message = json_last_error_msg();
            throw new coding_exception(
                "Cannot decode the json data due to: {$message}"
            );
        }

        return $json_data;
    }

    /**
     * @param mixed  $data
     * @param string $structure_name
     * @param string $component
     *
     * @return validation_result
     */
    public function validate_by_structure_name($data, string $structure_name, string $component): validation_result {
        $class_name = structure_factory::get_structure_class_name($structure_name, $component);
        return $this->validate_by_structure_class_name($data, $class_name);
    }

    /**
     * Validate the json data by the structure class name.
     *
     * @param mixed  $data
     * @param string $structure_class_name
     *
     * @return validation_result
     */
    public function validate_by_structure_class_name($data, string $structure_class_name): validation_result {
        if (!class_exists($structure_class_name)) {
            throw new coding_exception("The class '{$structure_class_name}' does not exist in the system");
        } else if (!is_subclass_of($structure_class_name, structure::class)) {
            $parent_class_name = structure::class;
            throw new coding_exception("The class '{$structure_class_name}' is not a child of '{$parent_class_name}'");
        }

        /**
         * @see structure::get_definition()
         * @var string|array|stdClass $json_structure
         */
        $json_structure = $structure_class_name::get_definition();
        if (is_array($json_structure)) {
            return $this->validate_by_array_structure($data, $json_structure);
        }

        if (is_string($json_structure)) {
            return $this->validate_by_json_structure($data, $json_structure);
        }

        return $this->validate($data, $json_structure);
    }

    /**
     * @param mixed  $data
     * @param string $json_structure
     *
     * @return validation_result
     */
    public function validate_by_json_structure($data, string $json_structure): validation_result {
        $structure = $this->decode_json($json_structure);
        return $this->validate($data, $structure);
    }

    /**
     * This function will encode the structure to json string, so that we can decode it to an object, which
     * also cast the n+1 depth level of properties that we want it to be an object too.
     *
     * @param mixed $data
     * @param array $structure
     * @return validation_result
     */
    public function validate_by_array_structure($data, array $structure): validation_result {
        $json_structure = json_encode($structure);
        return $this->validate_by_json_structure($data, $json_structure);
    }

    /**
     * If the $data is a string, this function will attempts to encode that json string.
     *
     * @param mixed    $data
     * @param stdClass $structure
     *
     * @return validation_result
     */
    public function validate($data, stdClass $structure): validation_result {
        $json_data = $data;

        if (is_string($data)) {
            // Decode the json data, if the given $data is a json string string.
            // Note that this process will fail if the given data isn't an invalid data.
            $json_data = $this->decode_json($data);
        }

        return $this->validator->in_structure($json_data, $structure);
    }
}