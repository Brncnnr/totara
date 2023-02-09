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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

class totara_reportbuilder_filtering_required_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_is_filtering_required() {
        global $CFG, $DB;
        require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

        $this->setAdminUser(); // We need permissions to view all reports.

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $rid = $this->create_report('user', 'Test user report');
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_DESC, array('id' => $rid));
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, null, null, 0);
        $this->add_column($report, 'user', 'lastname', null, null, null, 0);
        // Sort the columns in predictable way - PostgreSQL may return random order otherwise.
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $report->_id));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_DESC, array('id' => $report->_id));
        $this->add_filter($report, 'user', 'firstname', 0, 'Name', 0, 0, []);
        $this->add_filter($report, 'user', 'fullname', 0, 'Name', 0, 0, []);
        $this->add_filter($report, 'user', 'deleted', 0, 'User Status', 0, 0, []);

        $report = reportbuilder::create($rid);
        $this->assertFalse($report->is_filtering_required());

        $DB->set_field('report_builder_filters', 'filteringrequired', 1, ['reportid' => $report->_id, 'type' => 'user', 'value' => 'fullname']);
        $report = reportbuilder::create($rid);
        $this->assertTrue($report->is_filtering_required());

        $DB->set_field('report_builder_filters', 'advanced', 1, ['reportid' => $report->_id, 'type' => 'user', 'value' => 'fullname']);
        $report = reportbuilder::create($rid);
        $this->assertFalse($report->is_filtering_required());

        $DB->set_field('report_builder_filters', 'advanced', 0, ['reportid' => $report->_id, 'type' => 'user', 'value' => 'fullname']);
        $DB->set_field('report_builder_filters', 'region', rb_filter_type::RB_FILTER_REGION_SIDEBAR, ['reportid' => $report->_id, 'type' => 'user', 'value' => 'fullname']);
        $report = reportbuilder::create($rid);
        $this->assertFalse($report->is_filtering_required());

        $DB->set_field('report_builder_filters', 'region', rb_filter_type::RB_FILTER_REGION_STANDARD, ['reportid' => $report->_id, 'type' => 'user', 'value' => 'fullname']);
        $report = reportbuilder::create($rid);
        $this->assertTrue($report->is_filtering_required());
    }

    public function test_get_missing_filtering() {
        global $CFG, $DB, $SESSION;
        require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

        $this->setAdminUser(); // We need permissions to view all reports.

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $rid = $this->create_report('user', 'Test user report');
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_DESC, array('id' => $rid));
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, null, null, 0);
        $this->add_column($report, 'user', 'lastname', null, null, null, 0);
        // Sort the columns in predictable way - PostgreSQL may return random order otherwise.
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $report->_id));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_DESC, array('id' => $report->_id));
        $this->add_filter($report, 'user', 'firstname', 0, 'First', 0, 0, [], 1);
        $this->add_filter($report, 'user', 'fullname', 0, 'Name', 0, 0, [], 1);
        $this->add_filter($report, 'user', 'lastname', 0, 'Last', 0, 0, [], 0);

        $SESSION->reportbuilder = [];
        $report = reportbuilder::create($rid);
        $this->assertTrue($report->is_filtering_required());
        $this->assertSame(['User First Name', 'User\'s Fullname'], $report->get_missing_filtering());

        $SESSION->reportbuilder = [];
        $SESSION->reportbuilder[$report->get_uniqueid()]['user-fullname'] = ['operator' => 1, 'value' => 'xx'];
        $report = reportbuilder::create($rid);
        $this->assertTrue($report->is_filtering_required());
        $this->assertSame(['User First Name'], $report->get_missing_filtering());

        $SESSION->reportbuilder = [];
        $SESSION->reportbuilder[$report->get_uniqueid()]['user-fullname'] = ['operator' => 1, 'value' => 'xx'];
        $SESSION->reportbuilder[$report->get_uniqueid()]['user-firstname'] = ['operator' => 1, 'value' => 'yy'];
        $report = reportbuilder::create($rid);
        $this->assertTrue($report->is_filtering_required());
        $this->assertSame([], $report->get_missing_filtering());

        $SESSION->reportbuilder = [];
        $SESSION->reportbuilder[$report->get_uniqueid()]['user-lastname'] = ['operator' => 1, 'value' => 'xx'];
        $report = reportbuilder::create($rid);
        $this->assertTrue($report->is_filtering_required());
        $this->assertSame(['User First Name', 'User\'s Fullname'], $report->get_missing_filtering());
    }

    public function test_scheduled_reports_checked() {
        global $CFG, $DB;
        require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

        $this->setAdminUser(); // We need permissions to access all reports.
        $admin = get_admin();

        $testdir = make_writable_directory($CFG->dataroot . '/mytest');
        $testdir = realpath($testdir);
        $this->assertFileExists($testdir);

        set_config('exporttofilesystem', '1', 'reportbuilder');
        set_config('exporttofilesystempath', $testdir, 'reportbuilder');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $rid = $this->create_report('user', 'Test user report');
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_DESC, array('id' => $rid));
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, null, null, 0);
        $this->add_column($report, 'user', 'lastname', null, null, null, 0);
        // Sort the columns in predictable way - PostgreSQL may return random order otherwise.
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $report->_id));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_DESC, array('id' => $report->_id));
        $this->add_filter($report, 'user', 'firstname', 0, 'Name', 0, 0, []);
        $this->add_filter($report, 'user', 'fullname', 0, 'Name', 0, 0, []);
        $this->add_filter($report, 'user', 'deleted', 0, 'User Status', 0, 0, []);

        $schedule1 = new stdClass();
        $schedule1->reportid = $report->_id;
        $schedule1->savedsearchid = 0;
        $schedule1->format = 'csv';
        $schedule1->frequency = 1; // Means daily.
        $schedule1->schedule = 0; // Means midnight.
        $schedule1->exporttofilesystem = REPORT_BUILDER_EXPORT_SAVE;
        $schedule1->nextreport = 0; // Means asap.
        $schedule1->userid = $admin->id;
        $schedule1->usermodified = $admin->id;
        $schedule1->lastmodified = time();
        $schedule1->id = $DB->insert_record('report_builder_schedule', $schedule1);
        $schedule1 = $DB->get_record('report_builder_schedule', array('id' => $schedule1->id));
        $DB->insert_record('report_builder_schedule_email_systemuser', array('scheduleid' => $schedule1->id, 'userid' => $user1->id));
        ob_start(); // Verify diagnostic output.
        $result = reportbuilder_send_scheduled_report($schedule1);
        $this->assertTrue($result);
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Scheduled report {$schedule1->id} was saved in file system\n";
        $this->assertSame($expected, $info);

        $DB->set_field('report_builder_filters', 'filteringrequired', 1, ['reportid' => $report->_id, 'type' => 'user', 'value' => 'fullname']);
        $report = reportbuilder::create($rid);
        $this->assertTrue($report->is_filtering_required());

        ob_start(); // Verify diagnostic output.
        $result = reportbuilder_send_scheduled_report($schedule1);
        $this->assertFalse($result);
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Error: Scheduled report {$schedule1->id} is missing required filtering input for filters: User's Fullname\n";
        $this->assertSame($expected, $info);

        $rbsaved = new stdClass();
        $rbsaved->reportid = $report->_id;
        $rbsaved->userid = $user1->id;
        $rbsaved->name = 'Saved Search';
        $rbsaved->search = serialize(['user-fullname' => ['operator' => 0, 'value' => 'xx']]);
        $rbsaved->ispublic = 1;
        $rbsaved->id = $DB->insert_record('report_builder_saved', $rbsaved);

        $schedule2 = new stdClass();
        $schedule2->reportid = $report->_id;
        $schedule2->savedsearchid = $rbsaved->id;
        $schedule2->format = 'csv';
        $schedule2->frequency = 1; // Means daily.
        $schedule2->schedule = 0; // Means midnight.
        $schedule2->exporttofilesystem = REPORT_BUILDER_EXPORT_SAVE;
        $schedule2->nextreport = 0; // Means asap.
        $schedule2->userid = $admin->id;
        $schedule2->usermodified = $admin->id;
        $schedule2->lastmodified = time();
        $schedule2->id = $DB->insert_record('report_builder_schedule', $schedule2);
        $schedule2 = $DB->get_record('report_builder_schedule', array('id' => $schedule2->id));
        ob_start(); // Verify diagnostic output.
        $result = reportbuilder_send_scheduled_report($schedule2);
        $this->assertFalse($result); // We don't send or save empty reports.
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Scheduled Report {$schedule2->id} does not contain any data\n";
        $this->assertSame($expected, $info);

        $rbsaved = new stdClass();
        $rbsaved->reportid = $report->_id;
        $rbsaved->userid = $user1->id;
        $rbsaved->name = 'Saved Search';
        $rbsaved->search = serialize(['user-fullname' => ['operator' => 0, 'value' => 'Admin']]);
        $rbsaved->ispublic = 1;
        $rbsaved->id = $DB->insert_record('report_builder_saved', $rbsaved);

        $schedule2 = new stdClass();
        $schedule2->reportid = $report->_id;
        $schedule2->savedsearchid = $rbsaved->id;
        $schedule2->format = 'csv';
        $schedule2->frequency = 1; // Means daily.
        $schedule2->schedule = 0; // Means midnight.
        $schedule2->exporttofilesystem = REPORT_BUILDER_EXPORT_SAVE;
        $schedule2->nextreport = 0; // Means asap.
        $schedule2->userid = $admin->id;
        $schedule2->usermodified = $admin->id;
        $schedule2->lastmodified = time();
        $schedule2->id = $DB->insert_record('report_builder_schedule', $schedule2);
        $schedule2 = $DB->get_record('report_builder_schedule', array('id' => $schedule2->id));
        ob_start(); // Verify diagnostic output.
        $result = reportbuilder_send_scheduled_report($schedule2);
        $this->assertTrue($result);
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Scheduled report {$schedule2->id} was saved in file system\n";
        $this->assertSame($expected, $info);
    }
}
