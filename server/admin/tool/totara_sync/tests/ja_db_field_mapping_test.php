<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_database_testcase.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_jobassignment_database.php');

class ja_db_field_mapping_test extends totara_sync_database_testcase {

    public function setUp(): void {
        $this->elementname = 'jobassignment';
        $this->sourcetable = 'job_assignment_user_source';

        parent::setUp();

        set_config('element_jobassignment_enabled', 1, 'totara_sync');
        set_config('source_jobassignment', 'totara_sync_source_jobassignment_database', 'totara_sync');

        $this->setAdminUser();
        $this->create_external_db_table();
    }

    public function test_ja_db_with_date_fields_mapped() {
        global $DB;

        $this->create_external_db_table();

        $configdb = [
            'import_deleted' => '1',
            'import_enddate' => '1',
            'import_idnumber' => '1',
            'import_startdate' => '1',
            'import_timemodified' => '1',
            'import_useridnumber' => '1',

            'fieldmapping_enddate' => 'c_enddate',
            'fieldmapping_idnumber' => 'id',
            'fieldmapping_startdate' => 'c_startdate',
            'fieldmapping_timemodified' => 'c_timemodified',
        ];

        foreach ($configdb as $k => $v) {
            set_config($k, $v, 'totara_sync_source_jobassignment_database');
        }

        set_config('database_dateformat', 'd/m/Y', 'totara_sync_source_jobassignment_database');

        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => 'One', 'idnumber' => 'USER1']);

        // Create db entry for import.
        $entry = new stdClass();
        $entry->id = '1';
        $entry->useridnumber = 'USER1';
        $entry->c_timemodified = '01/04/2021';
        $entry->deleted = 0;
        $entry->fullname = 'My First Job';
        $entry->c_startdate = '01/01/2010';
        $entry->c_enddate = '31/12/2050';
        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $jas = $DB->get_records('job_assignment');
        $this->assertCount(0, $jas);

        // Run sync
        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->set_config('allow_create', '1');
        $result = $element->sync(); // Run the sync.
        $this->assertTrue($result);

        $jas = $DB->get_records('job_assignment');
        $this->assertCount(1, $jas);

        $job_assignment = reset($jas);
        $database_dateformat = get_config('totara_sync_source_jobassignment_database', 'database_dateformat');
        $expected_start_date = totara_date_parse_from_format($database_dateformat, '01/01/2010', true);
        $expected_end_date = totara_date_parse_from_format($database_dateformat, '31/12/2050', true);
        $expected_timemodified = totara_date_parse_from_format($database_dateformat, '01/04/2021', true);
        $this->assertEquals($expected_start_date, $job_assignment->startdate);
        $this->assertEquals($expected_end_date, $job_assignment->enddate);
        $this->assertEquals($expected_timemodified, $job_assignment->synctimemodified);
    }

    public function create_external_db_table() {
        $dbman = $this->ext_dbconnection->get_manager();
        $table = new xmldb_table($this->dbtable);

        // Drop table first, if it exists
        if ($dbman->table_exists($this->dbtable)) {
            $dbman->drop_table($table);
        }

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('useridnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('c_timemodified', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1');
        $table->add_field('c_fullname', XMLDB_TYPE_CHAR, '255');
        $table->add_field('c_startdate', XMLDB_TYPE_CHAR, '50', null);
        $table->add_field('c_enddate', XMLDB_TYPE_CHAR, '50', null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $dbman->create_table($table);
    }
}
