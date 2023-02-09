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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\entity\activity\element_response;
use mod_perform\entity\activity\participant_instance;
use mod_perform\entity\activity\section_element;

require_once(__DIR__ . '/../db/upgradelib.php');

/**
 * @group perform
 */
class upgrade_unwrap_response_data_testcase extends advanced_testcase {

    /**
     * Test simple unwrapping cases.
     *
     * @dataProvider simple_wrapping_field_provider
     * @param string $wrapping_field
     */
    public function test_simple_unwrapping_cases(string $wrapping_field): void {
        $element_response = $this->save_response_data([$wrapping_field => 'some text']);

        mod_perform_upgrade_unwrap_response_data();

        $element_response->refresh();
        self::assertEquals('"some text"', $element_response->response_data);
    }

    public function test_dates(): void {
        $element_response = $this->save_response_data(['date' => ['iso' => '2019-10-10']]);

        mod_perform_upgrade_unwrap_response_data();

        $element_response->refresh();
        self::assertEquals(json_encode(['iso' => '2019-10-10']), $element_response->response_data);
    }

    public function test_already_unwrapped_strings_are_untouched(): void {
        $element_response = $this->save_response_data('some text');

        mod_perform_upgrade_unwrap_response_data();

        $element_response->refresh();
        self::assertEquals('"some text"', $element_response->response_data);
    }

    public function test_wrapped_nulls_are_untouched(): void {
        $element_response = $this->save_response_data(null);

        mod_perform_upgrade_unwrap_response_data();

        $element_response->refresh();
        self::assertEquals('null', $element_response->response_data);
    }

    public function test_unwrapped_nulls_are_untouched(): void {
        $element_response = $this->save_response_data(null, false);

        mod_perform_upgrade_unwrap_response_data();

        $element_response->refresh();
        self::assertEquals(null, $element_response->response_data);
    }

    public function simple_wrapping_field_provider(): array {
        $wrapping_fields = ['answer_text', 'answer_option', 'answer_value'];

        $cases = [];

        foreach ($wrapping_fields as $field) {
            $cases[$field] = [$field];
        }

        return $cases;
    }

    private function save_response_data($response_data, bool $encode = true): element_response {
        $this->setAdminUser();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = \mod_perform\testing\generator::instance();

        $config = \mod_perform\testing\activity_generator_configuration::new()->set_number_of_elements_per_section(1);
        $perform_generator->create_full_activities($config);

        $participant_instance_id = participant_instance::repository()->order_by('id')->first()->id;
        $section_element_id = section_element::repository()->order_by('id')->first()->id;

        $element_response = new element_response();
        $element_response->participant_instance_id = $participant_instance_id;
        $element_response->section_element_id = $section_element_id;
        $element_response->response_data = $encode ? json_encode($response_data) : $response_data;
        $element_response->save();

        $this->setUser();

        return $element_response;
    }

}
