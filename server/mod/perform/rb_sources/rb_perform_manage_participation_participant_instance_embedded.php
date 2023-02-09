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
 * @author: Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package: mod_perform
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/perform/rb_sources/rb_source_perform_manage_participation_participant_instance.php');

class rb_perform_manage_participation_participant_instance_embedded extends rb_base_embedded {

    /**
     * @var string {report_builder}.defaultsortcolumn
     */
    public $defaultsortcolumn = '';

    /**
     * @param array $data
     */
    public function __construct(array $data) {
        $this->url = '/mod/perform/manage/participation/participant_instances.php';

        $this->source = 'perform_manage_participation_participant_instance';
        $this->shortname = 'perform_manage_participation_participant_instance';
        $this->fullname = get_string('embedded_perform_manage_participation_participant_instance', 'mod_perform');
        $this->columns = $this->define_columns();
        $this->filters = $this->define_filters();
        $this->defaultsortcolumn = 'participant_instance_default_sort';

        if (isset($data['activity_id']) && (int)$data['activity_id'] > 0) {
            $this->embeddedparams['activity_id'] = $data['activity_id'];
        }

        if (isset($data['subject_instance_id']) && (int)$data['subject_instance_id'] > 0) {
            $this->embeddedparams['subject_instance_id'] = $data['subject_instance_id'];
        }

        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_ALL;

        $this->contentsettings = array(
            'user_visibility' => array(
                'enable' => 1,
            )
        );

        parent::__construct();
    }

    /**
     * Define the default columns for this report.
     *
     * @return array
     */
    protected function define_columns() {
        return \rb_source_perform_manage_participation_participant_instance::get_default_columns();
    }

    /**
     * Define the default filters for this report.
     *
     * @return array
     */
    protected function define_filters() {
        return \rb_source_perform_manage_participation_participant_instance::get_default_filters();
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
        return true;
    }
}
