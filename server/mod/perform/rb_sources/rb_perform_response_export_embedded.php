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
require_once($CFG->dirroot . '/mod/perform/rb_sources/rb_perform_response_data_base.php');


class rb_perform_response_export_embedded extends rb_perform_response_data_base {

    /**
     * @var string {report_builder}.defaultsortcolumn
     */
    public $defaultsortcolumn = '';

    public function __construct($data) {
        $this->url = '/mod/perform/reporting/performance/export.php';
        $this->shortname = 'perform_response_export';
        $this->fullname = get_string('embedded_perform_response_export', 'mod_perform');
        $this->columns = $this->define_columns();
        $this->filters = $this->define_filters();
        parent::__construct($data);
    }

    /**
     * Message to display indicating why this report can't be cloned.
     *
     * @return string
     */
    public function get_cloning_not_allowed_message(): string {
        return get_string('embedded_perform_response_export_cloning_not_allowed', 'mod_perform');
    }
}
