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
 * @author David Curry <david.curry@totaralearning.com>
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/db/upgradelib.php');

/**
 * Test report builder database upgrades.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_reportbuilder_upgradelib_testcase
 *
 * @group totara_reportbuilder
 */
class totara_reportbuilder_upgradelib_testcase extends advanced_testcase {

    private $report, $user, $rbcolumn, $contcol, $rbfilter, $contfil, $rbsaved, $contsave, $rbgraph;

    public function setUp(): void {
        global $DB;

        parent::setUp();

        $this->setAdminUser();
        $this->user = $user = get_admin();

        $report = new stdclass();
        $report->fullname = 'Test Report';
        $report->shortname = 'tstrpt';
        $report->source = 'user';
        $report->hidden = 0;
        $report->accessmode = 0;
        $report->contentmode = 0;
        $report->description = '';
        $report->recordsperpage = 40;
        $report->defaultsortcolumn = 'user_fullname';
        $report->defaultsortorder = 4;
        $report->embedded = 0;
        $report->id = $DB->insert_record('report_builder', $report);
        $this->report = $report;

        $rbcolumn = new stdClass();
        $rbcolumn->reportid = $report->id;
        $rbcolumn->type = 'user';
        $rbcolumn->value = 'fullname';
        $rbcolumn->heading = 'Participant';
        $rbcolumn->sortorder = 1;
        $rbcolumn->hidden = 0;
        $rbcolumn->customheading = 1;
        $rbcolumn->id = $DB->insert_record('report_builder_columns', $rbcolumn);
        $this->rbcolumn = $rbcolumn;

        $contcol = new stdClass();
        $contcol->reportid = $report->id;
        $contcol->type = 'user';
        $contcol->value = 'username';
        $contcol->heading = 'Control Column';
        $contcol->sortorder = 1;
        $contcol->hidden = 0;
        $contcol->customheading = 1;
        $contcol->id = $DB->insert_record('report_builder_columns', $contcol);
        $this->contcol = $contcol;

        $rbfilter = new stdClass();
        $rbfilter->reportid = $report->id;
        $rbfilter->type = 'user';
        $rbfilter->value = 'fullname';
        $rbfilter->filtername = 'Participant';
        $rbfilter->advanced = 0;
        $rbfilter->sortorder = 1;
        $rbfilter->id = $DB->insert_record('report_builder_filters', $rbfilter);
        $this->rbfilter = $rbfilter;

        $contfil = new stdClass();
        $contfil->reportid = $report->id;
        $contfil->type = 'user';
        $contfil->value = 'username';
        $contfil->filtername = 'Control Filter';
        $contfil->advanced = 0;
        $contfil->sortorder = 1;
        $contfil->id = $DB->insert_record('report_builder_filters', $contfil);
        $this->contfil = $contfil;

        $rbsaved = new stdClass();
        $rbsaved->reportid = $report->id;
        $rbsaved->userid = $user->id;
        $rbsaved->name = 'Saved Search';
        $rbsaved->search = 'a:1:{s:13:"user-fullname";a:2:{s:8:"operator";i:0;s:5:"value";s:1:"a";}}';
        $rbsaved->ispublic = 1;
        $rbsaved->id = $DB->insert_record('report_builder_saved', $rbsaved);
        $this->rbsaved = $rbsaved;

        $contsave = new stdClass();
        $contsave->reportid = $report->id;
        $contsave->userid = $user->id;
        $contsave->name = 'Control Saved';
        $contsave->search = 'a:1:{s:13:"user-username";a:2:{s:8:"operator";i:0;s:5:"value";s:1:"a";}}';
        $contsave->ispublic = 1;
        $contsave->id = $DB->insert_record('report_builder_saved', $contsave);
        $this->contsave = $contsave;

        $rbgraph = new stdClass();
        $rbgraph->reportid = $report->id;
        $rbgraph->type = 'column';
        $rbgraph->stacked = false;
        $rbgraph->maxrecords = 500;
        $rbgraph->category = 'none';
        $rbgraph->series = json_encode(['user-username']);
        $rbgraph->settings = '';
        $rbgraph->id = $DB->insert_record('report_builder_graph', $rbgraph);
        $this->rbgraph = $rbgraph;
    }

