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
 * @package pathway_manual
 */

use core_phpunit\testcase;
use pathway_learning_plan\learning_plan;
use pathway_learning_plan\learning_plan_evaluator;
use pathway_manual\manual_evaluator_user_source;
use totara_competency\aggregation_users_table;
use totara_competency\entity\pathway_achievement;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;

/**
 * @group totara_competency
 */
class pathway_learning_plan_evaluator_testcase extends testcase {

    private function setup_data() {
        global $DB;

        $data = new class() {
            public $users = [];

            /** @var competency $competency*/
            public $competency;
            public $scale;
            public $scalevalues = [];

            /** @var learning_plan $lp_pathway */
            public $lp_pathway;

            /** @var aggregation_users_table $user_id_table */
            public $user_id_table;
            /** @var manual_evaluator_user_source $user_id_source*/
            public $user_id_source;
        };

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
        foreach ($rows as $row) {
            $data->scalevalues[$row->sortorder] = new scale_value($row->id);
        }

        $framework = $hierarchygenerator->create_comp_frame(['scale' => $data->scale->id]);
        $comp = $hierarchygenerator->create_comp(['frameworkid' => $framework->id]);
        $data->competency = new competency($comp->id);

        $data->users[1] = $this->getDataGenerator()->create_user();
        $data->users[2] = $this->getDataGenerator()->create_user();

        $data->lp_pathway = new learning_plan();
        $data->lp_pathway->set_competency($data->competency);
        $data->lp_pathway->save();

        // The user_source
        $data->user_id_table = new aggregation_users_table();

        $data->user_id_source = new manual_evaluator_user_source($data->user_id_table, true);

        return $data;
    }

    public function test_aggregate() {

        $data = $this->setup_data();
        $now = time();

        // Manually insert into dp plan_competency_value to exercise the table sql explicitly
        // The full process is exercised in pathway_learning_plan_learning_plan_testcase::test_integration
        // found in learning_plan_test.php

        $evaluator = new learning_plan_evaluator($data->lp_pathway, $data->user_id_source);

        // Adding an empty
        $this->create_rating_record($data->competency->id, $data->users[1]->id, 0, $now++);
        $this->create_userid_table_records($data->user_id_table, $data->competency->id, [$data->users[1]->id]);

        // Now aggregate first time for user1
        $evaluator->aggregate($now++);

        $expected = [
            [
                'pathway_id' => $data->lp_pathway->get_id(),
                'scale_value_id' => null,
                'status' => pathway_achievement::STATUS_CURRENT,
                'related_info' => [],
            ],
        ];
        $this->verify_userid_table_records($data->user_id_table, [$data->users[1]->id => 1]);
        $this->verify_pathway_achievements($data->users[1]->id, $expected);

        // Let's start from scratch to simulate that aggregation works with an existing achievement record with no scale value
        $data->user_id_table->delete();

        $this->create_rating_record($data->competency->id, $data->users[1]->id, $data->scalevalues[2]->id, $now++);
        $this->create_userid_table_records($data->user_id_table, $data->competency->id, [$data->users[1]->id]);

        // Now aggregate second time for user1
        $evaluator->aggregate($now++);

        $expected = [
            [
                'pathway_id' => $data->lp_pathway->get_id(),
                'scale_value_id' => null,
                'status' => pathway_achievement::STATUS_ARCHIVED,
                'related_info' => [],
            ],
            [
                'pathway_id' => $data->lp_pathway->get_id(),
                'scale_value_id' => $data->scalevalues[2]->id,
                'status' => pathway_achievement::STATUS_CURRENT,
                'related_info' => [],
            ],
        ];
        $this->verify_userid_table_records($data->user_id_table, [$data->users[1]->id => 1]);
        $this->verify_pathway_achievements($data->users[1]->id, $expected);

        // Update user1's value. Add a value for user2, but do not 'assign' user2
        $this->create_rating_record($data->competency->id, $data->users[1]->id, $data->scalevalues[3]->id, $now++);
        $this->create_rating_record($data->competency->id, $data->users[2]->id, $data->scalevalues[4]->id, $now++);

        // Reset the has_changed flag
        $data->user_id_table->reset_has_changed(0);

        // Aggregate
        $evaluator->aggregate($now++);

        $expected = [
            1 => [
                [
                    'pathway_id' => $data->lp_pathway->get_id(),
                    'scale_value_id' => null,
                    'status' => pathway_achievement::STATUS_ARCHIVED,
                    'related_info' => [],
                ],
                [
                    'pathway_id' => $data->lp_pathway->get_id(),
                    'scale_value_id' => $data->scalevalues[2]->id,
                    'status' => pathway_achievement::STATUS_ARCHIVED,
                    'related_info' => [],
                ],
                [
                    'pathway_id' => $data->lp_pathway->get_id(),
                    'scale_value_id' => $data->scalevalues[3]->id,
                    'status' => pathway_achievement::STATUS_CURRENT,
                    'related_info' => [],
                ],
            ],
            2 => [],
        ];

        $this->verify_userid_table_records($data->user_id_table, [$data->users[1]->id => 1]);
        $this->verify_pathway_achievements($data->users[1]->id, $expected[1]);
        $this->verify_pathway_achievements($data->users[2]->id, $expected[2]);

        // Now 'assign' user 2
        $this->create_userid_table_records($data->user_id_table, $data->competency->id, [$data->users[1]->id, $data->users[2]->id]);

        // Aggregate
        $evaluator->aggregate($now++);

        $expected = [
            1 => [
                [
                    'pathway_id' => $data->lp_pathway->get_id(),
                    'scale_value_id' => null,
                    'status' => pathway_achievement::STATUS_ARCHIVED,
                    'related_info' => [],
                ],
                [
                    'pathway_id' => $data->lp_pathway->get_id(),
                    'scale_value_id' => $data->scalevalues[2]->id,
                    'status' => pathway_achievement::STATUS_ARCHIVED,
                    'related_info' => [],
                ],
                [
                    'pathway_id' => $data->lp_pathway->get_id(),
                    'scale_value_id' => $data->scalevalues[3]->id,
                    'status' => pathway_achievement::STATUS_CURRENT,
                    'related_info' => [],
                ],
            ],
            2 => [
                [
                    'pathway_id' => $data->lp_pathway->get_id(),
                    'scale_value_id' => $data->scalevalues[4]->id,
                    'status' => pathway_achievement::STATUS_CURRENT,
                    'related_info' => [],
                ],
            ],
        ];

        $this->verify_userid_table_records($data->user_id_table, [$data->users[1]->id => 0, $data->users[2]->id => 1]);
        $this->verify_pathway_achievements($data->users[1]->id, $expected[1]);
        $this->verify_pathway_achievements($data->users[2]->id, $expected[2]);
    }


