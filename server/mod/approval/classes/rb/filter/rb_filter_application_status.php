<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/filters/select.php');

/**
 * Application status report builder filter
 */
class rb_filter_application_status extends rb_filter_select {

    public const COMPLETED = 'COMPLETED';

    public const SUBMITTED = 'SUBMITTED';

    public function __construct($type, $value, $advanced, $region, $report, $defaultvalue) {
        parent::__construct($type, $value, $advanced, $region, $report, ['value' => self::COMPLETED]);

        $this->options['selectchoices'] = [
            self::COMPLETED => get_string('report_application_completed', 'rb_source_approval_workflow_applications'),
            self::SUBMITTED => get_string('report_application_submitted', 'rb_source_approval_workflow_applications'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function get_sql_filter($data) {
        $value = $data['value'];
        $field = $this->get_field();

        switch ($value) {
            case self::COMPLETED:
                return ["$field IS NOT NULL", []];
            case self::SUBMITTED:
                return ["$field IS NULL", []];
            default:
                return [' 1=1 ', []];
        }
    }

    /**
     * Filter by application status: Completed & Submitted
     *
     * @return rb_filter_option
     */
    public static function generate_filter_option(): rb_filter_option {
        return new rb_filter_option(
            'application',
            'status',
            get_string('application_status', 'rb_source_approval_workflow_applications'),
            'application_status',
            [
                'simplemode' => true,
                'selectchoices' => [
                    self::COMPLETED => get_string('report_application_completed', 'rb_source_approval_workflow_applications'),
                    self::SUBMITTED => get_string('report_application_submitted', 'rb_source_approval_workflow_applications'),
                ],
            ]
        );
    }
}