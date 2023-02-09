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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package criteria_othercompetency
 */

use criteria_othercompetency\othercompetency_display;
use totara_criteria\criterion;

/**
 * @group totara_competency
 */
class criteria_othercompetency_display_testcase extends advanced_testcase {

    private function setup_data() {
        $data = new class() {
            /** @var \totara_criteria\testing\generator criteria_generator */
            public $criteria_generator;
            /** @var competency[] other_competency_items */
            public $other_competency_items = [];
            /** @var int[] $other_competency_ids */
            public $other_competency_ids = [];
        };

        $data->criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $data->other_competency_items = [
            $competency_generator->create_competency('<span>Other Comp 1</span>'),
            $competency_generator->create_competency('Other Comp 2'),
            $competency_generator->create_competency('<span>Other Comp 3</span>'),
        ];

        $data->other_competency_ids = array_column($data->other_competency_items, 'id');

        return $data;
    }

    /**
     * Data provider for test_display_configuration
     */
    public function data_provider_test_display_configuration() {
        return [
            [
                'aggregation' => [
                    'method' => criterion::AGGREGATE_ALL,
                ],
                'competencies' => [],
            ],
            [
                'aggregation' => [
                    'method' => criterion::AGGREGATE_ALL,
                ],
                'competencies' => [0],
            ],
            [
                'aggregation' => [
                    'method' => criterion::AGGREGATE_ANY_N,
                    'req_items' => 2
                ],
                'competencies' => [0, 1, 2],
            ],
        ];
    }


    /**
     * Test configuration display
     *
     * @dataProvider data_provider_test_display_configuration
     * @param $aggregation
     * @param $competencies
     */
    public function test_display_configuration($aggregation, $competencies) {
        $data = $this->setup_data();

        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $creation_params = [];
        $creation_params['aggregation'] = $aggregation;
        $creation_params['competencyids'] = [];
        foreach ($competencies as $competency_idx) {
            $creation_params['competencyids'][] = $data->other_competency_items[$competency_idx]->id;
        }

        $cc = $generator->create_othercompetency($creation_params);
        $display_configuration = (new othercompetency_display($cc))->get_configuration();

        $expected = (object)[
            'item_type' => get_string('other_competencies', 'criteria_othercompetency'),
            'item_aggregation' => get_string('complete_all', 'totara_criteria'),
            'items' => [],
            'error' => get_string('error_invalid_configuration', 'totara_criteria'),
        ];

        if ($aggregation['method'] == criterion::AGGREGATE_ANY_N) {
            $expected->item_aggregation = get_string('aggregate_any',
                'totara_criteria',
                (object)['x' => $aggregation['req_items'] ?? 1]
            );
        }

        if (!empty($competencies)) {
            foreach ($competencies as $competency_idx) {
                $expected->items[] = (object)[
                    'description' => $data->other_competency_items[$competency_idx]->fullname,
                    'error' => get_string('error_competency_cannot_proficient', 'criteria_othercompetency'),
                ];
            }
        } else {
            $expected->error = get_string('error_invalid_configuration', 'totara_criteria');
            $expected->items[] = (object)[
                'description' => '',
                'error' => get_string('error_no_competencies', 'criteria_othercompetency'),
            ];
        }

        $this->validate_display_configuration($expected, $display_configuration);
    }

    /**
     * Validate the actual vs expected display configuration
     * @param \stdClass $expected
     * @param \stdClass $actual
     */
    private function validate_display_configuration($expected, $actual) {
        $this->assertEquals($expected->item_type, $actual->item_type);
        $this->assertEquals($expected->item_aggregation, $actual->item_aggregation);

        $expected_error = $expected->error ?? null;
        $actual_error = $actual->error ?? null;
        $this->assertEquals($expected_error, $actual_error);

        $this->assertSame(count($expected->items), count($actual->items));
        foreach ($actual->items as $actual_item) {
            foreach ($expected->items as $idx => $expected_item) {
                $expected_error = $expected_item->error ?? null;
                $actual_error = $actual_item->error ?? null;
                if ($expected_item->description == $actual_item->description
                    && $expected_error == $actual_error) {
                    unset($expected->items[$idx]);
                    break;
                }
            }
        }

        $this->assertEmpty($expected->items);
    }

}
