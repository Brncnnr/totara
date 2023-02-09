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

class rb_facetoface_facilitators_embedded extends rb_base_embedded {

    public function __construct($data) {
        $this->url = '/mod/facetoface/facilitator/manage.php';
        $this->source = 'facetoface_facilitator';
        $this->shortname = 'facetoface_facilitators';
        $this->fullname = get_string('embedded:seminarfacilitators', 'mod_facetoface');
        $this->columns = array(
            array('type' => 'facilitator', 'value' => 'namelink', 'heading' => null),
            array('type' => 'facilitator', 'value' => 'allowconflicts', 'heading' => null),
            array('type' => 'facilitator', 'value' => 'published', 'heading' => null),
            array('type' => 'facilitator', 'value' => 'visible', 'heading' => null),
            array('type' => 'facilitator', 'value' => 'actions', 'heading' => null)
        );
        $this->filters = array(
            array('type' => 'facilitator', 'value' => 'name', 'advanced' => 0),
            array('type' => 'facilitator', 'value' => 'facilitatoravailable', 'advanced' => 0),
            array('type' => 'facilitator', 'value' => 'published', 'advanced' => 0, 'defaultvalue' => ['value' => 0])
        );
        $this->defaultsortcolumn = 'facilitator_name';

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
    public function is_capable($reportfor, $report): bool {
        $context = context_system::instance();
        return has_capability('mod/facetoface:managesitewidefacilitators', $context, $reportfor);
    }
}