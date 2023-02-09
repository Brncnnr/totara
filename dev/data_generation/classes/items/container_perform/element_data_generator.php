<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace degeneration\items\container_perform;

use coding_exception;
use degeneration\App;
use mod_perform\entity\activity\element_identifier as element_identifier_entity;

class element_data_generator {

    private $faker;

    private $element_identifiers = [];

    public function __construct() {
        $this->faker = App::faker();
        $this->set_element_identifiers();
    }

    /**
     * @param array $element_identifiers
     */
    public function set_element_identifiers(): void {
        $data = element_identifier_entity::repository()->get();
        foreach ($data as $identifier_entity) {
            $this->element_identifiers[$identifier_entity->identifier] = $identifier_entity;
        }
    }



    public function generate_data(array $element_config): array {
        return [
            'title' => $this->faker->catchPhrase,
            'plugin_name' => $element_config['plugin_name'],
            'data' => $this->get_data($element_config),
            'required' => $element_config['required'] ?? false,
            'identifier' => empty($element_config['identifier'])
                ? null
                : $this->get_identifier_id($element_config['identifier']),
        ];
    }

    private function get_identifier_id($identifier) {
        if (!empty($this->element_identifiers[$identifier])) {
            return $this->element_identifiers[$identifier]->id;
        }

        $entity = new element_identifier_entity();
        $entity->identifier = $identifier;
        $entity->save();
        $this->element_identifiers[$identifier] = $entity;

        return $this->element_identifiers[$identifier]->id;
    }

    private function get_data($element_config) {
        switch ($element_config['plugin_name']) {
            case 'static_content':
                throw new coding_exception('Static content isn\'t supported yet');
            case 'date_picker':
                return '{}';
            case 'numeric_rating_scale':
                return $this->get_numeric_rating_data();
            case 'custom_rating_scale':
                return $this->get_custom_rating_data($element_config['number_of_options']);
            case 'multi_choice_single':
            case 'multi_choice_multi':
                return $this->multi_choice_options($element_config['number_of_options']);
            case 'short_text':
            case 'long_text':
            default:
                return null;
        }
    }

    private function multi_choice_options(int $count = 3): string {
        $data = [
            'options' => [],
            'settings' => [
                [
                    'name' => 'min',
                    'value' => 2,
                ],
                [
                    'name' => 'max',
                    'value' => 7,
                ],
            ]
        ];

        for ($i = 0; $i < $count; $i++) {
            $data['options'][] = [
                'name' => "option_$i",
                'value' => $this->faker->catchPhrase,
            ];
        }

        return json_encode($data);
    }

    private function get_numeric_rating_data() {
        $data = [
            'lowValue' => 0,
            'highValue' => rand(1, 50),
        ];
        $data['defaultValue'] = rand(0, $data['highValue']);

        return json_encode($data);
    }

    private function get_custom_rating_data(int $count = 3) {
        $data = [
            'options' => [],
        ];

        for ($i = 0; $i < $count; $i++) {
            $data['options'][] = [
                'name' => "option_$i",
                'value' => [
                    'score' => rand(0, 12345),
                    'text' => $this->faker->catchPhrase,
                ]
            ];
        }

        return json_encode($data);
    }
}