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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package performelement_aggregation
 */

use mod_perform\testing\generator as perform_generator;
use mod_perform\entity\activity\element as element_entity;
use performelement_aggregation\aggregation;
use performelement_aggregation\calculations\average;
use performelement_numeric_rating_scale\numeric_rating_scale;
use performelement_short_text\short_text;

require_once(__DIR__ . '/../../../tests/section_element_reference_test.php');

/**
 * @group perform
 * @group perform_element
 */
class performelement_aggregation_aggregation_testcase extends section_element_reference_testcase {

    public function test_validation_success(): void {
        $this->create_test_data();

        $aggregation = new aggregation();

        /** @var element_entity $referencing_element */
        $referencing_element = element_entity::repository()->find($this->referencing_aggregation_element->id);

        $referencing_element->data = $aggregation->process_data($referencing_element);
        $aggregation->validate_element($referencing_element);
    }

    /**
     * @dataProvider validation_errors_provider
     * @param array|closure $data
     * @param string $expected_message
     */
    public function test_validation_errors($data, string $expected_message): void {
        $this->create_test_data();

        if ($data instanceof Closure) {
            $data = Closure::fromCallable($data)->call($this);
        }

        $aggregation = new aggregation();

        /** @var element_entity $referencing_element */
        $referencing_element = element_entity::repository()->find($this->referencing_aggregation_element->id);
        $referencing_element->data = json_encode($data, JSON_THROW_ON_ERROR);
        $referencing_element->save();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage($expected_message);

        $aggregation->validate_element($referencing_element);
    }

    public function validation_errors_provider(): array {
        $source_section_elements_must_be_set_message = aggregation::SOURCE_SECTION_ELEMENT_IDS .' must be an array specified in the element data field';
        $excluded_values_must_be_set_message = aggregation::EXCLUDED_VALUES .' must be an array specified in the element data field';
        $calculations_must_be_set_message = aggregation::CALCULATIONS .' must be an array specified in the element data field';

        return [
            'Empty source section element ids' => [
                [
                    aggregation::CALCULATIONS => [average::get_name()],
                    aggregation::EXCLUDED_VALUES => [],
                ], $source_section_elements_must_be_set_message,
            ],
            'Null source section element ids' => [
                [
                    aggregation::CALCULATIONS => [average::get_name()],
                    aggregation::EXCLUDED_VALUES => [],
                    aggregation::SOURCE_SECTION_ELEMENT_IDS => null
                ],
                $source_section_elements_must_be_set_message,
            ],
            "Source section element id doesn't exist" => [
                [
                    aggregation::CALCULATIONS => [average::get_name()],
                    aggregation::EXCLUDED_VALUES => [],
                    aggregation::SOURCE_SECTION_ELEMENT_IDS => [-1]]
                ,
                'Not all supplied source section elements exist',
            ],
            "Source section element id doesn't belong to the same activity" => [
                function(): array {
                    $generator = perform_generator::instance();
                    $activity = $generator->create_activity_in_container();
                    $section = $generator->create_section($activity);
                    $element = $generator->create_element(['plugin_name' => numeric_rating_scale::get_plugin_name()]);
                    $section_element_in_another_activity = $generator->create_section_element($section, $element);

                    return [
                        aggregation::CALCULATIONS => [average::get_name()],
                        aggregation::EXCLUDED_VALUES => [],
                        aggregation::SOURCE_SECTION_ELEMENT_IDS => [$section_element_in_another_activity->id],
                    ];
                },
                'Source section elements must be from the same activity as a referencing aggregation element',
            ],
            "Source section element id isn't aggregatable" => [
                function(): array {
                    // Make the source element non-aggregatable.
                    element_entity::repository()
                        ->where('id', $this->source_element->id)
                        ->update(['plugin_name' => short_text::get_plugin_name()]);

                    return [
                        aggregation::CALCULATIONS => [average::get_name()],
                        aggregation::EXCLUDED_VALUES => [],
                        aggregation::SOURCE_SECTION_ELEMENT_IDS => [$this->referencing_redisplay_section_element->id]
                    ];
                },
                'The supplied source section elements are not all aggregatable',
            ],
            'Null excluded values' => [
                [aggregation::EXCLUDED_VALUES => null],
                $excluded_values_must_be_set_message,
            ],
            'Non numeric excluded values' => [
                [aggregation::EXCLUDED_VALUES => ['abc']], aggregation::EXCLUDED_VALUES . ' must be numeric.',
            ],
            'Null calculations' => [
                [
                    aggregation::CALCULATIONS => null,
                    aggregation::EXCLUDED_VALUES => []
                ],
                $calculations_must_be_set_message,
            ],
            'Empty array calculations' => [
                [aggregation::CALCULATIONS => [], aggregation::EXCLUDED_VALUES => []], aggregation::CALCULATIONS . ' must have at least one value',
            ],
        ];
    }

}
