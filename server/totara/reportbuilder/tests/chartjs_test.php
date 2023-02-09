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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_chartjs_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    protected function init_graph($rid) {
        $report = reportbuilder::create($rid);
        $graph = new \totara_reportbuilder\local\graph\chartjs($report, false);
        $this->assertTrue($graph->is_valid());
        list($sql, $params, $cache) = $report->build_query(false, true);
        $order = $report->get_report_sort(false);
        $reportdb = $report->get_report_db();
        if ($records = $reportdb->get_recordset_sql($sql.$order, $params, 0, $graph->get_max_records())) {
            foreach ($records as $record) {
                $graph->add_record($record);
            }
        }

        return $graph;
    }

    public function test_chartjs_month_created() {
        global $DB;

        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $rid = $this->create_report('user', 'Test user report 1');

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);
        $this->add_column($report, 'statistics', 'coursescompleted', null, null, null, 0);
        $this->add_column($report, 'user', 'timecreated', 'month', null, null, 0);

        $graphrecords = $this->add_graph($rid, 'column', 0, 500, 'user-username', '', array('user-timecreated'), '');
        $graphrecord = reset($graphrecords);

        $graph = $this->init_graph($rid);

        $data = $graph->render();
        $this->assertStringNotContainsString('Zero length axis', $data);
        $this->assertStringContainsString($user1->username, $data);
        $this->assertStringContainsString($user2->username, $data);
    }
}
