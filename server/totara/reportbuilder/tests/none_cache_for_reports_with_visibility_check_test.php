<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_none_cache_for_reports_with_visibility_check_testcase extends advanced_testcase {

    use totara_reportbuilder\phpunit\report_testing;

    /**
     * Data provider for test_none_cache_for_reports_with_visibility_check
     */
    public function reports_with_visibility_checks() {
        return [
            ['dp_program', 'Record of Learning: Programs', 1],
            ['dp_program_recurring', 'Record of Learning: Recurring programs', 1],
            ['course_completion', 'Test course report', 0],
            ['course_completion_all', 'Test course report all', 0],
            ['facetoface_events', 'Seminar events', 0],
            ['facetoface_signin', 'Seminar Signin', 0],
            ['facetoface_sessions', 'Seminar Sessions', 0],
            ['facetoface_summary', 'Seminar Summary', 0],
            ['course_membership', 'Course membership', 0],
            ['dp_certification', 'Record of Learning: Certifications', 1],
            ['perform_element', 'Perform element', 1],
            ['perform_response', 'Perform response', 1],
            ['perform_response_element', 'Perform response element', 1],
            ['perform_response_subject_instance', 'Perform response subject instance', 1],
        ];
    }

    /**
     * Check all reports with visibility check has the cache disabled.
     *
     * @dataProvider reports_with_visibility_checks
     */
    public function test_none_cache_for_reports_with_visibility_check($source, $shortname, $embedded) {
        global $CFG;
        require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

        $this->setAdminUser(); // We need permissions to access all reports.

        // Create report.
        $reportid = $this->create_report($source, $shortname, false, $embedded);
        $report = reportbuilder::create($reportid, null, false);
        $this->assertEquals(-1, $report->get_cache_status(), 'Cache is allowed for a report that checks visibility.');
    }
}
