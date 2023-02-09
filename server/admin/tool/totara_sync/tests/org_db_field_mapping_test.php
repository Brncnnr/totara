<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_database_testcase.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_org_database.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_org_database_field_mapping_testcase extends totara_sync_database_testcase {

    protected $elementname  = 'org';
    protected $sourcename   = 'totara_sync_source_org_database';

    public function setUp(): void {
        $this->sourcetable = 'totara_sync_org_source';

        parent::setUp();

        $this->source = new $this->sourcename();

        set_config('source_org', 'totara_sync_source_org_database', 'totara_sync');

        $this->setAdminUser();
        $this->create_external_db_table();
    }

    public function tearDown(): void {
        $this->elementname = null;
        $this->sourcetable = null;

        parent::tearDown();
    }

    public function create_external_db_table() {

        $dbman = $this->ext_dbconnection->get_manager();
        $table = new xmldb_table($this->dbtable);

        // Drop table first, if it exists
        if ($dbman->table_exists($this->dbtable)) {
            $dbman->drop_table($table);
        }

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '255');
        $table->add_field('sname', XMLDB_TYPE_CHAR, '255');
        $table->add_field('frameworkidnumber', XMLDB_TYPE_CHAR, 255);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1');
        $table->add_field('description', XMLDB_TYPE_TEXT);
        $table->add_field('parentidnumber', XMLDB_TYPE_CHAR, '255');
        $table->add_field('typeid', XMLDB_TYPE_CHAR, '255');
        $table->add_field('textcf1', XMLDB_TYPE_CHAR, '255');
        $table->add_field('menucf1', XMLDB_TYPE_CHAR, '255');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $dbman->create_table($table);
    }


    public function test_field_mapping_for_types() {
        global $DB;

        $this->assertCount(0, $DB->get_records('org'));

        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchygenerator->create_framework('organisation', ['idnumber' => 'OF1']);

        // Create a type
        $orgtypeid = $hierarchygenerator->create_org_type(['idnumber' => 'T1', 'fullname' => 'Type 1']);

        $type_cf1_data = ['hierarchy' => 'organisation', 'typeidnumber' => 'T1', 'value' => '', 'shortname' => 'field1'];
        $hierarchygenerator->create_hierarchy_type_text($type_cf1_data);
        $type_cf2_data = ['hierarchy' => 'organisation', 'typeidnumber' => 'T1', 'value' => '2345', 'shortname' => 'field2'];
        $hierarchygenerator->create_hierarchy_type_menu($type_cf2_data);

        $source = new totara_sync_source_org_database();
        $source->set_config('import_idnumber', '1');
        $source->set_config('import_fullname', '1');
        $source->set_config('import_shortname', '1');
        $source->set_config('import_frameworkidnumber', '1');
        $source->set_config('import_timemodified', '1');
        $source->set_config('import_deleted', '1');
        $source->set_config('import_typeidnumber', '1');
        $config = [
            'fieldmapping_idnumber' => '',
            'fieldmapping_shortname' => 'sname',
            'fieldmapping_typeidnumber' => 'typeid',

            'import_idnumber' => '1',
            'import_fullname' => '1',
            'import_frameworkidnumber' => '1',
            'import_timemodified' => '1',
            'import_deleted' => '1',
            'import_typeidnumber' => '1',
        ];

        $this->set_source_config($config);

        // Add Customfield mappings
        $source->set_config('import_customfield_' .$orgtypeid . '_field1', '1');
        $source->set_config('import_customfield_' .$orgtypeid . '_field2', '1');
        $source->set_config('fieldmapping_customfield_' .$orgtypeid . '_field1', 'textcf1');
        $source->set_config('fieldmapping_customfield_' .$orgtypeid . '_field2', 'menucf1');

        // Add an entry to our import table to run sync with
        $entry = new stdClass();
        $entry->idnumber = 'org2';
        $entry->fullname = 'Organisation 2';
        $entry->sname = 'org2';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->frameworkidnumber = 'OF1';
        $entry->typeid = 'T1';
        $entry->textcf1 = 'test1';
        $entry->menucf1 = null;

        $this->ext_dbconnection->insert_record($this->dbtable, $entry);

        $element = new totara_sync_element_org();
        $element->set_config('allow_update', '1');
        $element->set_config('allow_create', '1');
        $this->assertTrue($element->sync());

        // Ensure our record was created and mapped fields are correct
        $records = $DB->get_records('org');
        $this->assertCount(1, $records);
        $this->assertEquals('Organisation 2', current($records)->fullname);
        $this->assertEquals('org2', current($records)->shortname);
        $orgtype = $DB->get_record('org_type', ['idnumber' => 'T1']);
        $this->assertEquals($orgtype->id, current($records)->typeid);
    }
}
