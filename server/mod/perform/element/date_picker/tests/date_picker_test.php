<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package performelement_date_picker
 */

use core\collection;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\models\activity\element as element_model;
use mod_perform\models\activity\element_plugin;
use performelement_date_picker\answer_required_error;
use performelement_date_picker\date_iso_required_error;
use performelement_date_picker\date_picker;
use performelement_date_picker\invalid_date_error;
use performelement_date_picker\year_outside_range;

/**
 * @group perform
 * @group perform_element
 */
class performelement_date_picker_testcase extends advanced_testcase {

    /**
     * @param int|null $year_range_start
     * @param int|null $year_range_end
     * @param string|null $expected_message
     * @dataProvider process_data_validate_years_config_provider
     */
    public function test_process_data_validate_years_config(
        ?int $year_range_start,
        ?int $year_range_end,
        ?string $expected_message
    ): void {

        $element = new element_entity();
        $element->plugin_name = 'date_picker';
        $element->data = json_encode([
            'yearRangeStart' => $year_range_start,
            'yearRangeEnd' => $year_range_end,
        ], JSON_THROW_ON_ERROR);

        if ($expected_message !== null) {
            $this->expectException(coding_exception::class);
            $this->expectExceptionMessage($expected_message);
        }
        element_model::validate($element);
    }

    public function process_data_validate_years_config_provider(): array {
        return [
            'Empty values (use front end defined defaults)' => [
                null, null, 'Year range cannot be empty',
            ],
            'Filled valid values' => [
                1000, 2071, null,
            ],
            'Start too early fails' => [
                999, 2021, 'Year range start must be 1000 or more',
            ],
            'End too late fails' => [
                1900, (int) (new DateTime)->modify('+51 years')->format('Y'), ' or less',
            ],
            'Backwards range fails' => [
                2021, 2020, 'Year range start must less than or equal to year range end',
            ],
        ];
    }

    public function test_format_response_lines(): void {
        $date_picker = date_picker::load_by_plugin('date_picker');
        $response = ['iso' => '2020-12-04'];

        $element_data = [];

        $lines = $date_picker->format_response_lines(json_encode($response), json_encode($element_data));
        self::assertCount(1, $lines);
        self::assertEquals('4 December 2020', $lines[0]);

        $lines = $date_picker->format_response_lines(json_encode(null), json_encode($element_data));
        self::assertCount(0, $lines);
    }

    /**
     * @dataProvider validation_provider
     * @param collection $expected_errors
     * @param array|null $answer
     */
    public function test_validation(collection $expected_errors, ?array $answer): void {
        /** @var date_picker $element_type */
        $element_type = element_plugin::load_by_plugin('date_picker');

        $json = '{}';
        $element = $this->perform_generator()->create_element(['title' => 'element one', 'is_required' => true, 'data' => $json]);
        $errors = $element_type->validate_response(json_encode($answer), $element);

        self::assertEquals($expected_errors, $errors);
    }

    public function validation_provider(): array {
        return [
            'valid' => [
                new collection(),
                ['iso' => '1903-03-03'],
            ],
            'missing answer' => [
                new collection([new answer_required_error()]),
                null,
            ],
            'missing iso' => [
                new collection([new date_iso_required_error()]),
                ['i' => '1903-03-03']
            ],
            'invalid date' => [
                new collection([new invalid_date_error()]),
                ['iso' => 'not-a-date']
            ]
        ];
    }

    /**
     * @dataProvider draft_validation_provider
     * @param collection $expected_errors
     * @param array|null $answer
     */
    public function test_draft_validation(collection $expected_errors, ?array $answer): void {
        /** @var date_picker $element_type */
        $element_type = element_plugin::load_by_plugin('date_picker');

        $json = '{}';
        $element = $this->perform_generator()->create_element(['title' => 'element one', 'is_required' => true, 'data' => $json]);
        $errors = $element_type->validate_response(json_encode($answer), $element, true);

        self::assertEquals($expected_errors, $errors);
    }

    public function draft_validation_provider(): array {
        return [
            'valid' => [
                new collection(),
                ['iso' => '1903-03-03'],
            ],
            'missing answer' => [
                new collection(),
                null,
            ],
            'missing iso' => [
                new collection([new date_iso_required_error()]),
                ['i' => '1903-03-03']
            ],
            'invalid date' => [
                new collection([new invalid_date_error()]),
                ['iso' => 'not-a-date']
            ]
        ];
    }

    /**
     * @dataProvider year_selection_validation_provider
     * @param collection $expected_errors
     * @param array $element_data
     * @param array|null $answer
     */
    public function test_year_selection_validation(collection $expected_errors, array $element_data, array $answer): void {
        $encoded_element_data = json_encode($element_data, JSON_THROW_ON_ERROR);

        $encoded_answer = json_encode($answer, JSON_THROW_ON_ERROR);
        $element = $this->perform_generator()->create_element(
            ['title' => 'element one', 'is_required' => true, 'data' => $encoded_element_data]
        );

        $errors = (new date_picker())->validate_response($encoded_answer, $element);

        self::assertEquals($expected_errors, $errors);
    }

    public function year_selection_validation_provider(): array {
        $current_year = (int) (new DateTime())->format('Y');
        $too_early = (new date_picker)->get_default_min_year() - 1;
        $too_late = (new date_picker)->get_default_max_year() + 1;

        $default_acceptable_range_error = new year_outside_range($current_year - 50, $current_year + 50);

        return [
            'Selected year is before default range start' => [
                new collection([$default_acceptable_range_error]),
                [],
                ['iso' => "{$too_early}-12-05"],
            ],
            'Selected year is after default range start' => [
                new collection([$default_acceptable_range_error]),
                [],
                ['iso' => "{$too_late}-12-05"],
            ],
            'Selected year is within default range' => [
                new collection([]),
                [],
                ['iso' => "{$current_year}-12-05"],
            ],
            'Selected year is before configured range start' => [
                new collection([new year_outside_range(2000, 2000)]),
                [
                    'yearRangeStart' => 2000,
                    'yearRangeEnd' => 2000,
                ],
                ['iso' => '1991-12-04'],
            ],
            'Selected year is after configured range start' => [
                new collection([new year_outside_range(2000, 2010)]),
                [
                    'yearRangeStart' => 2000,
                    'yearRangeEnd' => 2010,
                ],
                ['iso' => '2011-12-04'],
            ],
            'Selected year is within configured range (specific offsets)' => [
                new collection([]),
                [
                    'yearRangeStart' => 2000,
                    'yearRangeEnd' => 2001,
                ],
                ['iso' => '2001-12-04'],
            ],
            'Selected year is within configured range (default offsets)' => [
                new collection([]),
                [],
                ['iso' => '2050-12-04'],
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