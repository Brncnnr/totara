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
 * @author Simon Player <simon.playerv@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

use totara_core\advanced_feature;

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_disabled_subsystems_testcase extends advanced_testcase {

    protected $embedded_reports_id = null;

    /**
     * Do the setup.
     */
    protected function setUp(): void {
        global $DB;
        parent::setup();
        $this->setAdminUser();

        // For 'Record of Learning: Competencies' only
        advanced_feature::disable('competency_assignment');
        // Generate the embedded reports.
        reportbuilder::generate_embedded_reports();

        // Get the embedded reports report id.
        $this->embedded_reports_id = $DB->get_field('report_builder', 'id', ['shortname' => 'manage_embedded_reports', 'embedded' => 1]);
    }

    /**
     * Tear down
     */
    protected function tearDown(): void {
        $this->embedded_reports_id = null;
        parent::tearDown();
    }

    /**
     * Gets the records for the embedded reports report
     *
     * @return array
     */
    private function get_embedded_report_records() {
        global $DB;

        reportbuilder::reset_source_object_cache();
        reportbuilder::reset_caches();
        totara_rb_purge_ignored_reports();

        $report = reportbuilder::create_embedded('manage_embedded_reports');
        list($sql, $params) = $report->build_query();

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * @param array $data report records from get_embedded_report_records
     * @param string $search report name to search for
     * @return bool True if report exists
     */
    private function embedded_report_exists($data, $search) {
        foreach ($data as $item) {
            if (!empty($item->report_namelinkeditview) && $item->report_namelinkeditview == $search) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enable/disable auth_connect plugin.
     *
     * @param bool $enabled
     */
    private function set_auth_connect_enabled($enabled) {
        global $CFG;
        $authsenabled = explode(',', $CFG->auth);

        if ($enabled) {
            $authsenabled[] = 'connect';
            $authsenabled = array_unique($authsenabled);
            set_config('auth', implode(',', $authsenabled));
        } else {
            $key = array_search('connect', $authsenabled);
            if ($key !== false) {
                unset($authsenabled[$key]);
                set_config('auth', implode(',', $authsenabled));
            }
        }
    }

    public function test_rol_enabled() {
        advanced_feature::enable('recordoflearning');

        // Embedded reports.
        $records = $this->get_embedded_report_records($this->embedded_reports_id);
        self::assertTrue($this->embedded_report_exists($records, 'Record of Learning: Certifications'));
        self::assertTrue($this->embedded_report_exists($records, 'Record of Learning: Competencies'));
        self::assertTrue($this->embedded_report_exists($records, 'Record of Learning: Courses'));
        self::assertTrue($this->embedded_report_exists($records, 'Record of Learning: Objectives'));
        self::assertTrue($this->embedded_report_exists($records, 'Record of Learning: Previous Certifications'));
        self::assertTrue($this->embedded_report_exists($records, 'Record of Learning: Programs Completion History'));
        self::assertTrue($this->embedded_report_exists($records, 'Record of Learning: Programs'));
        self::assertTrue($this->embedded_report_exists($records, 'Record of Learning: Recurring programs'));
        self::assertTrue($this->embedded_report_exists($records, 'Record of Learning: Programs Completion History'));
        self::assertTrue($this->embedded_report_exists($records, 'My Current Courses'));

        // User reports.
        $reports = reportbuilder::get_source_list();
        self::assertTrue(in_array('Record of Learning: Certifications', $reports));
        self::assertTrue(in_array('Record of Learning: Competencies', $reports));
        self::assertTrue(in_array('Record of Learning: Courses', $reports));
        self::assertTrue(in_array('Record of Learning: Objectives', $reports));
        self::assertTrue(in_array('Record of Learning: Previous Certifications', $reports));
        self::assertTrue(in_array('Record of Learning: Previous Course Completions', $reports));
        self::assertTrue(in_array('Record of Learning: Programs', $reports));
        self::assertTrue(in_array('Record of Learning: Recurring Programs', $reports));
    }

    public function test_rol_disabled() {
        advanced_feature::disable('recordoflearning');

        // Embedded reports.
        $records = $this->get_embedded_report_records($this->embedded_reports_id);
        self::assertFalse($this->embedded_report_exists($records, 'Record of Learning: Certifications'));
        self::assertFalse($this->embedded_report_exists($records, 'Record of Learning: Competencies'));
        self::assertFalse($this->embedded_report_exists($records, 'Record of Learning: Courses'));
        self::assertFalse($this->embedded_report_exists($records, 'Record of Learning: Objectives'));
        self::assertFalse($this->embedded_report_exists($records, 'Record of Learning: Previous Certifications'));
        self::assertFalse($this->embedded_report_exists($records, 'Record of Learning: Programs Completion History'));
        self::assertFalse($this->embedded_report_exists($records, 'Record of Learning: Programs'));
        self::assertFalse($this->embedded_report_exists($records, 'Record of Learning: Recurring programs'));
        self::assertFalse($this->embedded_report_exists($records, 'Record of Learning: Programs Completion History'));
        self::assertFalse($this->embedded_report_exists($records, 'My Current Courses'));

        // User reports.
        $reports = reportbuilder::get_source_list();
        self::assertFalse(in_array('Record of Learning: Certifications', $reports));
        self::assertFalse(in_array('Record of Learning: Competencies', $reports));
        self::assertFalse(in_array('Record of Learning: Courses', $reports));
        self::assertFalse(in_array('Record of Learning: Objectives', $reports));
        self::assertFalse(in_array('Record of Learning: Previous Certifications', $reports));
        self::assertFalse(in_array('Record of Learning: Previous Course Completions', $reports));
        self::assertFalse(in_array('Record of Learning: Programs', $reports));
        self::assertFalse(in_array('Record of Learning: Recurring Programs', $reports));
    }

    public function test_program_completion_editor_enabled() {
        set_config('enableprogramcompletioneditor', true);

        // Embedded reports.
        $records = $this->get_embedded_report_records($this->embedded_reports_id);
        self::assertTrue($this->embedded_report_exists($records, 'Certification Membership'));

        // User reports.
        $reports = reportbuilder::get_source_list();
        self::assertTrue(in_array('Certification Membership', $reports));
    }

    public function test_program_completion_editor_disabled() {
        set_config('enableprogramcompletioneditor', false);

        // Embedded reports.
        $records = $this->get_embedded_report_records($this->embedded_reports_id);
        self::assertFalse($this->embedded_report_exists($records, 'Certification Membership'));

        // User reports.
        $reports = reportbuilder::get_source_list();
        self::assertTrue(in_array('Certification Membership', $reports));
    }

    public function test_totara_connect_server_enabled() {
        set_config('enableconnectserver', '1');

        // Embedded reports.
        $records = $this->get_embedded_report_records($this->embedded_reports_id);
        self::assertTrue($this->embedded_report_exists($records, 'Totara Connect clients'));

        // User reports.
        $reports = reportbuilder::get_source_list();
        self::assertTrue(in_array('Totara Connect clients', $reports));
    }

    public function test_totara_connect_server_disabled() {
        set_config('enableconnectserver', '0');

        // Embedded reports.
        $records = $this->get_embedded_report_records($this->embedded_reports_id);
        self::assertFalse($this->embedded_report_exists($records, 'Totara Connect servers clients'));

        // User reports.
        $reports = reportbuilder::get_source_list();
        self::assertFalse(in_array('Totara Connect servers clients', $reports));
    }

    public function test_totara_connect_client_enabled() {
        $this->set_auth_connect_enabled(true);

        // Embedded reports.
        $records = $this->get_embedded_report_records($this->embedded_reports_id);
        self::assertTrue($this->embedded_report_exists($records, 'Totara Connect servers'));

        // User reports.
        $reports = reportbuilder::get_source_list();
        self::assertTrue(in_array('Totara Connect servers', $reports));
    }

    public function test_totara_connect_client_disabled() {
        $this->set_auth_connect_enabled(false);

        // Embedded reports.
        $records = $this->get_embedded_report_records($this->embedded_reports_id);
        self::assertFalse($this->embedded_report_exists($records, 'Totara Connect servers'));

        // User reports.
        $reports = reportbuilder::get_source_list();
        self::assertFalse(in_array('Totara Connect servers', $reports));
    }

    public function test_audience_based_visibility_enabled() {
        set_config('audiencevisibility', '1');

        // Embedded reports.
        $records = $this->get_embedded_report_records($this->embedded_reports_id);
        self::assertTrue($this->embedded_report_exists($records, 'Audience: Visible Learning'));

        // User reports.
        $reports = reportbuilder::get_source_list();
        self::assertTrue(in_array('Audience: Visible Learning', $reports));
    }

    public function test_audience_based_visibility__disabled() {
        set_config('audiencevisibility', '0');

        // Embedded reports.
        $records = $this->get_embedded_report_records($this->embedded_reports_id);
        self::assertFalse($this->embedded_report_exists($records, 'Audience: Visible Learning'));

        // User reports.
        $reports = reportbuilder::get_source_list();
        self::assertFalse(in_array('Audience: Visible Learning', $reports));
    }
}
