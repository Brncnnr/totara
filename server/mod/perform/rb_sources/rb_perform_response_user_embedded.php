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
require_once($CFG->dirroot . '/mod/perform/rb_sources/rb_source_perform_response_user.php');

class rb_perform_response_user_embedded extends rb_base_embedded {

    public function __construct($data) {
        $this->url = '/mod/perform/reporting/performance/activity_responses_by_user.php';
        $this->source = 'perform_response_user';
        $this->shortname = 'perform_response_user';
        $this->fullname = get_string('embedded_perform_response_user', 'mod_perform');
        $this->columns = $this->define_columns();
        $this->filters = $this->define_filters();
        $this->defaultsortcolumn = 'user_fullname';

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
                'type' => 'user',
                'value' => 'name_linked_to_performance_reporting',
                'heading' => get_string('subject_user', 'mod_perform'),
            ],
            [
                'type' => 'user',
                'value' => 'user_performance_emailunobscured',
                'heading' => get_string('email', 'moodle'),
            ],
            [
                'type' => 'user',
                'value' => 'username',
                'heading' => get_string('username', 'moodle'),
            ],
            [
                'type' => 'user',
                'value' => 'idnumber',
                'heading' => get_string('idnumber', 'moodle'),
            ],
            [
                'type' => 'user',
                'value' => 'user_performance_reporting_actions',
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
                'type' => 'user',
                'value' => 'fullname',
            ],
            [
                'type' => 'user',
                'value' => 'user_performance_emailunobscured',
                'fieldname' => get_string('email', 'moodle'),
            ],
            [
                'type' => 'user',
                'value' => 'username',
            ],
            [
                'type' => 'user',
                'value' => 'idnumber',
            ],
            [
                'type' => 'user',
                'value' => 'deleted',
                'defaultvalue' => ['value' => 0]
            ],
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
