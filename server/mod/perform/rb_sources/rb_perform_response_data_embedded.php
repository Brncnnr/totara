<?php
/**
 * This file is part of Totara Perform
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
 * @author: Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package: mod_perform
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/perform/rb_sources/rb_perform_response_data_base.php');

class rb_perform_response_data_embedded extends rb_perform_response_data_base {

    /**
     * @var string {report_builder}.defaultsortcolumn
     */
    public $defaultsortcolumn = '';

    public function __construct($data) {
        $this->url = '/mod/perform/reporting/performance/response_data.php';
        $this->shortname = 'perform_response_data';
        $this->fullname = get_string('embedded_perform_response_data', 'mod_perform');
        $this->columns = $this->define_columns();
        $this->filters = $this->define_filters();

        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    protected function define_filters() {
        $filters = parent::define_filters();
        $filters[] = [
            'type' => 'activity',
            'value' => 'name',
        ];
        $filters[] = [
            'type' => 'subject_user',
            'value' => 'fullname',
        ];
        $filters[] = [
            'type' => 'participant_user',
            'value' => 'fullname',
        ];
        $filters[] = [
            'type' => 'participant_instance',
            'value' => 'relationship_id',
        ];
        $filters[] = [
            'type' => 'element',
            'value' => 'type',
        ];
        $filters[] = [
            'type' => 'response',
            'value' => 'response_data',
        ];
        $filters[] = [
            'type' => 'additional',
            'value' => 'linked_review_content_type',
        ];

        return $filters;
    }

    /**
     * Message to display indicating why this report can't be cloned.
     *
     * @return string
     */
    public function get_cloning_not_allowed_message(): string {
        return get_string('embedded_perform_response_data_cloning_not_allowed', 'mod_perform');
    }
}
