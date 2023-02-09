<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @package totara
 * @subpackage hierarchy
 */

require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/evidence/evidence.php');
require_once($CFG->dirroot.'/blocks/totara_stats/locallib.php');

/**
 * Add competency evidence records
 *
 * @access  public
 * @param   int         $competencyid
 * @param   int         $userid
 * @param   int         $prof
 * @param   dp_competency_component|null $component Full plan component class instance or null. This is a hack.
 * @param   object      $details        Object containing the (all optional) params positionid, organisationid, assessorid, assessorname, assessmenttype, manual
 * @param   true|int    $reaggregate (optional) time() if set to true, otherwise timestamp supplied
 * @param   bool        $notify (optional)
 * @return  int
 *
 * @deprecated since Totara 13
 */
function hierarchy_add_competency_evidence($competencyid, $userid, $prof, $component, $details, $reaggregate = true, $notify = true) {
    debugging('hierarchy_add_competency_evidence has been deprecated. Please use \dp_competency_component\set_value',
        DEBUG_DEVELOPER
    );

    global $DB;

    $todb = new competency_evidence(
        array(
            'competencyid'  => $competencyid,
            'userid'        => $userid
        )
    );

    // Cleanup data
    if (isset($details->positionid)) {
        $todb->positionid = $details->positionid;
    }
    if (isset($details->organisationid)) {
        $todb->organisationid = $details->organisationid;
    }
    if (isset($details->assessorid)) {
        $todb->assessorid = $details->assessorid;
    }
    if (isset($details->assessorname)) {
        $todb->assessorname = $details->assessorname;
    }
    if (isset($details->assessmenttype)) {
        $todb->assessmenttype = $details->assessmenttype;
    }

    $isproficient = competency::value_is_proficient($prof);

    // Set the timeproficient value if it has been passed through and the selected value is considered proficient.
    $todb->timeproficient = null;
    if ($isproficient && !empty($details->timeproficient)) {
        $todb->timeproficient = $details->timeproficient;
    }

    if (!empty($details->manual)) {
        $todb->manual = 1;
    } else {
        $todb->manual = 0;
    }
    if ($reaggregate === true) {
        $todb->reaggregate = time();
    } else {
        $todb->reaggregate = (int) $reaggregate;
    }

    // Update the proficiency
    $todb->update_proficiency($prof);

    // update stats block
    $currentuser = $userid;
    $event = STATS_EVENT_COMP_ACHIEVED;
    $data2 = $competencyid;
    $time = $todb->reaggregate;
    $count = $DB->count_records('block_totara_stats', array('userid' => $currentuser, 'eventtype' => $event, 'data2' => $data2));

    // Check the proficiency is set to "proficient" and check for duplicate data.
    if ($isproficient && $count == 0) {
        totara_stats_add_event($time, $currentuser, $event, '', $data2);
        if ($notify && $component instanceof dp_competency_component) {
            //Send Alert.
            $alert_detail = new stdClass();
            $alert_detail->itemname = $DB->get_field('comp', 'fullname', array('id' => $data2));
            $alert_detail->text = get_string('competencycompleted', 'totara_plan');
            $component->send_component_complete_alert($alert_detail);
        }
    }
    // check record exists for removal and is set to "not proficient"
    else if ($isproficient == 0 && $count > 0) {
        totara_stats_remove_event($currentuser, $event, $data2);
    }

    return $todb->id;
}

/**
 * Delete evidence records associated with a specified course
 *
 * @param $courseid ID of the course that is no longer required
 * @return boolean True if all delete operations succeeded, false otherwise
 *
 * @deprecated since Totara 13
 */
function hierarchy_delete_competency_evidence($courseid) {
    global $DB;

    if (empty($courseid)) {
        return false;
    }

    // Remove all competency evidence items evidence
    $like_sql = $DB->sql_like('itemtype', '?');
    $like_param = 'course%';
    $DB->delete_records_select("comp_criteria_record",
        "itemid IN (SELECT id FROM {comp_criteria} WHERE $like_sql AND iteminstance = ?)", array($like_param, $courseid));

    $DB->delete_records_select("comp_criteria",
        "(itemtype = 'coursecompletion' OR itemtype='coursegrade') AND iteminstance = ?", array($courseid));

    return true;
}
