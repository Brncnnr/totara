<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

class totara_core_ddl_testcase extends \core_phpunit\database_driver_testcase {
    public function test_unique_nullable_index_one_field() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table = new xmldb_table('test_sessions');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sid', XMLDB_TYPE_CHAR, '255', null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('sid', XMLDB_INDEX_UNIQUE, ['sid']);
        $dbman->create_table($table);

        $DB->insert_record('test_sessions', ['sid' => 'abc123']);
        $this->assertSame(1, $DB->count_records('test_sessions'));

        $DB->insert_record('test_sessions', ['sid' => null]);
        $this->assertSame(2, $DB->count_records('test_sessions'));

        $DB->insert_record('test_sessions', ['sid' => null]);
        $this->assertSame(3, $DB->count_records('test_sessions'));

        try {
            $DB->insert_record('test_sessions', ['sid' => 'abc123']);
            $this->fail('Exception expected when sid non-unique and not null');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertSame(3, $DB->count_records('test_sessions'));

        $dbman->drop_table($table);
    }

    public function test_unique_nullable_index_two_fields() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table = new xmldb_table('test_sessions');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('sid', XMLDB_TYPE_CHAR, '255', null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('sid-courseid', XMLDB_INDEX_UNIQUE, ['sid', 'courseid']);
        $dbman->create_table($table);

        $id1 = $DB->insert_record('test_sessions', (object)['sid' => 'abc123', 'courseid' => 1]);
        $this->assertSame(1, $DB->count_records('test_sessions'));

        $id2 = $DB->insert_record('test_sessions', (object)['sid' => 'abc123', 'courseid' => 2]);
        $this->assertSame(2, $DB->count_records('test_sessions'));

        $id3 = $DB->insert_record('test_sessions', (object)['sid' => 'def456', 'courseid' => 1]);
        $this->assertSame(3, $DB->count_records('test_sessions'));

        $id4 = $DB->insert_record('test_sessions', (object)['sid' => 'abc123', 'courseid' => null]);
        $this->assertSame(4, $DB->count_records('test_sessions'));

        $id5 = $DB->insert_record('test_sessions', (object)['sid' => 'abc123', 'courseid' => null]);
        $this->assertSame(5, $DB->count_records('test_sessions'));

        $id6 = $DB->insert_record('test_sessions', (object)['sid' => null, 'courseid' => 1]);
        $this->assertSame(6, $DB->count_records('test_sessions'));

        $id7 = $DB->insert_record('test_sessions', (object)['sid' => null, 'courseid' => 1]);
        $this->assertSame(7, $DB->count_records('test_sessions'));

        $id8 = $DB->insert_record('test_sessions', (object)['sid' => null, 'courseid' => null]);
        $this->assertSame(8, $DB->count_records('test_sessions'));

        $id9 = $DB->insert_record('test_sessions', (object)['sid' => null, 'courseid' => null]);
        $this->assertSame(9, $DB->count_records('test_sessions'));

        try {
            $DB->insert_record('test_sessions', (object)['sid' => 'abc123', 'courseid' => 1]);
            $this->fail('Exception expected when sid,courseid non-unique');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertSame(9, $DB->count_records('test_sessions'));

        $DB->set_field('test_sessions', 'courseid', null, ['id' => $id2]);

        try {
            $DB->set_field('test_sessions', 'courseid', 1, ['id' => $id2]);
            $this->fail('Exception expected when sid,courseid non-unique');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }

        $DB->set_field('test_sessions', 'sid', null, ['id' => $id2]);

        $DB->set_field('test_sessions', 'courseid', 1, ['id' => $id9]);
        try {
            $DB->set_field('test_sessions', 'sid', 'abc123', ['id' => $id9]);
            $this->fail('Exception expected when sid,courseid non-unique');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }

        $dbman->drop_table($table);
    }

    public function test_find_key_name() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table1 = new xmldb_table('test_course');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table1);

        $table2 = new xmldb_table('test_other2');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table2->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('courseid', XMLDB_KEY_UNIQUE, ['courseid'], 'test_course', ['id']);
        $dbman->create_table($table2);

        $table3 = new xmldb_table('test_other3');
        $table3->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table3->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table3->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table3->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table3->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id']);
        $dbman->create_table($table3);

