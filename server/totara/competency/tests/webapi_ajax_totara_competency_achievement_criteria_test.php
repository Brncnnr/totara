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
 * @package totara_competency
 */

use pathway_manual\models\roles\manager;
use totara_criteria\criterion;
use \totara_webapi\graphql;
use core\webapi\execution_context;

/**
 * @group totara_competency
 */
class totara_competency_webapi_ajax_totara_competency_achievement_criteria_testcase extends advanced_testcase {

    private function setup_data() {
        global $DB;

        $data = new class() {
            public $comp;
            public $scalevalues;
            public $courses;
        };

        $this->setAdminUser();

        /** @var \totara_hierarchy\testing\generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $data->scale = $hierarchygenerator->create_scale(
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
        $data->scalevalues = [];
        foreach ($rows as $row) {
            $data->scalevalues[$row->sortorder] = $row;
        }

        $framework = $hierarchygenerator->create_comp_frame(['scale' => $data->scale->id]);

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $data->comp = $competency_generator->create_competency('Test competency', $framework->id);

        // Some courses
        for ($i = 1; $i <= 5; $i++) {
            $record = [
                'shortname' => "Course $i",
                'fullname' => "Course $i",
            ];

            $data->courses[$i] = $this->getDataGenerator()->create_course($record);
        }

        return $data;
    }

    public function test_execute_no_pathways() {
        $data = $this->setup_data();

        $operationname = 'totara_competency_achievement_criteria';
        $result = graphql::execute_operation(execution_context::create('ajax', $operationname),
            ['competency_id' => $data->comp->id]
        );

        $this->assertEmpty($result->errors);
        $this->assertTrue(is_array($result->data));

        $expected_aggregation = new \aggregation_highest\highest();
        $expected = [
            $operationname => [
                'competency_id' => (string)$data->comp->id,
                'overall_aggregation' => [
                    'aggregation_type' => $expected_aggregation->get_agg_type(),
                    'title' => $expected_aggregation->get_title(),
                    'description' => $expected_aggregation->get_description(),
                ],
                'paths' => [],
            ]
        ];

        $this->assertEqualsCanonicalizing($expected, $result->data);
    }

    public function test_execute_with_pathways() {
        $data = $this->setup_data();

        // Create a coursecompletion criteria
        //      - Course 1 AND Course 2

        /** @var \totara_criteria\testing\generator $criteria_generator */
        $criteria_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        $cc = $criteria_generator->create_coursecompletion([
            'aggregation' => criterion::AGGREGATE_ALL,
            'courseids' => [$data->courses[1]->id, $data->courses[2]->id],
        ]);

        // Create pathways:
        // - Manual rating by manager
        // - Criteria group

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $pathways = [];

        $pw = $competency_generator->create_manual($data->comp, [manager::class]);
        $pathways[$pw->get_id()] = $pw;

        $pw = $competency_generator->create_criteria_group($data->comp, $cc, $data->scalevalues[2]->id, null, null, 2);
        $pathways[$pw->get_id()] = $pw;

        $pw = $competency_generator->create_learning_plan_pathway($data->comp);
        $pathways[$pw->get_id()] = $pw;

        $operationname = 'totara_competency_achievement_criteria';

        // Without summary_criteria
        $result = graphql::execute_operation(execution_context::create('ajax', $operationname),
            ['competency_id' => $data->comp->id]
        );

        $this->assertEmpty($result->errors);
        $this->assertTrue(is_array($result->data));
        $result_data = $result->data[$operationname];

        $this->assertEquals($data->comp->id, $result_data['competency_id']);

        $expected_aggregation = new \aggregation_highest\highest();

        $expected = [
            'aggregation_type' => $expected_aggregation->get_agg_type(),
            'title' => $expected_aggregation->get_title(),
            'description' => $expected_aggregation->get_description(),
        ];

        $this->assertEqualsCanonicalizing($expected, $result_data['overall_aggregation']);

        foreach ($result_data['paths'] as $path) {
            $this->assertTrue(isset($pathways[$path['id']]));
            $pw = $pathways[$path['id']];

            $this->assertEquals($pw->get_id(), $path['id']);
            $this->assertEquals($pw->get_path_type(), $path['pathway_type']);
            $this->assertEquals($pw->get_path_instance_id(), $path['instance_id']);
            $this->assertEquals($pw->get_title(), $path['title']);
            $this->assertEquals($pw->get_sortorder(), $path['sortorder']);
            $this->assertEquals($pw->get_status_name(), $path['status']);
            $this->assertEquals($pw->get_classification_name(), $path['classification']);

            unset($pathways[$path['id']]);
        }
    }
}
