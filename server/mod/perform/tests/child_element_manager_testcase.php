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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use mod_perform\data_providers\activity\element_plugin as element_plugin_data_provider;
use mod_perform\entity\activity\element;
use mod_perform\models\activity\element as element_model;
use mod_perform\models\activity\element_plugin;

abstract class child_element_manager_testcase extends advanced_testcase {

    public function generate_data(): array {
        self::setAdminUser();

        /** @var mod_perform\testing\generator $perform_generator*/
        $perform_generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $activity = $perform_generator->create_activity_in_container();
        $elements_supporting_child_elements = array_filter(
            (new element_plugin_data_provider())->fetch()->get(),
            function ($element_plugin) {
                return $element_plugin->get_child_element_config()->supports_child_elements === true;
            }
        );
        /** @var element_plugin $parent_element*/
        $parent_element = reset($elements_supporting_child_elements);

        if ($parent_element === null) {
            $this->markTestSkipped("No element supporting child elements to test with.");
        }

        $element = new element();
        $element->context_id = $activity->get_context_id();
        $element->plugin_name = $parent_element->get_plugin_name();
        $element->title = 'Parent element';
        $element->data = json_encode([
            'content_type' => 'totara_competency',
            'content_type_settings' => [
                'enable_rating' => false,
            ],
        ]);
        $element = $element->save();
        $parent_element_model = element_model::load_by_entity($element);

        $child_element_a = $parent_element_model->get_child_element_manager()->create_child_element(
            [
                'title' => 'Child A',
            ],
            'short_text'
        );
        $child_element_c = $parent_element_model->get_child_element_manager()->create_child_element(
            [
                'title' => 'Child C',
            ],
            'short_text',
            $child_element_a->id
        );
        $child_element_b = $parent_element_model->get_child_element_manager()->create_child_element(
            [
                'title' => 'Child B',
            ],
            'short_text',
            $child_element_a->id
        );

        return [
            'parent' => $parent_element_model,
            'children' => [
                'a' => $child_element_a,
                'b' => $child_element_b,
                'c' => $child_element_c,
            ]
        ];
    }
}