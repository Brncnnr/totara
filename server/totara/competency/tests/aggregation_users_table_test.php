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


use totara_competency\aggregation_users_table;
use totara_competency\expand_task;
use totara_competency\models\assignment_actions;
use totara_core\advanced_feature;

/**
 * @group totara_competency
 */
class totara_competency_aggregation_users_table_testcase extends \advanced_testcase {

    private function setup_data() {
        global $DB;

        $data = new class() {
            /** @var aggregation_users_table */
            public $tbl;
            /** @var array */
            public $records;
        };

        $data->tbl = new aggregation_users_table();

        $data->records = [
            ['user_id' => 1, 'competency_id' => 10, 'has_changed' => 0, 'process_key' => null, 'update_operation_name' => 'op1'],
            ['user_id' => 2, 'competency_id' => 9, 'has_changed' => 1, 'process_key' => null, 'update_operation_name' => ''],
            ['user_id' => 3, 'competency_id' => 8, 'has_changed' => 0, 'process_key' => 'proc2', 'update_operation_name' => ''],
            ['user_id' => 4, 'competency_id' => 7, 'has_changed' => 1, 'process_key' => 'proc2', 'update_operation_name' => ''],
            ['user_id' => 5, 'competency_id' => 6, 'has_changed' => 0, 'process_key' => 'proc3', 'update_operation_name' => 'op1'],
            ['user_id' => 6, 'competency_id' => 5, 'has_changed' => 1, 'process_key' => 'proc3', 'update_operation_name' => 'op1'],
            ['user_id' => 7, 'competency_id' => 4, 'has_changed' => 0, 'process_key' => 'proc3', 'update_operation_name' => 'op2'],
            ['user_id' => 8, 'competency_id' => 3, 'has_changed' => 1, 'process_key' => 'proc3', 'update_operation_name' => 'op3'],
            ['user_id' => 9, 'competency_id' => 2, 'has_changed' => 0, 'process_key' => null, 'update_operation_name' => 'op3'],
            ['user_id' => 10, 'competency_id' => 1, 'has_changed' => 1, 'process_key' => null, 'update_operation_name' => 'op3'],
        ];

        $DB->insert_records($data->tbl->get_table_name(), $data->records);

        return $data;
    }

    /**
     * Test constructor, getters and setters
     */
    public function test_constructor_getters_setters() {
        // First use defaults
        $tbl = new aggregation_users_table();

        $this->assertSame('totara_competency_aggregation_queue', $tbl->get_table_name());
        $this->assertSame('user_id', $tbl->get_user_id_column());
        $this->assertSame('competency_id', $tbl->get_competency_id_column());
        $this->assertSame('has_changed', $tbl->get_has_changed_column());
        $this->assertSame('process_key', $tbl->get_process_key_column());
        $this->assertEmpty($tbl->get_process_key_value());
        $this->assertSame('update_operation_name', $tbl->get_update_operation_column());
        $this->assertEmpty($tbl->get_update_operation_value());

        $tbl->set_process_key_value('my_value');
        $this->assertSame('my_value', $tbl->get_process_key_value());

        $tbl->set_update_operation_value('my_value2');
        $this->assertSame('my_value2', $tbl->get_update_operation_value());

        $tbl = new aggregation_users_table('my_table', false, 'ui', 'ci', 'hc', 'pc', 'uon');

        $this->assertSame('my_table', $tbl->get_table_name());
        $this->assertSame('ui', $tbl->get_user_id_column());
        $this->assertSame('ci', $tbl->get_competency_id_column());
        $this->assertSame('hc', $tbl->get_has_changed_column());
        $this->assertSame('pc', $tbl->get_process_key_column());
        $this->assertEmpty($tbl->get_process_key_value());
        $this->assertSame('uon', $tbl->get_update_operation_column());
        $this->assertEmpty($tbl->get_update_operation_value());

        $tbl = new aggregation_users_table('my_table', false, 'ui', 'ci', null, 'pc', 'uon');

        $this->assertSame(null, $tbl->get_has_changed_column());
        $this->assertSame('pc', $tbl->get_process_key_column());
        $this->assertSame('uon', $tbl->get_update_operation_column());

        $tbl = new aggregation_users_table('my_table', false, 'ui', 'ci', 'hc', null, 'uon');

        $this->assertSame('hc', $tbl->get_has_changed_column());
        $this->assertSame(null, $tbl->get_process_key_column());
        $this->assertSame('uon', $tbl->get_update_operation_column());

        $tbl = new aggregation_users_table('my_table', false, 'ui', 'ci', 'hc', 'pc', null);

        $this->assertSame('hc', $tbl->get_has_changed_column());
        $this->assertSame('pc', $tbl->get_process_key_column());
        $this->assertSame(null, $tbl->get_update_operation_column());
    }

