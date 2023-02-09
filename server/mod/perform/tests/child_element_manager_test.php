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

require_once(__DIR__ . '/child_element_manager_testcase.php');

use mod_perform\models\activity\element;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\element_plugin;
use mod_perform\data_providers\activity\element_plugin as element_plugin_data_provider;

/**
 * @group perform
 */
class mod_perform_child_element_manager_testcase extends child_element_manager_testcase {

    public function test_create_child_element_at_top_of_list() {
        $test_data = $this->generate_data();
        $new_child_title = 'Child 047';
        $test_data['parent']->get_child_element_manager()->create_child_element(
            [
                'title' => $new_child_title,
            ],
            'short_text'
        );
        $parent = element::load_by_id($test_data['parent']->id);
        $children = $parent->get_children()->all();
        $this->assertCount(4, $children);
        $this->assertEquals($new_child_title, $children[0]->title);
        $this->assertEquals($test_data['children']['a']->title, $children[1]->title);
    }

    public function test_create_child_element_after_specified_child_element() {
        $test_data = $this->generate_data();
        $new_child_title = 'Child 1-A';
        $test_data['parent']->get_child_element_manager()->create_child_element(
            [
                'title' => $new_child_title,
            ],
            'short_text',
            $test_data['children']['b']->id
        );
        $parent = element::load_by_id($test_data['parent']->id);
        $children = $parent->get_children()->all();
        $this->assertCount(4, $children);
        $this->assertEquals($test_data['children']['b']->title, $children[1]->title);
        $this->assertEquals($new_child_title, $children[2]->title);
        $this->assertEquals(3, $children[2]->sort_order);
    }

    public function test_create_child_element_after_child_element_that_does_not_exist() {
        $test_data = $this->generate_data();
        $new_child_title = 'Child last';
        $test_data['parent']->get_child_element_manager()->create_child_element(
            [
                'title' => $new_child_title,
            ],
            'short_text',
            7898
        );
        $parent = element::load_by_id($test_data['parent']->id);
        $children = $parent->get_children()->all();
        $this->assertCount(4, $children);
        $this->assertEquals($test_data['children']['c']->title, $children[2]->title);
        $this->assertEquals($new_child_title, $children[3]->title);
        $this->assertEquals(4, $children[3]->sort_order);
    }

    public function test_create_child_element_at_bottom() {
        $test_data = $this->generate_data();
        $new_child_title = 'Child 1-A';
        $test_data['parent']->get_child_element_manager()->create_child_element(
            [
                'title' => $new_child_title,
            ],
            'short_text',
            $test_data['children']['c']->id
        );
        $parent = element::load_by_id($test_data['parent']->id);
        $children = $parent->get_children()->all();
        $this->assertCount(4, $children);
        $this->assertEquals($test_data['children']['c']->title, $children[2]->title);
        $this->assertEquals($new_child_title, $children[3]->title);
        $this->assertEquals(4, $children[3]->sort_order);
    }

    public function test_create_grand_child_element() {
        $test_data = $this->generate_data();
        $elements_supporting_child_elements = array_filter(
            (new element_plugin_data_provider())->fetch()->get(),
            function($element_plugin) {
                return $element_plugin->get_child_element_config()->supports_child_elements === true;
            }
        );
        /** @var element_plugin $element_supporting_child_elements*/
        $element_supporting_child_elements = reset($elements_supporting_child_elements);
        $new_child_title = 'Child 1-A';

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Can not create an element that supports child elements as well");
        $test_data['parent']->get_child_element_manager()->create_child_element(
            [
                'title' => $new_child_title,
            ],
            $element_supporting_child_elements->get_plugin_name()
        );
    }

    public function test_create_child_element_for_element_that_does_not_support_children() {
        self::setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator*/
        $perform_generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $activity = $perform_generator->create_activity_in_container();
        $element = new element_entity();

        $element->context_id = $activity->get_context_id();
        $element->plugin_name = 'short_text';
        $element->title = 'Parent element';
        $element->data = '';
        $element = $element->save();
        $parent_element_model = element::load_by_entity($element);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Element doesn't support child elements");
        $parent_element_model->get_child_element_manager()->create_child_element(
            ['title' => 'Child A'],
            'short_text'
        );
    }

    public function test_remove_child_element() {
        $test_data = $this->generate_data();
        /** @var element $parent*/
        $parent = $test_data['parent'];
        $parent->get_child_element_manager()->remove_child_element($test_data['children']['a']->id);
        $reloaded_parent = element::load_by_id($parent->id);
        $child_elements = $reloaded_parent->get_children()->all();
        $this->assertCount(2, $child_elements);
        $this->assertEquals($test_data['children']['b']->id, $child_elements[0]->id);
        $this->assertEquals(1, $child_elements[0]->sort_order);
        $this->assertEquals($test_data['children']['c']->id, $child_elements[1]->id);
        $this->assertEquals(2, $child_elements[1]->sort_order);
    }

    public function test_reorder_child_element() {
        $test_data = $this->generate_data();

        /** @var element $parent*/
        $parent = $test_data['parent'];
        $parent->get_child_element_manager()->reorder_child_element_to_after($test_data['children']['a']->id, $test_data['children']['c']->id);
        $children = $parent->get_children()->all();
        $this->assertEquals($children[0]->id, $test_data['children']['b']->id);
        $this->assertEquals($children[1]->id, $test_data['children']['c']->id);
        $this->assertEquals($children[2]->id, $test_data['children']['a']->id);
    }

    public function test_reorder_child_elements_from_different_sections() {
        $test_data_1 = $this->generate_data();
        $parent_1 = $test_data_1['parent'];
        $parent_1_element = $test_data_1['children']['a']->id;

        $test_data_2 = $this->generate_data();
        $parent_2_element = $test_data_2['children']['a']->id;

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Child elements to be reordered are not siblings");
        $parent_1
            ->get_child_element_manager()
            ->reorder_child_element_to_after($parent_1_element, $parent_2_element);
    }
}