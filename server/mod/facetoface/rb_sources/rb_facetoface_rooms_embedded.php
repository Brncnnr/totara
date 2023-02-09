<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 */

defined('MOODLE_INTERNAL') || die();

class rb_facetoface_rooms_embedded extends rb_base_embedded {

    public function __construct($data) {
        $this->url = '/mod/facetoface/room/manage.php';
        $this->source = 'facetoface_rooms';
        $this->shortname = 'facetoface_rooms';
        $this->fullname = get_string('embedded:seminarrooms', 'mod_facetoface');
        $this->columns = array(
            array('type' => 'room', 'value' => 'namelink', 'heading' => null),
            array('type' => 'room', 'value' => 'allowconflicts', 'heading' => null),
            // NOTE: hardcoding custom field ids is not nice, but this should work fine at least in new installs and upgrades,
            //       if fields does not exist it is ignored.
            array('type' => 'facetoface_room', 'value' => 'custom_field_1', 'heading' => null),
            array('type' => 'facetoface_room', 'value' => 'custom_field_2', 'heading' => null),
            array('type' => 'room', 'value' => 'capacity', 'heading' => null),
            array('type' => 'room', 'value' => 'published', 'heading' => null),
            array('type' => 'room', 'value' => 'visible', 'heading' => null),
            array('type' => 'room', 'value' => 'actions', 'heading' => null)
        );
        $this->filters = array(
            array('type' => 'room', 'value' => 'name', 'advanced' => 0),
            array('type' => 'room', 'value' => 'roomavailable', 'advanced' => 0),
            array('type' => 'room', 'value' => 'published', 'advanced' => 0, 'defaultvalue' => ['value' => 0])
        );
        $this->defaultsortcolumn = 'room_name';

        if (isset($data['published']) && $data['published'] !== false) {
            $this->embeddedparams['published'] = $data['published'];
        }

        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        parent::__construct();
    }

    /**
     * Check if the user is capable of accessing this report.
     * @param int $reportfor userid of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return boolean true if the user can access this report
     */
    public function is_capable($reportfor, $report) {
        $context = context_system::instance();
        return has_capability('mod/facetoface:managesitewiderooms', $context, $reportfor);
    }
}
