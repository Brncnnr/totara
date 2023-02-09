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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\entity\activity\section as section_entity;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element;
use mod_perform\testing\generator;
use performelement_aggregation\aggregation;
use performelement_aggregation\calculations\average;
use performelement_aggregation\data_provider\aggregation_data;
use performelement_numeric_rating_scale\numeric_rating_scale;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group perform
 * @group perform_element
 */
class mod_perform_webapi_resolver_mutation_create_and_update_aggregation_section_elements_testcase extends advanced_testcase {
    private const CREATE_MUTATION = 'mod_perform_create_element_in_section';
    private const UPDATE_MUTATION = 'mod_perform_update_element_in_section';

    use webapi_phpunit_helper;

    public function test_create_and_update_aggregation_section_elements(): void {
        aggregation_data::$aggregatable_section_cache = [];
        self::setAdminUser();

        $perform_generator = generator::instance();

        $activity = $perform_generator->create_activity_in_container();
        $section = $perform_generator->create_section($activity);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'element' => [
                    'plugin_name' => numeric_rating_scale::get_plugin_name(),
                    'element_details' => [
                        'title' => 'Original source numeric rating scale',
                        'identifier' => 'num-rating-scale',
                        'data' => '{}',
                        'is_required' => true,
                    ]
                ],
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::CREATE_MUTATION, $args);
        [$source_section_element] = $this->assert_correct_elements_returned($result, 1);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'element' => [
                    'plugin_name' => numeric_rating_scale::get_plugin_name(),
                    'element_details' => [
                        'title' => 'Other numeric rating scale',
                        'identifier' => 'num-rating-scale2',
                        'data' => '{}',
                        'is_required' => true,
                    ],
                ],
                'after_section_element_id' => $source_section_element->get_id(),
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::CREATE_MUTATION, $args);
        [$source_section_element, $other_source_section_element] = $this->assert_correct_elements_returned($result, 2);

        $args = [
            'input' => [
                'section_id' => $section->id,
                'element' => [
                    'plugin_name' => aggregation::get_plugin_name(),
                    'element_details' => [
                        'title' => 'Aggregation element',
                        'identifier' => 'agg-element',
                        'data' => json_encode([
                            aggregation::SOURCE_SECTION_ELEMENT_IDS => [$source_section_element->id],
                            aggregation::EXCLUDED_VALUES => [],
                            aggregation::CALCULATIONS => [average::get_name()],
                        ], JSON_THROW_ON_ERROR),
                        'is_required' => false,
                    ],
                ],
                'after_section_element_id' => $other_source_section_element->get_id(),
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::CREATE_MUTATION, $args);

        [
            $source_section_element,
            $other_rating_scale_section_element,
            $aggregation_section_element
        ] = $this->assert_correct_elements_returned($result, 3);

        $source_section_entity = new section_entity($source_section_element->section_id);

        $all_data = json_decode($aggregation_section_element->get_element()->get_data(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $all_data[aggregation_data::AGGREGATABLE_SECTIONS]);
        self::assertEquals($all_data[aggregation_data::AGGREGATABLE_SECTIONS][0]['id'], $source_section_entity->id);
        unset($all_data[aggregation_data::AGGREGATABLE_SECTIONS]);

        self::assertEquals(
            [
                aggregation::SOURCE_SECTION_ELEMENT_IDS => [$source_section_element->id],
                aggregation::EXCLUDED_VALUES => [],
                aggregation::CALCULATIONS => [average::get_name()],
            ],
            $all_data
        );

        $args = [
            'input' => [
                'section_element_id' => $aggregation_section_element->get_id(),
                'element_details' => [
                    'title' => 'Aggregation element',
                    'identifier' => 'agg-element',
                    'data' => json_encode([
                        aggregation::SOURCE_SECTION_ELEMENT_IDS => [
                            $other_rating_scale_section_element->id, // <-- Specifically placed first.
                            $source_section_element->id,
                        ],
                        aggregation::EXCLUDED_VALUES => [],
                        aggregation::CALCULATIONS => [average::get_name()],
                    ], JSON_THROW_ON_ERROR),
                    'is_required' => false,
                ],
            ],
        ];

        $result = $this->resolve_graphql_mutation(self::UPDATE_MUTATION, $args);

        [
            $source_section_element,
            $other_rating_scale_section_element,
            $aggregation_section_element
        ] = $this->assert_correct_elements_returned($result, 3);

        $all_data = json_decode($aggregation_section_element->get_element()->get_data(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $all_data[aggregation_data::AGGREGATABLE_SECTIONS]);
        self::assertEquals($all_data[aggregation_data::AGGREGATABLE_SECTIONS][0]['id'], $source_section_entity->id);
        unset($all_data[aggregation_data::AGGREGATABLE_SECTIONS]);

        self::assertEquals(
            [
                aggregation::SOURCE_SECTION_ELEMENT_IDS => [
                    $other_rating_scale_section_element->id, // <-- Still specifically placed first.
                    $source_section_element->id,
                ],
                aggregation::EXCLUDED_VALUES => [],
                aggregation::CALCULATIONS => [average::get_name()],
            ],
            $all_data
        );

        aggregation_data::$aggregatable_section_cache = [];
    }

    /**
     * @param array $result
     * @param int $element_count
     * @return section_element[]
     */
    private function assert_correct_elements_returned(array $result, int $element_count): array {
        /** @var section $section */
        $section = $result['section'];

        /** @var section_element[] $section_elements */
        $section_elements = $section->get_section_elements()->all(false);

        $source_section_element = $section_elements[0] ?? null;
        $other_source_section_element = $section_elements[1] ?? null;
        $aggregation_section_element = $section_elements[2] ?? null;

        self::assertCount($element_count, $section_elements);

        if ($element_count >= 1) {
            self::assertNotNull($source_section_element);
            self::assertEquals('Original source numeric rating scale', $source_section_element->get_element()->title);
        }

        if ($element_count >= 2) {
            self::assertNotNull($other_source_section_element);
            self::assertEquals('Other numeric rating scale', $other_source_section_element->get_element()->title);
        }

        if ($element_count >= 3) {
            self::assertNotNull($aggregation_section_element);
            self::assertEquals('Aggregation element', $aggregation_section_element->get_element()->title);

            // The section element ids are never saved in the json data field, this is to prevent them falling out of sync when cloning.
            self::assertEquals(
                [
                    aggregation::EXCLUDED_VALUES => [],
                    aggregation::CALCULATIONS => [average::get_name()],
                ],
                json_decode($aggregation_section_element->get_element()->get_raw_data(), true, 512, JSON_THROW_ON_ERROR)
            );
        }

        return [$source_section_element, $other_source_section_element, $aggregation_section_element ?? null];
    }

}
