<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/totara/reportbuilder/filters/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/filters/select.php');

/**
 * Class rb_filter_f2f_userstatus
 *
 * Custom filter for selecting seminar viewer status, this filter combines
 * the three requested states into one option to provide a simpler user
 * experience
 */
class rb_filter_f2f_userstatus extends rb_filter_select {
    public function __construct($type, $value, $advanced, $region, $report, $defaultvalue) {
        $this->choices_required = false;
        parent::__construct($type, $value, $advanced, $region, $report, $defaultvalue);

        $states = \mod_facetoface\signup\state\state::get_all_states();

        foreach ($states as $state) {
            $status = $state::get_code();
            $class = explode('\\', $state);
            $name = end($class);

            // Special case for requested
            if (in_array($name, ['requested', 'requestedadmin', 'requestedrole'])) {
                continue;
            }

            $statusopts[$status] =  get_string('userstatus:' . $name, 'rb_source_facetoface_events');
        }

        // Add special requested option which we will handle differently in the SQL
        $statusopts[1] = get_string('userstatus:requested', 'rb_source_facetoface_events');

        // Set select options
        $this->options['selectchoices'] = $statusopts;
    }

    public function get_sql_filter($data) {
        $requested_code = mod_facetoface\signup\state\requested::get_code();
        $requested_admin_code = mod_facetoface\signup\state\requestedadmin::get_code();
        $requested_role_code = mod_facetoface\signup\state\requestedrole::get_code();

        $requested_codes = implode(',', [$requested_code, $requested_admin_code, $requested_role_code]);

        if ($data['value'] === '1') {
            $data['value'] = $requested_codes;
        }

        $sql = parent::get_sql_filter($data);

        return $sql;
    }
}
