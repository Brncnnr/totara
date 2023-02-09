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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

use core_phpunit\testcase;
use totara_competency\pathway;
use pathway_criteria_group\criteria_group;
use totara_criteria\criterion;
use totara_hierarchy\entity\competency as competency_entity;
use totara_hierarchy\entity\scale;

/**
 * @group totara_competency
 */
class totara_competency_generator_testcase extends testcase {

    private function setup_data() {
        $data = new class() {
            public $comp;
            public $scale;
            public $scalevalues;
            public $courses;
            public $cc;
        };

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
        $data->scale = new scale($data->scale->id);
        $data->scalevalues = $data->scale->sorted_values_high_to_low->all();

        $framework = $hierarchygenerator->create_comp_frame(['scale' => $data->scale->id]);
        $data->comp = new competency_entity($hierarchygenerator->create_comp(['frameworkid' => $framework->id])->id);

        for ($i = 1; $i <= 5; $i++) {
            $record = [
                'shortname' => "Course $i",
                'fullname' => "Course $i",
            ];

            $data->courses[$i] = $this->getDataGenerator()->create_course($record);
        }

        // Create 2 coursecompletion criteria
        //      - Course 1 AND Course 2
        //      - Course 1 OR Course 3 OR Course 5
        $crit_generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');
        $data->cc[1] = $crit_generator->create_coursecompletion([
            'aggregation' => criterion::AGGREGATE_ALL,
            'courseids' => [$data->courses[1]->id, $data->courses[2]->id],
        ]);

        $data->cc[2] = $crit_generator->create_coursecompletion([
            'aggregation' => [
                'method' => criterion::AGGREGATE_ANY_N,
                'req_items' => 1,
            ],
            'courseids' => [$data->courses[1]->id, $data->courses[3]->id, $data->courses[5]->id],
        ]);

        return $data;
    }


    /**
     * Test criteria_group generator with single criteria
     */
    public function test_generator_criteria_group_single_criteria() {
        $data = $this->setup_data();

        $cg = $this->generator()->create_criteria_group($data->comp, $data->cc[1], $data->scalevalues[3]);

        $this->validate_criteria_group($cg, [
            'competency_id' => $data->comp->id,
            'scale_value_id' => $data->scalevalues[4]->id,
            'criteria' => [$data->cc[1]],
        ]);
    }

    /**
     * Test criteria_group generator for active with multiple criteria
     */
    public function test_generator_criteria_group_active_multi_criteria() {
        $data = $this->setup_data();

        $cg = $this->generator()->create_criteria_group($data->comp, [$data->cc[1], $data->cc[2]], $data->scalevalues[3]);

        $this->validate_criteria_group($cg, [
            'competency_id' => $data->comp->id,
            'scale_value_id' => $data->scalevalues[3]->id,
            'status' => pathway::PATHWAY_STATUS_ACTIVE,
            'criteria' => [$data->cc[1], $data->cc[2]],
        ]);
    }


     /**
      * Validate the generated criteria_group
      *
      * @param  criteria_group $cg Generated criteria_group
      * @param  array $record Record to test agains
      */
    private function validate_criteria_group(criteria_group $cg, array $record) {
        global $DB;

        $rows = $DB->get_records('totara_competency_pathway');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $this->assertEquals($cg->get_id(), $row->id);
        $this->assertEquals('criteria_group', $row->path_type);
        $this->assertEquals($cg->get_path_instance_id(), $row->path_instance_id);

        $expected_status = $record['status'] ?? pathway::PATHWAY_STATUS_ACTIVE;
        $this->assertEquals($expected_status, $row->status);

        $rows = $DB->get_records('pathway_criteria_group');
        $this->assertEquals(1, count($rows));
        $cgrow = reset($rows);
        $this->assertEquals($cg->get_path_instance_id(), $cgrow->id);
        $this->assertEquals($cg->get_scale_value()->get_attribute('id'), $cgrow->scale_value_id);

        $rows = $DB->get_records('pathway_criteria_group_criterion');
        $this->assertEquals(count($record['criteria']), count($rows));

        $critids = [];
        foreach ($record['criteria'] as $crit) {
            $critids[] = $crit->get_id();
        }

        foreach ($rows as $row) {
            $this->assertEquals($cg->get_path_instance_id(), $row->criteria_group_id);
            $this->assertEquals('coursecompletion', $row->criterion_type);
            $this->assertTrue(in_array($row->criterion_id, $critids));
        }
    }

    public function test_it_returns_assignment_generator() {
        $ag = $this->generator()->assignment_generator();
        $this->assertSame($ag, $this->generator()->assignment_generator());
    }

    /**
     * @return \totara_competency\testing\generator
     */
    protected function generator() {
        return \totara_competency\testing\generator::instance();
    }
}
