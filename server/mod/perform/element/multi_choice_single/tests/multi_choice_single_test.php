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

use core\collection;
use mod_perform\models\activity\element_plugin;
use performelement_multi_choice_single\option_required_error;
use performelement_multi_choice_single\multi_choice_single;

/**
 * @group perform
 * @group perform_element
 */
class performelement_multi_choice_single_testcase extends advanced_testcase {


    /**
     * @dataProvider validation_provider
     * @param collection $expected_errors
     * @param array|null $selected_options
     */
    public function test_validation(collection $expected_errors, ?array $selected_options): void {
        /** @var multi_choice_single $element_type */
        $element_type = element_plugin::load_by_plugin('multi_choice_single');

        $json = '{"options":[{"name":"option_0","value":"11"},{"name":"option_1","value":"12"},{"name":"option_2","value":"13"}]}';
        $element = $this->perform_generator()->create_element(['title' => 'element one', 'is_required' => true, 'data' => $json]);
        $errors = $element_type->validate_response(json_encode($selected_options), $element);

        self::assertEquals($expected_errors, $errors);
    }

    public function validation_provider(): array {
        return [
            'missing answer' => [
                new collection([new option_required_error()]),
                null,
            ],
        ];
    }

    /**
     * @dataProvider draft_validation_provider
     * @param collection $expected_errors
     * @param array|null $selected_options
     */
    public function test_draft_validation(collection $expected_errors, ?array $selected_options): void {
        /** @var multi_choice_single $element_type */
        $element_type = element_plugin::load_by_plugin('multi_choice_single');

        $json = '{"options":[{"name":"option_0","value":"11"},{"name":"option_1","value":"12"},{"name":"option_2","value":"13"}]}';
        $element = $this->perform_generator()->create_element(['title' => 'element one', 'is_required' => true, 'data' => $json]);
        $errors = $element_type->validate_response(json_encode($selected_options), $element, true);

        self::assertEquals($expected_errors, $errors);
    }

    public function draft_validation_provider(): array {
        return [
            'missing answer' => [
                new collection(),
                null,
            ],
        ];
    }

    /**
     * @return \mod_perform\testing\generator
     */
    protected function perform_generator() {
        return \mod_perform\testing\generator::instance();
    }
}