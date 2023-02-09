<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_totara_report_graph
 */

use \block_totara_report_graph\util;

/**
 * Test the util class for report graph block.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_totara_report_graph
 */
class block_totara_report_graph_util_testcase extends advanced_testcase {

    use \block_totara_report_graph\phpunit\block_testing;

    public function test_get_report() {
        global $DB, $USER;

        $this->setAdminUser();

        $rid = $this->create_user_report_with_graph();

        $rbsaved = new stdClass();
        $rbsaved->reportid = $rid;
        $rbsaved->userid = $USER->id;
        $rbsaved->name = 'Saved Search';
        $rbsaved->search = 'a:1:{s:13:"user-fullname";a:2:{s:8:"operator";i:0;s:5:"value";s:5:"Admin";}}'; // Invalid data will do here.
        $rbsaved->ispublic = 1;
        $rbsaved->id = $DB->insert_record('report_builder_saved', $rbsaved);

        $report = util::get_report($rid);
        $this->assertInstanceOf('stdClass', $report);
        $this->assertSame((string)$rid, $report->id);

        $report = util::get_report(0);
        $this->assertFalse($report);

        $report = util::get_report(-1 * $rbsaved->id);
        $this->assertInstanceOf('stdClass', $report);
        $this->assertSame((string)$rid, $report->id);

        $report = util::get_report($rid + 1);
        $this->assertFalse($report);
    }

    public function test_get_chart_data() {
        global $CFG, $USER;

        $oldlog = ini_get('error_log');
        ini_set('error_log', "$CFG->dataroot/testlog.log"); // Prevent standard logging.

        $this->setAdminUser();

        $rid = $this->create_user_report_with_graph();
        $block = $this->create_report_graph_block_instance($rid, ['graph_height' => 777, 'reportfor' => $USER->id]);
        $config = unserialize(base64_decode($block->configdata));

        $data = util::get_chart_data($block, $config);
        $this->assertIsArray($data);
        $this->assertSame(null, $data['width']);
        $this->assertSame(777, $data['height']);
        $this->assertArrayHasKey('chart', $data);

        ini_set('error_log', $oldlog);
    }

    public function test_get_get_cached_chart_data() {
        global $CFG, $USER;

        $oldlog = ini_get('error_log');
        ini_set('error_log', "$CFG->dataroot/testlog.log"); // Prevent standard logging.

        $this->setAdminUser();

        $rid = $this->create_user_report_with_graph();
        $block = $this->create_report_graph_block_instance($rid, ['graph_height' => 777, 'reportfor' => $USER->id]);
        $config = unserialize(base64_decode($block->configdata));

        $cacheddata = util::get_cached_chart_data($block, $config);
        $this->assertSame(null, $cacheddata);

        $data = util::get_chart_data($block, $config);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('chart', $data);

        $cacheddata = util::get_cached_chart_data($block, $config);
        $this->assertSame($data, $cacheddata);

        ini_set('error_log', $oldlog);
    }
}
