<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package totara_criteria
 */

use criteria_coursecompletion\coursecompletion_display;
use totara_criteria\criterion;

/**
 * @group totara_competency
 */
class criteria_coursecompletion_display_testcase extends advanced_testcase {

    private function setup_data() {
        $data = new class() {
            public $courses;
        };

        $prefix = 'Course ';
        for ($i = 1; $i <= 5; $i++) {
            $record = [
                'shortname' => $prefix . $i,
                'fullname' => $prefix . $i,
                'enablecompletion' => true,
            ];

            $data->courses[$i] = $this->getDataGenerator()->create_course($record);
        }

        return $data;
    }

    /**
     * Data provider for test_configuration
     */
    public function data_provider_test_configuration() {
        return [
            [
                'aggregation' => [
                    'method' => criterion::AGGREGATE_ALL,
                ],
                'courses' => [],
            ],
            [
                'aggregation' => [
                    'method' => criterion::AGGREGATE_ALL,
                ],
                'courses' => [1],
            ],
            [
                'aggregation' => [
                    'method' => criterion::AGGREGATE_ANY_N,
                    'req_items' => 2
                ],
                'courses' => [1, 3, 5],
            ],
        ];
    }

    /**
     * Test configuration display
     *
     * @dataProvider data_provider_test_configuration
     * @param $aggregation
     * @param $courses
     */
    public function test_configuration($aggregation, $courses) {
        $data = $this->setup_data();

        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $creation_params = [];
        $creation_params['aggregation'] = $aggregation;
        $creation_params['courseids'] = [];
        foreach ($courses as $course_idx) {
            $creation_params['courseids'][] = $data->courses[$course_idx]->id;
        }

        $cc = $generator->create_coursecompletion($creation_params);
        $display_configuration = (new coursecompletion_display($cc))->get_configuration();

        $expected = (object)[
            'item_type' => get_string('courses', 'criteria_coursecompletion'),
            'item_aggregation' => get_string('complete_all', 'totara_criteria'),
            'items' => [],
        ];

        if ($aggregation['method'] == criterion::AGGREGATE_ANY_N) {
            $expected->item_aggregation = get_string('aggregate_any',
                'totara_criteria',
                (object)['x' => $aggregation['req_items'] ?? 1]
            );
        }

        if (!empty($courses)) {
            foreach ($courses as $course_idx) {
                $expected->items[] = (object)['description' => $data->courses[$course_idx]->fullname];
            }
        } else {
            $expected->error = get_string('error_invalid_configuration', 'totara_criteria');
            $expected->items[] = (object)[
                'description' => '',
                'error' => get_string('error_no_courses', 'criteria_coursecompletion'),
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
