<?php
/*
* This file is part of Totara Learn
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface\rb\traits;

defined('MOODLE_INTERNAL') || die();

trait deprecated_sessions_source {

    /**
     * Display customfield with edit action icon
     * This module requires JS already to be included
     *
     * @deprecated Since Totara 12.0
     * @param string $note
     * @param stdClass $row
     * @param bool $isexport
     */
    public function rb_display_allcustomfieldssignupmanage($note, $row, $isexport = false) {
        debugging('rb_source_facetoface_sessions::rb_display_allcustomfieldssignupmanage has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_all_signup_customfields_manage::display', DEBUG_DEVELOPER);
        global $OUTPUT;

        if ($isexport) {
            return $note;
        }

        if (!$cm = get_coursemodule_from_instance('facetoface', $row->facetofaceid, $row->courseid)) {
            print_error('error:incorrectcoursemodule', 'facetoface');
        }
        $context = context_module::instance($cm->id);

        if (has_capability('mod/facetoface:manageattendeesnote', $context)) {
            $url = new moodle_url('/mod/facetoface/attendees/ajax/signup_notes.php', array(
                's' => $row->sessionid,
                'userid' => $row->userid,
                'sesskey'=> sesskey()
            ));
            $pix = new pix_icon('t/edit', get_string('edit'));
            $icon = $OUTPUT->action_icon($url, $pix, null, array('class' => 'js-hide action-icon attendee-add-note pull-right'));
            $notehtml = html_writer::span($note);
            return $icon . $notehtml;
        }
        return $note;
    }

    /**
     * Display the email address of the approver
     *
     * @deprecated Since Totara 12.0
     * @param int $approverid
     * @param object $row
     * @return string
     */
    function rb_display_approveremail($approverid, $row) {
        debugging('rb_source_facetoface_sessions::rb_display_approveremail has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_approver_email::display()', DEBUG_DEVELOPER);
        if (empty($approverid)) {
            return '';
        } else {
            $approver = core_user::get_user($approverid);
            return $approver->email;
        }
    }

    /**
     * Display the full name of the approver
     *
     * @deprecated Since Totara 12.0
     * @param int $approverid
     * @param object $row
     * @return string
     */
    function rb_display_approvername($approverid, $row) {
        debugging('rb_source_facetoface_sessions::rb_display_approvername has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_approver_name::display', DEBUG_DEVELOPER);
        if (empty($approverid)) {
            return '';
        } else {
            $approver = core_user::get_user($approverid);
            return fullname($approver);
        }
    }

    /**
     * Override user display function to show 'Reserved' for reserved spaces.
     *
     * @deprecated Since Totara 12.0
     * @param string $user
     * @param object $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_user($user, $row, $isexport = false) {
        debugging('rb_source_facetoface_sessions::rb_display_user has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\user::display', DEBUG_DEVELOPER);
        if (!empty($user)) {
            return parent::rb_display_user($user, $row, $isexport);
        }
        return get_string('reserved', 'rb_source_facetoface_sessions');
    }

    /**
     * Position name column with edit icon
     *
     * @deprecated Since Totara 12.0
     * @param $jobassignment
     * @param $row
     * @param bool $isexport
     * @return null|string|\totara_job\job_assignment
     */
    public function rb_display_job_assignment_edit($jobassignment, $row, $isexport = false) {
        debugging('rb_source_facetoface_sessions::rb_display_job_assignment_edit has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_job_assignment_edit::display', DEBUG_DEVELOPER);
        global $OUTPUT;

        if ($isexport) {
            return $jobassignment;
        }

        if (!$cm = get_coursemodule_from_instance('facetoface', $row->facetofaceid, $row->courseid)) {
            print_error('error:incorrectcoursemodule', 'facetoface');
        }
        $context = context_module::instance($cm->id);
        $canchangesignedupjobassignment = has_capability('mod/facetoface:changesignedupjobassignment', $context);

        $jobassignment = \totara_job\job_assignment::get_with_id($row->jobassignmentid, false);
        if (!empty($jobassignment)) {
            $label = position::job_position_label($jobassignment);
        } else {
            $label = '';
        }
        $url = new moodle_url('/mod/facetoface/attendees/ajax/job_assignment.php', array('s' => $row->sessionid, 'id' => $row->userid));
        $pix = new pix_icon('t/edit', get_string('edit'));
        $icon = $OUTPUT->action_icon($url, $pix, null, array('class' => 'action-icon attendee-edit-job-assignment pull-right'));
        $jobassignmenthtml = html_writer::span($label, 'jobassign'.$row->userid, array('id' => 'jobassign'.$row->userid));

        if ($canchangesignedupjobassignment) {
            return $icon . $jobassignmenthtml;
        }
        return $jobassignmenthtml;
    }

    /**
     * Override user display function to show 'Reserved' for reserved spaces.
     *
     * @deprecated Since Totara 12.0
     * @param string $user
     * @param object $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_link_user($user, $row, $isexport = false) {
        debugging('rb_source_facetoface_sessions::rb_display_link_user has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\user_link::display', DEBUG_DEVELOPER);
        if (!empty($row->id)) {
            return parent::rb_display_link_user($user, $row, $isexport);
        }
        return get_string('reserved', 'rb_source_facetoface_sessions');
    }

    /**
     * Override user display function to show 'Reserved' for reserved spaces.
     *
     * @deprecated Since Totara 12.0
     * @param string $user
     * @param object $row
     * @param bool $isexport
     * @return string
     */
    function rb_display_link_user_icon($user, $row, $isexport = false) {
        debugging('rb_source_facetoface_sessions::rb_display_link_user_icon has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\user_icon_link::display', DEBUG_DEVELOPER);
        if (!empty($row->id)) {
            return parent::rb_display_link_user_icon($user, $row, $isexport);
        }
        return get_string('reserved', 'rb_source_facetoface_sessions');
    }

    /**
     * Reformat a timestamp and timezone into a date, showing nothing if invalid or null
     *
     * @deprecated Since Totara 12.0
     * @param integer $date Unix timestamp
     * @param object $row Object containing all other fields for this row (which should include a timezone field)
     *
     * @return string Date in a nice format
     */
    function rb_display_show_cancelled_status($status) {
        debugging('rb_source_facetoface_sessions::rb_display_show_cancelled_status has been deprecated since Totara 12.0. Use mod_facetoface\rb\display\f2f_session_cancelled_status::display', DEBUG_DEVELOPER);
        if ($status == 1) {
            return get_string('cancelled', 'rb_source_facetoface_sessions');
        }
        return "";
    }
}