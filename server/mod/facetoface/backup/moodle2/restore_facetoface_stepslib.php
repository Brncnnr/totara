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
 * @package mod_facetoface
 */

use mod_facetoface\seminar;

/**
 * Structure step to restore one facetoface activity
 */
class restore_facetoface_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('facetoface', '/activity/facetoface');
        $paths[] = new restore_path_element('facetoface_notification', '/activity/facetoface/notifications/notification');
        $paths[] = new restore_path_element('facetoface_session', '/activity/facetoface/sessions/session');
        if ($userinfo) {
            $paths[] = new restore_path_element('facetoface_session_role', '/activity/facetoface/sessions/session/sessions_roles/sessions_role');
        }
        $paths[] = new restore_path_element('facetoface_session_custom_field', '/activity/facetoface/sessions/session/custom_fields/custom_field');
        if ($userinfo) {
            $paths[] = new restore_path_element('facetoface_signup', '/activity/facetoface/sessions/session/signups/signup');
            $paths[] = new restore_path_element('facetoface_signups_status', '/activity/facetoface/sessions/session/signups/signup/signups_status/signup_status');
            $paths[] = new restore_path_element('facetoface_signup_custom_field', '/activity/facetoface/sessions/session/signups/signup/signup_fields/signup_field');
            $paths[] = new restore_path_element('facetoface_cancellation_custom_field', '/activity/facetoface/sessions/session/signups/signup/cancellation_fields/cancellation_field');
        }
        $paths[] = new restore_path_element('facetoface_sessions_date', '/activity/facetoface/sessions/session/sessions_dates/sessions_date');
        if ($userinfo) {
            $paths[] = new restore_path_element('facetoface_signups_dates_status', '/activity/facetoface/sessions/session/sessions_dates/sessions_date/signups_dates_status/signup_date_status');
        }
        /**
         * Start.
         * This is totara12 leftover for case if client has t12 old backup, upgraded to t13 and wants to restore on t13.
         * Keep it as long as it necessary.
         * Do not forget to delete the process_facetoface_room_t12() and process_facetoface_room_custom_field_t12() methods.
         */
        $paths[] = new restore_path_element('facetoface_room_t12', '/activity/facetoface/sessions/session/sessions_dates/sessions_date/room');
        $paths[] = new restore_path_element('facetoface_room_custom_field_t12', '/activity/facetoface/sessions/session/sessions_dates/sessions_date/room/room_fields/room_field');
        /** End. */
        $paths[] = new restore_path_element('facetoface_room', '/activity/facetoface/sessions/session/sessions_dates/sessions_date/rooms/room');
        $paths[] = new restore_path_element('facetoface_room_custom_field', '/activity/facetoface/sessions/session/sessions_dates/sessions_date/rooms/room/room_fields/room_field');
        $paths[] = new restore_path_element('facetoface_asset', '/activity/facetoface/sessions/session/sessions_dates/sessions_date/assets/asset');
        $paths[] = new restore_path_element('facetoface_asset_custom_field', '/activity/facetoface/sessions/session/sessions_dates/sessions_date/assets/asset/asset_fields/asset_field');
        $paths[] = new restore_path_element('facetoface_facilitator', '/activity/facetoface/sessions/session/sessions_dates/sessions_date/facilitators/facilitator');
        $paths[] = new restore_path_element('facetoface_facilitator_custom_field', '/activity/facetoface/sessions/session/sessions_dates/sessions_date/facilitators/facilitator/facilitator_fields/facilitator_field');

        if ($userinfo) {
            $paths[] = new restore_path_element('facetoface_interest', '/activity/facetoface/interests/interest');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_facetoface($data) {
        global $DB;

        $data = (object)$data;

        $data->course = $this->get_courseid();
        // Keeping or moving these times makes little sense, but it is the expected Moodle way...
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        if (!empty($data->approvalrole)) {
            $data->approvalrole = $this->get_mappingid('role', $data->approvalrole);
        }
        if (!empty($data->approvaladmins)) {
            $oldadmins = explode(',', $data->approvaladmins);
            $admins = array();
            foreach ($oldadmins as $oldadmin) {
                $newadmin = $this->get_mappingid('user', $oldadmin);
                if ($newadmin) {
                    $admins[] = $newadmin;
                }
            }
            $data->approvaladmins = implode(',', $admins);
        }

        if (isset($data->sessionattendance)) {
            $data->sessionattendance = seminar::fix_up_session_attendance_time_with($data->attendancetime, $data->sessionattendance);
        }

        // insert the facetoface record
        $newitemid = $DB->insert_record('facetoface', $data);
        $this->apply_activity_instance($newitemid);

        // Fix up the facetoface database record, which has just been inserted, in the same way as upgrade.php.
        $DB->execute(
            "UPDATE {facetoface}
                SET completionpass = :cpa
              WHERE id = :fid AND EXISTS(
                SELECT *
                  FROM {course_modules} cm
                 WHERE cm.instance = :cmi AND cm.completion = :cc AND cm.completiongradeitemnumber = 0
              ) AND completionpass = :cpd",
            array(
                'cc' => COMPLETION_TRACKING_AUTOMATIC,
                'cpa' => seminar::COMPLETION_PASS_ANY,
                'cpd' => seminar::COMPLETION_PASS_DISABLED,
                'fid' => $newitemid,
                'cmi' => $newitemid
            )
        );
    }

    protected function process_facetoface_notification($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->facetofaceid = $this->get_new_parentid('facetoface');
        $data->courseid = $this->get_courseid();

        if (empty($data->templateid) or !$this->get_task()->is_samesite()) {
            $data->templateid = 0;
        } else {
            if (!$DB->record_exists('facetoface_notification_tpl', array('id' => $data->templateid))) {
                $data->templateid = 0;
            }
        }

        // Keeping or moving this time makes little sense, but it is the expected Moodle way...
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        // Always map the old user id, that is the standard way for now.
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);

        // Insert the notification record.
        $newitemid = $DB->insert_record('facetoface_notification', $data);
        $this->set_mapping('facetoface_notification', $oldid, $newitemid);
    }

    protected function process_facetoface_session($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        /** @var restore_activity_task $task */
        $task = $this->get_task();

        if (!empty($data->cancelledstatus)) {
            // Support for restoring of cancelled sessions is not available!
            $this->set_mapping('facetoface_session', $oldid, null);
            return;
        }

        $data->facetoface = $this->get_new_parentid('facetoface');

        // Keeping or moving these two times makes little sense, but it is the expected Moodle way...
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        // Moodle way is to map the original user even if it makes little sense.
        if (!empty($data->usermodified)) {
            $data->usermodified = (int)$this->get_mappingid('user', $data->usermodified);
        } else {
            $data->usermodified = 0;
        }
        // Following dates are definitely expected to change when course date moves!
        if (!empty($data->registrationtimestart)) {
            $data->registrationtimestart = $this->apply_date_offset($data->registrationtimestart);
        }
        if (!empty($data->registrationtimefinish)) {
            $data->registrationtimefinish = $this->apply_date_offset($data->registrationtimefinish);
        }

        // insert the entry record
        $newitemid = $DB->insert_record('facetoface_sessions', $data);
        $this->set_mapping('facetoface_session', $oldid, $newitemid, true);

        $this->add_related_files('mod_facetoface', 'session', 'facetoface_session', $task->get_old_contextid(), $oldid);
    }

    protected function process_facetoface_session_role($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->sessionid = $this->get_new_parentid('facetoface_session');
        if (!$data->sessionid) {
            return;
        }

        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!$data->userid) {
            return;
        }
        $data->roleid = $this->get_mappingid('role', $data->roleid);
        if (!$data->roleid) {
            return;
        }

        // insert the entry record
        $newitemid = $DB->insert_record('facetoface_session_roles', $data);
    }

    protected function process_facetoface_signup($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->sessionid = $this->get_new_parentid('facetoface_session');
        if (!$data->sessionid) {
            $this->set_mapping('facetoface_signup', $oldid, null);
            return;
        }

        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!$data->userid) {
            $this->set_mapping('facetoface_signup', $oldid, null);
            return;
        }
        if (!empty($data->bookedby)) {
            $data->bookedby = $this->get_mappingid('user', $data->bookedby);
        }
        if (!empty($data->managerid)) {
            $data->managerid = $this->get_mappingid('user', $data->managerid);
        }

        // insert the entry record
        $newitemid = $DB->insert_record('facetoface_signups', $data);
        $this->set_mapping('facetoface_signup', $oldid, $newitemid); // childs and files by itemname
    }

    protected function process_facetoface_signups_status($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->signupid = $this->get_new_parentid('facetoface_signup');
        if (!$data->signupid) {
            $this->set_mapping('facetoface_signups_status', $oldid, null);
            return;
        }

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->createdby = (int)$this->get_mappingid('user', $data->createdby);
        // fixup grade
        if ($data->grade == 0) {
            $f2fversion = $this->get_task()->get_old_moduleversion();
            $totaramajor = floor(floatval($this->get_task()->get_info()->totara_release));
            $totarabuild = 0;
            // Prevent passing null as input parameter in PHP 8.1
            preg_match('/(\d{8})/', $this->get_task()->get_info()->totara_build ?? '', $matches);
            if (!empty($matches[1])) {
                $totarabuild = (int) $matches[1]; // The date of Totara build at the time of the backup.
            }
            // recalculate only for backups made with Totara 12 before TL-20720, and Totara 13 before TL-20400
            if (($totaramajor == 12 && $f2fversion < 2018112207)
                    || ($totaramajor == 13 && $f2fversion < 2019030100)
                    // T13 Evergreen
                    || ($totaramajor == 0 && 20181207 <= $totarabuild && $totarabuild < 20190322 && $f2fversion < 2019030100)
                    // T12 Evergreen
                    || ($totaramajor == 0 && 20180919 <= $totarabuild && $totarabuild < 20181207 && $f2fversion < 2018112207)) {
                try {
                    $data->grade = \mod_facetoface\signup\state\state::from_code($data->statuscode)::get_grade();
                } catch (\mod_facetoface\exception\signup_exception $e) {
                    // swallow exception and set NULL if the status code is not valid
                    $data->grade = null;
                }
            }
        }

        // insert the entry record
        $newitemid = $DB->insert_record('facetoface_signups_status', $data);
        $this->set_mapping('facetoface_signups_status', $oldid, $newitemid);
    }

    protected function process_facetoface_signups_dates_status($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->sessiondateid = $this->get_new_parentid('facetoface_sessions_date');
        if (!$data->sessiondateid) {
            $this->set_mapping('facetoface_signups_dates_status', $oldid, null);
            return;
        }

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->createdby = (int)$this->get_mappingid('user', $data->createdby);
        $data->signupid = (int)$this->get_mappingid('facetoface_signup', $data->signupid);

        // insert the entry record
        $newitemid = $DB->insert_record('facetoface_signups_dates_status', $data);
        $this->set_mapping('facetoface_signups_dates_status', $oldid, $newitemid);
    }

    protected function process_facetoface_session_custom_field($data) {
        $data = (object)$data;

        $newparentid = $this->get_new_parentid('facetoface_session');
        if (!$newparentid) {
            return;
        }

        $this->create_custom_field_data($data, $newparentid, 'facetoface_session', 'facetofacesessionid');
    }

    protected function process_facetoface_signup_custom_field($data) {
        $data = (object)$data;

        $newparentid = $this->get_new_parentid('facetoface_signup');
        if (!$newparentid) {
            return;
        }

        $this->create_custom_field_data($data, $newparentid, 'facetoface_signup', 'facetofacesignupid');
    }

    protected function process_facetoface_cancellation_custom_field($data) {
        $data = (object)$data;

        $newparentid = $this->get_new_parentid('facetoface_signup');
        if (!$newparentid) {
            return;
        }

        $this->create_custom_field_data($data, $newparentid, 'facetoface_cancellation', 'facetofacecancellationid');
    }

    protected function process_facetoface_sessions_date($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->sessionid = $this->get_new_parentid('facetoface_session');
        if (!$data->sessionid) {
            $this->set_mapping('facetoface_sessions_date', $oldid, null);
            return;
        }

        $data->timestart = $this->apply_date_offset($data->timestart);
        $data->timefinish = $this->apply_date_offset($data->timefinish);

        // insert the entry record
        $newitemid = $DB->insert_record('facetoface_sessions_dates', $data);

        $this->set_mapping('facetoface_sessions_date', $oldid, $newitemid);
    }

    /**
     * This is totara12 leftover for case if client has t12 old backup, upgraded to t13 and wants to restore on t13.
     * @param $data
     */
    protected function process_facetoface_room_t12($data) {
        $this->process_facetoface_room($data);
    }

    /**
     * This is totara12 leftover for case if client has t12 old backup, upgraded to t13 and wants to restore on t13.
     * @param $data
     */
    protected function process_facetoface_room_custom_field_t12($data) {
        $this->process_facetoface_room_custom_field($data);
    }

    protected function process_facetoface_room($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        /** @var restore_activity_task $task */
        $task = $this->get_task();
        $facetofaceid = $task->get_activityid();

        $sessionsdateid = $this->get_new_parentid('facetoface_sessions_date');
        if (!$sessionsdateid) {
            $this->set_mapping('facetoface_room', $oldid, null);
            return;
        }
        $sessiondate = $DB->get_record('facetoface_sessions_dates', array('id' => $sessionsdateid), '*', MUST_EXIST);

        if ($data->custom == 1) {
            // Custom rooms are easy, we just add a new one as exact copy,
            // but watch out that custom rooms might be shared in one seminar activity.
            $newid = $this->get_mappingid('facetoface_room', $oldid);
            if (!$newid) {
                $newid = $this->create_facetoface_room($data);
            }
            $DB->insert_record('facetoface_room_dates', (object)array('roomid' => $newid, 'sessionsdateid' => $sessionsdateid));
            return;
        }
        // Only set the mapping when we actually create a new room!!!
        $this->set_mapping('facetoface_room', $oldid, null);

        if (!$this->get_task()->is_samesite()) {
            // We cannot restore site rooms from inside courses, sorry!
            $this->log('shared seminar room from other site cannot be restored', backup::LOG_WARNING);
            return;
        }

        // Ok, we are on the same site, let's see if the room still exists and use it if there are no conflicts.
        $room = \mod_facetoface\room::seek($oldid);
        if (!$room->exists()) {
            $this->log('seminar room is no longer available', backup::LOG_WARNING);
            return;
        }
        if ($room->get_custom()) {
            // This should not ever happen, somebody hacked DB or backup file.
            return;
        }
        if (!$room->get_allowconflicts()) {
            $seminarevent = new \mod_facetoface\seminar_event($sessiondate->sessionid);
            if (!$room->is_available($sessiondate->timestart, $sessiondate->timefinish, $seminarevent)) {
                $this->log('seminar room not available', backup::LOG_WARNING);
                return;
            }
        }
        // It should be fine to add the room to the session.
        $DB->insert_record('facetoface_room_dates', (object)array('roomid' => $room->get_id(), 'sessionsdateid' => $sessionsdateid));
    }

    protected function process_facetoface_room_custom_field($data) {
        $data = (object)$data;

        $newparentid = $this->get_new_parentid('facetoface_room');
        if (!$newparentid) {
            return;
        }

        $this->create_custom_field_data($data, $newparentid, 'facetoface_room', 'facetofaceroomid');
    }

    /**
     * Create a new room.
     *
     * @param stdClass $room
     * @return int new room id
     */
    private function create_facetoface_room(stdClass $room) {
        global $DB;

        $oldid = $room->id;
        unset($room->id);

        // Keeping or moving these two times makes little sense, but it is the expected Moodle way...
        $room->timecreated = $this->apply_date_offset($room->timecreated);
        $room->timemodified = $this->apply_date_offset($room->timemodified);

        // Moodle way is to map the original user even if it makes little sense.
        $room->usercreated = $this->get_mappingid('user', $room->usercreated);
        $room->usermodified = $this->get_mappingid('user', $room->usermodified);

        $newid = $DB->insert_record('facetoface_room', $room);
        $this->set_mapping('facetoface_room', $oldid, $newid, true, $this->get_task()->get_old_system_contextid());

        $this->add_related_files('mod_facetoface', 'room', 'facetoface_room', $this->get_task()->get_old_system_contextid(), $oldid);

        return $newid;
    }

    protected function process_facetoface_asset($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        /** @var restore_activity_task $task */
        $task = $this->get_task();
        $facetofaceid = $task->get_activityid();

        $sessionsdateid = $this->get_new_parentid('facetoface_sessions_date');
        if (!$sessionsdateid) {
            $this->set_mapping('facetoface_asset', $oldid, null);
            return;
        }
        $sessiondate = $DB->get_record('facetoface_sessions_dates', array('id' => $sessionsdateid), '*', MUST_EXIST);

        if ($data->custom == 1) {
            // Custom assets are easy, we just add a new one as exact copy,
            // but watch out that custom assets might be shared in one seminar activity.
            $newid = $this->get_mappingid('facetoface_asset', $oldid);
            if (!$newid) {
                $newid = $this->create_facetoface_asset($data);
            }
            $DB->insert_record('facetoface_asset_dates', (object)array('assetid' => $newid, 'sessionsdateid' => $sessionsdateid));
            return;
        }
        // Only set the mapping when we actually create a new asset!!!
        $this->set_mapping('facetoface_asset', $oldid, null);

        if (!$this->get_task()->is_samesite()) {
            // We cannot restore site assets from inside courses, sorry!
            $this->log('shared seminar asset from other site cannot be restored', backup::LOG_WARNING);
            return;
        }

        // Ok, we are on the same site, let's see if the asset still exists and use it if there are no conflicts.
        $asset = \mod_facetoface\asset::seek($oldid);
        if (!$asset->exists()) {
            $this->log('seminar asset is no longer available', backup::LOG_WARNING);
            return;
        }
        if ($asset->get_custom()) {
            // This should not ever happen, somebody hacked DB or backup file.
            return;
        }
        if (!$asset->get_allowconflicts()) {
            $seminarevent = new \mod_facetoface\seminar_event($sessiondate->sessionid);
            if (!$asset->is_available($sessiondate->timestart, $sessiondate->timefinish, $seminarevent)) {
                $this->log('seminar asset not available', backup::LOG_WARNING);
                return;
            }
        }
        // It should be fine to add the asset to the session.
        $DB->insert_record('facetoface_asset_dates', (object)array('assetid' => $asset->get_id(), 'sessionsdateid' => $sessionsdateid));
    }

    protected function process_facetoface_asset_custom_field($data) {
        $data = (object)$data;

        $newparentid = $this->get_new_parentid('facetoface_asset');
        if (!$newparentid) {
            return;
        }

        $this->create_custom_field_data($data, $newparentid, 'facetoface_asset', 'facetofaceassetid');
    }

    /**
     * Create a new asset.
     *
     * @param stdClass $asset
     * @return int new asset id
     */
    private function create_facetoface_asset(stdClass $asset) {
        global $DB;

        $oldid = $asset->id;
        unset($asset->id);

        // Keeping or moving these two times makes little sense, but it is the expected Moodle way...
        $asset->timecreated = $this->apply_date_offset($asset->timecreated);
        $asset->timemodified = $this->apply_date_offset($asset->timemodified);

        // Moodle way is to map the original user even if it makes little sense.
        $asset->usercreated = $this->get_mappingid('user', $asset->usercreated);
        $asset->usermodified = $this->get_mappingid('user', $asset->usermodified);

        $newid = $DB->insert_record('facetoface_asset', $asset);
        $this->set_mapping('facetoface_asset', $oldid, $newid, true, $this->get_task()->get_old_system_contextid());

        $this->add_related_files('mod_facetoface', 'asset', 'facetoface_asset', $this->get_task()->get_old_system_contextid(), $oldid);

        return $newid;
    }

    protected function process_facetoface_facilitator($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        /** @var restore_activity_task $task */
        $task = $this->get_task();
        $facetofaceid = $task->get_activityid();

        $sessionsdateid = $this->get_new_parentid('facetoface_sessions_date');
        if (!$sessionsdateid) {
            $this->set_mapping('facetoface_facilitator', $oldid, null);
            return;
        }
        $sessiondate = $DB->get_record('facetoface_sessions_dates', array('id' => $sessionsdateid), '*', MUST_EXIST);

        if ($data->custom == 1) {
            // Custom facilitators are easy, we just add a new one as exact copy,
            // but watch out that custom facilitators might be shared in one seminar activity.
            $newid = $this->get_mappingid('facetoface_facilitator', $oldid);
            if (!$newid) {
                $newid = $this->create_facetoface_facilitator($data);
            }
            $DB->insert_record('facetoface_facilitator_dates', (object)array('facilitatorid' => $newid, 'sessionsdateid' => $sessionsdateid));
            return;
        }
        // Only set the mapping when we actually create a new facilitator!!!
        $this->set_mapping('facetoface_facilitator', $oldid, null);

        if (!$this->get_task()->is_samesite()) {
            // We cannot restore site facilitators from inside courses, sorry!
            $this->log('shared seminar facilitator from other site cannot be restored', backup::LOG_WARNING);
            return;
        }

        // Ok, we are on the same site, let's see if the facilitator still exists and use it if there are no conflicts.
        $facilitator = \mod_facetoface\facilitator::seek($oldid);
        if (!$facilitator->exists()) {
            $this->log('seminar facilitator is no longer available', backup::LOG_WARNING);
            return;
        }
        if ($facilitator->get_custom()) {
            // This should not ever happen, somebody hacked DB or backup file.
            return;
        }
        if (!$facilitator->get_allowconflicts()) {
            $seminarevent = new \mod_facetoface\seminar_event($sessiondate->sessionid);
            if (!$facilitator->is_available($sessiondate->timestart, $sessiondate->timefinish, $seminarevent)) {
                $this->log('seminar facilitator not available', backup::LOG_WARNING);
                return;
            }
        }
        if ((int)$data->userid > 0) {
            $data->userid = $this->get_mappingid('user', $data->userid);
            if (!$data->userid) {
                $this->log('seminar facilitator is no longer available 2', backup::LOG_WARNING);
                return;
            }
        }

        // It should be fine to add the facilitator to the session.
        $DB->insert_record('facetoface_facilitator_dates', (object)array('facilitatorid' => $facilitator->get_id(), 'sessionsdateid' => $sessionsdateid));
    }

    protected function process_facetoface_facilitator_custom_field($data) {
        $data = (object)$data;

        $newparentid = $this->get_new_parentid('facetoface_facilitator');
        if (!$newparentid) {
            return;
        }

        $this->create_custom_field_data(
            $data,
            $newparentid,
            \mod_facetoface\customfield_area\facetofacefacilitator::get_prefix(),
            'facetofacefacilitatorid'
        );
    }

    /**
     * Create a new facilitator.
     * @param stdClass $facilitator
     * @return int new facilitator id
     */
    private function create_facetoface_facilitator(stdClass $facilitator): int {
        global $DB;

        $oldid = $facilitator->id;
        unset($facilitator->id);

        // Keeping or moving these two times makes little sense, but it is the expected Moodle way...
        $facilitator->timecreated = $this->apply_date_offset($facilitator->timecreated);
        $facilitator->timemodified = $this->apply_date_offset($facilitator->timemodified);

        // Moodle way is to map the original user even if it makes little sense.
        $facilitator->usercreated = $this->get_mappingid('user', $facilitator->usercreated);
        $facilitator->usermodified = $this->get_mappingid('user', $facilitator->usermodified);

        $newid = $DB->insert_record('facetoface_facilitator', $facilitator);
        $this->set_mapping('facetoface_facilitator', $oldid, $newid, true, $this->get_task()->get_old_system_contextid());

        $this->add_related_files(
            \mod_facetoface\customfield_area\facetofacefacilitator::get_component(),
            \mod_facetoface\customfield_area\facetofacefacilitator::get_area_name(),
            \mod_facetoface\customfield_area\facetofacefacilitator::get_prefix(),
            $this->get_task()->get_old_system_contextid(),
            $oldid
        );

        return $newid;
    }

    /**
     * Add new custom field data record if possible.
     *
     * @param stdClass $data
     * @param int $newparentid
     * @param string $tableprefix
     * @param string $parentfield
     */
    private function create_custom_field_data(stdClass $data, $newparentid, $tableprefix, $parentfield) {
        global $DB;

        $infofield = $DB->get_record($tableprefix . '_info_field', array('shortname' => $data->field_name, 'datatype' => $data->field_type));
        if (!$infofield) {
            $info = "{$tableprefix} custom field {$data->field_name} could not be restored";
            $this->log($info, backup::LOG_WARNING);
            return;
        }

        $infodata = $DB->get_record($tableprefix . '_info_data', array($parentfield => $newparentid, 'fieldid' => $infofield->id));
        if ($infodata) {
            // Add the values only the first time, the fields can be easily duplicated in the backup tree,
            // such as for reused custom seminar rooms, but multiselect customfield can have more then one param value.
            if ($infofield->datatype == 'multiselect' && isset($data->paramdatavalue)) {
                // Lets check if the data already exists in the param table for multiselect customfield, return if it does.
                // if not, we create a new record below.
                $dataid = $infodata->id;
                if ($DB->record_exists($tableprefix . '_info_data_param', ['dataid' => $dataid, 'value' => $data->paramdatavalue])) {
                    return;
                }
            } else {
                return;
            }
        } else {
            $customfield = new stdClass();
            $customfield->{$parentfield} = $newparentid;
            $customfield->fieldid = $infofield->id;
            $customfield->data = $data->field_data;
            $dataid = $DB->insert_record($tableprefix . '_info_data', $customfield);
        }

        // Insert params only if previously existed.
        if (isset($data->paramdatavalue)) {
            $param = new stdClass();
            $param->dataid = $dataid;
            $param->value = $data->paramdatavalue;
            $DB->insert_record($tableprefix . '_info_data_param', $param);
        }
    }

    protected function process_facetoface_interest($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->facetoface = $this->get_new_parentid('facetoface');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!$data->userid) {
            return;
        }

        // Insert the entry record.
        $newitemid = $DB->insert_record('facetoface_interest', $data);
    }

    protected function after_execute() {
        $this->add_related_files('mod_facetoface', 'intro', null);
    }

    /**
     * Hook to execute facetoface calendar entries upgrade after restore.
     */
    protected function after_restore() {
        // The current module must exist.
        $pluginmanager = core_plugin_manager::instance();
        $plugininfo = $pluginmanager->get_plugin_info('mod_facetoface');
        // Check that the facetoface module is installed.
        if ($plugininfo && $plugininfo->is_installed_and_upgraded()) {
            // Get the id and type of this facetoface.
            $seminarid = $this->get_task()->get_activityid();
            $seminar = new \mod_facetoface\seminar($seminarid);
            $seminarevents = \mod_facetoface\seminar_event_list::from_seminar($seminar);
            foreach ($seminarevents as $seminarevent) {
                \mod_facetoface\calendar::update_entries($seminarevent);
            }
        }
    }
}
