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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 */

use mod_perform\entity\activity\section as section_entity;
use mod_perform\models\activity\activity;
use mod_perform\models\activity\element;
use mod_perform\models\activity\helpers\section_element_manager;
use mod_perform\models\activity\section;
use mod_perform\models\activity\section_element;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\state\activity\draft;
use performelement_aggregation\aggregation;
use performelement_aggregation\calculations\average;
use performelement_aggregation\data_provider\aggregation_data;

/**
 * @group perform
 * @group perform_element
 */
class performelement_aggregation_aggregation_data_testcase extends advanced_testcase {

    /**
     * @var activity
    */
    private $activity;

    /**
     * @var section_element
     */
    private $aggregation_section_element;

    /**
     * @var section_element[]
     */
    private $source_section_elements;

    public function test_aggregation_element_adds_extra_info_to_data(): void {
        $this->create_test_data();

        $aggregation_data = json_decode($this->aggregation_section_element->element->get_data(), true, 512, JSON_THROW_ON_ERROR);

        /* @type section_element_entity $aggregation_section_element_entity */
        $aggregation_section_element_entity = section_element_entity::repository()->find_or_fail($this->aggregation_section_element->id);

        self::assertEquals(
            [
                aggregation::EXCLUDED_VALUES => [],
                aggregation::CALCULATIONS => [average::get_name()],
            ],
            json_decode($aggregation_section_element_entity->element->data, true, 512, JSON_THROW_ON_ERROR)
        );

        // The order returned in the extra data matters here, it should be the same as the insertion order (id).
        self::assertEquals(array_column($this->source_section_elements, 'id'), $aggregation_data[aggregation::SOURCE_SECTION_ELEMENT_IDS]);

        // Check the static cache was used.
        $activity_id = $this->aggregation_section_element->section->activity_id;
        self::assertEquals(aggregation_data::$aggregatable_section_cache[$activity_id], $aggregation_data[aggregation_data::AGGREGATABLE_SECTIONS]);

        aggregation_data::$aggregatable_section_cache = [];
    }

    private function create_test_data(): void {
        self::setAdminUser();

        $perform_generator = \mod_perform\testing\generator::instance();

        $this->activity = $perform_generator->create_activity_in_container(
            [
                'create_section' => false,
                'activity_name' => 'My aggregation test activity',
                'activity_status' => draft::get_code()
            ]
        );

        $section_1 = section::create($this->activity, 'First section');

        $numeric_rating_scale_1 = $element = element::create(
            $this->activity->get_context(),
            'numeric_rating_scale',
            'numeric_rating_scale_1',
            'BBB',
            '{"defaultValue": "3", "highValue": "5", "lowValue": "1"}',
            true
        );

        $numeric_rating_scale_2 = $element = element::create(
            $this->activity->get_context(),
            'numeric_rating_scale',
            'numeric_rating_scale_2',
            'BBB',
            '{"defaultValue": "2", "highValue": "5", "lowValue": "1"}',
            true
        );

        /** @var section_entity $section1_entity */
        $section1_entity = section_entity::repository()->find($section_1->get_id());
        $section_element_manager = new section_element_manager($section1_entity);
        $source_section_element_1 = $section_element_manager->add_element_after($numeric_rating_scale_1);
        $source_section_element_2 = $section_element_manager->add_element_after($numeric_rating_scale_2, $numeric_rating_scale_1->get_id());

        $aggregation_element = $this->get_aggregation_element([$source_section_element_1->id, $source_section_element_2->id], 'Performance analysis');
        $aggregation_section_element = $section_element_manager->add_element_after($aggregation_element, $numeric_rating_scale_2->get_id());

        $this->aggregation_section_element = $aggregation_section_element;
        $this->source_section_elements = [$source_section_element_1, $source_section_element_2];
    }

    /**
     * @param int[] $section_element_ids
     * @param $name
     * @return element
     * @throws moodle_exception
     */
    private function get_aggregation_element(array $section_element_ids, $name): element {
        return element::create(
            $this->activity->get_context(),
            'aggregation',
            $name,
            'A2 Element',
            json_encode([
                aggregation::SOURCE_SECTION_ELEMENT_IDS => $section_element_ids,
                aggregation::EXCLUDED_VALUES => [],
                aggregation::CALCULATIONS => [average::get_name()],
            ], JSON_THROW_ON_ERROR)
        );
    }

    public function tearDown(): void {
        $this->activity = null;
        $this->aggregation_section_element = null;
        $this->source_section_elements = null;
    }

}