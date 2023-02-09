<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_csv_testcase.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_comp_csv.php');

class tool_totara_sync_comp_csv_sync_temp_table_defaults_testcase extends totara_sync_csv_testcase {

    protected $filedir = null;
    protected $elementname  = 'comp';

    /**
     * @return void
     */
    public function setUp(): void {
        global $CFG;

        parent::setUp();

        $this->setAdminUser();

        $this->filedir = $CFG->dataroot . '/totara_sync';
        mkdir($this->filedir . '/csv/ready', 0777, true);

        $this->source = new totara_sync_source_comp_csv();

        $this->set_config();
    }

    /**
     * @return void
     */
    public function tearDown(): void {
        $this->filedir = null;
        $this->source = null;
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_comp_csv_temp_table_defaults(): void {
        global $DB;

        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        // Create competency types.
        $comp_type_1_id = $hierarchy_generator->create_comp_type([
            'idnumber' => 'comp_type_1'
        ]);
        $comp_type_2_id = $hierarchy_generator->create_comp_type([
            'idnumber' => 'comp_type_2'
        ]);

        // Create competency framework.
        $comp_framework = $hierarchy_generator->create_framework('competency', [
            'idnumber' => 'compfw1',
            'typeid' => $comp_type_1_id
        ]);

        // Create competency.
        $comp1 = $hierarchy_generator->create_comp(
            [
                'frameworkid' => $comp_framework->id,
                'fullname' => 'Competency 1',
                'idnumber' => 'comp1',
                'totarasync' => 1,
                'typeid' => $comp_type_1_id,
                // These values should be used when importing temp table data
                'proficiencyexpected' => 0,
                'evidencecount' => 1,
            ]
        );

        // Validate competency before sync.
        $records = $DB->get_records('comp', ['idnumber' => 'comp1']);
        $this->assertCount(1, $records);

        $record = reset($records);
        $this->assertEquals($comp1->proficiencyexpected, $record->proficiencyexpected, 'Invalid value for "proficiencyexpected"');
        $this->assertEquals($comp1->evidencecount, $record->evidencecount, 'Invalid value for "evidencecount"');
        $this->assertEquals($comp_type_1_id, $record->typeid);

        // Update/insert competencies.
        $this->add_csv('competency.csv', 'comp');
        $this->sync();

        // Validate updated competency.
        $records = $DB->get_records('comp');
        $this->assertCount(2, $records);

        $update_validated = false;
        $insert_validated = false;
        foreach ($records as $record) {
            if ($record->id === $comp1->id) {
                // Validate updated record.
                $this->assertEquals($comp1->proficiencyexpected, $record->proficiencyexpected, '"proficiencyexpected" updated during sync');
                $this->assertEquals($comp1->evidencecount, $record->evidencecount, '"evidencecount" updated during sync');
                $this->assertEquals($comp_type_2_id, $record->typeid);
                $update_validated = true;
            } else {
                // Validate inserted record's defaults.
                $this->assertEquals('1', $record->proficiencyexpected, 'Invalid default for "proficiencyexpected"');
                $this->assertEquals('0', $record->evidencecount, 'Invalid default for "evidencecount"');
                $insert_validated = true;
            }
        }
        $this->assertTrue($update_validated);
        $this->assertTrue($insert_validated);
    }

    /**
     * @return void
     */
    private function set_config(): void {
        set_config('element_comp_enabled', 1, 'totara_sync');
        set_config('source_comp', 'totara_sync_source_comp_csv', 'totara_sync');
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');

        $this->set_source_config([
            'csvuserencoding'                   => 'UTF-8',
            'delimiter'                         => ',',
            'csvsaveemptyfields'                => true,

            'fieldmapping_idnumber'             => '',
            'fieldmapping_fullname'             => '',
            'fieldmapping_frameworkidnumber'    => '',
            'fieldmapping_timemodified'         => '',

            'fieldmapping_shortname'            => '',
            'fieldmapping_description'          => '',
            'fieldmapping_parentidnumber'       => '',
            'fieldmapping_typeidnumber'         => '',
            'fieldmapping_aggregationmethod'    => '',

            'import_idnumber'                   => '1',
            'import_fullname'                   => '1',
            'import_deleted'                    => '0',
            'import_shortname'                  => '0',
            'import_description'                => '0',
            'import_parentidnumber'             => '0',
            'import_typeidnumber'               => '1',
            'import_frameworkidnumber'          => '1',
            'import_aggregationmethod'          => '1',
            'import_timemodified'               => '1',
        ]);

        $this->set_element_config([
            'sourceallrecords'  => '1',
            'allow_create'      => '1',
            'allow_delete'      => '0',
            'allow_update'      => '1',
        ]);
    }

}