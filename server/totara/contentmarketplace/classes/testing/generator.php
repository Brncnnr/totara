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
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\testing;

use coding_exception;
use core\testing\component_generator;
use totara_contentmarketplace\learning_object\abstraction\metadata\model;
use totara_contentmarketplace\learning_object\factory;
use totara_core\http\response;
use totara_core\http\response_code;

/**
 * @method static generator instance()
 */
class generator extends component_generator {
    /**
     * @param array $json_data
     * @param int   $code
     * @param array $response_header
     *
     * @return response
     */
    public function create_json_response(
        array $json_data,
        int $code = response_code::OK,
        array $response_header = []
    ): response {
        $document = json_encode($json_data);
        if (empty($document)) {
            throw new coding_exception("Cannot encode the json document");
        }

        return new response(
            $document,
            $code,
            $response_header,
            'application/json; charset=utf-8'
        );
    }

    /**
     * Create a learning object randomly with faker data provider.
     *
     * @param string      $marketplace_component
     * @param string|null $name                     The custom name that we want to give it to the learning object.
     * @return model
     */
    public function create_learning_object(string $marketplace_component, ?string $name = null): model {
        if (!factory::is_valid_marketplace_component($marketplace_component)) {
            throw new coding_exception("Invalid marketplace type '{$marketplace_component}'");
        }

        $generator_class = "{$marketplace_component}\\testing\\generator";
        $interface_class = learning_object_generator::class;

        if (!is_a($generator_class, $interface_class, true)) {
            throw new coding_exception(
                "The generator class '{$generator_class}' does not implement '{$interface_class}'"
            );
        }

        /** @var learning_object_generator $learning_object_generator */
        $learning_object_generator = call_user_func([$generator_class, 'instance']);
        return $learning_object_generator->generate_learning_object($name);
    }
}