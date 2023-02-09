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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core
 * @group orm
 */

use core\orm\query\builder;

defined('MOODLE_INTERNAL') || die();

abstract class orm_query_builder_base extends advanced_testcase {

    protected $table_name = 'test__qb';

    protected $another_table_name = 'test__another_qb';

    /**
     * Remove the created table after test
     */
    protected function tearDown(): void {
        parent::tearDown();
        $this->drop_tables();
    }

    /**
     * Date generator shortcut
     *
     * @return \core\testing\generator
     */
    protected function generator() {
        return self::getDataGenerator();
    }

    /**
     * @return \moodle_database
     */
    protected function db() {
        return $GLOBALS['DB'];
    }

    /**
     * @return \database_manager
     */
    protected function db_man() {
        return $this->db()->get_manager();
    }

    /**
     * Create sample table for the entity
     */
    protected function create_table() {


        if ($this->db_man()->table_exists($this->table_name)) {
            return;
        }

        $table = new xmldb_table($this->table_name);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('parent_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('is_deleted', XMLDB_TYPE_INTEGER, '1');
        $table->add_field('params', XMLDB_TYPE_TEXT);

        $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null);
        $table->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $this->db_man()->create_table($table);
    }

    /**
     * Create sample table for the entity
     */
    protected function create_another_table() {


        if ($this->db_man()->table_exists($this->another_table_name)) {
            return;
        }

        $table = new xmldb_table($this->another_table_name);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('type', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('parent_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('is_deleted', XMLDB_TYPE_INTEGER, '1');
        $table->add_field('params', XMLDB_TYPE_TEXT);

        $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null);
        $table->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $this->db_man()->create_table($table);
    }

    /**
     * One sample record
     *
     * @param array $attributes Override attributes
     * @return array
     */
    protected function sample_record(array $attributes = []) {
        return array_merge([
            'name' => 'John',
            'type' => 0,
            'parent_id' => 0,
            'is_deleted' => 0,
            'params' => '{ status: true, count: 69 }',
            'created_at' => '1544500654',
            'updated_at' => '1544500654',
        ], $attributes);
    }

    /**
     * Populate database with one sample record
     *
     * @param array $attributes Override attributes
     * @return array
     */
    protected function create_sample_record(array $attributes = []) {

        $this->create_table();
        $record = $this->sample_record($attributes);
        $record['id'] = $this->db()->insert_record($this->table_name, (object) $record);

        return $record;
    }

    /**
     * Returns a set of sample records to test against
     *
     * @return array
     */
    protected function sample_records() {
        return [
            [
                'name' => 'John',
                'type' => 0,
                'parent_id' => 0,
                'is_deleted' => 0,
                'params' => '{ status: true, count: 69 }',
                'created_at' => '1544500654',
                'updated_at' => '1544500654',
            ],
            [
                'name' => 'Jane',
                'type' => 1,
                'parent_id' => 0,
                'is_deleted' => 0,
                'params' => '{ status: false, count: 96 }',
                'created_at' => '1544500754',
                'updated_at' => '1544500765',
            ],
            [
                'name' => 'Peter',
                'type' => 0,
                'parent_id' => 2,
                'is_deleted' => 1,
                'params' => '{ status: true, count: 138 }',
                'created_at' => '1544500854',
                'updated_at' => '1544500865',
            ],
            [
                'name' => 'Basil',
                'type' => 2,
                'parent_id' => 1,
                'is_deleted' => 0,
                'params' => '{ status: false, count: 165 }',
                'created_at' => '1544500954',
                'updated_at' => '1544500965',
            ],
            [
                'name' => 'Roxanne',
                'type' => 2,
                'parent_id' => 1,
                'is_deleted' => 0,
                'params' => '{ status: true, count: 192 }',
                'created_at' => '1544501054',
                'updated_at' => '1544501065',
            ],
        ];
    }

    /**
     * Populate database with a few sample record to test more complex things like sorting, filtering, etc.
     *
     * @return array
     */
    protected function create_sample_records() {

        $this->create_table();
        $records = $this->sample_records();

        foreach ($records as &$record) {
            $record['id'] = $this->db()->insert_record($this->table_name, (object) $record);
        }

        return $records;
    }

    /**
     * Remove the created table after test
     */
    protected function drop_tables() {
        if ($this->db_man()->table_exists($this->table_name)) {
            $this->db_man()->drop_table(new xmldb_table($this->table_name));
        }

        if ($this->db_man()->table_exists($this->another_table_name)) {
            $this->db_man()->drop_table(new xmldb_table($this->another_table_name));
        }
    }

    /**
     * Get a builder instance for easier testing
     *
     * @param string $alias Set an alias for the table
     * @return builder
     */
    protected function new_test_where_builder(string $alias = '') {
        $builder = builder::table($this->table_name)
            ->where_raw('1 = 1');

        if (!empty($alias)) {
            $builder->as($alias);
        }

        return $builder;
    }

}