    protected function tearDown(): void {
        $this->report = null;
        $this->user = null;
        $this->rbcolumn = null;
        $this->contcol = null;
        $this->rbfilter = null;
        $this->contfil = null;
        $this->rbsaved = null;
        $this->contsave = null;
        $this->rbgraph = null;

        parent::tearDown();
    }

    public function test_upgradelib_migrate_columns() {
        global $DB;

        totara_reportbuilder_migrate_column_names(array('fullname' => 'shortname'), 'user');

        $column = $DB->get_record('report_builder_columns', array('id' => $this->rbcolumn->id));
        $this->assertEquals('shortname', $column->value);
        $this->assertEquals($this->rbcolumn->type, $column->type);
        $this->assertEquals($this->rbcolumn->heading, $column->heading);
        $this->assertEquals($this->rbcolumn->sortorder, $column->sortorder);
        $this->assertEquals($this->rbcolumn->hidden, $column->hidden);

        $control = $DB->get_record('report_builder_columns', array('id' => $this->contcol->id));
        $this->assertEquals($this->contcol->value, $control->value);
        $this->assertEquals($this->contcol->type, $control->type);
        $this->assertEquals($this->contcol->heading, $control->heading);
        $this->assertEquals($this->contcol->sortorder, $control->sortorder);
        $this->assertEquals($this->contcol->hidden, $control->hidden);

        totara_reportbuilder_migrate_column_types(array('fullname' => 'shortname'), 'user', 'manager');

        $column = $DB->get_record('report_builder_columns', array('id' => $this->rbcolumn->id));
        $this->assertEquals('shortname', $column->value);
        $this->assertEquals('manager', $column->type);
        $this->assertEquals($this->rbcolumn->heading, $column->heading);
        $this->assertEquals($this->rbcolumn->sortorder, $column->sortorder);
        $this->assertEquals($this->rbcolumn->hidden, $column->hidden);

        $control = $DB->get_record('report_builder_columns', array('id' => $this->contcol->id));
        $this->assertEquals($this->contcol->value, $control->value);
        $this->assertEquals($this->contcol->type, $control->type);
        $this->assertEquals($this->contcol->heading, $control->heading);
        $this->assertEquals($this->contcol->sortorder, $control->sortorder);
        $this->assertEquals($this->contcol->hidden, $control->hidden);
    }

    public function test_upgradelib_migrate_filters() {
        global $DB;

        totara_reportbuilder_migrate_filter_names(array('fullname' => 'shortname'), 'user');

        $filter = $DB->get_record('report_builder_filters', array('id' => $this->rbfilter->id));
        $this->assertEquals('shortname', $filter->value);
        $this->assertEquals($this->rbfilter->type, $filter->type);
        $this->assertEquals($this->rbfilter->filtername, $filter->filtername);
        $this->assertEquals($this->rbfilter->sortorder, $filter->sortorder);
        $this->assertEquals($this->rbfilter->advanced, $filter->advanced);

        $control = $DB->get_record('report_builder_filters', array('id' => $this->contfil->id));
        $this->assertEquals($this->contfil->value, $control->value);
        $this->assertEquals($this->contfil->type, $control->type);
        $this->assertEquals($this->contfil->filtername, $control->filtername);
        $this->assertEquals($this->contfil->sortorder, $control->sortorder);
        $this->assertEquals($this->contfil->advanced, $control->advanced);

        totara_reportbuilder_migrate_filter_types(array('fullname' => 'shortname'), 'user', 'manager');

        $filter = $DB->get_record('report_builder_filters', array('id' => $this->rbfilter->id));
        $this->assertEquals('shortname', $filter->value);
        $this->assertEquals('manager', $filter->type);
        $this->assertEquals($this->rbfilter->filtername, $filter->filtername);
        $this->assertEquals($this->rbfilter->sortorder, $filter->sortorder);
        $this->assertEquals($this->rbfilter->advanced, $filter->advanced);

        $control = $DB->get_record('report_builder_filters', array('id' => $this->contfil->id));
        $this->assertEquals($this->contfil->value, $control->value);
        $this->assertEquals($this->contfil->type, $control->type);
        $this->assertEquals($this->contfil->filtername, $control->filtername);
        $this->assertEquals($this->contfil->sortorder, $control->sortorder);
        $this->assertEquals($this->contfil->advanced, $control->advanced);
    }

