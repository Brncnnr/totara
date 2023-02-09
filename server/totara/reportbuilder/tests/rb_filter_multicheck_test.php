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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/cohort/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/filters/multicheck.php');

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_rb_filter_multicheck_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    private $report = null;

    public function tearDown(): void {
        $this->report = null;
    }

    public function setUp(): void {
        global $DB, $CFG;

        $this->setAdminUser();

        $cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        // Create some courses.
        $courses[] = $this->getDataGenerator()->create_course(
            ['fullname' => 'Name0', 'summary' => 'Summary0', 'enablecompletion' => 1, 'completionstartonenrol' => 1, 'coursetype' => 0]);
        $courses[] = $this->getDataGenerator()->create_course(
            ['fullname' => 'Name1', 'summary' => 'Summary1', 'enablecompletion' => 1, 'completionstartonenrol' => 1, 'coursetype' => 1]);


        // Create some users.
        $members = [];
        for ($i = 1; $i <= 8; $i++) {
            $user = $this->getDataGenerator()->create_user(['username' => 'user' . $i]);
            $users[$i] = $user;

            if ($i % 2 === 0) {
                $members[] = $user->id;
            }
        }

        // Assign everyone to a cohort so we can use cohort enrolments.
        $cohort = $cohort_generator->create_cohort(['cohorttype' => \cohort::TYPE_STATIC]);
        $cohort_generator->cohort_assign_users($cohort->id, $members);
        $cohortplugin = enrol_get_plugin('cohort');
        $cohortplugin->add_instance($courses[0], ['customint1' => $cohort->id, 'roleid' => $studentrole->id]);

        /**
         * Data structure
         * --------------------
         *  U | S | M | C | P |
         * --------------------
         *  0 | - | - | - | - |
         *  1 | Y | - | - | - |
         *  2 | - | Y | - | - |
         *  3 | - | - | Y | - |
         *  4 | Y | Y | - | - |
         *  5 | - | Y | Y | - |
         *  6 | Y | - | Y | - |
         *  7 | Y | Y | Y | - |
         */
        $this->getDataGenerator()->enrol_user($users[1]->id, $courses[0]->id, null, 'self', $studentrole->id);
        $this->getDataGenerator()->enrol_user($users[2]->id, $courses[0]->id, null, 'manual', $studentrole->id);
        $this->getDataGenerator()->enrol_user($users[3]->id, $courses[0]->id, null, 'cohort', $studentrole->id);
        $this->getDataGenerator()->enrol_user($users[4]->id, $courses[0]->id, null, 'self', $studentrole->id);
        $this->getDataGenerator()->enrol_user($users[4]->id, $courses[0]->id, null, 'manual', $studentrole->id);
        $this->getDataGenerator()->enrol_user($users[5]->id, $courses[0]->id, null, 'manual', $studentrole->id);
        $this->getDataGenerator()->enrol_user($users[5]->id, $courses[0]->id, null, 'cohort', $studentrole->id);
        $this->getDataGenerator()->enrol_user($users[6]->id, $courses[0]->id, null, 'self', $studentrole->id);
        $this->getDataGenerator()->enrol_user($users[6]->id, $courses[0]->id, null, 'cohort', $studentrole->id);
        $this->getDataGenerator()->enrol_user($users[7]->id, $courses[0]->id, null, 'self', $studentrole->id);
        $this->getDataGenerator()->enrol_user($users[7]->id, $courses[0]->id, null, 'manual', $studentrole->id);
        $this->getDataGenerator()->enrol_user($users[7]->id, $courses[0]->id, null, 'cohort', $studentrole->id);

        // Create a report.
        $this->report = $this->create_report('course_completion', 'enrolment_types_report');
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($this->report, $config);

        // Add the enrolment type column and filter.
        $filter = new \stdClass();
        $filter->reportid = $this->report;
        $filter->advanced = 0;
        $filter->region = rb_filter_type::RB_FILTER_REGION_STANDARD;
        $filter->type = 'course_completion';
        $filter->value = 'enrolmenttype';
        $filter->filtername = 'EnrolType';
        $filter->customname = 1;
        $filter->sortorder = 1;
        $DB->insert_record('report_builder_filters', $filter);

    }

    /**
     * Test the multiselect filter without data set.
     */
    public function test_multiselect_filter_matches_notset() {
        global $DB;

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($this->report, $config);
        $filters = $report->get_filters();
        $filter = $filters['course_completion-enrolmenttype'];
        $this->assertInstanceOf('rb_filter_multicheck', $filter);

        // Check there are 7 users displayed without the filter enabled.
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(7, $records);
    }

    /**
     * Test the multiselect filter matches any operator.
     */
    public function test_multiselect_filter_matches_any() {
        global $DB;

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($this->report, $config);
        $filters = $report->get_filters();
        $filter = $filters['course_completion-enrolmenttype'];
        $this->assertInstanceOf('rb_filter_multicheck', $filter);

        // Set the filter to self enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ANY, 'value' => ['self' => 1, 'manual' => 0, 'cohort' => 0, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(4, $records);

        // Set the filter to manual enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ANY, 'value' => ['self' => 0, 'manual' => 1, 'cohort' => 0, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(4, $records);

        // Set the filter to audience enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ANY, 'value' => ['self' => 0, 'manual' => 0, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(4, $records);

        // Set the filter to program enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ANY, 'value' => ['self' => 0, 'manual' => 0, 'cohort' => 0, 'totara_program' => 1]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(0, $records);

        // Set the filter to self + manual enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ANY, 'value' => ['self' => 1, 'manual' => 1, 'cohort' => 0, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(6, $records);

        // Set the filter to self + cohort enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ANY, 'value' => ['self' => 1, 'manual' => 0, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(6, $records);

        // Set the filter to manual + cohort enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ANY, 'value' => ['self' => 0, 'manual' => 1, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(6, $records);

        // Set the filter to self + manual + cohort enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ANY, 'value' => ['self' => 1, 'manual' => 1, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(7, $records);

        // Set the filter to self + manual + cohort + program enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ANY, 'value' => ['self' => 1, 'manual' => 1, 'cohort' => 1, 'totara_program' => 1]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(7, $records);
    }

    /**
     * Test the multiselect filter matches all operator.
     */
    public function test_multiselect_filter_matches_all() {
        global $DB;

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($this->report, $config);
        $filters = $report->get_filters();
        $filter = $filters['course_completion-enrolmenttype'];
        $this->assertInstanceOf('rb_filter_multicheck', $filter);

        // Set the filter to self enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ALL, 'value' => ['self' => 1, 'manual' => 0, 'cohort' => 0, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(4, $records);

        // Set the filter to manual enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ALL, 'value' => ['self' => 0, 'manual' => 1, 'cohort' => 0, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(4, $records);

        // Set the filter to audience enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ALL, 'value' => ['self' => 0, 'manual' => 0, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(4, $records);

        // Set the filter to program enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ALL, 'value' => ['self' => 0, 'manual' => 0, 'cohort' => 0, 'totara_program' => 1]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(0, $records);

        // Set the filter to self + manual enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ALL, 'value' => ['self' => 1, 'manual' => 1, 'cohort' => 0, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(2, $records);

        // Set the filter to self + cohort enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ALL, 'value' => ['self' => 1, 'manual' => 0, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(2, $records);

        // Set the filter to manual + cohort enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ALL, 'value' => ['self' => 0, 'manual' => 1, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(2, $records);

        // Set the filter to self + manual + cohort enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ALL, 'value' => ['self' => 1, 'manual' => 1, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);

        // Set the filter to self + manual + cohort + program enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ALL, 'value' => ['self' => 1, 'manual' => 1, 'cohort' => 1, 'totara_program' => 1]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(0, $records);
    }

    /**
     * Test the multiselect filter matches any operator.
     */
    public function test_multiselect_filter_matches_notany() {
        global $DB;

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($this->report, $config);
        $filters = $report->get_filters();
        $filter = $filters['course_completion-enrolmenttype'];
        $this->assertInstanceOf('rb_filter_multicheck', $filter);

        // Set the filter to self enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTANY, 'value' => ['self' => 1, 'manual' => 0, 'cohort' => 0, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(3, $records);

        // Set the filter to manual enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTANY, 'value' => ['self' => 0, 'manual' => 1, 'cohort' => 0, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(3, $records);

        // Set the filter to audience enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTANY, 'value' => ['self' => 0, 'manual' => 0, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(3, $records);

        // Set the filter to program enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTANY, 'value' => ['self' => 0, 'manual' => 0, 'cohort' => 0, 'totara_program' => 1]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(7, $records);

        // Set the filter to self + manual enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTANY, 'value' => ['self' => 1, 'manual' => 1, 'cohort' => 0, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);

        // Set the filter to self + cohort enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTANY, 'value' => ['self' => 1, 'manual' => 0, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);

        // Set the filter to manual + cohort enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTANY, 'value' => ['self' => 0, 'manual' => 1, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);

        // Set the filter to self + manual + cohort enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTANY, 'value' => ['self' => 1, 'manual' => 1, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(0, $records);

        // Set the filter to self + manual + cohort + program enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTANY, 'value' => ['self' => 1, 'manual' => 1, 'cohort' => 1, 'totara_program' => 1]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(0, $records);
    }

    /**
     * Test the multiselect filter matches any operator.
     */
    public function test_multiselect_filter_matches_notall() {
        global $DB;

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($this->report, $config);
        $filters = $report->get_filters();
        $filter = $filters['course_completion-enrolmenttype'];
        $this->assertInstanceOf('rb_filter_multicheck', $filter);

        // Set the filter to self enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTALL, 'value' => ['self' => 1, 'manual' => 0, 'cohort' => 0, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(3, $records);

        // Set the filter to manual enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTALL, 'value' => ['self' => 0, 'manual' => 1, 'cohort' => 0, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(3, $records);

        // Set the filter to audience enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTALL, 'value' => ['self' => 0, 'manual' => 0, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(3, $records);

        // Set the filter to program enrolment.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTALL, 'value' => ['self' => 0, 'manual' => 0, 'cohort' => 0, 'totara_program' => 1]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(7, $records);

        // Set the filter to self + manual enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTALL, 'value' => ['self' => 1, 'manual' => 1, 'cohort' => 0, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(5, $records);

        // Set the filter to self + cohort enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTALL, 'value' => ['self' => 1, 'manual' => 0, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(5, $records);

        // Set the filter to manual + cohort enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTALL, 'value' => ['self' => 0, 'manual' => 1, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(5, $records);

        // Set the filter to self + manual + cohort enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTALL, 'value' => ['self' => 1, 'manual' => 1, 'cohort' => 1, 'totara_program' => 0]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(6, $records);

        // Set the filter to self + manual + cohort + program enrolments.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTALL, 'value' => ['self' => 1, 'manual' => 1, 'cohort' => 1, 'totara_program' => 1]]);
        $report = reportbuilder::create($this->report);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(7, $records);
    }

    public function test_multicheck_for_filters_with_concat_false() {
        global $DB;

        /** @var \mod_facetoface\testing\generator $f2fgenerator */
        $f2fgenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        // Create a couple of courses
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Create sessions for the courses.
        $session1 = $f2fgenerator->create_session_for_course($course1);
        $session2 = $f2fgenerator->create_session_for_course($course2, 2);

        // Create some users
        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $student3 = $this->getDataGenerator()->create_user();
        $student4 = $this->getDataGenerator()->create_user();

        // Enrol users
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($student3->id, $course1->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course2->id);
        $this->getDataGenerator()->enrol_user($student4->id, $course2->id);

        // Generate signups for the users.
        $f2fgenerator->create_signup($student1, $session1);
        $f2fgenerator->create_signup($student2, $session1);
        $f2fgenerator->create_signup($student3, $session1);
        $f2fgenerator->create_signup($student2, $session2);
        $f2fgenerator->create_signup($student4, $session2);

        // Create cancellation
        $f2fgenerator->create_cancellation($student2, $session1);

        // Create report.
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create_embedded('facetoface_sessions', $config);

        // Add status filter.
        $filter = new \stdClass();
        $filter->reportid = $report->_id;
        $filter->advanced = 0;
        $filter->region = rb_filter_type::RB_FILTER_REGION_STANDARD;
        $filter->type = 'status';
        $filter->value = 'statuscode';
        $filter->filtername = 'statusCode';
        $filter->customname = 1;
        $filter->sortorder = 1;
        $DB->insert_record('report_builder_filters', $filter);

        // Check filter was added.
        $filters = $report->get_filters();
        $filter = $filters['status-statuscode'];
        $this->assertInstanceOf('rb_filter_multicheck', $filter);

        // Check the filter has concat option set to false.
        $reflection = new \ReflectionClass($filter);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $this->assertFalse($property->getValue($filter)['concat']);

        // Check records without filters.
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(5, $records);

        // Defining some of the status options.
        $booked = \mod_facetoface\signup\state\booked::get_code();
        $waitlisted = \mod_facetoface\signup\state\waitlisted::get_code();
        $usercancelled = \mod_facetoface\signup\state\user_cancelled::get_code();
        $eventcancelled = \mod_facetoface\signup\state\event_cancelled::get_code();

        // Set filter to 'Any of the selected'.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ANY, 'value' => [$booked => 1, $waitlisted => 1, $usercancelled => 0, $eventcancelled => 0]]);
        $report = reportbuilder::create($report->_id);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(4, $records);

        // Set filter to 'All of the selected'.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_ALL, 'value' => [$booked => 1, $waitlisted => 1, $usercancelled => 0, $eventcancelled => 0]]);
        $report = reportbuilder::create($report->_id);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(0, $records);

        // Set filter to 'Not any of the selected'.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTANY, 'value' => [$booked => 1, $waitlisted => 1, $usercancelled => 0, $eventcancelled => 0]]);
        $report = reportbuilder::create($report->_id);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);

        // Set filter to 'Not all of the selected'.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTALL, 'value' => [$booked => 1, $waitlisted => 1, $usercancelled => 1, $eventcancelled => 0]]);
        $report = reportbuilder::create($report->_id);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(5, $records);

        // Set filter to 'Not all of the selected'.
        $filter->set_data(['operator' => rb_filter_multicheck::RB_MULTICHECK_NOTALL, 'value' => [$booked => 1, $waitlisted => 0, $usercancelled => 0, $eventcancelled => 0]]);
        $report = reportbuilder::create($report->_id);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(1, $records);
    }
}
