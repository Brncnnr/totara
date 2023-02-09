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
* @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
* @package mod_perform
*/
/**
 * @group perform
 */

use  mod_perform\data_providers\activity\reportable_element_identifiers;

class mod_perform_data_provider_reportable_element_identifier_testcase extends advanced_testcase {

    public function test_fetch() {
        $this->setAdminUser();

        $data = $this->create_test_data();

        $data_provider = new reportable_element_identifiers();
        $identifiers = $data_provider->fetch()->get();

        $this->assertCount(2, $identifiers);
        $this->assertEqualsCanonicalizing(
            [$data->identifier1->identifier, $data->identifier2->identifier],
            [$identifiers->first()->identifier, $identifiers->last()->identifier]
        );

        //check non assignable identifiers not listed
        $this->assertNotContainsEquals($data->identifier3, [$identifiers->first(), $identifiers->last()]);
    }

    private function create_test_data(): stdClass {
        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $data = new stdClass();
        $data->identifier1 = $perform_generator->create_element_identifier('test_identifier_1');
        $data->identifier2 = $perform_generator->create_element_identifier('test_identifier_2');
        $data->identifier3 = $perform_generator->create_element_identifier('test_identifier_3');

        $data->activity1 = $perform_generator->create_full_activities()->first();
        $section = $data->activity1->sections->first();

        $element1 = $perform_generator->create_element(['identifier'=>'test_identifier_1' ]);
        $element2 = $perform_generator->create_element(['identifier'=>'test_identifier_2' ]);
        $data->section_element = $perform_generator->create_section_element($section, $element1);
        $data->section_element = $perform_generator->create_section_element($section, $element2);

        return $data;
    }
}