    public function test_creating_temp_table() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/lib/ddllib.php');

        $table_name = 'my_temporary_for_aggregation';

        $dbman = $DB->get_manager();
        $table = new xmldb_table($table_name);
        $this->assertFalse($dbman->table_exists($table));

        $table = new aggregation_users_table($table_name, true);
        $this->assertTrue($dbman->table_exists($table_name));

        $table->drop_temp_table();

        // Test if the table got dropped
        $this->assertFalse($dbman->table_exists($table_name));
    }

    /**
     * Test truncate deletes everything
     */
    public function test_truncate() {
        global $DB;

        $data = $this->setup_data();

        $this->assertSame(count($data->records), $DB->count_records($data->tbl->get_table_name()));

        $data->tbl->truncate();

        $this->assertSame(0, $DB->count_records($data->tbl->get_table_name()));
    }

    /**
     * Test truncate deletes everything
     */
    public function test_delete_records() {
        global $DB;

        $data = $this->setup_data();
        $this->assertSame(count($data->records), $DB->count_records($data->tbl->get_table_name()));

        // Set the process key
        $data->tbl->set_process_key_value('proc3');
        $to_delete = array_filter($data->records, function ($record) {
            return $record['process_key'] == 'proc3';
        });

        $expected_record_keys = array_diff(array_keys($data->records), array_keys($to_delete));

        $data->tbl->delete();

        $this->assertSame(count($expected_record_keys), $DB->count_records($data->tbl->get_table_name()));
    }

    /**
     * Test truncate with process_key and update_operation
     */
    public function test_truncate_with_process_key() {
        global $DB;

        $data = $this->setup_data();
        $this->assertEquals(count($data->records), $DB->count_records($data->tbl->get_table_name()));

        // Set the process key abd update_operation
        $data->tbl->set_process_key_value('proc3');
        $data->tbl->set_update_operation_value('op3');

        $to_delete = array_filter($data->records, function ($record) {
            return $record['process_key'] == 'proc3';
        });

        $expected_record_keys = array_diff(array_keys($data->records), array_keys($to_delete));
        $data->tbl->delete();
        $this->assertEquals(count($expected_record_keys), $DB->count_records($data->tbl->get_table_name()));
    }

    /**
     * Test truncate with process_key and update_operation
     */
    public function test_truncate_without_process_key() {
        global $DB;

        $data = $this->setup_data();
        $this->assertEquals(count($data->records), $DB->count_records($data->tbl->get_table_name()));

        $data->tbl->delete();

        // Everything should be gone now
        $this->assertEquals(0, $DB->count_records($data->tbl->get_table_name()));
    }

    public function test_queue_for_aggregation() {
        global $DB;

        $data = $this->setup_data();
        $original_count = count($data->records);
        $this->assertSame($original_count, $DB->count_records($data->tbl->get_table_name()));

        $data->tbl->queue_for_aggregation(123, 321);

        $this->assertSame($original_count + 1, $DB->count_records($data->tbl->get_table_name()));

        // With process value should still add
        $data->tbl->queue_for_aggregation(3, 8);
        $this->assertSame($original_count + 2, $DB->count_records($data->tbl->get_table_name()));

        // Without process value it should not be added
        $data->tbl->queue_for_aggregation(3, 8);
        $this->assertSame($original_count + 2, $DB->count_records($data->tbl->get_table_name()));

        // New with has_changed
        $data->tbl->queue_for_aggregation(345, 543, 1);
        $rows = $DB->get_records($data->tbl->get_table_name(),
            [
                $data->tbl->get_user_id_column() => 345,
                $data->tbl->get_competency_id_column() => 543,
                $data->tbl->get_process_key_column() => null,
            ]
        );
        $this->assertSame(1, count($rows));
        $row = reset($rows);
        $this->assertEquals(1, $row->{$data->tbl->get_has_changed_column()});

        // Existing without process value, with has_changed should update has_changed
        $row = $DB->get_record($data->tbl->get_table_name(),
            [
                $data->tbl->get_user_id_column() => 3,
                $data->tbl->get_competency_id_column() => 8,
                $data->tbl->get_process_key_column() => null,
            ]
        );
        $this->assertEquals(0, $row->{$data->tbl->get_has_changed_column()});

        $data->tbl->queue_for_aggregation(3, 8, 1);
        $rows = $DB->get_records($data->tbl->get_table_name(),
            [
                $data->tbl->get_user_id_column() => 3,
                $data->tbl->get_competency_id_column() => 8,
                $data->tbl->get_process_key_column() => null,
            ]
        );
        $this->assertSame(1, count($rows));
        $row = reset($rows);
        $this->assertEquals(1, $row->{$data->tbl->get_has_changed_column()});

        // Existing without process value or has_changed should not update has_changed
        $data->tbl->queue_for_aggregation(3, 8);
        $rows = $DB->get_records($data->tbl->get_table_name(),
            [
                $data->tbl->get_user_id_column() => 3,
                $data->tbl->get_competency_id_column() => 8,
                $data->tbl->get_process_key_column() => null,
            ]
        );
        $this->assertSame(1, count($rows));
        $row = reset($rows);
        $this->assertEquals(1, $row->{$data->tbl->get_has_changed_column()});

        // Explicitly queueing with has_changed = 0 should update the persisted has_changed value
        $data->tbl->queue_for_aggregation(3, 8, 0);
        $rows = $DB->get_records($data->tbl->get_table_name(),
            [
                $data->tbl->get_user_id_column() => 3,
                $data->tbl->get_competency_id_column() => 8,
                $data->tbl->get_process_key_column() => null,
            ]
        );
        $this->assertSame(1, count($rows));
        $row = reset($rows);
        $this->assertEquals(0, $row->{$data->tbl->get_has_changed_column()});
    }

    public function test_claim_process() {
        global $DB;

        $data = $this->setup_data();

        $data->tbl->set_process_key_value('thisismyprocess');
        $data->tbl->claim_process();

        $result = $DB->get_records($data->tbl->get_table_name(), [$data->tbl->get_process_key_column() => 'thisismyprocess']);
        $this->assertCount(4, $result);

        $user_ids = array_column($result, 'user_id');
        $competency_ids = array_column($result, 'competency_id');

        $this->assertEqualsCanonicalizing([1, 2, 9, 10], $user_ids);
        $this->assertEqualsCanonicalizing([10, 9, 2, 1], $competency_ids);
    }

    /**
     * Data provider for test_filter
     */
    public function data_provider_test_filter() {
        return [
            [],
            ['process_key' => 'proc1', 'update_operation' => null],
            ['process_key' => null, 'update_operation' => 'op1'],
            ['process_key' => 'proc3', 'update_operation' => 'op2'],
            ['process_key' => 'proc3', 'update_operation' => 'op2', 'include_update_operation' => false],
        ];
    }

    /**
     * Test get_filter
     *
     * @dataProvider data_provider_test_filter
     */
    public function test_get_filter(
        ?string $process_key = null,
        ?string $update_operation = null,
        bool $include_update_operation = true
    ) {
        $data = $this->setup_data();
        $nocolumn_tbl = new aggregation_users_table('tablename',
            false,
            'useridcolumn',
            'competencyidcolumn',
            'haschangedcolumn',
            null,
            null
        );

        $expected = [];
        if (!empty($process_key)) {
            $data->tbl->set_process_key_value($process_key);
            $nocolumn_tbl->set_process_key_value($process_key);

            $expected[$data->tbl->get_process_key_column()] = $process_key;
        }

        if (!empty($update_operation)) {
            $data->tbl->set_update_operation_value($update_operation);
            $nocolumn_tbl->set_update_operation_value($update_operation);

            if ($include_update_operation) {
                $expected[$data->tbl->get_update_operation_column()] = $update_operation;
            }
        }

        $params = $data->tbl->get_filter($include_update_operation);
        $this->assertEqualsCanonicalizing($expected, $params);

        // temp table with no process_key or update_operation columns should not return a filter
        $params = $nocolumn_tbl->get_filter($include_update_operation);
        $this->assertEquals([], $params);
    }

    /**
     * Test get_filter_sql_with_params
     *
     * @dataProvider data_provider_test_filter
     */
    public function test_get_filter_sql_with_params(
        ?string $process_key = '',
        ?string $update_operation = '',
        bool $include_update_operation = true
    ) {

        $data = $this->setup_data();
        $nocolumn_tbl = new aggregation_users_table('tablename',
            false,
            'useridcolumn',
            'competencyidcolumn',
            'haschangedcolumn',
            null,
            null
        );

        $table_alias = 'tmp';
        $expected_sql_parts = [];
        $expected_sql_with_alias = [];
        $expected_params = [];

        if (!empty($process_key)) {
            $data->tbl->set_process_key_value($process_key);
            $nocolumn_tbl->set_process_key_value($process_key);

            $expected_sql_parts[] = $data->tbl->get_process_key_column() . ' = :autbl_processkey';
            $expected_sql_with_alias[] = $table_alias . '.' . $data->tbl->get_process_key_column() . ' = :autbl_processkey';
            $expected_params['autbl_processkey'] = $process_key;
        }

        if (!empty($update_operation)) {
            $data->tbl->set_update_operation_value($update_operation);
            $nocolumn_tbl->set_update_operation_value($update_operation);

            if ($include_update_operation) {
                $expected_sql_parts[] = $data->tbl->get_update_operation_column() . ' = :autbl_updateoperation';
                $expected_sql_with_alias[] = $table_alias . '.' . $data->tbl->get_update_operation_column() . ' = :autbl_updateoperation';
                $expected_params['autbl_updateoperation'] = $update_operation;
            }
        }

        $expected_sql = implode(' AND ' , $expected_sql_parts);
        [$sql, $params] = $data->tbl->get_filter_sql_with_params('', $include_update_operation);
        $this->assertEquals($expected_sql, $sql);
        $this->assertEqualsCanonicalizing($expected_params, $params);

        // With a table alias
        $expected_sql_with_alias = implode(' AND ', $expected_sql_with_alias);
        [$sql, $params] = $data->tbl->get_filter_sql_with_params($table_alias, $include_update_operation);
        $this->assertEquals($expected_sql_with_alias, $sql);
        $this->assertEquals($expected_params, $params);

        // temp table with no process_key or update_operation columns should not return a filter
        [$sql, $params] = $nocolumn_tbl->get_filter_sql_with_params($table_alias, $include_update_operation);
        $this->assertEquals('', $sql);
        $this->assertEquals([], $params);
    }

    /**
     * Data provider for test_get_set_has_changed_sql_with_params
     */
    public function data_provider_test_get_set_has_changed_sql_with_params() {
        return [
            ['update_operation' => ''],
            ['update_operation' => 'op1'],
        ];
    }

    /**
     * Test get_filter
     *
     * @dataProvider data_provider_test_get_set_has_changed_sql_with_params
     */
    public function test_get_set_has_changed_sql_with_params(string $update_operation = '') {
        $data = $this->setup_data();
        $nocolumn_tbl = new aggregation_users_table('tablename', false, 'useridcolumn', 'competencyidcolumn', null, null, null);

        $table_alias = 'tmp';
        $expected_sql = $data->tbl->get_has_changed_column() . ' = :agtbl_haschanged';
        $expected_sql_with_alias = $table_alias . '.' . $data->tbl->get_has_changed_column() . ' = :agtbl_haschanged';
        $expected_params = ['agtbl_haschanged' => 1];

        if (!empty($update_operation)) {
            $data->tbl->set_update_operation_value($update_operation);
            $nocolumn_tbl->set_update_operation_value($update_operation);

            $expected_sql .= ', ' . $data->tbl->get_update_operation_column() . ' = :agtbl_updateoperation';
            $expected_sql_with_alias .= ', ' . $table_alias . '.' . $data->tbl->get_update_operation_column() . ' = :agtbl_updateoperation';
            $expected_params['agtbl_updateoperation'] = $update_operation;
        }

        [$sql, $params] = $data->tbl->get_set_has_changed_sql_with_params(1);
        $this->assertEquals($expected_sql, $sql);
        $this->assertEqualsCanonicalizing($expected_params, $params);

        // Now with an alias
        [$sql, $params] = $data->tbl->get_set_has_changed_sql_with_params(1, $table_alias);
        $this->assertEquals($expected_sql_with_alias, $sql);
        $this->assertEqualsCanonicalizing($expected_params, $params);

        // temp table with no process_key or update_operation columns should return empty
        [$sql, $params] = $nocolumn_tbl->get_set_has_changed_sql_with_params(1);
        $this->assertEquals('', $sql);
        $this->assertEquals([], $params);
    }

    /**
     * Test queue_multiple_for_aggregation
     */
    public function test_queue_multiple_for_aggregation() {
        global $DB;

        $table = new aggregation_users_table();

        $records = [
            ['user_id' => 1, 'competency_id' => 1, 'has_changed' => 0, 'process_key' => null],
            ['user_id' => 2, 'competency_id' => 1, 'has_changed' => 0, 'process_key' => 'proc1'],
            ['user_id' => 3, 'competency_id' => 1, 'has_changed' => 0, 'process_key' => 'proc1'],
            ['user_id' => 1, 'competency_id' => 2, 'has_changed' => 0, 'process_key' => null],
            ['user_id' => 2, 'competency_id' => 2, 'has_changed' => 0, 'process_key' => 'proc2'],
            ['user_id' => 3, 'competency_id' => 2, 'has_changed' => 0, 'process_key' => null],
            ['user_id' => 1, 'competency_id' => 3, 'has_changed' => 0, 'process_key' => null],
            ['user_id' => 2, 'competency_id' => 3, 'has_changed' => 1, 'process_key' => null],
            ['user_id' => 3, 'competency_id' => 3, 'has_changed' => 1, 'process_key' => null],
        ];

        $DB->insert_records($table->get_table_name(), $records);

        $to_queue = [];
        for ($user_id = 1; $user_id <= 3; $user_id++) {
            for ($competency_id = 1; $competency_id <= 3; $competency_id++) {
                $to_queue[] = [$table->get_user_id_column() => $user_id, $table->get_competency_id_column() => $competency_id];
            }
        }

        $result = $table->queue_multiple_for_aggregation($to_queue);
        $this->assertCount(3, $result);

        $expected_rows = array_merge($records,
            [
                ['user_id' => 2, 'competency_id' => 1, 'has_changed' => 0, 'process_key' => null],
                ['user_id' => 3, 'competency_id' => 1, 'has_changed' => 0, 'process_key' => null],
                ['user_id' => 2, 'competency_id' => 2, 'has_changed' => 0, 'process_key' => null],
            ]
        );

        $rows = $DB->get_records($table->get_table_name());
        foreach ($rows as $row) {
            foreach ($expected_rows as $idx => $expected_row) {
                if ($expected_row['user_id'] == $row->{$table->get_user_id_column()}
                    && $expected_row['competency_id'] == $row->{$table->get_competency_id_column()}
                    && $expected_row['process_key'] == $row->{$table->get_process_key_column()}) {
                    unset($expected_rows[$idx]);
                    break;
                }
            }
        }

        $this->assertEmpty($expected_rows);

        // Now we also specify the has_changed value
        $result = $table->queue_multiple_for_aggregation($to_queue, 1);
        $this->assertCount(7, $result);

        // Ensure the has_changed flag was not updated on claimed queue entries
        $this->assertSame(9, $DB->count_records($table->get_table_name(),
            [$table->get_has_changed_column() => 1]));

        $this->assertSame(3, $DB->count_records($table->get_table_name(),
            [$table->get_has_changed_column() => 0]));

        // Queue without specifying a value - should not overwrite the existing has_changed values
        $result = $table->queue_multiple_for_aggregation($to_queue);
        $this->assertCount(0, $result);

        // Ensure the has_changed flag was not updated on claimed queue entries
        $this->assertSame(9, $DB->count_records($table->get_table_name(),
            [$table->get_has_changed_column() => 1]));

        $this->assertSame(3, $DB->count_records($table->get_table_name(),
            [$table->get_has_changed_column() => 0]));

        // Now queue and specify the has_changed value explicitly as 0.
        // This should overwrite the existing has_changed values
        $result = $table->queue_multiple_for_aggregation($to_queue, 0);
        $this->assertCount(9, $result);

        // Ensure the has_changed flag was not updated on claimed queue entries
        $this->assertSame(0, $DB->count_records($table->get_table_name(),
            [$table->get_has_changed_column() => 1]));

        $this->assertSame(12, $DB->count_records($table->get_table_name(),
            [$table->get_has_changed_column() => 0]));
    }

    /**
     * Test queue_multiple_for_aggregation
     */
    public function test_queue_multiple_for_aggregation_with_invalid_data() {
        $table = new aggregation_users_table();

        // Try empty data
        $result = $table->queue_multiple_for_aggregation([]);
        $this->assertEmpty($result);

        // Try invalid data
        $to_queue = [
            ['user_id' => 1, 'competency_id' => 1],
            ['user_id' => 2, 'thisshouldfail' => 1],
            ['user_id' => 1, 'competency_id' => 3],
            ['user_id' => 2, 'competency_id' => 3],
            ['user_id' => 3, 'competency_id' => 3],
        ];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Data passed to queue_multiple_for_aggregation must contain a user_id and competency_id');

        $table->queue_multiple_for_aggregation($to_queue);
    }

    /**
     * Test queue_all_assigned_users_for_aggregation
     */
    public function test_queue_all_assigned_users_for_aggregation() {
        global $DB;

        advanced_feature::enable('competency_assignment');

        $sink = $this->redirectEvents();
        // The assignment table's foreign keys require us to create some actual competencies and users

        $competency_generator = \totara_competency\testing\generator::instance();
        $assignment_generator = $competency_generator->assignment_generator();

        $competencies = [];
        $competencies[1] = $competency_generator->create_competency();
        $competencies[2] = $competency_generator->create_competency();
        $competencies[3] = $competency_generator->create_competency();

        $users = [];
        $user_ids_all = [];
        $user_ids_some = [];
        $assignment_ids = [];
        for ($i = 1; $i < 10; $i++) {
            $users[$i] = $this->getDataGenerator()->create_user();
            $user_ids_all[] = $users[$i]->id;
            $assignment = $assignment_generator->create_user_assignment($competencies[1]->id, $users[$i]->id);
            $assignment_ids[] = $assignment->id;

            if ($i % 2) {
                $user_ids_some[] = $users[$i]->id;
                $assignment = $assignment_generator->create_user_assignment($competencies[2]->id, $users[$i]->id);
                $assignment_ids[] = $assignment->id;
            }

            $assignment = $assignment_generator->create_user_assignment($competencies[3]->id, $users[$i]->id);
            $assignment_ids[] = $assignment->id;
        }

        (new assignment_actions())->activate($assignment_ids);
        (new expand_task($DB))->expand_all();

        $user_table = new aggregation_users_table();

        // Now for the tests

        // Start with an empty queue - add all users assigned to competency2
        $user_table->queue_all_assigned_users_for_aggregation($competencies[2]->id);

        $sql =
            "SELECT id, {$user_table->get_user_id_column()}
               FROM {{$user_table->get_table_name()}}
              WHERE {$user_table->get_competency_id_column()} = :compid
                AND {$user_table->get_process_key_column()} IS NULL";

        $actual_user_ids = $DB->get_records_sql_menu($sql, ['compid' => $competencies[2]->id]);
        $this->assertEqualsCanonicalizing($user_ids_some, $actual_user_ids);

        $actual_user_ids = $DB->get_records_sql_menu($sql, ['compid' => $competencies[1]->id]);
        $this->assertEmpty($actual_user_ids);

        // First manually add some rows for competency1
        for ($i = 1; $i <= 3; $i++) {
            $user_table->queue_for_aggregation($users[$i]->id, $competencies[1]->id);
        }

        // Now test that each user appear only once
        $user_table->queue_all_assigned_users_for_aggregation($competencies[1]->id);
        $actual_user_ids = $DB->get_records_sql_menu($sql, ['compid' => $competencies[1]->id]);
        $this->assertSame(count($user_ids_all), count($actual_user_ids));
        $this->assertEqualsCanonicalizing($user_ids_all, $actual_user_ids);


        // Specify a has_changed valued when queuing

        // First manually add some rows for competency3 and set the has_changed field for some
        for ($i = 1; $i <= 5; $i++) {
            $user_table->queue_for_aggregation($users[$i]->id, $competencies[3]->id, $i % 2);
        }

        $sql = "UPDATE {{$user_table->get_table_name()}}
                   SET {$user_table->get_process_key_column()} = :key
                 WHERE {$user_table->get_competency_id_column()} = :compid
                   AND {$user_table->get_user_id_column()} = :userid";
        $params = [
            'key' => 'sometest',
            'compid' => $competencies[3]->id,
            'userid' => $users[2]->id,
        ];
        $DB->execute($sql, $params);

        $params = [
            $user_table->get_competency_id_column() => $competencies[3]->id,
            $user_table->get_has_changed_column() => 0,
            $user_table->get_process_key_column() => 'sometest',
        ];
        $this->assertSame(1, $DB->count_records($user_table->get_table_name(), $params));

        // Now queue and test that each user appear only once. Also ensure that records with a process_key is not updated
        $user_table->queue_all_assigned_users_for_aggregation($competencies[3]->id, 1);

        $sql =
            "SELECT id, {$user_table->get_user_id_column()}
               FROM {{$user_table->get_table_name()}}
              WHERE {$user_table->get_competency_id_column()} = :compid
                AND {$user_table->get_has_changed_column()} = :haschanged
                AND {$user_table->get_process_key_column()} IS NULL";
        $params = ['compid' => $competencies[3]->id, 'haschanged' => 1];

        $actual_user_ids = $DB->get_records_sql_menu($sql, $params);
        $this->assertSame(count($user_ids_all), count($actual_user_ids));
        $this->assertEqualsCanonicalizing($user_ids_all, $actual_user_ids);

        $params = [
            $user_table->get_competency_id_column() => $competencies[3]->id,
            $user_table->get_has_changed_column() => 0,
        ];
        $this->assertSame(1, $DB->count_records($user_table->get_table_name(), $params));

        // Now queue without specifying a has_changed value. Nothing should be changed in the table
        $user_table->queue_all_assigned_users_for_aggregation($competencies[3]->id);

        $sql =
            "SELECT id, {$user_table->get_user_id_column()}
               FROM {{$user_table->get_table_name()}}
              WHERE {$user_table->get_competency_id_column()} = :compid
                AND {$user_table->get_has_changed_column()} = :haschanged
                AND {$user_table->get_process_key_column()} IS NULL";
        $params = ['compid' => $competencies[3]->id, 'haschanged' => 1];

        $actual_user_ids = $DB->get_records_sql_menu($sql, $params);
        $this->assertSame(count($user_ids_all), count($actual_user_ids));
        $this->assertEqualsCanonicalizing($user_ids_all, $actual_user_ids);

        $params = [
            $user_table->get_competency_id_column() => $competencies[3]->id,
            $user_table->get_has_changed_column() => 0,
        ];
        $this->assertSame(1, $DB->count_records($user_table->get_table_name(), $params));

        // And then queue with an explicit has_changed value of 0 - this should overwrite the existing has_changed value
        $user_table->queue_all_assigned_users_for_aggregation($competencies[3]->id, 0);

        $sql =
            "SELECT id, {$user_table->get_user_id_column()}
               FROM {{$user_table->get_table_name()}}
              WHERE {$user_table->get_competency_id_column()} = :compid
                AND {$user_table->get_has_changed_column()} = :haschanged
                AND {$user_table->get_process_key_column()} IS NULL";

        $params = ['compid' => $competencies[3]->id, 'haschanged' => 1];
        $actual_user_ids = $DB->get_records_sql_menu($sql, $params);
        $this->assertSame(0, count($actual_user_ids));

        $params = ['compid' => $competencies[3]->id, 'haschanged' => 0];
        $actual_user_ids = $DB->get_records_sql_menu($sql, $params);
        $this->assertSame(count($user_ids_all), count($actual_user_ids));

        $this->assertEqualsCanonicalizing($user_ids_all, $actual_user_ids);

        $params = [
            $user_table->get_competency_id_column() => $competencies[3]->id,
            $user_table->get_has_changed_column() => 0,
            $user_table->get_process_key_column() => 'sometest',
        ];
        $this->assertSame(1, $DB->count_records($user_table->get_table_name(), $params));


        $sink->close();
    }

    public function test_has_pending_aggregation(): void {
        $data = $this->setup_data();

        self::assertTrue($data->tbl->has_pending_aggregation(1));
        self::assertFalse($data->tbl->has_pending_aggregation(- 1));

        self::assertTrue($data->tbl->has_pending_aggregation(1, 10));
        self::assertFalse($data->tbl->has_pending_aggregation(1, 9));
    }
}