    public function test_upgradelib_migrate_saved_search_filters() {
        global $DB;

        totara_reportbuilder_migrate_saved_search_filters(array('fullname' => 'shortname'), 'user', 'user');

        $saved = $DB->get_record('report_builder_saved', array('id' => $this->rbsaved->id));
        $this->assertEquals($this->rbsaved->name, $saved->name);
        $search = unserialize($saved->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('user-shortname', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        $control = $DB->get_record('report_builder_saved', array('id' => $this->contsave->id));
        $this->assertEquals($this->contsave->name, $control->name);
        $search = unserialize($control->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('user-username', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        totara_reportbuilder_migrate_saved_search_filters(array('shortname' => 'middlename'), 'user', 'manager');

        $saved = $DB->get_record('report_builder_saved', array('id' => $this->rbsaved->id));
        $this->assertEquals($this->rbsaved->name, $saved->name);
        $search = unserialize($saved->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('manager-middlename', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        $control = $DB->get_record('report_builder_saved', array('id' => $this->contsave->id));
        $this->assertEquals($this->contsave->name, $control->name);
        $search = unserialize($control->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('user-username', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }
    }

    public function test_upgradelib_migrate_saved_searches() {
        global $DB;

        $saved = $DB->get_record('report_builder_saved', array('id' => $this->rbsaved->id));
        totara_reportbuilder_migrate_saved_searches('*', 'user', 'fullname', 'user', 'shortname');

        $saved = $DB->get_record('report_builder_saved', array('id' => $this->rbsaved->id));
        $this->assertEquals($this->rbsaved->name, $saved->name);
        $search = unserialize($saved->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('user-shortname', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        $control = $DB->get_record('report_builder_saved', array('id' => $this->contsave->id));
        $this->assertEquals($this->contsave->name, $control->name);
        $search = unserialize($control->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('user-username', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        totara_reportbuilder_migrate_saved_searches('user', 'user', 'shortname', 'manager', 'middlename');

        $saved = $DB->get_record('report_builder_saved', array('id' => $this->rbsaved->id));
        $this->assertEquals($this->rbsaved->name, $saved->name);
        $search = unserialize($saved->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('manager-middlename', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        $control = $DB->get_record('report_builder_saved', array('id' => $this->contsave->id));
        $this->assertEquals($this->contsave->name, $control->name);
        $search = unserialize($control->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('user-username', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

        // Check if source is incorrect, record is not migrated.
        totara_reportbuilder_migrate_saved_searches('course_completions', 'manager', 'middlename', 'user', 'shortname');

        $saved = $DB->get_record('report_builder_saved', array('id' => $this->rbsaved->id));
        $this->assertEquals($this->rbsaved->name, $saved->name);
        $search = unserialize($saved->search);
        foreach ($search as $key => $value) {
            $this->assertEquals('manager-middlename', $key);
            $this->assertEquals(0, $value['operator']);
            $this->assertEquals('a', $value['value']);
        }

    }

    public function test_upgradelib_populate_scheduled_reports_usermodified() {
        global $DB;

        $yesterday = \time() - (24 * 60 * 60);
        $template = new \stdClass();
        $template->reportid = $this->report->id;
        $template->userid = 0;
        $template->savedsearchid = 0;
        $template->format = 'csv';
        $template->exporttofilesystem = 0;
        $template->frequency = 4;
        $template->schedule = 1;
        $template->nextreport = $yesterday;
        $template->usermodified = 0;
        $template->lastmodified = $yesterday;

        $users = \array_map(
            function ($i) {
                return $this->getDataGenerator()->create_user()->id;
            },

            \range(0, 20, 1)
        );

        $scheduled = \array_map(
            function ($userid) use ($template) {
                $record = clone $template;
                $record->userid = $userid;

                return $record;
            },

            $users
        );

        $DB->insert_records('report_builder_schedule', $scheduled);
        \totara_reportbuilder_populate_scheduled_reports_usermodified();

        \array_map(
            function (\stdClass $actual) use ($template) {
                $this->assertNotEmpty($actual->id, 'invalid id');
                $this->assertSame((int)$actual->reportid, $template->reportid, 'wrong reportid');
                $this->assertNotEmpty($actual->userid, 'invalid userid');
                $this->assertSame((int)$actual->savedsearchid, $template->savedsearchid, 'wrong savedsearchid');
                $this->assertSame($actual->format, $template->format, 'wrong format');
                $this->assertSame((int)$actual->exporttofilesystem, $template->exporttofilesystem, 'wrong exporttofilesystem');
                $this->assertSame((int)$actual->frequency, $template->frequency, 'wrong frequency');
                $this->assertSame((int)$actual->schedule, $template->schedule, 'wrong schedule');
                $this->assertNotEmpty($actual->usermodified, 'invalid usermodified');
                $this->assertSame($actual->userid, $actual->usermodified, 'wrong usermodified');
                $this->assertSame((int)$actual->lastmodified, $template->lastmodified, 'wrong lastmodified');
            },

            $DB->get_records('report_builder_schedule')
        );
    }

    public function test_upgradelib_totara_reportbuilder_migrate_svggraph_settings() {
        global $DB;

        // Test the basics
        $this->rbgraph->settings = "
            graph_title=Hello
            graph_title_font_size=24
            graph_title_colour=#f00
            show_legend=true
            show_tooltips=false
            axis_text_angle_h=-90
            colours = red,green,#0000ff
        ";

        $DB->update_record('report_builder_graph', $this->rbgraph);
        totara_reportbuilder_migrate_svggraph_settings();
        $graph = $DB->get_record('report_builder_graph', array('id' => $this->rbgraph->id));

        // the way this converts, booleans are converted into strings, but it doesn't matter
        // so long as they are correctly truthy and falsy values. These aren't converted because
        // we can't tell whether the value is meant to be 'true', or the integer '1'
        $expected = json_encode([
            'title' => [
                'text' => 'Hello',
                'fontSize' => '24',
                'color' => '#f00'
            ],
            'legend' => [
                'display' => '1'
            ],
            'tooltips' => [
                'display' => ''
            ],
            // Makes sure the unrecognized settings are placed into the "custom" field for full backwards compatibility
            'custom' => [
                'axis_text_angle_h' => '-90'
            ],
            'colors' => 'red,green,#0000ff',
        ], JSON_PRETTY_PRINT);
        $this->assertEquals($expected, $graph->settings);

        $settings = json_decode($graph->settings);

        // Check truthy and falsy values
        $this->assertEquals(false, $settings->tooltips->display);
        $this->assertEquals(true, $settings->legend->display);

        // When we run the upgrade a second time, verify that the settings remain unchanged
        totara_reportbuilder_migrate_svggraph_settings();

        $graph = $DB->get_record('report_builder_graph', array('id' => $this->rbgraph->id));
        $this->assertEquals($expected, $graph->settings);
    }

    /**
     * Test migration of competency_evidence report to competency_status report
     * We create the test report will all columns and filter and with a saved report containing values for all columns
     */
    public function test_migrate_competency_evidence_to_competency_status_perform() {
        global $DB;

        $report = new stdclass();
        $report->fullname = 'Test Evidence Report';
        $report->shortname = 'tster';
        $report->source = 'competency_evidence';
        $report->hidden = 0;
        $report->accessmode = 0;
        $report->contentmode = 0;
        $report->description = '';
        $report->recordsperpage = 40;
        $report->embedded = 0;
        $report->id = $DB->insert_record('report_builder', $report);
        $this->report = $report;

        $orig_columns = [
            [
                'type' => 'competency_evidence',
                'value' => 'proficiency',
                'updated_to' => [
                    'type' => 'competency_status',
                    'value' => 'scale_value_name',
                ],
            ],
            [
                'type' => 'competency_evidence',
                'value' => 'proficiencyid',
                'filter' => [
                    'operator' => 1,
                    'value' => 1,
                ],
                'updated_to' => [
                    'type' => 'competency_status',
                    'value' => 'scale_value_id',
                ],
            ],
            [
                'type' => 'competency_evidence',
                'value' => 'timemodified',
                'filter' => [
                    'before' => time(),
                ],
                'updated_to' => [
                    'type' => 'competency',
                    'value' => 'time_created',
                ],
            ],
            [
                'type' => 'competency_evidence',
                'value' => 'proficientdate',
                'filter' => [
                    'before' => time(),
                ],
                'deleted' => true,
            ],
            [
                'type' => 'competency_evidence',
                'value' => 'organisationid',
                'filter' => [
                    'operator' => 1,
                    'value' => 1,
                ],
                'deleted' => true,
            ],
            [
                'type' => 'competency_evidence',
                'value' => 'organisationid2',
                'filter' => [
                    'value' => 1,
                ],
                'deleted' => true,
            ],
            [
                'type' => 'competency_evidence',
                'value' => 'organisationpath',
                'filter' => [
                    'operator' => 1,
                    'value' => 1,
                ],
                'deleted' => true,
            ],
            [
                'type' => 'competency_evidence',
                'value' => 'organisation',
                'deleted' => true,
            ],
            [
                'type' => 'competency_evidence',
                'value' => 'positionid',
                'filter' => [
                    'operator' => 1,
                    'value' => 1,
                ],
                'deleted' => true,
            ],
            [
                'type' => 'competency_evidence',
                'value' => 'positionid2',
                'filter' => [
                    'value' => 1,
                ],
                'deleted' => true,
            ],
            [
                'type' => 'competency_evidence',
                'value' => 'positionpath',
                'deleted' => true,
            ],
            [
                'type' => 'competency_evidence',
                'value' => 'position',
                'deleted' => true,
            ],
            [
                'type' => 'competency_evidence',
                'value' => 'assessor',
                'filter' => [
                    'operator' => 0,
                    'value' => 'Assessor',
                ],
                'deleted' => true,
            ],
            [
                'type' => 'competency_evidence',
                'value' => 'assessorname',
                'filter' => [
                    'operator' => 0,
                    'value' => 'AssessorName',
                ],
                'deleted' => true,
            ],
            [
                'type' => 'competency',
                'value' => 'fullname',
                'filter' => [
                    'operator' => 0,
                    'value' => 'CompFull',
                ],
            ],
            [
                'type' => 'competency',
                'value' => 'shortname',
                'filter' => [
                    'operator' => 0,
                    'value' => 'CompShort',
                ],
                'deleted' => true,
            ],
            [
                'type' => 'competency',
                'value' => 'idnumber',
                'filter' => [
                    'operator' => 0,
                    'value' => 'CompID',
                ],
            ],
            [
                'type' => 'competency',
                'value' => 'competencylink',
                'deleted' => true,
            ],
            [
                'type' => 'competency',
                'value' => 'id',
            ],
            [
                'type' => 'competency',
                'value' => 'id2',
                'filter' => [
                    'value' => 1,
                ],
                'deleted' => true,
            ],
            [
                'type' => 'competency',
                'value' => 'path',
                'filter' => [
                    'operator' => 1,
                    'value' => 1,
                ],
                'deleted' => true,
            ],
            [
                'type' => 'competency',
                'value' => 'statushistorylink',
                'deleted' => true,
            ],
        ];

        // Columns, Filters and Search cols
        $orig_saved = [];

        foreach ($orig_columns as $idx => $data) {
            $rbcolumn = new stdClass();
            $rbcolumn->reportid = $report->id;
            $rbcolumn->type = $data['type'];
            $rbcolumn->value = $data['value'];
            $rbcolumn->sortorder = $idx;
            $rbcolumn->hidden = 0;
            $rbcolumn->customheading = 0;
            $rbcolumn->id = $DB->insert_record('report_builder_columns', $rbcolumn);

            if (isset($data['filter'])) {
                $rbfilter = new stdClass();
                $rbfilter->reportid = $report->id;
                $rbfilter->type = $data['type'];
                $rbfilter->value = $data['value'];
                $rbfilter->filtername = "{$data['value']} Filter";
                $rbfilter->advanced = 0;
                $rbfilter->sortorder = $idx;
                $rbfilter->id = $DB->insert_record('report_builder_filters', $rbfilter);

                $save_key = "{$data['type']}-{$data['value']}";
                $orig_saved[$save_key] = $data['filter'];

                $search_col = new stdClass();
                $search_col->reportid = $report->id;
                $search_col->type = $data['type'];
                $search_col->value = $data['value'];
                $search_col->id = $DB->insert_record('report_builder_search_cols', $search_col);
            }
        }

        // Saved Search
        $rbsaved = new stdClass();
        $rbsaved->reportid = $report->id;
        $rbsaved->userid = $this->user->id;
        $rbsaved->name = 'Saved Search';
        $rbsaved->search = serialize($orig_saved);
        $rbsaved->ispublic = 1;
        $rbsaved->id = $DB->insert_record('report_builder_saved', $rbsaved);

        // Verify saved report
        $this->assertSame(count($orig_columns), $DB->count_records('report_builder_columns', ['reportid' => $report->id]));

        $orig_filters = array_filter($orig_columns, function ($column) {
            return isset($column['filter']);
        });
        $this->assertSame(count($orig_filters), $DB->count_records('report_builder_filters', ['reportid' => $report->id]));
        $this->assertSame(count($orig_filters), $DB->count_records('report_builder_search_cols', ['reportid' => $report->id]));

        $saved_row = $DB->get_record('report_builder_saved', ['reportid' => $report->id]);
        $this->assertEquals(serialize($orig_saved), $saved_row->search);


        // Now for the migration
        reportbuilder_migrate_competency_evidence_to_competency_status_perform();

        $new_columns = array_filter($orig_columns, function ($column) {
            return empty($column['deleted']);
        });
        $new_columns = array_map(function ($column) {
            if (isset($column['updated_to'])) {
                return  $column['updated_to'] + (isset($column['filter']) ? ['filter' => $column['filter']] : []);
            } else {
                return $column;
            }
        }, $new_columns);

        $new_filters = array_filter($new_columns, function ($column) {
            return isset($column['filter']);
        });

        $new_saved = [];
        foreach ($new_filters as $column) {
            $key = "{$column['type']}-{$column['value']}";
            $new_saved[$key] = $column['filter'];
        }

        $actual_columns = $DB->get_records('report_builder_columns', ['reportid' => $report->id]);
        $actual_filters = $DB->get_records('report_builder_filters', ['reportid' => $report->id]);
        $actual_search_cols = $DB->get_records('report_builder_search_cols', ['reportid' => $report->id]);
        $actual_saved = $DB->get_record('report_builder_saved', ['reportid' => $report->id]);
        $actual_report = $DB->get_record('report_builder', ['id' => $report->id]);

        $this->assertSame(count($new_columns), count($actual_columns));
        $this->assertSame(count($new_filters), count($actual_filters));
        $this->assertSame(count($new_filters), count($actual_search_cols));
        $this->assertSame('competency_status', $actual_report->source);

        $this->assertEqualsCanonicalizing($new_saved, unserialize($actual_saved->search));
    }

    public function test_totara_reportbuilder_migrate_svggraph_category() {
        global $DB;

        $this->assertEquals('none', $this->rbgraph->category); // this is wrong, should be {$type}-{$value}
        // Update
        $DB->update_record('report_builder_graph', (object)['id' => $this->rbgraph->id, 'category' => 'manager-middlename']);
        $graph = $DB->get_record('report_builder_graph', array('id' => $this->rbgraph->id));
        $this->assertEquals('manager-middlename', $graph->category);

        totara_reportbuilder_migrate_svggraph_category('user', 'manager', 'middlename', 'user', 'shortname');
        $graph = $DB->get_record('report_builder_graph', array('id' => $this->rbgraph->id));
        $this->assertEquals('user-shortname', $graph->category);
        // different source, should not change the original
        totara_reportbuilder_migrate_svggraph_category('abc', 'user', 'shortname', 'manager', 'middlename');
        $graph = $DB->get_record('report_builder_graph', array('id' => $this->rbgraph->id));
        $this->assertEquals('user-shortname', $graph->category);
        // different old type, should not change the original
        totara_reportbuilder_migrate_svggraph_category('user', 'abc', 'shortname', 'manager', 'middlename');
        $graph = $DB->get_record('report_builder_graph', array('id' => $this->rbgraph->id));
        $this->assertEquals('user-shortname', $graph->category);
        // different old value, should not change the original
        totara_reportbuilder_migrate_svggraph_category('user', 'user', 'abc', 'manager', 'middlename');
        $graph = $DB->get_record('report_builder_graph', array('id' => $this->rbgraph->id));
        $this->assertEquals('user-shortname', $graph->category);
        // missing a new type
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("all params must have a valid value");
        totara_reportbuilder_migrate_svggraph_category('user', 'user', 'shortname', '', 'middlename');
    }

    public function test_totara_reportbuilder_migrate_default_sort_columns_by_source() {
        global $DB;

        $this->assertEquals('user_fullname', $this->report->defaultsortcolumn);

        totara_reportbuilder_migrate_default_sort_columns_by_source('user', 'user', 'fullname', 'manager', 'middlename');
        $rbreport = $DB->get_record('report_builder', array('id' => $this->report->id));
        $this->assertEquals('manager_middlename', $rbreport->defaultsortcolumn);

        // different source, should not change the original
        totara_reportbuilder_migrate_default_sort_columns_by_source('abc', 'manager', 'middlename', 'user', 'fullname');
        $rbreport = $DB->get_record('report_builder', array('id' => $this->report->id));
        $this->assertEquals('manager_middlename', $rbreport->defaultsortcolumn);
        // different old type, should not change the original
        totara_reportbuilder_migrate_default_sort_columns_by_source('user', 'abc', 'middlename', 'user', 'fullname');
        $rbreport = $DB->get_record('report_builder', array('id' => $this->report->id));
        $this->assertEquals('manager_middlename', $rbreport->defaultsortcolumn);
        // different old value, should not change the original
        totara_reportbuilder_migrate_default_sort_columns_by_source('user', 'manager', 'abc', 'user', 'fullname');
        $rbreport = $DB->get_record('report_builder', array('id' => $this->report->id));
        $this->assertEquals('manager_middlename', $rbreport->defaultsortcolumn);
        // missing a new type
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("all params must have a valid value");
        totara_reportbuilder_migrate_default_sort_columns_by_source('user', 'manager', 'middlename', '', 'fullname');
    }

    public function test_totara_reportbuilder_inject_filter_into_report_no_shortname() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/filters/lib.php');

        $this->expectException(coding_exception::class);
        $this->expectErrorMessage('all params must have a valid value');

        totara_reportbuilder_inject_filter_into_report('', 'test_type', 'test_value');
    }

    public function test_totara_reportbuilder_inject_filter_into_report_no_type() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/filters/lib.php');

        $this->expectException(coding_exception::class);
        $this->expectErrorMessage('all params must have a valid value');

        totara_reportbuilder_inject_filter_into_report('test_shortname', '', 'test_value');
    }

    public function test_totara_reportbuilder_inject_filter_into_report_no_value() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/filters/lib.php');

        $this->expectException(coding_exception::class);
        $this->expectErrorMessage('all params must have a valid value');

        totara_reportbuilder_inject_filter_into_report('test_shortname', 'test_type', '');
    }

    public function test_totara_reportbuilder_inject_filter_into_report() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/reportbuilder/filters/lib.php');

        /** @var totara_reportbuilder_generator $reportgenerator */
        $report_generator = $this->getDataGenerator()->get_plugin_generator("totara_reportbuilder");

        // First add something in the report_builder and  report_builder_filters tables
        $report_record = [
            'fullname' => 'test_report',
            'shortname' => 'test_report',
            'source' => 'test_report',
        ];
        $report_id = $DB->insert_record('report_builder', $report_record);

        $filter_record = [
            'reportid' => $report_id,
            'type' => 'base',
            'value' => 'test',
            'sortorder' => 1,
        ];
        $filter_id = $DB->insert_record('report_builder_filters', $filter_record);

        $this->assertSame(1, $DB->count_records('report_builder', ['shortname' => 'test_report']));
        $this->assertSame(1, $DB->count_records('report_builder_filters', ['reportid' => $report_id]));

        // Non-existing shortname - nothing should change
        totara_reportbuilder_inject_filter_into_report('other_report', 'base', 'test');
        $this->assertSame(1, $DB->count_records('report_builder_filters', ['reportid' => $report_id]));

        // Existing filter - nothing should change
        totara_reportbuilder_inject_filter_into_report('test_report', 'base', 'test');
        $this->assertSame(1, $DB->count_records('report_builder_filters', ['reportid' => $report_id]));

        // New filter - should be added
        totara_reportbuilder_inject_filter_into_report('test_report', 'base', 'new');
        $this->assertSame(2, $DB->count_records('report_builder_filters', ['reportid' => $report_id]));
    }

}
