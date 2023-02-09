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
require_once($CFG->dirroot . '/mod/perform/rb_sources/rb_perform_response_element_base.php');

class rb_perform_response_element_by_activity_embedded extends rb_perform_response_element_base {

    public function __construct($data) {
        if (isset($data['activity_id'])) {
            $this->embeddedparams['activity_id'] = $data['activity_id'];
        }

        parent::__construct();
    }

    /**
     * Define the default columns for this report.
     *
     * @return array
     */
    protected function define_columns(): array {
        return [
            [
                'type' => 'element',
                'value' => 'title',
                'heading' => get_string('question_title', 'mod_perform'),
            ],
            [
                'type' => 'section',
                'value' => 'title',
                'heading' => get_string('element_reporting_title_section_title', 'mod_perform'),
            ],
            [
                'type' => 'element',
                'value' => 'type',
                'heading' => get_string('element_reporting_title_element_type', 'mod_perform'),
            ],
            [
                'type' => 'section',
                'value' => 'responding_relationship_count',
                'heading' => get_string('element_reporting_title_responding_relationships', 'mod_perform'),
            ],
            [
                'type' => 'element',
                'value' => 'is_required',
                'heading' => get_string('element_reporting_title_required', 'mod_perform'),
            ],
            [
                'type' => 'element',
                'value' => 'identifier',
                'heading' => get_string('element_identifier', 'mod_perform'),
            ],
            [
                'type' => 'element',
                'value' => 'actions',
                'heading' => ' ',
            ],
            [
                'type' => 'element',
                'value' => 'default_sort',
                'heading' => get_string('default_sort', 'mod_perform'),
                'hidden' => true,
            ],
        ];
    }

    /**
     * Define the default filters for this report.
     *
     * @return array
     */
    protected function define_filters(): array {
        $filters = parent::define_filters();

        $filters[] = [
            'type' => 'element',
            'value' => 'identifier',
        ];

        $filters[] = [
            'type' => 'section',
            'value' => 'id',
            'heading' => get_string('element_reporting_title_section_title', 'mod_perform'),
        ];

        return $filters;
    }

    /**
     * @inheritDoc
     */
    public function is_capable($reportfor, $report): bool {
        return util::can_potentially_report_on_subjects($reportfor);
    }

    protected function get_url(): string {
        return '/mod/perform/reporting/performance/activity.php';
    }

    protected function get_short_name(): string {
        return 'perform_response_element_by_activity';
    }

    protected function get_full_name(): string {
        return get_string('embedded_perform_response_element_by_activity', 'mod_perform');
    }
}