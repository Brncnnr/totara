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
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_jobassignment_csv.php');

class ja_csv_field_mapping_test extends totara_sync_csv_testcase {

    protected $elementname = 'jobassignment';
    protected $sourcename = 'totara_sync_source_jobassignment_csv';

    public function setUp(): void {
        global $CFG;

        parent::setUp();

        $this->source = new $this->sourcename();

        $this->filedir = $CFG->dataroot . '/totara_sync/';
        mkdir($this->filedir . '/csv/ready', 0777, true);

        set_config('element_jobassignment_enabled', 1, 'totara_sync');
        set_config('source_jobassignment', 'totara_sync_source_jobassignment_csv', 'totara_sync');
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');
    }

    public function test_ja_csv_with_field_mapping() {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => 'One', 'idnumber' => 'USER1']);

        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $posframework = $hierarchygenerator->create_pos_frame(['idnumber' => 'POSF1']);
        $pos2 = $hierarchygenerator->create_pos(['frameworkid' => $posframework->id, 'fullname' => 'Position 2', 'idnumber' => 'POS2']);

        $orgframework = $hierarchygenerator->create_org_frame(['idnumber' => 'ORGF1']);
        $org2 = $hierarchygenerator->create_org(['frameworkid' => $orgframework->id, 'fullname' => 'Organisation 2', 'idnumber' => 'ORG2']);

        $configcsv = [
            'import_deleted' => '1',
            'import_fullname' => '1',
            'import_idnumber' => '1',
            'import_orgidnumber' => '1',
            'import_posidnumber' => '1',
            'import_timemodified' => '1',
            'import_useridnumber' => '1',

            'fieldmapping_idnumber' => 'jaidnumber',
        ];

        foreach ($configcsv as $k => $v) {
            set_config($k, $v, 'totara_sync_source_jobassignment_csv');
        }

        $data = file_get_contents(__DIR__ . '/fixtures/ja_field_mapping_1.csv');
        $filepath = $this->filedir . '/csv/ready/jobassignment.csv';
        file_put_contents($filepath, $data);

        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->set_config('allow_create', '1');
        $result = $element->sync();
        $this->assertTrue($result);

        $sql = 'SELECT * FROM {job_assignment} WHERE fullname = :jafullname';
        $ja = $DB->get_record_sql($sql, ['jafullname' => 'Job Assignment 1']);
        $this->assertEquals($pos2->id, $ja->positionid);
        $this->assertEquals($org2->id, $ja->organisationid);
        $this->assertEquals('JAID1', $ja->idnumber);
        $this->assertEquals($user1->id, $ja->userid);
    }

    public function test_ja_csv_with_all_fields_mapped() {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => 'One', 'idnumber' => 'USER1']);

        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $posframework = $hierarchygenerator->create_pos_frame(['idnumber' => 'POSF1']);
        $pos2 = $hierarchygenerator->create_pos(['frameworkid' => $posframework->id, 'fullname' => 'Position 2', 'idnumber' => 'POS2']);

        $orgframework = $hierarchygenerator->create_org_frame(['idnumber' => 'ORGF1']);
        $org2 = $hierarchygenerator->create_org(['frameworkid' => $orgframework->id, 'fullname' => 'Organisation 2', 'idnumber' => 'ORG2']);

        $configcsv = [
            'import_deleted' => '1',
            'import_fullname' => '1',
            'import_idnumber' => '1',
            'import_orgidnumber' => '1',
            'import_posidnumber' => '1',
            'import_timemodified' => '1',
            'import_useridnumber' => '1',

            'fieldmapping_deleted' => 'a',
            'fieldmapping_fullname' => 'b',
            'fieldmapping_idnumber' => 'c',
            'fieldmapping_orgidnumber' => 'd',
            'fieldmapping_posidnumber' => 'e',
            'fieldmapping_timemodified' => 'f',
            'fieldmapping_useridnumber' => 'g',
        ];

        foreach ($configcsv as $k => $v) {
            set_config($k, $v, 'totara_sync_source_jobassignment_csv');
        }

        $data = file_get_contents(__DIR__ . '/fixtures/ja_field_mapping_2.csv');
        $filepath = $this->filedir . '/csv/ready/jobassignment.csv';
        file_put_contents($filepath, $data);

        $job_assignments = $DB->get_records('job_assignment');
        $this->assertCount(0, $job_assignments);

        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->set_config('allow_create', '1');
        $result = $element->sync();
        $this->assertTrue($result);

        $sql = 'SELECT * FROM {job_assignment} WHERE fullname = :jafullname';
        $ja = $DB->get_record_sql($sql, ['jafullname' => 'Job Assignment 1']);
        $this->assertEquals($pos2->id, $ja->positionid);
        $this->assertEquals($org2->id, $ja->organisationid);
        $this->assertEquals('JAID1', $ja->idnumber);
        $this->assertEquals($user1->id, $ja->userid);
    }

    public function test_ja_csv_with_date_fields_mapped() {
        global $DB;
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => 'One', 'idnumber' => 'USER1']);

        $configcsv = [
            'import_deleted' => '1',
            'import_enddate' => '1',
            'import_idnumber' => '1',
            'import_startdate' => '1',
            'import_timemodified' => '1',
            'import_useridnumber' => '1',

            'fieldmapping_enddate' => 'custom_end_date',
            'fieldmapping_startdate' => 'custom_start_date',
            'fieldmapping_timemodified' => 'custom_timemodified',
        ];

        foreach ($configcsv as $k => $v) {
            set_config($k, $v, 'totara_sync_source_jobassignment_csv');
        }

        $data = file_get_contents(__DIR__ . '/fixtures/ja_field_mapping_3.csv');
        $filepath = $this->filedir . '/csv/ready/jobassignment.csv';
        file_put_contents($filepath, $data);

        $jas = $DB->get_records('job_assignment');
        $this->assertCount(0, $jas);

        $element = new totara_sync_element_jobassignment();
        $element->set_config('allow_update', '1');
        $element->set_config('allow_create', '1');
        $result = $element->sync();
        $this->assertTrue($result);

        $jas = $DB->get_records('job_assignment');
        $this->assertCount(1, $jas);

        $job_assignment = reset($jas);
        $csvdateformat = get_string('csvdateformatdefault', 'totara_core');
        $expected_start_date = totara_date_parse_from_format($csvdateformat, '01/01/2010', true);
        $expected_end_date = totara_date_parse_from_format($csvdateformat, '31/12/2050', true);
        $expected_timemodified = totara_date_parse_from_format($csvdateformat, '01/04/2021', true);
        $this->assertEquals($expected_start_date, $job_assignment->startdate);
        $this->assertEquals($expected_end_date, $job_assignment->enddate);
        $this->assertEquals($expected_timemodified, $job_assignment->synctimemodified);
    }
}