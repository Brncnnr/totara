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

use core\collection;
use mod_perform\models\activity\element_plugin;
use performelement_short_text\answer_length_exceeded_error;
use performelement_short_text\answer_required_error;
use performelement_short_text\short_text;

/**
 * @group perform
 * @group perform_element
 */
class mod_perform_element_short_text_testcase extends advanced_testcase {

    public function test_validate_response_invalid_format(): void {
        /** @var short_text $short_text */
        $short_text = element_plugin::load_by_plugin('short_text');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid response data format, expected a string');

        $element = $this->perform_generator()->create_element(['title' => 'element one', 'is_required' => true]);
        $short_text->validate_response(json_encode(['key']), $element);
    }

    /**
     * @dataProvider validation_provider
     * @param collection $expected_errors
     * @param string $answer_text
     * @throws coding_exception
     */
    public function test_validation(collection $expected_errors, string $answer_text): void {
        /** @var short_text $short_text */
        $short_text = element_plugin::load_by_plugin('short_text');

        $element = $this->perform_generator()->create_element(['title' => 'element one', 'is_required' => true]);
        $errors = $short_text->validate_response(json_encode($answer_text), $element);

        self::assertEquals($expected_errors, $errors);
    }

    public function validation_provider(): array {
        return [
            'no errors' => [
                new collection(), 'A short answer'
            ],
            'missing answer' => [
                new collection([new answer_required_error()]), '',
            ],
            'missing answer (whitespace only)' => [
                new collection([new answer_required_error()]), '             ',
            ],
            'answer too long' => [
                new collection([new answer_length_exceeded_error()]), random_string(short_text::MAX_ANSWER_LENGTH + 1)
            ],
        ];
    }

    /**
     * @dataProvider draft_validation_provider
     * @param collection $expected_errors
     * @param string $answer_text
     * @throws coding_exception
     */
    public function test_draft_validation(collection $expected_errors, string $answer_text): void {
        /** @var short_text $short_text */
        $short_text = element_plugin::load_by_plugin('short_text');

        $element = $this->perform_generator()->create_element(['title' => 'element one', 'is_required' => true]);
        $errors = $short_text->validate_response(json_encode($answer_text), $element, true);

        self::assertEquals($expected_errors, $errors);
    }

    public function draft_validation_provider(): array {
        return [
            'no errors' => [
                new collection(), 'A short answer'
            ],
            'missing answer' => [
                new collection(), '',
            ],
            'missing answer (whitespace only)' => [
                new collection(), '             ',
            ],
            'answer too long' => [
                new collection([new answer_length_exceeded_error()]), random_string(short_text::MAX_ANSWER_LENGTH + 1)
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