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

use mod_perform\models\activity\section;
use mod_perform\testing\generator as mod_perform_generator;


abstract class section_element_manager_testcase extends advanced_testcase {

    public function generate_data(): array {
        self::setAdminUser();

        /** @var mod_perform_generator $perform_generator*/
        $perform_generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $activity = $perform_generator->create_activity_in_container();

        /** @var section $section*/
        $section = $activity->get_sections()->first();
        $element_A = $perform_generator->create_element(
            [
                'plugin_name' => 'short_text',
                'title' => 'Element A',
            ]
        );
        $section_element_A = $section->get_section_element_manager()->add_element_after($element_A);

        $element_B = $perform_generator->create_element(
            [
                'plugin_name' => 'short_text',
                'title' => 'Element B',
            ]
        );
        $section_element_B = $section->get_section_element_manager()->add_element_after($element_B, $element_A->id);

        $element_C = $perform_generator->create_element(
            [
                'plugin_name' => 'short_text',
                'title' => 'Element C',
            ]
        );
        $section_element_C = $section->get_section_element_manager()->add_element_after($element_C, $element_B->id);

        $section2 = $perform_generator->create_section($activity, ['title' => 'Section two']);

        $element_2_a = $perform_generator->create_element(
            [
                'plugin_name' => 'short_text',
                'title'       => 'Element 2 A',
            ]
        );

        $section_element_2_a = $section2->get_section_element_manager()->add_element_after($element_2_a);

        return [
            'section'            => $section,
            'section_elements'   => [
                'a' => $section_element_A,
                'b' => $section_element_B,
                'c' => $section_element_C,
            ],
            'section2'           => $section2,
            'section_elements_2' => [
                'a' => $section_element_2_a,
            ],

        ];
    }
}