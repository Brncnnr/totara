<?php
/*
 * This file is part of Totara Perform
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
 * @author: Simon Coggins <simon.coggins@totaralearning.com>
 * @package: mod_perform
 */

use mod_perform\util;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/perform/rb_sources/rb_source_perform_response_subject_instance.php');

class rb_perform_response_subject_instance_embedded extends rb_base_embedded {

    public function __construct($data) {

        $this->url = '/mod/perform/reporting/performance/user.php';
        $this->source = 'perform_response_subject_instance';
        $this->shortname = 'perform_response_subject_instance';
        $this->fullname = get_string('embedded_perform_response_subject_instance', 'mod_perform');
        $this->columns = $this->define_columns();
        $this->filters = $this->define_filters();
        $this->defaultsortcolumn = 'activity_name';
        if (isset($data['subject_user_id'])) {
            $this->embeddedparams['subject_user_id'] = $data['subject_user_id'];
        }

        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_ALL;

        $this->contentsettings = [
            'user_visibility' => [
                'enable' => 1,
            ]
        ];

        parent::__construct();
    }

    /**
     * Define the default columns for this report.
     *
     * @return array
     */
    protected function define_columns() {
        return [
            [
                'type' => 'activity',
                'value' => 'nameviewformlink',
                'heading' => get_string('activity_name', 'rb_source_perform_response_subject_instance'),
            ],
            [
                'type' => 'subject_instance',
                'value' => 'job_assignment_name',
                'heading' => get_string('activity_job_title', 'mod_perform'),
            ],
            [
                'type' => 'subject_instance',
                'value' => 'instance_number',
                'heading' => get_string('instance_number', 'mod_perform'),
            ],
            [
                'type' => 'subject_instance',
                'value' => 'created_at',
                'heading' => get_string('created_at', 'rb_source_perform_response_subject_instance'),
            ],
            [
                'type' => 'subject_instance',
                'value' => 'participant_count_performance_reporting',
                'heading' => get_string('participant_count', 'rb_source_perform_response_subject_instance'),
            ],
            [
                'type' => 'subject_instance',
                'value' => 'progress',
                'heading' => get_string('subject_progress', 'mod_perform'),
            ],
            [
                'type' => 'subject_instance',
                'value' => 'availability',
                'heading' => get_string('subject_availability', 'mod_perform'),
            ],
            [
                'type' => 'subject_instance',
                'value' => 'actions',
                'heading' => ' ',
            ],
        ];
    }

    /**
     * Define the default filters for this report.
     *
     * @return array
     */
    protected function define_filters() {
        return [
            [
                'type' => 'activity',
                'value' => 'name',
            ],
            [
                'type' => 'subject_instance',
                'value' => 'progress',
            ],
            [
                'type' => 'subject_instance',
                'value' => 'availability',
            ],
            [
                'type' => 'subject_instance',
                'value' => 'created_at',
            ],
            [
                'type' => 'subject_user',
                'value' => 'deleted',
                'defaultvalue' => ['value' => 0],
            ]
        ];
    }

    /**
     * Clarify if current embedded report support global report restrictions.
     * Override to true for reports that support GRR
     *
     * @return boolean
     */
    public function embedded_global_restrictions_supported() {
        return true;
    }

    /**
     * Can searches be saved?
     *
     * @return bool
     */
    public static function is_search_saving_allowed(): bool {
        return false;
    }

    /**
     * Check if the user is capable of accessing this report.
     *
     * @param int $reportfor userid of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return boolean true if the user can access this report
     */
    public function is_capable($reportfor, $report): bool {
        return util::can_potentially_report_on_subjects($reportfor);
    }
}
