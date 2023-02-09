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


require_once(__DIR__ . '/section_element_manager_testcase.php');

use mod_perform\models\activity\section;
use mod_perform\testing\generator as mod_perform_generator;

/**
 * @group perform
 */
class mod_perform_section_element_manager_testcase extends section_element_manager_testcase {

    public function test_create_section_element_at_top_of_list() {
        $test_data = $this->generate_data();
        /** @var section $section*/
        $section = $test_data['section'];
        $new_element_title = 'Last section element';
        /** @var mod_perform_generator $data_generator*/
        $data_generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $element = $data_generator->create_element(
            [
                'plugin_name' => 'short_text',
                'title' => $new_element_title
            ]
        );
        $section->section_element_manager->add_element_after($element);
        $section_elements = $section->section_element_manager->get_section_elements()->all();
        $this->assertCount(4, $section_elements);
        $new_section_element = reset($section_elements);
        $this->assertEquals(1, $new_section_element->sort_order);
        $this->assertEquals($new_element_title, $new_section_element->element->title);
    }

    public function test_create_section_element_after_specified_section_element() {
        $test_data = $this->generate_data();
        /** @var section $section*/
        $section = $test_data['section'];
        $new_element_title = 'Last section element';
        /** @var mod_perform_generator $data_generator*/
        $data_generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $element = $data_generator->create_element(
            [
                'plugin_name' => 'short_text',
                'title' => $new_element_title
            ]
        );
        $section->get_section_element_manager()->add_element_after($element, $test_data['section_elements']['b']->id);
        $section_elements = $section->get_section_element_manager()->get_section_elements()->all();
        $this->assertCount(4, $section_elements);
        $new_section_element = $section_elements[2];
        $this->assertEquals(3, $new_section_element->sort_order);
        $this->assertEquals($new_element_title, $new_section_element->element->title);
    }

    public function test_create_section_element_after_section_element_that_does_not_exist() {
        $test_data = $this->generate_data();
        /** @var section $section*/
        $section = $test_data['section'];
        $new_element_title = 'Last section element';
        /** @var mod_perform_generator $data_generator*/
        $data_generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $element = $data_generator->create_element(
            [
                'plugin_name' => 'short_text',
                'title' => $new_element_title
            ]
        );
        $section->get_section_element_manager()->add_element_after($element, 3892);
        $section_elements = $section->get_section_element_manager()->get_section_elements()->all();
        $this->assertCount(4, $section_elements);
        $last_section_element = end($section_elements);
        $this->assertEquals(4, $last_section_element->sort_order);
        $this->assertEquals($new_element_title, $last_section_element->element->title);
    }

    public function test_create_section_element_at_bottom() {
        $test_data = $this->generate_data();
        /** @var section $section*/
        $section = $test_data['section'];
        $new_element_title = 'Last section element';
        /** @var mod_perform_generator $data_generator*/
        $data_generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $element = $data_generator->create_element(
            [
                'plugin_name' => 'short_text',
                'title' => $new_element_title
            ]
        );
        $section->get_section_element_manager()->add_element_after($element, $test_data['section_elements']['c']->id);
        $section_elements = $section->get_section_element_manager()->get_section_elements()->all();
        $this->assertCount(4, $section_elements);
        $last_section_element = end($section_elements);
        $this->assertEquals(4, $last_section_element->sort_order);
        $this->assertEquals($new_element_title, $last_section_element->element->title);
    }

    public function test_remove_section_element() {
        $test_data = $this->generate_data();
        /** @var section $section*/
        $section = $test_data['section'];
        $section->get_section_element_manager()->remove_section_elements([
            $test_data['section_elements']['b']
        ]);
        $section_elements = $section->get_section_element_manager()->get_section_elements()->all();
        $section_element_a = $test_data['section_elements']['a'];
        $section_element_c = $test_data['section_elements']['c'];
        $this->assertCount(2, $section_elements);
        $this->assertEquals($section_element_a->id, $section_elements[0]->id);
        $this->assertEquals(1, $section_elements[0]->sort_order);
        $this->assertEquals($section_element_c->id, $section_elements[1]->id);
        $this->assertEquals(2, $section_elements[1]->sort_order);
    }

    public function test_reorder_section_element() {
        $test_data = $this->generate_data();
        /** @var section $section*/
        $section = $test_data['section'];
        $section->get_section_element_manager()->reorder_section_element_to_after($test_data['section_elements']['a']->id, $test_data['section_elements']['c']->id);
        $section_elements = $section->get_section_element_manager()->get_section_elements()->all();
        $this->assertEquals(3, $section_elements[2]->sort_order);
        $this->assertEquals($test_data['section_elements']['a']->id, $section_elements[2]->id);
        $this->assertEquals(1, $section_elements[0]->sort_order);
        $this->assertEquals($test_data['section_elements']['b']->id, $section_elements[0]->id);
        $this->assertEquals(2, $section_elements[1]->sort_order);
        $this->assertEquals($test_data['section_elements']['c']->id, $section_elements[1]->id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot move a section element that does not belong to this section');
        $section->get_section_element_manager()->reorder_section_element_to_after(
            $test_data['section_elements']['a']->id,
            $test_data['section_elements_2']['a']->id
        );
    }
}
