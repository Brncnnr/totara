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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara_reportbuilder
 */

use totara_core\advanced_feature;

global $CFG;
require_once($CFG->dirroot . '/totara/plan/rb_sources/rb_source_dp_competency.php');

class rb_plan_competencies_embedded extends rb_base_embedded {

    public function __construct($data) {
        $this->url = '/totara/plan/record/competencies.php';
        $this->source = 'dp_competency';
        $this->defaultsortcolumn = 'competency_fullname';
        $this->shortname = 'plan_competencies';
        $this->fullname = get_string('recordoflearningcompetencies', 'totara_plan');
        $this->columns = rb_source_dp_competency::get_default_columns();
        $this->filters = rb_source_dp_competency::get_default_filters();

        // no restrictions
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        $this->embeddedparams = array();
        if (isset($data['userid'])) {
            $this->embeddedparams['userid'] = $data['userid'];
        }
        if (isset($data['rolstatus'])) {
            $this->embeddedparams['rolstatus'] = $data['rolstatus'];
        }
        if (isset($data['competencyid'])) {
            $this->embeddedparams['competencyid'] = $data['competencyid'];
        }

        parent::__construct();
    }

    /**
     * Check if the user is capable of accessing this report.
     * We use $reportfor instead of $USER->id and $report->get_param_value() instead of getting report params
     * some other way so that the embedded report will be compatible with the scheduler (in the future).
     *
     * @param int $reportfor userid of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return boolean true if the user can access this report
     */
    public function is_capable($reportfor, $report) {
        global $USER;
        // If no user param passed, assume current user only.
        if (!($subjectid = $report->get_param_value('userid'))) {
            $subjectid = $USER->id;
        }
        // Users can only view their own and their staff's pages or if they are an admin.
        return (
            $reportfor == $subjectid ||
            \totara_job\job_assignment::is_managing($reportfor, $subjectid) ||
            has_capability('totara/plan:accessanyplan', context_system::instance(), $reportfor) ||
            has_capability('totara/core:viewrecordoflearning', context_user::instance($subjectid), $reportfor)
        );
    }

    /**
     * Check if the report is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public static function is_report_ignored() {
        return (
            !advanced_feature::is_enabled('recordoflearning') or
            !advanced_feature::is_enabled('competencies') ||
            advanced_feature::is_enabled('competency_assignment')
        );
    }
}
