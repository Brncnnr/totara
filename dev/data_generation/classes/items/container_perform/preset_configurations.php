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

use mod_perform\constants;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\settings\visibility_conditions\all_responses;
use mod_perform\state\activity\active;
use totara_core\entity\relationship;

class preset_configurations {

    private $configuration_cursor = 0;

    private $element_data_generator;

    private $configurations = [
        0 => [
            'information' => [
                'activity_name' => '1: My first configuration.',
                'activity_type' => 'check-in',
                'description' => "
                    About the configuration.
                    other information here.
                ",
                'anonymous_responses' => true,
                'activity_status' => 1,
            ],
            'settings' => [
                'general' => [
                    'visibility_condition' => all_responses::VALUE,
                    'manual_relationships' => [
                        constants::RELATIONSHIP_PEER => constants::RELATIONSHIP_MANAGER,
                    ],
                ],
                'content' => [
                    activity_setting::CLOSE_ON_COMPLETION => true,
                    'sections' => [
                        [
                            'count' => 10,
                            'relationships' => [
                                [
                                    'relationship' => constants::RELATIONSHIP_SUBJECT,
                                    'can_view' => true,
                                    'can_answer' => true,
                                ],
                                [
                                    'relationship' => constants::RELATIONSHIP_MANAGER,
                                    'can_view' => true,
                                    'can_answer' => true,
                                ],
                                [
                                    'relationship' => constants::RELATIONSHIP_APPRAISER,
                                    'can_view' => true,
                                    'can_answer' => true,
                                ],
                                [
                                    'relationship' => constants::RELATIONSHIP_MANAGERS_MANAGER,
                                    'can_view' => true,
                                    'can_answer' => false,
                                ],
                            ],
                            // a short_text element is created by default for each activity. you can include other elements if needed.
                            'other_elements' => [
                                [
                                    'plugin_name' => 'short_text',
                                    'count' => 5,
                                    'identifier' => '007',
                                    'required' => true,
                                ],
                                [
                                    'plugin_name' => 'long_text',
                                    'count' => 5,
                                    'identifier' => '007',
                                    'required' => true,
                                ],
                                [
                                    'plugin_name' => 'multi_choice_single',
                                    'count' => 5,
                                    'identifier' => '007',
                                    'number_of_options' => 5,
                                    'required' => true,
                                ],
                                [
                                    'plugin_name' => 'multi_choice_multi',
                                    'count' => 3,
                                    'identifier' => '007',
                                    'number_of_options' => 10,
                                    'required' => true,
                                ],
                                [
                                    'plugin_name' => 'custom_rating_scale',
                                    'count' => 5,
                                    'number_of_options' => 3,
                                    'identifier' => '007',
                                    'required' => true,
                                ],
                                [
                                    'plugin_name' => 'numeric_rating_scale',
                                    'count' => 5,
                                    'number_of_options' => 5,
                                    'identifier' => '007',
                                    'required' => true,
                                ],
                            ],
                        ],
                    ],
                ],
                'track' => [
                    'subject_instance_generation' => track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_JOB,
                ],
            ],
        ],
    ];

    private $configuration_count;

    private $core_relationships;

    public function __construct() {
        $this->set_configurations_count();
        $this->set_core_relationships();
        $this->set_element_data_generator();
    }

    private function set_configurations_count() {
        $this->configuration_count = count($this->configurations);
    }

    private function set_core_relationships() {
        $this->core_relationships = relationship::repository()->get();
    }

    private function set_element_data_generator() {
        $this->element_data_generator = new element_data_generator();
    }

    public function get_a_configuration() {
        return $this->get_next_configuration();
    }

    private function get_next_configuration() {
        $config = $this->configurations[$this->configuration_cursor];
        $this->move_cursor();

        return $this->return_config($config);
    }

    private function move_cursor() {
        $this->configuration_cursor++;
        if ($this->configuration_cursor === $this->configuration_count) {
            $this->configuration_cursor = 0;
        }
    }

    private function return_config($config) {
        $relationships_by_id = [];

        foreach ($this->core_relationships as $core_relationship) {
            $relationships_by_id[$core_relationship->idnumber] = $core_relationship;
        }
        $config['options']['core_relationships'] = $relationships_by_id;
        $config['options']['element_data_generator'] = $this->element_data_generator;
        $config['information']['activity_status'] = $config['information']['activity_status'] ?? active::get_code();

        return $config;
    }
}