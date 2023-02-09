<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_graph_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_normalize_numeric_value() {
        $method = new ReflectionMethod('totara_reportbuilder\local\graph\svggraph', 'normalize_numeric_value');
        $method->setAccessible(true);

        $this->assertSame(111,    $method->invoke(null, '111'));
        $this->assertSame(-1e10,  $method->invoke(null, '-1e10'));
        $this->assertSame(111,    $method->invoke(null, 111));
        $this->assertSame(11.1,   $method->invoke(null, 11.1));
        $this->assertSame(0,      $method->invoke(null, 0));
        $this->assertSame(0,      $method->invoke(null, '0'));
        $this->assertSame(0.0,    $method->invoke(null, '0.0'));
        $this->assertSame(111,    $method->invoke(null, ' 111 '));
        $this->assertSame(111.11, $method->invoke(null, '111.11'));
        $this->assertSame(111.11, $method->invoke(null, '111,11'));
        $this->assertSame(99,     $method->invoke(null, '99%'));
        $this->assertSame(99,     $method->invoke(null, '99 %'));
        $this->assertSame(0,      $method->invoke(null, '1 111'));
        $this->assertSame(0,      $method->invoke(null, '111,111.111'));
        $this->assertSame(0,      $method->invoke(null, '%99'));
        $this->assertSame(0,      $method->invoke(null, '  '));
        $this->assertSame(0,      $method->invoke(null, ''));
        $this->assertSame(0,      $method->invoke(null, null));
        $this->assertSame(0,      $method->invoke(null, 'abc'));
        $this->assertSame(0,      $method->invoke(null, true));
        $this->assertSame(0,      $method->invoke(null, false));
        $this->assertSame(10,     $method->invoke(null, 012));
        $this->assertSame(12.0,   $method->invoke(null, '012')); // No octal support in strings - cast to float.
        $this->assertSame(800.0,  $method->invoke(null, '0800')); // No octal support in strings - cast to float.
        $this->assertSame(496,    $method->invoke(null, 0x1f0));
        $this->assertEquals(0,    $method->invoke(null, '0x1f0')); // No hexadecimal support in strings - cast to float. PHP7 returns 0, older 0.0.
        $this->assertSame(255,    $method->invoke(null, 0b11111111));
        $this->assertSame(0,      $method->invoke(null, '0b11111111')); // No binary support in strings.
    }

    public function test_is_graphable() {
        global $DB;
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $user->firstaccess  = strtotime('2013-01-10 10:00:00 UTC');
        $user->timemodified = strtotime('2013-01-10 10:00:00 UTC');
        $user->lastlogin    = 0;
        $user->currentlogin = strtotime('2013-01-10 10:00:00 UTC'); // This is the lastlogin in reports.
        $user->timecreated  = strtotime('2013-01-10 10:00:00 UTC');
        $user->firstname  = 'řízek';
        $DB->update_record('user', $user);

        context_user::instance($user->id);

        $rid = $this->create_report('user', 'Test user report 1');

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);
        $this->add_column($report, 'user', 'firstaccess', 'month', null, null, 0);
        $this->add_column($report, 'user', 'timemodified', null, null, null, 0);
        $this->add_column($report, 'user', 'lastlogin', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, 'countany', null, 0);
        $this->add_column($report, 'user', 'timecreated', 'weekday', null, null, 0);
        $this->add_column($report, 'statistics', 'coursescompleted', null, null, null, 0);
        $this->add_column($report, 'user', 'namewithlinks', null, null, null, 0);

        $report = reportbuilder::create($rid);

        // Let's hack the column options in memory only, hopefully this will continue working in the future...
        $report->columns['user-firstaccess']->displayfunc = 'month';
        $report->columns['user-timemodified']->displayfunc = 'nice_date';
        $report->columns['user-lastlogin']->displayfunc = 'nice_datetime';
        $report->columns['user-firstname']->displayfunc = 'ucfirst';
        $report->columns['user-timecreated']->displayfunc = 'weekday';

        $column = $report->columns['user-id'];
        $this->assertTrue($column->is_graphable($report));

        $column = $report->columns['user-username'];
        $this->assertFalse($column->is_graphable($report));

        $column = $report->columns['user-firstaccess'];
        $this->assertTrue($column->is_graphable($report));

        $column = $report->columns['user-timemodified'];
        $this->assertFalse($column->is_graphable($report));

        $column = $report->columns['user-lastlogin'];
        $this->assertFalse($column->is_graphable($report));

        $column = $report->columns['user-firstname'];
        $this->asserttrue($column->is_graphable($report));

        $column = $report->columns['user-timecreated'];
        $this->assertTrue($column->is_graphable($report));

        $column = $report->columns['statistics-coursescompleted'];
        $this->assertTrue($column->is_graphable($report));

        $column = $report->columns['user-namewithlinks'];
        $this->assertFalse($column->is_graphable($report));
    }

    protected function init_graph($rid) {
        $report = reportbuilder::create($rid);
        $graph = new \totara_reportbuilder\local\graph\svggraph($report, false);
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

    public function test_graph_zero_data() {
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

        $graphrecords = $this->add_graph($rid, 'column', 0, 500, 'user-username', '', array('statistics-coursescompleted'), '');
        $graphrecord = reset($graphrecords);

        $graph = $this->init_graph($rid);
        $data = $graph->render();
        $this->assertStringNotContainsString('Zero length axis', $data);
        $this->assertStringContainsString($user1->username, $data);
        $this->assertStringContainsString($user2->username, $data);

        $graph = $this->init_graph($rid);
        $data = $graph->render(400, 400);
        $this->assertStringNotContainsString('Zero length axis', $data);
        $this->assertStringContainsString($user1->username, $data);
        $this->assertStringContainsString($user2->username, $data);

        $graph = $this->init_graph($rid);
        $data = $graph->render(1000, 1000);
        $this->assertStringNotContainsString('Zero length axis', $data);
        $this->assertStringContainsString($user1->username, $data);
        $this->assertStringContainsString($user2->username, $data);

        $DB->set_field('report_builder_graph', 'type', 'bar', array('id' => $graphrecord->id));
        $graph = $this->init_graph($rid);
        $data = $graph->render();
        $this->assertStringNotContainsString('Zero length axis', $data);
        $this->assertStringContainsString($user1->username, $data);
        $this->assertStringContainsString($user2->username, $data);

        $DB->set_field('report_builder_graph', 'type', 'line', array('id' => $graphrecord->id));
        $graph = $this->init_graph($rid);
        $data = $graph->render();
        $this->assertStringNotContainsString('Zero length axis', $data);
        $this->assertStringContainsString($user1->username, $data);
        $this->assertStringContainsString($user2->username, $data);

        $DB->set_field('report_builder_graph', 'type', 'scatter', array('id' => $graphrecord->id));
        $graph = $this->init_graph($rid);
        $data = $graph->render();
        $this->assertStringNotContainsString('Zero length axis', $data);
        $this->assertStringContainsString($user1->username, $data);
        $this->assertStringContainsString($user2->username, $data);

        $DB->set_field('report_builder_graph', 'type', 'area', array('id' => $graphrecord->id));
        $graph = $this->init_graph($rid);
        $data = $graph->render();
        $this->assertStringNotContainsString('Zero length axis', $data);
        $this->assertStringContainsString($user1->username, $data);
        $this->assertStringContainsString($user2->username, $data);

        $DB->set_field('report_builder_graph', 'type', 'pie', array('id' => $graphrecord->id));
        $graph = $this->init_graph($rid);
        $data = $graph->render();
        $this->assertStringContainsString('Empty pie chart', $data);
    }

    public function test_remove_empty_series() {
        global $DB;
        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $notfirstdayofyear = 1485946800;
        $DB->set_field('user', 'timecreated', $notfirstdayofyear, array());

        // Remove all should result in null data.
        $rid = $this->create_report('user', 'Test user report 1');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);
        $this->add_column($report, 'statistics', 'coursescompleted', null, null, null, 0);
        $graphrecords = $this->add_graph($rid, 'column', 0, 500, 'user-username', '', array('statistics-coursescompleted'), 'remove_empty_series=0');
        $graph = $this->init_graph($rid);
        $data = $graph->render();
        $this->assertNotEquals($data, '');
        $this->assertStringContainsString($user1->username, $data);
        $this->assertStringContainsString($user2->username, $data);
        $this->assertStringContainsString('admin', $data);
        $this->assertStringContainsString('guest', $data);

        $graphrecord = reset($graphrecords);
        $graphrecord->settings = '{ "custom": { "remove_empty_series" : true }}';
        $DB->update_record('report_builder_graph', $graphrecord);
        $graph = $this->init_graph($rid);
        $data = $graph->render();
        $this->assertEquals($data, '');

        // No empty series the data has to be the same (not exactly the same because there are static properties in svggraph).

        $rid = $this->create_report('user', 'Test user report 2a');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);
        $this->add_column($report, 'user', 'timecreated', 'dayyear', null, null, 0);
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $column = $report->columns['user-timecreated'];
        $this->assertTrue($column->is_graphable($report));
        $graphrecords = $this->add_graph($rid, 'column', 0, 500, 'user-username', '', array('user-timecreated'), 'remove_empty_series=0');

        $graph = $this->init_graph($rid);
        $data = $graph->render();
        $this->assertNotEquals($data, '');
        $this->assertStringContainsString($user1->username, $data);
        $this->assertStringContainsString($user2->username, $data);
        $this->assertStringContainsString('admin', $data);
        $this->assertStringContainsString('guest', $data);

        $graphrecord = reset($graphrecords);
        $graphrecord->settings = '{ "custom": { "remove_empty_series" : true }}';
        $DB->update_record('report_builder_graph', $graphrecord);
        $graph = $this->init_graph($rid);
        $this->assertNotEquals($data, '');
        $this->assertStringContainsString($user1->username, $data);
        $this->assertStringContainsString($user2->username, $data);
        $this->assertStringContainsString('admin', $data);
        $this->assertStringContainsString('guest', $data);

        // Removal of empty series.

        $rid = $this->create_report('user', 'Test user report 3');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, null, null, 0);
        $this->add_column($report, 'user', 'timecreated', 'dayyear', null, null, 0);
        $this->add_column($report, 'statistics', 'coursescompleted', null, null, null, 0);
        $this->add_column($report, 'statistics', 'coursesstarted', null, null, null, 0);
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $column = $report->columns['user-timecreated'];
        $this->assertTrue($column->is_graphable($report));
        $graphrecords = $this->add_graph($rid, 'column', 0, 500, 'user-username', '', array('user-timecreated', 'statistics-coursescompleted', 'statistics-coursesstarted'), 'remove_empty_series=0');

        $graph = $this->init_graph($rid);
        $data = $graph->render();
        $this->assertNotEquals($data, '');
        $this->assertStringContainsString($user1->username, $data);
        $this->assertStringContainsString($user2->username, $data);
        $this->assertStringContainsString('admin', $data);
        $this->assertStringContainsString('guest', $data);
        $this->assertStringContainsString('User Creation Time - day of year', $data);
        $this->assertStringContainsString('User\'s Courses Completed Count', $data);
        $this->assertStringContainsString('User\'s Courses Started Count', $data);

        $graphrecord = reset($graphrecords);
        $graphrecord->settings = '{ "custom": { "remove_empty_series" : true }}';
        $DB->update_record('report_builder_graph', $graphrecord);
        $graph = $this->init_graph($rid);
        $data = $graph->render();
        $this->assertNotEquals($data, '');
        $this->assertStringContainsString($user1->username, $data);
        $this->assertStringContainsString($user2->username, $data);
        $this->assertStringContainsString('admin', $data);
        $this->assertStringContainsString('guest', $data);
        $this->assertStringContainsString('User Creation Time - day of year', $data);
        $this->assertStringNotContainsString('User\'s Courses Completed Count', $data);
        $this->assertStringNotContainsString('User\'s Courses Started Count', $data);
    }
}
