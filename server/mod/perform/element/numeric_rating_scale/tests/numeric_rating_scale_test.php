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
use performelement_numeric_rating_scale\numeric_rating_scale;
use performelement_numeric_rating_scale\answer_required_error;
use performelement_numeric_rating_scale\answer_invalid_error;

/**
 * @group perform
 * @group perform_element
 */
class performelement_numeric_rating_scale_testcase extends advanced_testcase {

    /**
     * @dataProvider validation_provider
     * @param collection $expected_errors
     * @param array|null $selected_options
     */
    public function test_validation(collection $expected_errors, string $answer_text): void {
        /** @var numeric_rating_scale $element_type */
        $element_type = element_plugin::load_by_plugin('numeric_rating_scale');

        $json = '{"defaultValue":"3","highValue":"5","lowValue":"1"}';
        $element = $this->perform_generator()->create_element(['title' => 'element one', 'is_required' => true, 'data' => $json]);
        $errors = $element_type->validate_response(json_encode($answer_text), $element);

        self::assertEquals($expected_errors, $errors);
    }

    public function validation_provider(): array {
        return [
            'valid' => [
                new collection(),
                '4',
            ],
            'missing answer' => [
                new collection([new answer_required_error()]),
                '',
            ],
            'invalid answer' => [
                new collection([new answer_invalid_error()]),
                '10',
            ],
        ];
    }

    /**
     * @dataProvider draft_validation_provider
     * @param collection $expected_errors
     * @param array|null $selected_options
     */
    public function test_draft_validation(collection $expected_errors, string $answer_text): void {
        /** @var numeric_rating_scale $element_type */
        $element_type = element_plugin::load_by_plugin('numeric_rating_scale');

        $json = '{"defaultValue":"3","highValue":"5","lowValue":"1"}';
        $element = $this->perform_generator()->create_element(['title' => 'element one', 'is_required' => true, 'data' => $json]);
        $errors = $element_type->validate_response(json_encode($answer_text), $element, true);

        self::assertEquals($expected_errors, $errors);
    }

    public function draft_validation_provider(): array {
        return [
            'valid' => [
                new collection(),
                '4',
            ],
            'missing answer' => [
                new collection(),
                '',
            ],
            'invalid answer' => [
                new collection([new answer_invalid_error()]),
                '10',
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