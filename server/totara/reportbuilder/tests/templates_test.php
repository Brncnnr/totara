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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

class totara_reportbuilder_templates_testcase extends advanced_testcase {

    /**
     * @var array Available graph types
     */
    private $graphtypes;

    /**
     * @var array Available content modes
     */
    private $contentmodes;

    /**
     * @var array Available access modes
     */
    private $accessmodes;

    public function setUp(): void {
        parent::setUp();

        $this->graphtypes = array('column', 'line', 'bar', 'pie', 'scatter', 'area', 'doughnut', 'progress');
        $this->contentmodes = array(REPORT_BUILDER_CONTENT_MODE_NONE, REPORT_BUILDER_CONTENT_MODE_ANY, REPORT_BUILDER_CONTENT_MODE_ALL);
        $this->accessmodes = array(REPORT_BUILDER_ACCESS_MODE_NONE, REPORT_BUILDER_ACCESS_MODE_ANY, REPORT_BUILDER_ACCESS_MODE_ALL);
    }

    public function tearDown(): void {
        $this->graphtypes = null;
        $this->contentmodes = null;
        $this->accessmodes = null;
        parent::tearDown();
    }

    public function test_templates() {
        global $DB;

        foreach (totara_reportbuilder\template_helper::get_templates() as $template_classname) {
            $template = totara_reportbuilder\template_helper::get_template_object($template_classname);

            // Ensure the template is using the correct instance.
            self::assertInstanceOf('totara_reportbuilder\rb\template\base', $template);

            // Test required fields.
            self::assertNotEmpty($template->fullname);
            self::assertNotEmpty($template->shortname);
            self::assertNotEmpty($template->source);
            self::assertNotEmpty($template->label);

            // Get the source object.
            $src = \reportbuilder::get_source_object($template->source);

            // Check template columns exist in source.
            if ($template->columns) {
                foreach ($template->columns as $template_column) {
                    $exist = false;
                    foreach ($src->columnoptions as $src_column) {
                        if ($template_column['type'] == $src_column->type && $template_column['value'] == $src_column->value) {
                            $exist = true;
                            continue;
                        }
                    }

                    self::assertTrue($exist, "Column {$template_column['type']} : {$template_column['value']} does not exist in source for template {$template_classname}");
                }
            }

            // Check template columns transforms exists.
            if ($template->columns) {
                foreach ($template->columns as $template_column) {
                    if (!empty($template_column['transform'])) {
                        $transform = $template_column['transform'];
                        $classname = "\\totara_reportbuilder\\rb\\transform\\$transform";

                        self::assertTrue(class_exists($classname), "Transform {$transform} does not exist for Column {$template_column['type']} : {$template_column['value']} in template {$template_classname}");
                    }
                }
            }

            // Check template columns aggregations exists.
            if ($template->columns) {
                foreach ($template->columns as $template_column) {
                    if (!empty($template_column['aggregate'])) {
                        $aggregate = $template_column['aggregate'];
                        $classname = "\\totara_reportbuilder\\rb\\aggregate\\$aggregate";

                        self::assertTrue(class_exists($classname), "Aggregation {$aggregate} does not exist for Column {$template_column['type']} : {$template_column['value']} in template {$template_classname}");
                    }
                }
            }

            // Check template filters exist in source.
            if ($template->filters) {
                foreach ($template->filters as $template_filter) {
                    $exist = false;
                    foreach ($src->filteroptions as $src_filter) {
                        if ($template_filter['type'] == $src_filter->type && $template_filter['value'] == $src_filter->value) {
                            $exist = true;
                            continue;
                        }
                    }
                    self::assertTrue($exist, "Filter {$template_filter['type']} : {$template_filter['value']} does not exist in source for template {$template_classname}");
                }
            }

            // Check content restrictions.
            foreach ($template->contentsettings as $option => $settings) {
                $classname = $src->resolve_content_classname($option);
                self::assertNotNull($classname, "The content restriction class {$option} does not exists for template {$template_classname}");
            }

            // Check access restrictions.
            foreach ($template->accesssettings as $option => $settings) {
                $classname = 'totara_reportbuilder\rb\access\\' . $option;
                self::assertTrue(class_exists($classname), "The access restriction class {$classname} does not exists for template {$template_classname}");
            }

            // Check graph.
            if ($template->graph) {
                self::assertTrue(in_array($template->graph['type'], $this->graphtypes), "The graph type {$template->graph['type']} does not exist for template {$template_classname}");
            }

            // Check content mode.
            self::assertTrue(in_array($template->contentmode, $this->contentmodes), "The content mode {$template->contentmode} does not exist for template {$template_classname}");

            // Check access mode.
            self::assertTrue(in_array($template->accessmode, $this->accessmodes), "The access mode {$template->accessmode} does not exist for template {$template_classname}");

            // Create and check the report.
            $reportid = totara_reportbuilder\template_helper::create_from_name($template_classname);
            $reportrecord = $DB->get_record('report_builder', array('id' => $reportid), '*', MUST_EXIST);

            self::assertEquals($template->fullname, $reportrecord->fullname);
            self::assertEquals('report_' . $template->shortname, $reportrecord->shortname);
            self::assertEquals($template->source, $reportrecord->source);
            self::assertEquals($template->hidden, $reportrecord->hidden);
            self::assertEquals($template->defaultsortcolumn, $reportrecord->defaultsortcolumn);
            self::assertEquals($template->defaultsortorder, $reportrecord->defaultsortorder);
            self::assertEquals($template->recordsperpage, $reportrecord->recordsperpage);
            self::assertEquals($template->contentmode, $reportrecord->contentmode);
            self::assertEquals($template->accessmode, $reportrecord->accessmode);
        }
    }
}