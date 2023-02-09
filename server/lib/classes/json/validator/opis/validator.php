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
namespace core\json\validator\opis;

use core\json\abstraction\data_format_aware;
use core\json\abstraction\validation_result;
use core\json\abstraction\validator as i_validator;
use core\json\data_format\data_format;
use Opis\JsonSchema\Validator as external_validator;
use stdClass;

/**
 * Implementation for json validator for opis json schema.
 */
class validator implements i_validator, data_format_aware {
    /**
     * @var external_validator
     */
    private external_validator $validator;

    /**
     * The schema to accept
     */
    private string $schema;

    /**
     * If no schema is defined, then fallback to this as the default.
     * This prevents a library change from unintentionally adjusting our schema.
     */
    private const DEFAULT_SCHEMA = 'http://json-schema.org/draft-07/schema';

    /**
     * @param string|null $schema The schema to associate with
     */
    public function __construct(?string $schema = null) {
        $this->validator = new external_validator();
        $this->schema = $schema ?? self::DEFAULT_SCHEMA;
    }

    /**
     * @param data_format ...$formats
     * @return void
     */
    public function set_format(data_format ...$formats): void {
        $container = $this->validator->parser()->getFormatResolver();
        foreach ($formats as $format) {
            $custom_format = new custom_format($format);
            $container->register(
                $format->get_for_type(),
                $format::get_name(),
                $custom_format
            );
        }
    }

    /**
     * Validates the json data by the given schema structure.
     *
     * @param stdClass|array $json_data
     * @param stdClass       $structure
     *
     * @return validation_result
     */
    public function in_structure($json_data, stdClass $structure): validation_result {
        // Add in the schema if one is missing & we have one set
        if (!property_exists($structure, '$schema') && $this->schema) {
            $structure->{'$schema'} = $this->schema;
        }

        $schema = $this->validator->loader()->loadObjectSchema($structure);
        $result = $this->validator->validate($json_data, $schema);

        return new result($result);
    }
}