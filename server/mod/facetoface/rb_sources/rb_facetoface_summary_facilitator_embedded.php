<?php
/*
* This file is part of Totara Learn
*
* Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

defined('MOODLE_INTERNAL') || die();

class rb_facetoface_summary_facilitator_embedded extends rb_base_embedded {

    public function __construct($data) {
        $this->url = '/mod/facetoface/reports/facilitators.php';
        $this->source = 'facetoface_facilitator_assignments';
        $this->shortname = 'facetoface_summary_facilitator';
        $this->fullname = get_string('embedded:seminarfacilitatorsupcoming', 'mod_facetoface');
        $this->columns = array(
            array('type' => 'facetoface', 'value' => 'name', 'heading' => null),
            array('type' => 'session', 'value' => 'numattendeeslink', 'heading' => get_string('numberofattendees', 'mod_facetoface')),
            array('type' => 'session', 'value' => 'capacity', 'heading' => null),
            array('type' => 'date', 'value' => 'sessionstartdate', 'heading' => null),
            array('type' => 'session', 'value' => 'bookingstatus', 'heading' => null),
            array('type' => 'session', 'value' => 'overallstatus', 'heading' => null),
        );

        $this->filters = array();

        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_ALL;

        $this->contentsettings = array(
            'date' => array(
                'enable' => 1,
                'when' => 'future'
            )
        );

        $facilitatorid = isset($data['facilitatorid']) ? $data['facilitatorid'] : null;
        if ($facilitatorid != null) {
            $this->embeddedparams['facilitatorid'] = $facilitatorid;
        }

        parent::__construct();
    }

    /**
     * Check if the user is capable of accessing this report.
     * @param int $reportfor userid of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return boolean true if the user can access this report
     */
    public function is_capable($reportfor, $report) {
        return self::is_capable_static($reportfor);
    }

    /**
     * Allow to check capability without instance creation
     * @param int $reportfor user id
     * @return bool
     */
    public static function is_capable_static($reportfor) {
        $systemcontext = context_system::instance();
        return has_capability('mod/facetoface:addinstance', $systemcontext, $reportfor);
    }
}