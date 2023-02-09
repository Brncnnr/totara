<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 */

use core_phpunit\testcase;
use totara_hierarchy\entity\scale_value;

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * @group pathway_criteria_group
 * @group totara_competency
 */
class pathway_criteria_group_services_testcase extends testcase {

    private function setup_data() {
        global $DB;

        $data = new class() {
            public $comp;
            public $scale;
            public $scalevalues = [];

            public $courses = [];
        };

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $data->scale = $hierarchy_generator->create_scale(
            'comp',
            ['name' => 'Test scale', 'description' => 'Test scale'],
            [
                5 => ['name' => 'No clue', 'proficient' => 0, 'sortorder' => 5, 'default' => 1],
                4 => ['name' => 'Learning', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
                3 => ['name' => 'Getting there', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
                2 => ['name' => 'Almost there', 'proficient' => 1, 'sortorder' => 2, 'default' => 0],
                1 => ['name' => 'Arrived', 'proficient' => 1, 'sortorder' => 1, 'default' => 0],
            ]
        );
        $rows = $DB->get_records('comp_scale_values', ['scaleid' => $data->scale->id], 'sortorder');
        foreach ($rows as $row) {
            $data->scalevalues[$row->sortorder] = new scale_value($row->id);
        }

        $compfw = $hierarchy_generator->create_comp_frame(['scale' => $data->scale->id]);
        $data->comp = $hierarchy_generator->create_comp(['frameworkid' => $compfw->id]);

        $data->course_ids = [];
        for ($i = 0; $i < 5; $i++) {
            $data->courses[$i] = $this->getDataGenerator()->create_course();
            $data->course_ids[] = $data->courses[$i]->id;
        }

        $this->setAdminUser();
        $GLOBALS['USER']->ignoresesskey = true;

        return $data;
    }

    public function test_pathway_criteria_group_get_criteria_types_service() {
        $data = $this->setup_data();

        $res = \external_api::call_external_function(
            'pathway_criteria_group_get_criteria_types',
            []
        );

        $result = $res['data'] ?? null;
        $error = $res['error'] ?? null;

        $this->assertEquals(false, $error);
        $this->assertTrue(is_array($result));
        $cc = array_filter($result, function ($v) {
            return $v['type'] === 'coursecompletion';
        });
        $this->assertSame(1, count($cc));
    }

    public function test_pathway_criteria_group_create_service_coursecompletion() {
        global $DB;

        $data = $this->setup_data();

        // Ensure we have no pathways before the API call
        $this->assertEquals(0, $DB->count_records('totara_competency_pathway'));
        $this->assertEquals(0, $DB->count_records('pathway_criteria_group'));
        $this->assertEquals(0, $DB->count_records('pathway_criteria_group_criterion', ['criterion_type' => 'coursecompletion']));
        $this->assertEquals(0, $DB->count_records('totara_criteria', ['plugin_type' => 'coursecompletion']));
        $this->assertEquals(0, $DB->count_records('totara_competency_configuration_change'));

        $params = [
            'competency_id' => $data->comp->id,
            'criteria' => [
                [
                    'aggregation' => [
                        'reqitems' => 1,
                        'method' => '1',
                    ],
                    'id' => 0,
                    'itemids' => [$data->course_ids[0], $data->course_ids[3]],
                    'type' => "coursecompletion",
                ],
            ],
            'scalevalue' => $data->scalevalues[5]->id,
            'sortorder' => 1,
            'actiontime' => time(),
        ];

        $res = \external_api::call_external_function(
            'pathway_criteria_group_create',
            $params
        );

        $result = $res['data'] ?? null;
        $error = $res['error'] ?? null;

        $this->assertEquals(false, $error);
        $this->assertTrue(is_numeric($result));

        // Assert that the new pathway is created and change is logged
        $this->assertEquals(1, $DB->count_records('totara_competency_pathway'));
        $this->assertEquals(1, $DB->count_records('pathway_criteria_group'));
        $this->assertEquals(1, $DB->count_records('pathway_criteria_group_criterion', ['criterion_type' => 'coursecompletion']));
        $this->assertEquals(1, $DB->count_records('totara_criteria', ['plugin_type' => 'coursecompletion']));
        $this->assertEquals(1, $DB->count_records('totara_competency_configuration_change'));
    }

    public function test_pathway_criteria_group_create_service_linkedcourses() {
        global $DB;

        $data = $this->setup_data();

        // Ensure we have no pathways before the API call
        $this->assertEquals(0, $DB->count_records('totara_competency_pathway'));
        $this->assertEquals(0, $DB->count_records('pathway_criteria_group'));
        $this->assertEquals(0, $DB->count_records('pathway_criteria_group_criterion', ['criterion_type' => 'linkedcourses']));
        $this->assertEquals(0, $DB->count_records('totara_criteria', ['plugin_type' => 'linkedcourses']));
        $this->assertEquals(0, $DB->count_records('totara_competency_configuration_change'));

        $params = [
            'competency_id' => $data->comp->id,
            'criteria' => [
                [
                    'aggregation' => [
                        'reqitems' => 1,
                        'method' => '1',
                    ],
                    'id' => 0,
                    'metadata' => [
                        [
                            'metakey' => "competency_id",
                            'metavalue' => $data->comp->id,
                        ],
                    ],
                    'type' => "linkedcourses",
                ],
            ],
            'scalevalue' => $data->scalevalues[5]->id,
            'sortorder' => 1,
            'actiontime' => time(),
        ];

        $res = \external_api::call_external_function(
            'pathway_criteria_group_create',
            $params
        );

        $result = $res['data'] ?? null;
        $error = $res['error'] ?? null;

        $this->assertEquals(false, $error);
        $this->assertTrue(is_numeric($result));

        // Assert that the new pathway is created and change is logged
        $this->assertEquals(1, $DB->count_records('totara_competency_pathway'));
        $this->assertEquals(1, $DB->count_records('pathway_criteria_group'));
        $this->assertEquals(1, $DB->count_records('pathway_criteria_group_criterion', ['criterion_type' => 'linkedcourses']));
        $this->assertEquals(1, $DB->count_records('totara_criteria', ['plugin_type' => 'linkedcourses']));
        $this->assertEquals(1, $DB->count_records('totara_competency_configuration_change'));
    }

    public function test_pathway_criteria_group_create_linkedcourses_update_with_coursecompletion() {
        global $DB;

        $data = $this->setup_data();

        // Ensure we have no pathways before the API call
        $this->assertEquals(0, $DB->count_records('totara_competency_pathway'));
        $this->assertEquals(0, $DB->count_records('pathway_criteria_group'));
        $this->assertEquals(0, $DB->count_records('pathway_criteria_group_criterion'));
        $this->assertEquals(0, $DB->count_records('totara_criteria'));
        $this->assertEquals(0, $DB->count_records('totara_competency_configuration_change'));

        $params = [
            'competency_id' => $data->comp->id,
            'criteria' => [
                [
                    'aggregation' => [
                        'reqitems' => 1,
                        'method' => '1',
                    ],
                    'id' => 0,
                    'metadata' => [
                        [
                            'metakey' => "competency_id",
                            'metavalue' => $data->comp->id,
                        ],
                    ],
                    'type' => "linkedcourses",
                ],
            ],
            'scalevalue' => $data->scalevalues[5]->id,
            'sortorder' => 1,
            'actiontime' => time(),
        ];

        $res = \external_api::call_external_function(
            'pathway_criteria_group_create',
            $params
        );

        $result = $res['data'] ?? null;
        $error = $res['error'] ?? null;

        $this->assertEquals(false, $error);
        $this->assertTrue(is_numeric($result));

        // Assert that the new pathway is created and change is logged
        $this->assertEquals(1, $DB->count_records('totara_competency_pathway'));
        $this->assertEquals(1, $DB->count_records('pathway_criteria_group'));
        $this->assertEquals(1, $DB->count_records('pathway_criteria_group_criterion', ['criterion_type' => 'linkedcourses']));
        $this->assertEquals(1, $DB->count_records('totara_criteria', ['plugin_type' => 'linkedcourses']));
        $this->assertEquals(1, $DB->count_records('totara_competency_configuration_change'));
    }

}
