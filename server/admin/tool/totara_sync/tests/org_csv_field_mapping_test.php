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
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_csv_testcase.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_org_csv.php');

/**
 * @group tool_totara_sync
 */
class org_csv_field_mapping_test extends totara_sync_csv_testcase {

    protected $elementname  = 'org';
    protected $sourcename   = 'totara_sync_source_org_csv';

    public function setUp(): void {
        global $CFG;

        parent::setUp();

        $this->source = new $this->sourcename();

        $this->filedir = $CFG->dataroot . '/totara_sync/';
        mkdir($this->filedir . '/csv/ready', 0777, true);

        set_config('source_org', 'totara_sync_source_csv_database', 'totara_sync');
        set_config('element_org_enabled', 1, 'totara_sync');
        set_config('source_org', 'totara_sync_source_org_csv', 'totara_sync');
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');
    }

    public function test_org_csv_with_field_mapping() {
        global $DB;

        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $hierarchygenerator->create_framework('organisation', ['idnumber' => 'ORGF1']);

        $configcsv = [
            'fieldmapping_idnumber' => 'orgidnumber',

            'import_idnumber' => '1',
            'import_fullname' => '1',
            'import_frameworkidnumber' => '1',
            'import_timemodified' => '1',
            'import_typeidnumber' => '0',
            'import_deleted' => '1',
        ];

        foreach ($configcsv as $k => $v) {
            set_config($k, $v, 'totara_sync_source_org_csv');
        }

        $data = file_get_contents(__DIR__ . '/fixtures/organisations_field_mapping_3.csv');
        $filepath = $this->filedir . '/csv/ready/org.csv';
        file_put_contents($filepath, $data);

        $element = new totara_sync_element_org();
        $element->set_config('allow_update', '1');
        $element->set_config('allow_create', '1');
        $result = $element->sync();
        $this->assertTrue($result);

        $organisation = $DB->get_record('org', ['fullname' => 'Organisation 3']);
        $this->assertEquals('OID3', $organisation->idnumber);
    }

    public function test_org_csv_with_all_fields_mapped() {
        global $DB;

        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchygenerator->create_framework('organisation', ['idnumber' => 'ORGF1']);

        $configcsv = [
            'import_idnumber' => '1',
            'import_fullname' => '1',
            'import_frameworkidnumber' => '1',
            'import_timemodified' => '1',
            'import_typeidnumber' => '0',
            'import_deleted' => '1',

            'fieldmapping_idnumber' => 'a',
            'fieldmapping_fullname' => 'b',
            'fieldmapping_frameworkidnumber' => 'c',
            'fieldmapping_timemodified' => 'd',
            'fieldmapping_deleted' => 'e',
        ];

        foreach ($configcsv as $k => $v) {
            set_config($k, $v, 'totara_sync_source_org_csv');
        }

        $data = file_get_contents(__DIR__ . '/fixtures/organisations_field_mapping_4.csv');
        $filepath = $this->filedir . '/csv/ready/org.csv';
        file_put_contents($filepath, $data);

        $element = new totara_sync_element_org();
        $element->set_config('allow_update', '1');
        $element->set_config('allow_create', '1');
        $result = $element->sync();
        $this->assertTrue($result);

        $organisation = $DB->get_record('org', ['fullname' => 'Organisation 3']);
        $this->assertEquals('OID3', $organisation->idnumber);
        $this->assertEquals($orgframework->id, $organisation->frameworkid);
    }

    public function test_org_csv_field_mapping_custom_fields() {
        global $DB;

        $this->setAdminUser();

        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $hierarchygenerator->create_framework('organisation', ['idnumber' => 'ORGF1']);

        $orgtypeid = $hierarchygenerator->create_org_type(['idnumber' => 'ORGT1']);

        $menudata = ['hierarchy' => 'organisation', 'typeidnumber' => 'ORGT1', 'value' => '1234', 'shortname' => 'org_menu'];
        $hierarchygenerator->create_hierarchy_type_menu($menudata);
        $textdata = ['hierarchy' => 'organisation', 'typeidnumber' => 'ORGT1', 'value' => '', 'shortname' => 'org_text'];
        $hierarchygenerator->create_hierarchy_type_text($textdata);

        // Get data for new type custom fields
        $menucf = $DB->get_record('org_type_info_field', ['shortname' => 'org_menu']);
        $textcf = $DB->get_record('org_type_info_field', ['shortname' => 'org_text']);

        $configcsv = [
            'fieldmapping_customfield_' . $orgtypeid . '_org_menu' => 'the_menu',
            'fieldmapping_customfield_' . $orgtypeid . '_org_text' => 'the_text',

            'import_idnumber' => '1',
            'import_fullname' => '1',
            'import_frameworkidnumber' => '1',
            'import_timemodified' => '1',
            'import_typeidnumber' => '1',
            'import_deleted' => '1',
            'import_customfield_' . $orgtypeid . '_org_menu' => '1',
            'import_customfield_' . $orgtypeid . '_org_text' => '1',
        ];

        foreach ($configcsv as $k => $v) {
            set_config($k, $v, 'totara_sync_source_org_csv');
        }

        $data = file_get_contents(__DIR__ . '/fixtures/organisations_field_mapping_5.csv');
        $filepath = $this->filedir . '/csv/ready/org.csv';
        file_put_contents($filepath, $data);

        $element = new totara_sync_element_org();
        $element->set_config('allow_update', '1');
        $element->set_config('allow_create', '1');
        $result = $element->sync();
        $this->assertTrue($result);

        $organisation = $DB->get_record('org', ['idnumber' => 'ORG4']);

        $record = $DB->get_record('org_type_info_data', ['fieldid' => $menucf->id, 'organisationid' => $organisation->id]);
        $this->assertEquals('4567', $record->data);
        $record = $DB->get_record('org_type_info_data', ['fieldid' => $textcf->id, 'organisationid' => $organisation->id]);
        $this->assertEquals('great organisation', $record->data);
    }
}
