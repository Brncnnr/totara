<?php
/*
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\models\activity\section_element;
use mod_perform\state\activity\draft;

/**
 * @group perform
 */
class mod_perform_section_element_model_testcase extends advanced_testcase {

    public function test_create() {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $activity = $perform_generator->create_activity_in_container();

        $section = $perform_generator->create_section($activity);
        $element = $perform_generator->create_element();
        $sort_order = 123;

        $section_element = section_element::create($section, $element, $sort_order);
        $id = $section_element->id;

        // Reload, just to make sure that we're getting it out of the DB.
        $actual_section_element = section_element::load_by_id($id);

        $this->assertEquals($section->id, $actual_section_element->section_id);
        $this->assertEquals($element->id, $actual_section_element->element_id);
    }

    public function test_move_element_to_another_section() {
        self::setAdminUser();
        $perform_generator = \mod_perform\testing\generator::instance();
        $activity = $perform_generator->create_activity_in_container(
            ['activity_name' => 'Activity 1', 'activity_status' => draft::get_code(), 'create_section' => false]
        );
        $activity_section1 = $perform_generator->create_section($activity, ['title' => 'Activity 1 section 1']);
        $activity_section2 = $perform_generator->create_section($activity, ['title' => 'Activity 1 section 2']);
        $element_one = $perform_generator->create_element(['title' => 'Question one']);
        $element_two = $perform_generator->create_element(['title' => 'Question two']);

        $section_element_one = $perform_generator->create_section_element($activity_section1, $element_one);
        $section_element_two = $perform_generator->create_section_element($activity_section1, $element_two);

        $this->assertEquals(2, $section_element_two->sort_order);
        $section_element_two->move_to_section($activity_section2);
        $this->assertEquals(1, $section_element_two->sort_order);
    }

    public function test_get_element_data() {
        self::setAdminUser();
        $perform_generator = $this->getDataGenerator()->get_plugin_generator('mod_perform');
        $activity = $perform_generator->create_activity_in_container(
            ['activity_status' => draft::get_code(), 'create_section' => false]
        );
        $activity_section = $perform_generator->create_section($activity);

        $data_key = 'contentType';
        $data_value = 'totara_competency';
        $data = $json = json_encode([$data_key => $data_value]);

        $element = $perform_generator->create_element(['data' => $data]);
        $section_element = $perform_generator->create_section_element($activity_section, $element);

        $element_data = $section_element->get_element_data();
        $this->assertEquals($data_value, $element_data[$data_key]);
    }
}