        $table4 = new xmldb_table('test_other4');
        $table4->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table4->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table4->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table4->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table4->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'restrict');
        $dbman->create_table($table4);

        $table5 = new xmldb_table('test_other5');
        $table5->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table5->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table5->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table5->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table5->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'cascade');
        $dbman->create_table($table5);

        $table6 = new xmldb_table('test_other6');
        $table6->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table6->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table6->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table6->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table6->add_key('courseid', XMLDB_KEY_FOREIGN_UNIQUE, ['courseid'], 'test_course', ['id'], 'restrict');
        $dbman->create_table($table6);

        $table7 = new xmldb_table('test_other7');
        $table7->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table7->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table7->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table7->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table7->add_key('courseid', XMLDB_KEY_FOREIGN_UNIQUE, ['courseid'], 'test_course', ['id'], 'cascade');
        $dbman->create_table($table7);

        $table8 = new xmldb_table('test_other8');
        $table8->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table8->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table8->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table8->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table8->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'setnull');
        $dbman->create_table($table8);

        $table9 = new xmldb_table('test_other9');
        $table9->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table9->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table9->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table9->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table9->add_key('courseid', XMLDB_KEY_FOREIGN_UNIQUE, ['courseid'], 'test_course', ['id'], 'setnull');
        $dbman->create_table($table9);

        $table10 = new xmldb_table('test_other10');
        $table10->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table10->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table10->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table10->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table10->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], null, 'cascade');
        $dbman->create_table($table10);

        $table11 = new xmldb_table('test_other11');
        $table11->add_field('id', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table11->add_field('courseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0');
        $table11->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table11->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table11->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], null, 'restrict');
        $dbman->create_table($table11);

        $table12 = new xmldb_table('test_other12');
        $table12->add_field('id', XMLDB_TYPE_INTEGER, '12', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table12->add_field('courseid', XMLDB_TYPE_INTEGER, '12', null, null, null, '0');
        $table12->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table12->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table12->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], null, 'setnull');
        $dbman->create_table($table12);

        $this->assertFalse($dbman->find_key_name($table1, $table1->getKey('primary')));
        $this->assertFalse($dbman->find_key_name($table2, $table2->getKey('courseid')));
        $this->assertFalse($dbman->find_key_name($table3, $table3->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table4, $table4->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table5, $table5->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table6, $table6->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table7, $table7->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table8, $table8->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table9, $table9->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table10, $table9->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table11, $table9->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table10, $table9->getKey('courseid')));

        // Now the unexpected matches.

        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table4, $table5->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table4, $table6->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table4, $table7->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table4, $table8->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table4, $table9->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table4, $table10->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table4, $table11->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table4, $table12->getKey('courseid')));

        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table5, $table4->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table5, $table6->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table5, $table7->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table5, $table8->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table5, $table9->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table5, $table10->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table5, $table11->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table5, $table12->getKey('courseid')));

        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table6, $table5->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table6, $table4->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table6, $table7->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table6, $table8->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table6, $table9->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table6, $table10->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table6, $table11->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table6, $table12->getKey('courseid')));

        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table7, $table5->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table7, $table6->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table7, $table4->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table7, $table8->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table7, $table9->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table7, $table10->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table7, $table11->getKey('courseid')));
        $this->assertMatchesRegularExpression('/_fk$/', $dbman->find_key_name($table7, $table12->getKey('courseid')));

        $dbman->drop_table($table12);
        $dbman->drop_table($table11);
        $dbman->drop_table($table10);
        $dbman->drop_table($table9);
        $dbman->drop_table($table8);
        $dbman->drop_table($table7);
        $dbman->drop_table($table6);
        $dbman->drop_table($table5);
        $dbman->drop_table($table4);
        $dbman->drop_table($table3);
        $dbman->drop_table($table2);
        $dbman->drop_table($table1);
    }

    public function test_key_exists() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table1 = new xmldb_table('test_course');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table1);

        $table2 = new xmldb_table('test_other2');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table2->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('courseid', XMLDB_KEY_UNIQUE, ['courseid'], 'test_course', ['id']);
        $dbman->create_table($table2);

        $table3 = new xmldb_table('test_other3');
        $table3->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table3->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table3->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table3->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table3->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id']);
        $dbman->create_table($table3);

        $table4 = new xmldb_table('test_other4');
        $table4->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table4->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table4->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table4->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table4->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'restrict');
        $dbman->create_table($table4);

        $table5 = new xmldb_table('test_other5');
        $table5->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table5->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table5->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table5->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table5->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'cascade');
        $dbman->create_table($table5);

        $table6 = new xmldb_table('test_other6');
        $table6->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table6->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table6->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table6->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table6->add_key('courseid', XMLDB_KEY_FOREIGN_UNIQUE, ['courseid'], 'test_course', ['id'], 'restrict');
        $dbman->create_table($table6);

        $table7 = new xmldb_table('test_other7');
        $table7->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table7->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table7->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table7->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table7->add_key('courseid', XMLDB_KEY_FOREIGN_UNIQUE, ['courseid'], 'test_course', ['id'], 'cascade');
        $dbman->create_table($table7);

        $table8 = new xmldb_table('test_other8');
        $table8->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table8->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table8->add_field('name', XMLDB_TYPE_CHAR, '288', null, XMLDB_NOTNULL, null);
        $table8->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table8->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'restrict', 'restrict');
        $dbman->create_table($table8);

        $table9 = new xmldb_table('test_other9');
        $table9->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table9->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table9->add_field('name', XMLDB_TYPE_CHAR, '299', null, XMLDB_NOTNULL, null);
        $table9->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table9->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], null, 'restrict');
        $dbman->create_table($table9);

        // This one has no primary key
        $table10 = new xmldb_table('test_other10');
        $table10->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table10->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table10->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table10->add_key('courseid', XMLDB_KEY_UNIQUE, ['courseid'], 'test_course', ['id']);
        $dbman->create_table($table10);

        $this->assertTrue($dbman->key_exists($table1, $table1->getKey('primary')));
        $this->assertTrue($dbman->key_exists($table2, $table2->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table3, $table3->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table4, $table4->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table5, $table5->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table6, $table6->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table7, $table7->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table8, $table8->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table9, $table9->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table10, $table10->getKey('courseid')));

        $this->assertFalse($dbman->key_exists($table2, $table4->getKey('courseid')));
        $this->assertFalse($dbman->key_exists($table3, $table4->getKey('courseid')));
        $this->assertFalse($dbman->key_exists($table2, $table5->getKey('courseid')));
        $this->assertFalse($dbman->key_exists($table3, $table5->getKey('courseid')));
        $this->assertFalse($dbman->key_exists($table2, $table6->getKey('courseid')));
        $this->assertFalse($dbman->key_exists($table3, $table6->getKey('courseid')));
        $this->assertFalse($dbman->key_exists($table2, $table7->getKey('courseid')));
        $this->assertFalse($dbman->key_exists($table3, $table7->getKey('courseid')));
        $this->assertFalse($dbman->key_exists($table2, $table8->getKey('courseid')));
        $this->assertFalse($dbman->key_exists($table3, $table8->getKey('courseid')));
        $this->assertFalse($dbman->key_exists($table2, $table9->getKey('courseid')));
        $this->assertFalse($dbman->key_exists($table3, $table9->getKey('courseid')));
        $this->assertFalse($dbman->key_exists($table10, $table9->getKey('primary')));

        // Now the unexpected results.

        $this->assertTrue($dbman->key_exists($table3, $table2->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table4, $table2->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table5, $table2->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table6, $table2->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table7, $table2->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table8, $table2->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table9, $table2->getKey('courseid')));

        $this->assertTrue($dbman->key_exists($table2, $table3->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table4, $table3->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table5, $table3->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table6, $table3->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table7, $table3->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table8, $table3->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table9, $table3->getKey('courseid')));

        $this->assertTrue($dbman->key_exists($table4, $table5->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table4, $table6->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table4, $table7->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table4, $table8->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table4, $table9->getKey('courseid')));

        $this->assertTrue($dbman->key_exists($table5, $table4->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table5, $table6->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table5, $table7->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table5, $table8->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table5, $table9->getKey('courseid')));

        $this->assertTrue($dbman->key_exists($table6, $table5->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table6, $table4->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table6, $table7->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table6, $table8->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table6, $table9->getKey('courseid')));

        $this->assertTrue($dbman->key_exists($table7, $table5->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table7, $table6->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table7, $table4->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table7, $table8->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table7, $table9->getKey('courseid')));

        $this->assertTrue($dbman->key_exists($table8, $table5->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table8, $table6->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table8, $table4->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table8, $table7->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table8, $table9->getKey('courseid')));

        $this->assertTrue($dbman->key_exists($table9, $table5->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table9, $table6->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table9, $table4->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table9, $table7->getKey('courseid')));
        $this->assertTrue($dbman->key_exists($table9, $table8->getKey('courseid')));

        $dbman->drop_table($table10);
        $dbman->drop_table($table9);
        $dbman->drop_table($table8);
        $dbman->drop_table($table7);
        $dbman->drop_table($table6);
        $dbman->drop_table($table5);
        $dbman->drop_table($table4);
        $dbman->drop_table($table3);
        $dbman->drop_table($table2);
        $dbman->drop_table($table1);
    }

    public function test_key_unique() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table = new xmldb_table('test_other');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_UNIQUE, ['courseid']);
        $dbman->create_table($table);

        $this->assertSame(0, $DB->count_records('test_other'));

        $record = ['courseid' => 10, 'name' => 'XX'];
        $DB->insert_record('test_other', $record);
        $this->assertSame(1, $DB->count_records('test_other'));

        $record = ['courseid' => 11, 'name' => 'XX'];
        $DB->insert_record('test_other', $record);
        $this->assertSame(2, $DB->count_records('test_other'));

        try {
            $record = ['courseid' => 10, 'name' => 'YY'];
            $DB->insert_record('test_other', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(2, $DB->count_records('test_other'));

        $record = ['courseid' => null, 'name' => 'XX'];
        $DB->insert_record('test_other', $record);
        $this->assertSame(3, $DB->count_records('test_other'));

        $record = ['courseid' => null, 'name' => 'ZZ'];
        $DB->insert_record('test_other', $record);
        $this->assertSame(4, $DB->count_records('test_other'));

        $key = new xmldb_key('courseid', XMLDB_KEY_UNIQUE, ['courseid']);
        $this->assertTrue($dbman->key_exists($table, $key));
        $dbman->drop_key($table, $key);
        $this->assertFalse($dbman->key_exists($table, $key));
        $record = ['courseid' => 10, 'name' => 'YY'];
        $DB->insert_record('test_other', $record);
        $this->assertSame(5, $DB->count_records('test_other'));

        // WARNING: MS SQL server does not like NULLs in unique indexes with multiple columns,
        //          we have a nasty hack for Unique indexes on single nullable columns in DDL.

        $dbman->drop_table($table);
    }

    public function test_foreign_key_ondelete_restrict() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table1 = new xmldb_table('test_course');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table1);

        $table2 = new xmldb_table('test_other');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table2->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'restrict');
        $dbman->create_table($table2);

        $key = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'restrict');
        $this->assertTrue($dbman->key_exists($table2, $key));

        $course1 = (object)['name' => 'XX'];
        $course1->id = $DB->insert_record('test_course', $course1);
        $this->assertSame(1, $DB->count_records('test_course'));

        $course2 = (object)['name' => 'YY'];
        $course2->id = $DB->insert_record('test_course', $course2);
        $this->assertSame(2, $DB->count_records('test_course'));

        $other1 = (object)['name' => 'AA', 'courseid' => $course1->id];
        $other1->id = $DB->insert_record('test_other', $other1);
        $this->assertSame(1, $DB->count_records('test_other'));

        try {
            $other2 = (object)['name' => 'AA', 'courseid' => $course1->id - 10];
            $DB->insert_record('test_other', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other'));
        $this->assertSame(2, $DB->count_records('test_course'));

        try {
            $trans = $DB->start_delegated_transaction();
            $other2 = (object)['name' => 'AA', 'courseid' => $course1->id - 10];
            $DB->insert_record('test_other', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
            $trans->allow_commit();
        }
        $this->assertSame(1, $DB->count_records('test_other'));
        $this->assertSame(2, $DB->count_records('test_course'));

        try {
            $DB->delete_records('test_course', ['id' => $course1->id]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other'));
        $this->assertSame(2, $DB->count_records('test_course'));

        try {
            $trans = $DB->start_delegated_transaction();
            $DB->delete_records('test_course', ['id' => $course1->id]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
            $trans->allow_commit();
        }
        $this->assertSame(1, $DB->count_records('test_other'));
        $this->assertSame(2, $DB->count_records('test_course'));


        $key = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'restrict');
        $dbman->drop_key($table2, $key);
        $this->assertFalse($dbman->key_exists($table2, $key));

        $other2 = (object)['name' => 'AA', 'courseid' => $course1->id - 10];
        $other2->id = $DB->insert_record('test_other', $other2);
        $this->assertSame(2, $DB->count_records('test_other'));
        $this->assertSame(2, $DB->count_records('test_course'));

        $DB->delete_records('test_course', ['id' => $course1->id]);
        $this->assertSame(2, $DB->count_records('test_other'));
        $this->assertSame(1, $DB->count_records('test_course'));

        $dbman->drop_table($table2);
        $dbman->drop_table($table1);
    }

    public function test_foreign_key_ondelete_restrict_null() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table1 = new xmldb_table('test_course');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table1);

        $table2 = new xmldb_table('test_other');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table2->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'restrict');
        $dbman->create_table($table2);

        $key = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'restrict');
        $this->assertTrue($dbman->key_exists($table2, $key));

        $course1 = (object)['name' => 'XX'];
        $course1->id = $DB->insert_record('test_course', $course1);
        $this->assertSame(1, $DB->count_records('test_course'));

        $course2 = (object)['name' => 'YY'];
        $course2->id = $DB->insert_record('test_course', $course2);
        $this->assertSame(2, $DB->count_records('test_course'));

        $other1 = (object)['name' => 'AA', 'courseid' => $course1->id];
        $other1->id = $DB->insert_record('test_other', $other1);
        $this->assertSame(1, $DB->count_records('test_other'));

        try {
            $other2 = (object)['name' => 'AA', 'courseid' => $course1->id - 10];
            $DB->insert_record('test_other', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other'));
        $this->assertSame(2, $DB->count_records('test_course'));

        $other3 = (object)['name' => 'AA', 'courseid' => null];
        $other3->id = $DB->insert_record('test_other', $other3);
        $this->assertSame(2, $DB->count_records('test_other'));
        $this->assertSame(2, $DB->count_records('test_course'));

        $dbman->drop_table($table2);
        $dbman->drop_table($table1);
    }

    public function test_foreign_key_ondelete_setnull() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table1 = new xmldb_table('test_course');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table1);

        $table2 = new xmldb_table('test_other');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table2->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'setnull');
        $dbman->create_table($table2);

        $key = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'setnull');
        $this->assertTrue($dbman->key_exists($table2, $key));

        $course1 = (object)['name' => 'XX'];
        $course1->id = $DB->insert_record('test_course', $course1);
        $this->assertSame(1, $DB->count_records('test_course'));

        $course2 = (object)['name' => 'YY'];
        $course2->id = $DB->insert_record('test_course', $course2);
        $this->assertSame(2, $DB->count_records('test_course'));

        $other1 = (object)['name' => 'AA', 'courseid' => $course1->id];
        $other1->id = $DB->insert_record('test_other', $other1);
        $this->assertSame(1, $DB->count_records('test_other'));

        try {
            $other2 = (object)['name' => 'AA', 'courseid' => $course1->id - 10];
            $DB->insert_record('test_other', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other'));
        $this->assertSame(2, $DB->count_records('test_course'));

        $DB->delete_records('test_course', ['id' => $course1->id]);
        $this->assertSame(1, $DB->count_records('test_other'));
        $this->assertSame(1, $DB->count_records('test_course'));
        $this->assertNull($DB->get_field('test_other', 'courseid', ['id' => $other1->id]));

        $dbman->drop_table($table2);
        $dbman->drop_table($table1);
    }

    public function test_foreign_key_ondelete_cascade() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table1 = new xmldb_table('test_course');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table1);

        $table2 = new xmldb_table('test_other');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table2->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'cascade');
        $dbman->create_table($table2);

        $key = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'cascade');
        $this->assertTrue($dbman->key_exists($table2, $key));

        $course1 = (object)['name' => 'XX'];
        $course1->id = $DB->insert_record('test_course', $course1);
        $this->assertSame(1, $DB->count_records('test_course'));

        $course2 = (object)['name' => 'YY'];
        $course2->id = $DB->insert_record('test_course', $course2);
        $this->assertSame(2, $DB->count_records('test_course'));

        $other1 = (object)['name' => 'AA', 'courseid' => $course1->id];
        $other1->id = $DB->insert_record('test_other', $other1);
        $this->assertSame(1, $DB->count_records('test_other'));

        try {
            $other2 = (object)['name' => 'AA', 'courseid' => $course1->id - 10];
            $DB->insert_record('test_other', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other'));
        $this->assertSame(2, $DB->count_records('test_course'));

        $DB->delete_records('test_course', ['id' => $course1->id]);
        $this->assertSame(0, $DB->count_records('test_other'));
        $this->assertSame(1, $DB->count_records('test_course'));

        $dbman->drop_table($table2);
        $dbman->drop_table($table1);
    }

    public function test_foreign_key_ondelete_cascade_multi() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table1 = new xmldb_table('test_course');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table1);

        $table2 = new xmldb_table('test_other');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table2->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'cascade');
        $dbman->create_table($table2);

        $table3 = new xmldb_table('test_another');
        $table3->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table3->add_field('otherid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table3->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table3->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table3->add_key('otherid', XMLDB_KEY_FOREIGN, ['otherid'], 'test_other', ['id'], 'cascade');
        $dbman->create_table($table3);

        $key = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'test_course', ['id'], 'cascade');
        $this->assertTrue($dbman->key_exists($table2, $key));

        $key = new xmldb_key('otherid', XMLDB_KEY_FOREIGN, ['otherid'], 'test_other', ['id'], 'cascade');
        $this->assertTrue($dbman->key_exists($table3, $key));

        $course1 = (object)['name' => 'XX'];
        $course1->id = $DB->insert_record('test_course', $course1);
        $this->assertSame(1, $DB->count_records('test_course'));

        $course2 = (object)['name' => 'YY'];
        $course2->id = $DB->insert_record('test_course', $course2);
        $this->assertSame(2, $DB->count_records('test_course'));

        $other1 = (object)['name' => 'AA', 'courseid' => $course1->id];
        $other1->id = $DB->insert_record('test_other', $other1);
        $this->assertSame(1, $DB->count_records('test_other'));

        $another1 = (object)['name' => 'AA', 'otherid' => $other1->id];
        $another1->id = $DB->insert_record('test_another', $another1);
        $this->assertSame(1, $DB->count_records('test_another'));

        $DB->delete_records('test_course', ['id' => $course1->id]);
        $this->assertSame(0, $DB->count_records('test_another'));
        $this->assertSame(0, $DB->count_records('test_other'));
        $this->assertSame(1, $DB->count_records('test_course'));

        $dbman->drop_table($table3);
        $dbman->drop_table($table2);
        $dbman->drop_table($table1);
    }

    public function test_foreign_keys_ondelete_file() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $dbman->install_from_xmldb_file(__DIR__ . '/fixtures/xmldb_foreign_keys_ondelete.xml');
        $this->assertTrue($dbman->table_exists('test_course4'));
        $this->assertTrue($dbman->table_exists('test_other4'));

        // Test "foreign" + "restrict".

        $course1 = (object)['name' => 'XX'];
        $course1->id = $DB->insert_record('test_course1', $course1);
        $this->assertSame(1, $DB->count_records('test_course1'));

        $course2 = (object)['name' => 'YY'];
        $course2->id = $DB->insert_record('test_course1', $course2);
        $this->assertSame(2, $DB->count_records('test_course1'));

        $other1 = (object)['name' => 'AA', 'courseid' => $course1->id];
        $other1->id = $DB->insert_record('test_other1', $other1);
        $this->assertSame(1, $DB->count_records('test_other1'));

        try {
            $other2 = (object)['name' => 'AA', 'courseid' => $course1->id - 10];
            $DB->insert_record('test_other1', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other1'));
        $this->assertSame(2, $DB->count_records('test_course1'));

        $other3 = (object)['name' => 'AA', 'courseid' => $course1->id];
        $other3->id = $DB->insert_record('test_other1', $other3);
        $this->assertSame(2, $DB->count_records('test_other1'));

        try {
            $DB->delete_records('test_course1', ['id' => $course1->id]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(2, $DB->count_records('test_other1'));
        $this->assertSame(2, $DB->count_records('test_course1'));

        // Test "foreign" + "cascade".

        $course1 = (object)['name' => 'XX'];
        $course1->id = $DB->insert_record('test_course2', $course1);
        $this->assertSame(1, $DB->count_records('test_course2'));

        $course2 = (object)['name' => 'YY'];
        $course2->id = $DB->insert_record('test_course2', $course2);
        $this->assertSame(2, $DB->count_records('test_course2'));

        $other1 = (object)['name' => 'AA', 'courseid' => $course1->id];
        $other1->id = $DB->insert_record('test_other2', $other1);
        $this->assertSame(1, $DB->count_records('test_other2'));

        $other1b = (object)['name' => 'PP', 'courseid' => $course1->id];
        $other1b->id = $DB->insert_record('test_other2b', $other1b);
        $this->assertSame(1, $DB->count_records('test_other2b'));

        try {
            $other2 = (object)['name' => 'YY', 'courseid' => $course1->id - 10];
            $DB->insert_record('test_other2', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other2'));
        $this->assertSame(2, $DB->count_records('test_course2'));

        try {
            $other2b = (object)['name' => 'YY', 'courseid' => $course1->id - 10];
            $DB->insert_record('test_other2b', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other2b'));
        $this->assertSame(2, $DB->count_records('test_course2'));

        $DB->delete_records('test_course2', ['id' => $course1->id]);
        $this->assertSame(0, $DB->count_records('test_other2'));
        $this->assertSame(1, $DB->count_records('test_other2b'));
        $this->assertSame(1, $DB->count_records('test_course2'));
        $this->assertNull($DB->get_field('test_other2b', 'courseid', ['id' => $other1b->id]));

        // Test "foreign-unique" + "restrict".

        $course1 = (object)['name' => 'XX'];
        $course1->id = $DB->insert_record('test_course3', $course1);
        $this->assertSame(1, $DB->count_records('test_course3'));

        $course2 = (object)['name' => 'YY'];
        $course2->id = $DB->insert_record('test_course3', $course2);
        $this->assertSame(2, $DB->count_records('test_course3'));

        $other1 = (object)['name' => 'AA', 'courseid' => $course1->id];
        $other1->id = $DB->insert_record('test_other3', $other1);
        $this->assertSame(1, $DB->count_records('test_other3'));

        try {
            $other2 = (object)['name' => 'AA', 'courseid' => $course1->id - 10];
            $DB->insert_record('test_other3', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other3'));
        $this->assertSame(2, $DB->count_records('test_course3'));

        try {
            $other3 = (object)['name' => 'AA', 'courseid' => $course1->id];
            $DB->insert_record('test_other3', $other3);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other3'));
        $this->assertSame(2, $DB->count_records('test_course3'));

        try {
            $DB->delete_records('test_course3', ['id' => $course1->id]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other3'));
        $this->assertSame(2, $DB->count_records('test_course3'));

        // Test "foreign-unique" + "cascade".

        $course1 = (object)['name' => 'XX'];
        $course1->id = $DB->insert_record('test_course4', $course1);
        $this->assertSame(1, $DB->count_records('test_course4'));

        $course2 = (object)['name' => 'YY'];
        $course2->id = $DB->insert_record('test_course4', $course2);
        $this->assertSame(2, $DB->count_records('test_course4'));

        $other1 = (object)['name' => 'AA', 'courseid' => $course1->id];
        $other1->id = $DB->insert_record('test_other4', $other1);
        $this->assertSame(1, $DB->count_records('test_other4'));

        $other1b = (object)['name' => 'PP', 'courseid' => $course1->id];
        $other1b->id = $DB->insert_record('test_other4b', $other1b);
        $this->assertSame(1, $DB->count_records('test_other4b'));

        try {
            $other2 = (object)['name' => 'AA', 'courseid' => $course1->id - 10];
            $DB->insert_record('test_other4', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other4'));
        $this->assertSame(2, $DB->count_records('test_course4'));

        try {
            $other2b = (object)['name' => 'AA', 'courseid' => $course1->id - 10];
            $DB->insert_record('test_other4b', $other2b);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other4b'));
        $this->assertSame(2, $DB->count_records('test_course4'));

        try {
            $other3 = (object)['name' => 'AA', 'courseid' => $course1->id];
            $DB->insert_record('test_other4', $other3);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other4'));
        $this->assertSame(2, $DB->count_records('test_course4'));

        try {
            $other3b = (object)['name' => 'PP', 'courseid' => $course1->id];
            $DB->insert_record('test_other4b', $other3b);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other4b'));
        $this->assertSame(2, $DB->count_records('test_course4'));

        $DB->delete_records('test_course4', ['id' => $course1->id]);
        $this->assertSame(0, $DB->count_records('test_other4'));
        $this->assertSame(1, $DB->count_records('test_other4b'));
        $this->assertSame(1, $DB->count_records('test_course4'));
        $this->assertNull($DB->get_field('test_other4b', 'courseid', ['id' => $other1b->id]));


        $dbman->drop_table(new xmldb_table('test_other1'));
        $dbman->drop_table(new xmldb_table('test_course1'));
        $dbman->drop_table(new xmldb_table('test_other2'));
        $dbman->drop_table(new xmldb_table('test_other2b'));
        $dbman->drop_table(new xmldb_table('test_course2'));
        $dbman->drop_table(new xmldb_table('test_other3'));
        $dbman->drop_table(new xmldb_table('test_course3'));
        $dbman->drop_table(new xmldb_table('test_other4'));
        $dbman->drop_table(new xmldb_table('test_other4b'));
        $dbman->drop_table(new xmldb_table('test_course4'));
    }

    public function test_foreign_key_non_id() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table1 = new xmldb_table('test_sessions');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('sid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table1->add_index('sid', XMLDB_INDEX_UNIQUE, ['sid']);
        $dbman->create_table($table1);

        $table2 = new xmldb_table('test_other');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('sid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('sid', XMLDB_KEY_FOREIGN_UNIQUE, ['sid'], 'test_sessions', ['sid'], 'cascade');
        $dbman->create_table($table2);

        $key = new xmldb_key('sid', XMLDB_KEY_FOREIGN_UNIQUE, ['sid'], 'test_sessions', ['sid'], 'cascade');
        $this->assertTrue($dbman->key_exists($table2, $key));

        $session1 = (object)['sid' => 'abc123'];
        $session1->id = $DB->insert_record('test_sessions', $session1);
        $this->assertSame(1, $DB->count_records('test_sessions'));

        $session2 = (object)['sid' => 'def456'];
        $session2->id = $DB->insert_record('test_sessions', $session2);
        $this->assertSame(2, $DB->count_records('test_sessions'));

        $other1 = (object)['name' => 'AA', 'sid' => $session1->sid];
        $other1->id = $DB->insert_record('test_other', $other1);
        $this->assertSame(1, $DB->count_records('test_other'));

        try {
            $other2 = (object)['name' => 'AA', 'sid' => 'xyz'];
            $DB->insert_record('test_other', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other'));

        $DB->delete_records('test_sessions', ['sid' => $session1->sid]);
        $this->assertSame(1, $DB->count_records('test_sessions'));
        $this->assertSame(0, $DB->count_records('test_other'));

        $dbman->drop_table($table2);
        $dbman->drop_table($table1);
    }

    public function test_foreign_key_onupdate_restrict() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table1 = new xmldb_table('test_sessions');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('sid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table1->add_index('sid', XMLDB_INDEX_UNIQUE, ['sid']);
        $dbman->create_table($table1);

        $table2 = new xmldb_table('test_other');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('sid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('sid', XMLDB_KEY_FOREIGN_UNIQUE, ['sid'], 'test_sessions', ['sid'], null, 'restrict');
        $dbman->create_table($table2);

        $key = new xmldb_key('sid', XMLDB_KEY_FOREIGN_UNIQUE, ['sid'], 'test_sessions', ['sid'], null, 'restrict');
        $this->assertTrue($dbman->key_exists($table2, $key));

        $session1 = (object)['sid' => 'abc123'];
        $session1->id = $DB->insert_record('test_sessions', $session1);
        $this->assertSame(1, $DB->count_records('test_sessions'));

        $session2 = (object)['sid' => 'def456'];
        $session2->id = $DB->insert_record('test_sessions', $session2);
        $this->assertSame(2, $DB->count_records('test_sessions'));

        $other1 = (object)['name' => 'AA', 'sid' => $session1->sid];
        $other1->id = $DB->insert_record('test_other', $other1);
        $this->assertSame(1, $DB->count_records('test_other'));

        try {
            $other2 = (object)['name' => 'AA', 'sid' => 'xyz'];
            $DB->insert_record('test_other', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other'));

        try {
            $session1->sid = 'grrr';
            $DB->update_record('test_sessions', $session1);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame('abc123', $DB->get_field('test_sessions', 'sid', ['id' => $session1->id]));

        try {
            $DB->delete_records('test_sessions', ['id' => $session1->id]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(2, $DB->count_records('test_sessions'));
        $this->assertSame(1, $DB->count_records('test_other'));

        $dbman->drop_table($table2);
        $dbman->drop_table($table1);
    }

    public function test_foreign_key_onupdate_cascade() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table1 = new xmldb_table('test_sessions');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('sid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table1->add_index('sid', XMLDB_INDEX_UNIQUE, ['sid']);
        $dbman->create_table($table1);

        $table2 = new xmldb_table('test_other');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('sid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('sid', XMLDB_KEY_FOREIGN_UNIQUE, ['sid'], 'test_sessions', ['sid'], null, 'cascade');
        $dbman->create_table($table2);

        $key = new xmldb_key('sid', XMLDB_KEY_FOREIGN_UNIQUE, ['sid'], 'test_sessions', ['sid'], null, 'cascade');
        $this->assertTrue($dbman->key_exists($table2, $key));

        $session1 = (object)['sid' => 'abc123'];
        $session1->id = $DB->insert_record('test_sessions', $session1);
        $this->assertSame(1, $DB->count_records('test_sessions'));

        $session2 = (object)['sid' => 'def456'];
        $session2->id = $DB->insert_record('test_sessions', $session2);
        $this->assertSame(2, $DB->count_records('test_sessions'));

        $other1 = (object)['name' => 'AA', 'sid' => $session1->sid];
        $other1->id = $DB->insert_record('test_other', $other1);
        $this->assertSame(1, $DB->count_records('test_other'));

        try {
            $other2 = (object)['name' => 'AA', 'sid' => 'xyz'];
            $DB->insert_record('test_other', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other'));

        $session1->sid = 'grrr';
        $DB->update_record('test_sessions', $session1);
        $this->assertSame($session1->sid, $DB->get_field('test_sessions', 'sid', ['id' => $session1->id]));
        $this->assertSame(2, $DB->count_records('test_sessions'));
        $this->assertSame(1, $DB->count_records('test_other'));

        try {
            $DB->delete_records('test_sessions', ['id' => $session1->id]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(2, $DB->count_records('test_sessions'));
        $this->assertSame(1, $DB->count_records('test_other'));

        $dbman->drop_table($table2);
        $dbman->drop_table($table1);
    }

    public function test_foreign_key_onupdate_cascade_ondelete_cascade() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table1 = new xmldb_table('test_sessions');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('sid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table1->add_index('sid', XMLDB_INDEX_UNIQUE, ['sid']);
        $dbman->create_table($table1);

        $table2 = new xmldb_table('test_other');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('sid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('sid', XMLDB_KEY_FOREIGN_UNIQUE, ['sid'], 'test_sessions', ['sid'], 'cascade', 'cascade');
        $dbman->create_table($table2);

        $key = new xmldb_key('sid', XMLDB_KEY_FOREIGN_UNIQUE, ['sid'], 'test_sessions', ['sid'], 'cascade', 'cascade');
        $this->assertTrue($dbman->key_exists($table2, $key));

        $session1 = (object)['sid' => 'abc123'];
        $session1->id = $DB->insert_record('test_sessions', $session1);
        $this->assertSame(1, $DB->count_records('test_sessions'));

        $session2 = (object)['sid' => 'def456'];
        $session2->id = $DB->insert_record('test_sessions', $session2);
        $this->assertSame(2, $DB->count_records('test_sessions'));

        $other1 = (object)['name' => 'AA', 'sid' => $session1->sid];
        $other1->id = $DB->insert_record('test_other', $other1);
        $this->assertSame(1, $DB->count_records('test_other'));

        try {
            $other2 = (object)['name' => 'AA', 'sid' => 'xyz'];
            $DB->insert_record('test_other', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other'));

        $session1->sid = 'grrr';
        $DB->update_record('test_sessions', $session1);
        $this->assertSame($session1->sid, $DB->get_field('test_sessions', 'sid', ['id' => $session1->id]));
        $this->assertSame(2, $DB->count_records('test_sessions'));
        $this->assertSame(1, $DB->count_records('test_other'));

        $DB->delete_records('test_sessions', ['id' => $session1->id]);
        $this->assertSame(1, $DB->count_records('test_sessions'));
        $this->assertSame(0, $DB->count_records('test_other'));

        $dbman->drop_table($table2);
        $dbman->drop_table($table1);
    }

    public function test_foreign_key_onupdate_setnull() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table1 = new xmldb_table('test_sessions');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('sid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table1->add_index('sid', XMLDB_INDEX_UNIQUE, ['sid']);
        $dbman->create_table($table1);

        $table2 = new xmldb_table('test_other');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('sid', XMLDB_TYPE_CHAR, '255', null, null, null);
        $table2->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_key('sid', XMLDB_KEY_FOREIGN_UNIQUE, ['sid'], 'test_sessions', ['sid'], null, 'setnull');
        $dbman->create_table($table2);

        $key = new xmldb_key('sid', XMLDB_KEY_FOREIGN_UNIQUE, ['sid'], 'test_sessions', ['sid'], null, 'setnull');
        $this->assertTrue($dbman->key_exists($table2, $key));

        $session1 = (object)['sid' => 'abc123'];
        $session1->id = $DB->insert_record('test_sessions', $session1);
        $this->assertSame(1, $DB->count_records('test_sessions'));

        $session2 = (object)['sid' => 'def456'];
        $session2->id = $DB->insert_record('test_sessions', $session2);
        $this->assertSame(2, $DB->count_records('test_sessions'));

        $other1 = (object)['name' => 'AA', 'sid' => $session1->sid];
        $other1->id = $DB->insert_record('test_other', $other1);
        $this->assertSame(1, $DB->count_records('test_other'));

        try {
            $other2 = (object)['name' => 'AA', 'sid' => 'xyz'];
            $DB->insert_record('test_other', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other'));

        $session1->sid = 'grrr';
        $DB->update_record('test_sessions', $session1);
        $this->assertNull(null, $DB->get_field('test_sessions', 'sid', ['id' => $session1->id]));
        $this->assertSame(2, $DB->count_records('test_sessions'));
        $this->assertSame(1, $DB->count_records('test_other'));

        $DB->delete_records('test_sessions', ['id' => $session1->id]);
        $this->assertSame(1, $DB->count_records('test_sessions'));
        $this->assertSame(1, $DB->count_records('test_other'));

        $dbman->drop_table($table2);
        $dbman->drop_table($table1);
    }
    public function test_foreign_keys_onupdate_file() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $dbman->install_from_xmldb_file(__DIR__ . '/fixtures/xmldb_foreign_keys_onupdate.xml');
        $this->assertTrue($dbman->table_exists('test_sessions'));
        $this->assertTrue($dbman->table_exists('test_other1'));
        $this->assertTrue($dbman->table_exists('test_other2'));
        $this->assertTrue($dbman->table_exists('test_other3'));
        $this->assertTrue($dbman->table_exists('test_other4'));

        // Test "foreign" + "restrict".

        $session1 = (object)['sid' => 'abc123'];
        $session1->id = $DB->insert_record('test_sessions', $session1);
        $this->assertSame(1, $DB->count_records('test_sessions'));

        $session2 = (object)['sid' => 'def456'];
        $session2->id = $DB->insert_record('test_sessions', $session2);
        $this->assertSame(2, $DB->count_records('test_sessions'));

        $other1 = (object)['name' => 'AA', 'sid' => $session1->sid];
        $other1->id = $DB->insert_record('test_other1', $other1);
        $this->assertSame(1, $DB->count_records('test_other1'));

        try {
            $other2 = (object)['name' => 'AA', 'sid' => 'xyz'];
            $DB->insert_record('test_other1', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other1'));

        try {
            $session1->sid = 'grrr';
            $DB->update_record('test_sessions', $session1);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame('abc123', $DB->get_field('test_sessions', 'sid', ['id' => $session1->id]));

        try {
            $DB->delete_records('test_sessions', ['id' => $session1->id]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(2, $DB->count_records('test_sessions'));
        $this->assertSame(1, $DB->count_records('test_other1'));

        $DB->delete_records('test_other1');
        $DB->delete_records('test_sessions');

        // "foreign" + "cascade"

        $session1 = (object)['sid' => 'abc123'];
        $session1->id = $DB->insert_record('test_sessions', $session1);
        $this->assertSame(1, $DB->count_records('test_sessions'));

        $session2 = (object)['sid' => 'def456'];
        $session2->id = $DB->insert_record('test_sessions', $session2);
        $this->assertSame(2, $DB->count_records('test_sessions'));

        $other1 = (object)['name' => 'AA', 'sid' => $session1->sid];
        $other1->id = $DB->insert_record('test_other2', $other1);
        $this->assertSame(1, $DB->count_records('test_other2'));

        try {
            $other2 = (object)['name' => 'AA', 'sid' => 'xyz'];
            $DB->insert_record('test_other2', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other2'));

        $session1->sid = 'grrr';
        $DB->update_record('test_sessions', $session1);
        $this->assertSame($session1->sid, $DB->get_field('test_sessions', 'sid', ['id' => $session1->id]));
        $this->assertSame(2, $DB->count_records('test_sessions'));
        $this->assertSame(1, $DB->count_records('test_other2'));

        try {
            $DB->delete_records('test_sessions', ['id' => $session1->id]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(2, $DB->count_records('test_sessions'));
        $this->assertSame(1, $DB->count_records('test_other2'));

        $DB->delete_records('test_other2');
        $DB->delete_records('test_sessions');

        // "foreign" + "setnull"

        $session1 = (object)['sid' => 'abc123'];
        $session1->id = $DB->insert_record('test_sessions', $session1);
        $this->assertSame(1, $DB->count_records('test_sessions'));

        $session2 = (object)['sid' => 'def456'];
        $session2->id = $DB->insert_record('test_sessions', $session2);
        $this->assertSame(2, $DB->count_records('test_sessions'));

        $other1 = (object)['name' => 'AA', 'sid' => $session1->sid];
        $other1->id = $DB->insert_record('test_other3', $other1);
        $this->assertSame(1, $DB->count_records('test_other3'));

        try {
            $other2 = (object)['name' => 'AA', 'sid' => 'xyz'];
            $DB->insert_record('test_other3', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other3'));

        $session1->sid = 'grrr';
        $DB->update_record('test_sessions', $session1);
        $this->assertNull(null, $DB->get_field('test_sessions', 'sid', ['id' => $session1->id]));
        $this->assertSame(2, $DB->count_records('test_sessions'));
        $this->assertSame(1, $DB->count_records('test_other3'));

        $DB->delete_records('test_sessions', ['id' => $session1->id]);
        $this->assertSame(1, $DB->count_records('test_sessions'));
        $this->assertSame(1, $DB->count_records('test_other3'));

        $DB->delete_records('test_other3');
        $DB->delete_records('test_sessions');

        // "foreign" + "cascade" + "cascade"

        $session1 = (object)['sid' => 'abc123'];
        $session1->id = $DB->insert_record('test_sessions', $session1);
        $this->assertSame(1, $DB->count_records('test_sessions'));

        $session2 = (object)['sid' => 'def456'];
        $session2->id = $DB->insert_record('test_sessions', $session2);
        $this->assertSame(2, $DB->count_records('test_sessions'));

        $other1 = (object)['name' => 'AA', 'sid' => $session1->sid];
        $other1->id = $DB->insert_record('test_other4', $other1);
        $this->assertSame(1, $DB->count_records('test_other4'));

        try {
            $other2 = (object)['name' => 'AA', 'sid' => 'xyz'];
            $DB->insert_record('test_other4', $other2);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('test_other4'));

        $session1->sid = 'grrr';
        $DB->update_record('test_sessions', $session1);
        $this->assertSame($session1->sid, $DB->get_field('test_sessions', 'sid', ['id' => $session1->id]));
        $this->assertSame(2, $DB->count_records('test_sessions'));
        $this->assertSame(1, $DB->count_records('test_other4'));

        $DB->delete_records('test_sessions', ['id' => $session1->id]);
        $this->assertSame(1, $DB->count_records('test_sessions'));
        $this->assertSame(0, $DB->count_records('test_other4'));

        $dbman->drop_table(new xmldb_table('test_other4'));
        $dbman->drop_table(new xmldb_table('test_other3'));
        $dbman->drop_table(new xmldb_table('test_other2'));
        $dbman->drop_table(new xmldb_table('test_other1'));
        $dbman->drop_table(new xmldb_table('test_sessions'));
    }

    public function test_foreign_keys_deferred_install() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $dbman->install_from_xmldb_file(__DIR__ . '/fixtures/xmldb_foreign_keys_deferred_install.xml');
        $this->assertTrue($dbman->table_exists('test_two'));

        $table1 = new xmldb_table('test_one');
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table1->add_field('twoid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table1->add_key('twoid', XMLDB_KEY_FOREIGN, ['twoid'], 'test_two', ['id'], 'restrict');

        $table2 = new xmldb_table('test_two');
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $this->assertTrue($dbman->table_exists($table1));
        $this->assertTrue($dbman->table_exists($table2));

        $this->assertfalse($dbman->key_exists($table1, $table1->getKey('twoid')));

        $dbman->add_key($table1, $table1->getKey('twoid'));

        $this->assertTrue($dbman->key_exists($table1, $table1->getKey('twoid')));

        $oneid1 = $DB->insert_record('test_one', ['name' => 'a']);
        $oneid2 = $DB->insert_record('test_one', ['name' => 'b']);
        $oneid3 = $DB->insert_record('test_one', ['name' => 'b']);

        $twoid1 = $DB->insert_record('test_two', ['name' => 'x', 'oneid' => $oneid1]);
        $DB->set_field('test_one', 'twoid', $twoid1, ['id' => $oneid1]);
        $twoid2 = $DB->insert_record('test_two', ['name' => 'y', 'oneid' => $oneid2]);
        $DB->set_field('test_one', 'twoid', $twoid2, ['id' => $oneid2]);
        $twoid3 = $DB->insert_record('test_two', ['name' => 'z', 'oneid' => $oneid3]);
        $DB->set_field('test_one', 'twoid', $twoid3, ['id' => $oneid3]);

        $DB->delete_records('test_one', ['id' => $oneid1]);
        $DB->set_field('test_one', 'twoid', null, ['id' => $oneid2]);
        $DB->delete_records('test_two', ['id' => $twoid2]);

        $this->assertSame(2, $DB->count_records('test_one'));
        $this->assertSame(2, $DB->count_records('test_two'));

        $this->assertTrue($DB->record_exists('test_one', ['id' => $oneid2]));
        $this->assertNull($DB->get_field('test_one', 'twoid', ['id' => $oneid2]));
        $this->assertEquals($twoid3, $DB->get_field('test_one', 'twoid', ['id' => $oneid3]));

        $this->assertTrue($DB->record_exists('test_one', ['id' => $oneid3]));
        $this->assertEquals($oneid1, $DB->get_field('test_two', 'oneid', ['id' => $twoid1]));
        $this->assertEquals($oneid3, $DB->get_field('test_two', 'oneid', ['id' => $twoid3]));

        try {
            $DB->delete_records('test_two', ['id' => $twoid3]);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(dml_write_exception::class, $ex);
        }

        $this->assertSame(2, $DB->count_records('test_one'));
        $this->assertSame(2, $DB->count_records('test_two'));

        // The dropping of all tables in PHPUNIT and Behat init works by using DELETE with CASCADE or dropping all keys first in MS SQL.
        $dbman->drop_key($table1, $table1->getKey('twoid'));
        $dbman->drop_table($table2);
        $dbman->drop_table($table1);

        $this->assertFalse($dbman->table_exists($table1));
        $this->assertFalse($dbman->table_exists($table2));
    }

    public function test_change_field_type() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        // Create the dummy table.
        $table = new xmldb_table('test_table');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('changeme', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        // Insert some dummy data.
        $todb = new stdClass();
        $todb->changeme = "1234567890";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = 1237894560;
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "0";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "   0321654987   ";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "-1";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "-123456789";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "123abc789";
        $DB->insert_record('test_table', $todb);
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "abcdefghij";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "abcdefghijklmnopqrstuvwxyz";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "1234567891234567891234569789";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "123 456 789 0";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "";
        $DB->insert_record('test_table', $todb);
        $todb->changeme = "    ";

        $this->assertEquals(13, $DB->count_records('test_table'));

        // Run the checks from the upgrade.
        $records = $DB->get_recordset('test_table');
        foreach ($records as $record) {
            if (!preg_match('/^[0-9]{1,10}$/', $record->changeme)) {
                $DB->delete_records('test_table', array('id' => $record->id));
                continue;
            }
        }
        $records->close();

        // Only the first three should pass the checks.
        $this->assertEquals(3, $DB->count_records('test_table'));

        // Run the field change.
        $field_integer = new xmldb_field('changeme', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');
        $dbman->change_field_type($table, $field_integer);

        // Check the results.
        $this->assertEquals(3, $DB->count_records('test_table'));
        $records = $DB->get_records('test_table');
        $this->assertEquals(1234567890, $records[1]->changeme);
        $this->assertEquals(1237894560, $records[2]->changeme);
        $this->assertEquals(0, $records[3]->changeme);

        $dbman->drop_table($table);
    }

    public function test_create_temp_table_large() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        // Create the dummy table.
        $table = new xmldb_table('test_table');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $prefield = 'id';
        for ($i = 1; $i < 64; $i++) {
            $field = 'varfield' . $i;
            $table->add_field($field, XMLDB_TYPE_CHAR, '255', null, null, null, null, $prefield);
            $prefield = $field;
        }
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_temp_table($table);

        $record = new stdClass();
        for ($i = 1; $i < 80; $i++) {
            $record->{'varfield' . $i} = str_pad('š', 255);
        }
        $DB->insert_record('test_table', $record);

        $dbman->drop_table($table);
    }

    public function test_manage_table_with_reserved_columns() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table = new xmldb_table('test_table');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('from', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);
        $columns = $DB->get_columns($table->getName());
        $this->assertArrayHasKey('from', $columns);

        $table = new xmldb_table('test_table');
        $field = new xmldb_field('from');
        $field->set_attributes(XMLDB_TYPE_CHAR, '20', null, null, null, 'general', 'id');
        $dbman->rename_field($table, $field, 'where');
        $columns = $DB->get_columns($table->getName());
        $this->assertArrayNotHasKey('from', $columns);
        $this->assertArrayHasKey('where', $columns);

        $table = new xmldb_table('test_table');
        $field = new xmldb_field('where');
        $field->set_attributes(XMLDB_TYPE_CHAR, '20', null, null, null, 'general', 'id');
        $dbman->drop_field($table, $field);
        $columns = $DB->get_columns($table->getName());
        $this->assertArrayNotHasKey('from', $columns);
        $this->assertArrayNotHasKey('where', $columns);

        $dbman->drop_table($table);
    }

    public function test_create_search_index() {
        $DB = $this->tdb;
        $dbman = $this->tdb->get_manager();
        $prefix = $DB->get_prefix();

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('low', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('course', XMLDB_INDEX_UNIQUE, array('course'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        $table->add_index('low', XMLDB_INDEX_NOTUNIQUE, array('low'), array('full_text_search'));

        $dbman->create_table($table);
        $this->assertTrue($dbman->table_exists($table));
        $this->assertTrue($dbman->field_exists($table, 'high'));
        $this->assertTrue($dbman->field_exists($table, 'low'));
        $this->assertTrue($dbman->index_exists($table, new xmldb_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), 'full_text_search')));
        $this->assertTrue($dbman->index_exists($table, new xmldb_index('low', XMLDB_INDEX_NOTUNIQUE, array('low'), 'full_text_search')));

        // Insert some data and perform database specific full text search to make sure
        // it works as expected - case and accent insensitive.

        $ids = [];
        $ids[0] = $DB->insert_record($tablename, array('course' => 10, 'high' => 'IL ÉTAIT UNE FOIS')); // 'once upon a time' in French.
        $ids[1] = $DB->insert_record($tablename, array('course' => 11, 'high' => 'il etait une fois')); // 'once upon a time' in French without accents.
        $ids[2] = $DB->insert_record($tablename, array('course' => 12, 'high' => 'abc def'));

        $this->wait_for_mssql_fts_indexing($tablename);

        if ($DB->get_dbfamily() === 'postgres') {
            // By default PostgreSQL is accent sensitive, you nee to create a new config to make accent insensitive searches,
            // see http://rachbelaid.com/postgres-full-text-search-is-good-enough/

            $ftslanguage = "french";
            $sql = "SELECT t.id, t.course
                      FROM {{$tablename}} t
                     WHERE to_tsvector('$ftslanguage', t.high) @@ plainto_tsquery(:search)
                  ORDER BY t.id";
            $params = array('search' => 'ETait');

        } else if ($DB->get_dbfamily() === 'mysql') {
            $sql = "SELECT t.id, t.course
                      FROM {{$tablename}} t
                     WHERE MATCH (t.high) AGAINST (:search IN NATURAL LANGUAGE MODE)
                  ORDER BY t.id";
            $params = array('search' => 'ETait');

        } else if ($DB->get_dbfamily() === 'mssql') {
            $sql = "SELECT t.id, t.course
                      FROM {{$tablename}} t
                      WHERE FREETEXT(t.high, :search) 
                  ORDER BY t.id";
            $params = array('search' => 'ETait');
        }

        $result = $DB->get_records_sql($sql, $params);

        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertLessThanOrEqual(2, count($result));

        $this->assertArrayHasKey($ids[1], $result);
        if (count($result) == 2) {
            $this->assertArrayHasKey($ids[0], $result);
        }

        $dbman->drop_table($table);
    }

    public function test_create_search_index_from_file() {
        $dbman = $this->tdb->get_manager();

        $tablename = 'test_table_search';

        $dbman->install_from_xmldb_file(__DIR__ . '/fixtures/xmldb_search_table.xml');
        $this->assertTrue($dbman->table_exists($tablename));

        $dbman->drop_table(new xmldb_table($tablename));
    }

    public function test_create_invalid_search_index() {
        $dbman = $this->tdb->get_manager();

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high', 'id'), array('full_text_search'));
        try {
            $dbman->create_table($table);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Full text search index must be over one text field only', $ex->getMessage());
        }

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search', 'xx'));
        try {
            $dbman->create_table($table);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Full text search index must be the only hint', $ex->getMessage());
        }

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('high', XMLDB_INDEX_UNIQUE, array('high'), array('full_text_search'));
        try {
            $dbman->create_table($table);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Full text search index cannot be unique', $ex->getMessage());
        }

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('high', XMLDB_TYPE_CHAR, 255, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        try {
            $dbman->create_table($table);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Full text search index can be used for text fields only', $ex->getMessage());
        }

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        try {
            $dbman->create_table($table);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Full text search index can be used for text fields that allow nulls only', $ex->getMessage());
        }

        $this->assertDebuggingNotCalled();
        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null, 'abc');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        $dbman->create_table($table);
        $this->assertDebuggingCalled('XMLDB has detected one TEXT/BINARY column (high) with some DEFAULT defined. This type of columns cannot have any default value. Please fix it in source (XML and/or upgrade script) to avoid this message to be displayed.');
        $this->resetDebugging();
        $this->assertTrue($dbman->table_exists($tablename));
        $dbman->drop_table($table);
    }

    public function test_add_search_index() {
        $DB = $this->tdb;
        $dbman = $this->tdb->get_manager();

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('low', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('course', XMLDB_INDEX_UNIQUE, array('course'));

        $dbman->create_table($table);
        $this->assertTrue($dbman->table_exists($table));

        $highindex = new xmldb_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        $lowindex = new xmldb_index('high', XMLDB_INDEX_NOTUNIQUE, array('low'), array('full_text_search'));
        $dbman->add_index($table, $highindex);
        $dbman->add_index($table, $lowindex);
        $this->assertTrue($dbman->index_exists($table, $highindex));
        $this->assertTrue($dbman->index_exists($table, $lowindex));

        $dbman->drop_table($table);
    }

    public function test_drop_search_index() {
        $DB = $this->tdb;
        $dbman = $this->tdb->get_manager();

        $tablename = 'test_table_search';
        $table = new xmldb_table($tablename);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('low', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('course', XMLDB_INDEX_UNIQUE, array('course'));
        $table->add_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        $table->add_index('low', XMLDB_INDEX_NOTUNIQUE, array('low'), array('full_text_search'));

        $dbman->create_table($table);

        $this->assertTrue($dbman->table_exists($table));
        $this->assertTrue($dbman->field_exists($table, 'high'));
        $this->assertTrue($dbman->field_exists($table, 'low'));

        $highindex = new xmldb_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        $dbman->drop_index($table, $highindex);
        $this->assertFalse($dbman->index_exists($table, $highindex));

        $dbman->drop_field($table, new xmldb_field('high', XMLDB_TYPE_TEXT));
        $this->assertFalse($dbman->field_exists($table, 'high'));

        $dbman->drop_table($table);
    }

    public function test_rebuild_fts_indexes() {
        $DB = $this->tdb;
        $dbman = $this->tdb->get_manager();
        $prefix = $DB->get_prefix();

        $tablename = 'test_table_search';
        $dbman->install_from_xmldb_file(__DIR__ . '/fixtures/xmldb_search_table.xml');
        $table = new xmldb_table($tablename);
        $fieldhigh = new xmldb_field('high', XMLDB_TYPE_TEXT, null, null, null, null);
        $fieldlow = new xmldb_field('low', XMLDB_TYPE_TEXT, null, null, null, null);
        $indexhigh = new xmldb_index('high', XMLDB_INDEX_NOTUNIQUE, array('high'), array('full_text_search'));
        $indexlow = new xmldb_index('low', XMLDB_INDEX_NOTUNIQUE, array('low'), array('full_text_search'));

        $schema = $this->load_schema(__DIR__ . '/fixtures/xmldb_search_table.xml');

        $result = $dbman->fts_rebuild_indexes($schema);
        $this->assertTrue($dbman->index_exists($table, $indexhigh));
        $this->assertTrue($dbman->index_exists($table, $indexlow));
        $this->assertCount(2, $result);
        $this->assertSame($prefix . $tablename, $result[0]->table);
        $this->assertSame('high', $result[0]->column);
        $this->assertNull($result[0]->error);
        $this->assertNull($result[0]->debuginfo);
        $this->assertTrue($result[0]->success);
        $this->assertSame($prefix . $tablename, $result[1]->table);
        $this->assertSame('low', $result[1]->column);
        $this->assertNull($result[1]->error);
        $this->assertNull($result[1]->debuginfo);
        $this->assertTrue($result[1]->success);

        $this->assertTrue($dbman->index_exists($table, $indexhigh));
        $this->assertTrue($dbman->index_exists($table, $indexlow));
        $dbman->drop_index($table, $indexlow);
        $this->assertTrue($dbman->index_exists($table, $indexhigh));
        $this->assertFalse($dbman->index_exists($table, $indexlow));
        $result = $dbman->fts_rebuild_indexes($schema);
        $this->assertTrue($dbman->index_exists($table, $indexhigh));
        $this->assertTrue($dbman->index_exists($table, $indexlow));
        $this->assertCount(2, $result);
        $this->assertSame($prefix . $tablename, $result[0]->table);
        $this->assertSame('high', $result[0]->column);
        $this->assertNull($result[0]->error);
        $this->assertNull($result[0]->debuginfo);
        $this->assertTrue($result[0]->success);
        $this->assertSame($prefix . $tablename, $result[1]->table);
        $this->assertSame('low', $result[1]->column);
        $this->assertNull($result[1]->error);
        $this->assertNull($result[1]->debuginfo);
        $this->assertTrue($result[1]->success);

        $dbman->drop_table(new xmldb_table($tablename));
    }

    public function test_create_table_allowed_values() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table = new xmldb_table('test_table');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, ['active', 'SUSPENDED']);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, null, null, null, null, ['0', '1']);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $dbman->create_table($table);
        $this->assertTrue($dbman->table_exists('test_table'));

        $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $table->getField('name')));
        if (!$this->is_mysql57()) {
            $this->assertTrue($dbman->field_allowed_values_constraint_exists($table, $table->getField('status')));
            $this->assertTrue($dbman->field_allowed_values_constraint_exists($table, $table->getField('deleted')));
        } else {
            $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $table->getField('status')));
            $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $table->getField('deleted')));
        }

        $record = new stdClass();
        $record->name = 'abc';
        $record->status = 'active';
        $record->deleted = 1;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        $record = new stdClass();
        $record->name = 'def';
        $record->status = 'SUSPENDED';
        $record->deleted = 0;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        $record = new stdClass();
        $record->name = 'ghi';
        $record->status = 'SUSPENDED';
        $record->deleted = null;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        if ($this->is_mysql57()) {
            return;
        }

        try {
            $update = clone($record);
            $update->status = 'grrr';
            $DB->update_record('test_table', $update);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        try {
            $update = clone($record);
            $update->deleted = 2;
            $DB->update_record('test_table', $update);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'xactive';
            $record->deleted = 1;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'active';
            $record->deleted = 2;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'Active';
            $record->deleted = 1;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'suspended';
            $record->deleted = 1;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));
    }

    public function test_create_table_allowed_values_file() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $dbman->install_from_xmldb_file(__DIR__ . '/fixtures/xmldb_allowed_values.xml');
        $this->assertTrue($dbman->table_exists('test_table'));

        $record = new stdClass();
        $record->name = 'abc';
        $record->status = 'active';
        $record->deleted = 1;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        $record = new stdClass();
        $record->name = 'def';
        $record->status = 'SUSPENDED';
        $record->deleted = 0;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        $record = new stdClass();
        $record->name = 'ghi';
        $record->status = 'SUSPENDED';
        $record->deleted = null;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        if ($this->is_mysql57()) {
            return;
        }

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'xactive';
            $record->deleted = 1;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'active';
            $record->deleted = 2;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'Active';
            $record->deleted = 1;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'suspended';
            $record->deleted = 1;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));
    }

    public function test_add_field_allowed_values() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table = new xmldb_table('test_table');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $dbman->create_table($table);
        $this->assertTrue($dbman->table_exists('test_table'));
        $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $table->getField('name')));

        $field = new xmldb_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, ['active', 'SUSPENDED']);
        $dbman->add_field($table, $field);
        if (!$this->is_mysql57()) {
            $this->assertTrue($dbman->field_allowed_values_constraint_exists($table, $field));
        } else {
            $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $field));
        }

        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, null, null, null, null, ['0', '1']);
        $dbman->add_field($table, $field);
        if (!$this->is_mysql57()) {
            $this->assertTrue($dbman->field_allowed_values_constraint_exists($table, $field));
        } else {
            $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $field));
        }

        $record = new stdClass();
        $record->name = 'abc';
        $record->status = 'active';
        $record->deleted = 1;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        $record = new stdClass();
        $record->name = 'def';
        $record->status = 'SUSPENDED';
        $record->deleted = 0;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        $record = new stdClass();
        $record->name = 'ghi';
        $record->status = 'SUSPENDED';
        $record->deleted = null;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        if ($this->is_mysql57()) {
            return;
        }

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'xactive';
            $record->deleted = 1;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'active';
            $record->deleted = 2;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'Active';
            $record->deleted = 1;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'suspended';
            $record->deleted = 1;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));
    }

    public function test_field_remove_allowed_values() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table = new xmldb_table('test_table');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, ['active', 'SUSPENDED']);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, null, null, null, null, ['0', '1']);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $dbman->create_table($table);
        $this->assertTrue($dbman->table_exists('test_table'));

        $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $table->getField('name')));
        if (!$this->is_mysql57()) {
            $this->assertTrue($dbman->field_allowed_values_constraint_exists($table, $table->getField('status')));
            $this->assertTrue($dbman->field_allowed_values_constraint_exists($table, $table->getField('deleted')));
        } else {
            $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $table->getField('status')));
            $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $table->getField('deleted')));
        }

        $field = new xmldb_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null);
        $dbman->change_field_allowed_values($table, $field);
        $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $field));

        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, null, null, null, null);
        $dbman->change_field_allowed_values($table, $field);
        $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $field));

        $record = new stdClass();
        $record->name = 'abc';
        $record->status = 'xxx';
        $record->deleted = 22;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);
    }

    public function test_field_add_allowed_values() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table = new xmldb_table('test_table');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $dbman->create_table($table);
        $this->assertTrue($dbman->table_exists('test_table'));
        $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $table->getField('name')));
        $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $table->getField('status')));
        $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $table->getField('deleted')));

        $field = new xmldb_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, ['active', 'SUSPENDED']);
        $dbman->change_field_allowed_values($table, $field);
        if (!$this->is_mysql57()) {
            $this->assertTrue($dbman->field_allowed_values_constraint_exists($table, $field));
        } else {
            $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $field));
        }

        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, null, null, null, null, ['0', '1']);
        $dbman->change_field_allowed_values($table, $field);
        if (!$this->is_mysql57()) {
            $this->assertTrue($dbman->field_allowed_values_constraint_exists($table, $field));
        } else {
            $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $field));
        }

        $record = new stdClass();
        $record->name = 'abc';
        $record->status = 'active';
        $record->deleted = 1;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        $record = new stdClass();
        $record->name = 'def';
        $record->status = 'SUSPENDED';
        $record->deleted = 0;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        $record = new stdClass();
        $record->name = 'ghi';
        $record->status = 'SUSPENDED';
        $record->deleted = null;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        if ($this->is_mysql57()) {
            return;
        }

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'xactive';
            $record->deleted = 1;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'active';
            $record->deleted = 2;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(3, $DB->get_records('test_table'));
    }

    public function test_field_change_allowed_values() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $table = new xmldb_table('test_table');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, ['active', 'SUSPENDED']);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, null, null, null, null, [0, 1]);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $dbman->create_table($table);
        $this->assertTrue($dbman->table_exists('test_table'));

        $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $table->getField('name')));
        if (!$this->is_mysql57()) {
            $this->assertTrue($dbman->field_allowed_values_constraint_exists($table, $table->getField('status')));
            $this->assertTrue($dbman->field_allowed_values_constraint_exists($table, $table->getField('deleted')));
        } else {
            $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $table->getField('status')));
            $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $table->getField('deleted')));
        }

        $field = new xmldb_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null, ['active', 'passive']);
        $dbman->change_field_allowed_values($table, $field);
        if (!$this->is_mysql57()) {
            $this->assertTrue($dbman->field_allowed_values_constraint_exists($table, $field));
        } else {
            $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $field));
        }

        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '1', null, null, null, null, null, [2, 3]);
        $dbman->change_field_allowed_values($table, $field);
        if (!$this->is_mysql57()) {
            $this->assertTrue($dbman->field_allowed_values_constraint_exists($table, $field));
        } else {
            $this->assertFalse($dbman->field_allowed_values_constraint_exists($table, $field));
        }

        $record = new stdClass();
        $record->name = 'abc';
        $record->status = 'passive';
        $record->deleted = 2;

        $record->id = $DB->insert_record('test_table', $record);
        $newrecord = $DB->get_record('test_table', ['id' => $record->id]);
        $this->assertEquals($record, $newrecord);

        if ($this->is_mysql57()) {
            return;
        }

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'SUSPENDED';
            $record->deleted = 2;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(1, $DB->get_records('test_table'));

        try {
            $record = new stdClass();
            $record->name = 'xyz';
            $record->status = 'passive';
            $record->deleted = 1;
            $DB->insert_record('test_table', $record);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(dml_write_exception::class, $e);
        }
        $this->assertCount(1, $DB->get_records('test_table'));
    }

    /**
     * Load db schema from text file.
     *
     * @param $file
     * @return xmldb_structure
     */
    protected function load_schema($file) {
        global $CFG;
        $this->tdb->get_manager(); // Load ddl libraries.

        $schema = new xmldb_structure('export');
        $schema->setVersion($CFG->version);
        $xmldb_file = new xmldb_file($file);
        $xmldb_file->loadXMLStructure();
        $structure = $xmldb_file->getStructure();
        $tables = $structure->getTables();
        foreach ($tables as $table) {
            $table->setPrevious(null);
            $table->setNext(null);
            $schema->addTable($table);
        }

        return $schema;
    }

    /**
     * Oh well, MS SQL Server needs time to index the data, we need to wait a few seconds.
     * @param string $tablename
     */
    public function wait_for_mssql_fts_indexing(string $tablename) {
        $DB = $this->tdb;

        if ($DB->get_dbfamily() !== 'mssql') {
            return;
        }

        /** @var sqlsrv_native_moodle_database $DB */
        $done = $DB->fts_wait_for_indexing($tablename, 10);
        $this->assertTrue($done);
    }

    protected function is_mysql57() {
        if ($this->tdb->get_dbvendor() === 'mysql') {
            $version = $this->tdb->get_server_info()['version'];
            if (version_compare($version, '8.0', '<')) {
                $this->markTestSkipped('Not supported in MySQL 5.7.x');
            }
        }
    }
}
