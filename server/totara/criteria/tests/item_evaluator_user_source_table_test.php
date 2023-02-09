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
 * @package totara_criteria
 */

use totara_competency\aggregation_users_table;
use totara_criteria\criterion;
use totara_criteria\evaluators\item_evaluator_user_source;

/**
 * @group totara_competency
 */
class totara_criteria_item_evaluator_user_source_testcase extends advanced_testcase {

    private $process_key = 'the_process_key';
    private $update_operation_value = 'the_update_operation_value';


    private function setup_data() {
        $data = new class() {
            /** @var array $users */
            public $users;
            /** @var stdClass $course */
            public $course;
            /** @var criterion $criterion */
            public $criterion;
            /** @var item_evaluator_user_source $full_source */
            public $full_source;
            /** @var item_evaluator_user_source $partial_source */
            public $partial_source;
        };

        $this->setAdminUser();
        $GLOBALS['USER']->ignoresesskey = true;

        $data->users = [];
        for ($i = 1; $i <= 2; $i++) {
            $data->users[$i] = $this->getDataGenerator()->create_user();
        }

        $record = [
            'shortname' => "course1",
            'fullname' => "Course 1",
        ];
        $data->course = $this->getDataGenerator()->create_course($record);

        /** @var \totara_criteria\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $record = ['courseids' => [$data->course->id]];
        $data->criterion = $generator->create_coursecompletion($record);

        $temp_table_def = new aggregation_users_table();

        // Testing with process_key and update_operation_name to exercise those where clauses
        $temp_table_def->set_process_key_value($this->process_key);
        $temp_table_def->set_update_operation_value($this->update_operation_value);

        $data->full_source = new item_evaluator_user_source($temp_table_def, true);
        $data->partial_source = new item_evaluator_user_source($temp_table_def, false);

        return $data;
    }

    private function insert_temp_users(array $user_ids, int $has_changed = 0) {
        global $DB;

        $DB->delete_records('totara_competency_aggregation_queue');
        $temp_records = [];
        foreach ($user_ids as $id) {
            $temp_records[] = [
                'competency_id' => 1,
                'user_id' => $id,
                'has_changed' => $has_changed,
                'process_key' => $this->process_key,
                'update_operation_name' => $this->update_operation_value
            ];
        }

        $DB->insert_records('totara_competency_aggregation_queue', $temp_records);
    }

    private function verify_item_records($expected) {
        global $DB;

        $records = $DB->get_records('totara_criteria_item_record');
        $this->assertSame(count($expected), count($records));
        foreach ($records as $record) {
            foreach ($expected as $key => $expected_values) {
                if ($record->user_id == $expected_values['user_id'] && $record->criterion_met == $expected_values['criterion_met']) {
                    unset($expected[$key]);
                    break;
                }
            }
        }

        $this->assertEmpty($expected);
    }


    public function test_create_item_records() {
        global $DB;

        $data = $this->setup_data();
        $this->assertSame(0, $DB->count_records('totara_criteria_item_record'));

        // No records
        $data->full_source->create_item_records($data->criterion->get_id(), 0);
        $this->assertSame(0, $DB->count_records('totara_criteria_item_record'));

        // Add users
        $user_ids = [1, 2, 3];
        $this->insert_temp_users($user_ids);
        $data->full_source->create_item_records($data->criterion->get_id(), 0);
        $expected = [
            ['user_id' => 1, 'criterion_met' => 0],
            ['user_id' => 2, 'criterion_met' => 0],
            ['user_id' => 3, 'criterion_met' => 0],
        ];
        $this->verify_item_records($expected);

        // Some duplicates, some new
        $user_ids = [2, 4, 6];
        $this->insert_temp_users($user_ids);
        $data->full_source->create_item_records($data->criterion->get_id(), 1);
        $expected = [
            ['user_id' => 1, 'criterion_met' => 0],
            ['user_id' => 2, 'criterion_met' => 0],
            ['user_id' => 4, 'criterion_met' => 1],
            ['user_id' => 3, 'criterion_met' => 0],
            ['user_id' => 6, 'criterion_met' => 1],
        ];
        $this->verify_item_records($expected);
    }

    public function test_delete_item_records() {
        $data = $this->setup_data();
        // Add some users to start
        $user_ids = [1, 2, 3, 4, 5];
        $this->insert_temp_users($user_ids);
        $data->full_source->create_item_records($data->criterion->get_id(), 0);

        // Now for the tests ...

        // When deleting from a partial set, nothing should happen
        $to_keep = [2, 4];
        $this->insert_temp_users($to_keep);
        $data->partial_source->delete_item_records($data->criterion->get_id());
        $expected = [
            ['user_id' => 1, 'criterion_met' => 0],
            ['user_id' => 2, 'criterion_met' => 0],
            ['user_id' => 3, 'criterion_met' => 0],
            ['user_id' => 4, 'criterion_met' => 0],
            ['user_id' => 5, 'criterion_met' => 0],
        ];
        $this->verify_item_records($expected);

        // When deleting from a full set, all but the user_ids in the source should be deleted
        $to_keep = [2, 4];
        $this->insert_temp_users($to_keep);
        $data->full_source->delete_item_records($data->criterion->get_id());
        $expected = [
            ['user_id' => 2, 'criterion_met' => 0],
            ['user_id' => 4, 'criterion_met' => 0],
        ];
        $this->verify_item_records($expected);
    }

    public function test_mark_updated_assigned_users() {
        global $DB;

        $data = $this->setup_data();
        // Add some users to start with item_records
        $user_ids = [1, 2, 3, 4, 5];
        $this->insert_temp_users($user_ids);
        $data->full_source->create_item_records($data->criterion->get_id(), 0);

        // Now update some of the timeevaluated values to a later time
        $row = $DB->get_record_sql("SELECT MAX(timeevaluated) as timeevaluated FROM {totara_criteria_item_record}");
        $previous_time = (int)$row->timeevaluated;
        [$user_id_sql, $params] = $DB->get_in_or_equal([1, 3, 5], SQL_PARAMS_NAMED);
        $params['newtime'] = $previous_time + 1;

        $sql =
            "UPDATE {totara_criteria_item_record} 
                SET timeevaluated = :newtime 
              WHERE user_id {$user_id_sql}";
        $DB->execute($sql, $params);

        // Now for the test
        // Initially all temp user rows has a 0 has_changed value
        $temp_user_rows = $DB->get_records('totara_competency_aggregation_queue');
        $this->assertEquals(5, count($temp_user_rows));
        foreach ($temp_user_rows as $row) {
            $this->assertEquals(0, $row->has_changed);
        }

        $data->full_source->mark_updated_assigned_users($data->criterion->get_id(), $previous_time);

        // Now users 1, 3 and 5 must be marked as having changes
        $temp_user_rows = $DB->get_records('totara_competency_aggregation_queue');
        $this->assertEquals(5, count($temp_user_rows));
        foreach ($temp_user_rows as $row) {
            $this->assertEquals(in_array($row->user_id, [1, 3, 5]) ? 1 : 0, $row->has_changed);
        }
    }

}