    /**
     * Create a rating record manually
     *
     */
    private function create_rating_record(int $competency_id, int $user_id, int $scale_value_id, ?int $date_assigned = null) {
        global $DB;

        $record = $DB->get_record(
            'dp_plan_competency_value',
            ['competency_id' => $competency_id, 'user_id' => $user_id]
        );

        if (!$record) {
            $record = new stdClass();
            $record->competency_id = $competency_id;
            $record->user_id = $user_id;
        }

        $record->scale_value_id = $scale_value_id;
        $record->date_assigned = $date_assigned ?? time();
        $record->manual = 1;

        if (empty($record->id)) {
            $DB->insert_record('dp_plan_competency_value', $record);
        } else {
            $DB->update_record('dp_plan_competency_value', $record);
        }
    }

    /**
     * Helper function to create rows in the user_id table
     *
     * @param aggregation_users_table $user_id_table
     * @param int $competency_id
     * @param array $assigned_users
     */
    private function create_userid_table_records(
        aggregation_users_table $user_id_table,
        int $competency_id,
        array $assigned_users
    ) {
        global $DB;

        $user_id_table->truncate();
        if (empty($assigned_users)) {
            return;
        }

        $tablename = $user_id_table->get_table_name();
        $temp_user_records = [];
        foreach ($assigned_users as $user_id) {
            $temp_user_records[] = $user_id_table->get_insert_record($user_id, $competency_id);
        }
        $DB->insert_records($tablename, $temp_user_records);
    }

    private function verify_pathway_achievements($user_id, $expected_rows) {
        global $DB;

        $actual_rows = $DB->get_records('totara_competency_pathway_achievement', ['user_id' => $user_id]);

        $this->assertSame(count($expected_rows), count($actual_rows));
        foreach ($actual_rows as $actual_row) {
            foreach ($expected_rows as $key => $expected_row) {
                if ((int)$actual_row->pathway_id == $expected_row['pathway_id'] &&
                    (int)$actual_row->status == $expected_row['status'] &&
                    (int)$actual_row->scale_value_id == $expected_row['scale_value_id']) {
                    unset($expected_rows[$key]);
                    break;
                }
            }
        }

        $this->assertSame(0, count($expected_rows));
    }

    /**
     * Helper function to verify rows in the user_id table
     *
     * @param aggregation_users_table $user_id_table
     * @param array $expected
     */
    private function verify_userid_table_records(aggregation_users_table $user_id_table, array $expected) {
        global $DB;

        $rows = $DB->get_records($user_id_table->get_table_name(), $user_id_table->get_filter('', true));
        $this->assertSame(count($expected), count($rows));

        foreach ($rows as $row) {
            $this->assertTrue(isset($expected[$row->user_id]));
            $this->assertEquals($expected[$row->user_id], $row->has_changed);
        }
    }

}
