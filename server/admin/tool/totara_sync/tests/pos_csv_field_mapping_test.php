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
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_pos_csv.php');

/**
 * @group tool_totara_sync
 */
class pos_csv_field_mapping_test extends totara_sync_csv_testcase {

    protected $elementname = 'pos';
    protected $sourcename = 'totara_sync_source_pos_csv';

    public function setUp(): void {
        global $CFG;

        parent::setUp();

        $this->source = new $this->sourcename();

        $this->filedir = $CFG->dataroot . '/totara_sync/';
        mkdir($this->filedir . '/csv/ready', 0777, true);

        set_config('element_pos_enabled', 1, 'totara_sync');
        set_config('source_pos', 'totara_sync_source_pos_csv', 'totara_sync');
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');
    }

    public function test_pos_csv_with_field_mapping() {
        global $DB;

        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $hierarchygenerator->create_framework('position', ['idnumber' => 'POSF1']);

        $configcsv = [
            'fieldmapping_idnumber' => 'posidnumber',

            'import_idnumber' => '1',
            'import_fullname' => '1',
            'import_frameworkidnumber' => '1',
            'import_timemodified' => '1',
            'import_typeidnumber' => '0',
            'import_deleted' => '1',
        ];

        foreach ($configcsv as $k => $v) {
            set_config($k, $v, 'totara_sync_source_pos_csv');
        }

        $data = file_get_contents(__DIR__ . '/fixtures/positions_field_mapping_3.csv');
        $filepath = $this->filedir . '/csv/ready/pos.csv';
        file_put_contents($filepath, $data);

        $element = new totara_sync_element_pos();
        $element->set_config('allow_update', '1');
        $element->set_config('allow_create', '1');
        $result = $element->sync();
        $this->assertTrue($result);

        $position = $DB->get_record('pos', ['fullname' => 'Position 3']);
        $this->assertEquals('PID3', $position->idnumber);
    }

    public function test_pos_csv_with_all_fields_mapped() {
        global $DB;

        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $posframework = $hierarchygenerator->create_framework('position', ['idnumber' => 'POSF1']);

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
            set_config($k, $v, 'totara_sync_source_pos_csv');
        }

        $data = file_get_contents(__DIR__ . '/fixtures/positions_field_mapping_4.csv');
        $filepath = $this->filedir . '/csv/ready/pos.csv';
        file_put_contents($filepath, $data);

        $element = new totara_sync_element_pos();
        $element->set_config('allow_update', '1');
        $element->set_config('allow_create', '1');
        $result = $element->sync();
        $this->assertTrue($result);

        $position = $DB->get_record('pos', ['fullname' => 'Position 4']);
        $this->assertEquals('PID4', $position->idnumber);
        $this->assertEquals($posframework->id, $position->frameworkid);
    }

    public function test_pos_csv_field_mapping_custom_fields() {
        global $DB;

        $this->setAdminUser();

        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $hierarchygenerator->create_framework('position', ['idnumber' => 'POSF1']);

        $postypeid = $hierarchygenerator->create_pos_type(['idnumber' => 'POST1']);

        $menudata = ['hierarchy' => 'position', 'typeidnumber' => 'POST1', 'value' => '1234', 'shortname' => 'pos_menu'];
        $hierarchygenerator->create_hierarchy_type_menu($menudata);
        $textdata = ['hierarchy' => 'position', 'typeidnumber' => 'POST1', 'value' => '', 'shortname' => 'pos_text'];
        $hierarchygenerator->create_hierarchy_type_text($textdata);

        // Get data for new type custom fields
        $menucf = $DB->get_record('pos_type_info_field', ['shortname' => 'pos_menu']);
        $textcf = $DB->get_record('pos_type_info_field', ['shortname' => 'pos_text']);

        $configcsv = [
            'fieldmapping_customfield_' . $postypeid . '_pos_menu' => 'the_menu',
            'fieldmapping_customfield_' . $postypeid . '_pos_text' => 'the_text',

            'import_idnumber' => '1',
            'import_fullname' => '1',
            'import_frameworkidnumber' => '1',
            'import_timemodified' => '1',
            'import_typeidnumber' => '1',
            'import_deleted' => '1',
            'import_customfield_' . $postypeid . '_pos_menu' => '1',
            'import_customfield_' . $postypeid . '_pos_text' => '1',
        ];

        foreach ($configcsv as $k => $v) {
            set_config($k, $v, 'totara_sync_source_pos_csv');
        }

        $data = file_get_contents(__DIR__ . '/fixtures/positions_field_mapping_5.csv');
        $filepath = $this->filedir . '/csv/ready/pos.csv';
        file_put_contents($filepath, $data);

        $element = new totara_sync_element_pos();
        $element->set_config('allow_update', '1');
        $element->set_config('allow_create', '1');
        $result = $element->sync();
        $this->assertTrue($result);

        $position = $DB->get_record('pos', ['idnumber' => 'PID3']);

        $record = $DB->get_record('pos_type_info_data', ['fieldid' => $menucf->id, 'positionid' => $position->id]);
        $this->assertEquals('3456', $record->data);
        $record = $DB->get_record('pos_type_info_data', ['fieldid' => $textcf->id, 'positionid' => $position->id]);
        $this->assertEquals('some text', $record->data);
    }
}
