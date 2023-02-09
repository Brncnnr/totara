<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

use \completion_completion;
use \completion_info;
use container_course\course;
use mod_facetoface\totara_notification\resolver\booking_cancelled;
use mod_facetoface\totara_notification\resolver\booking_confirmed;
use mod_facetoface\totara_notification\resolver\booking_request_created;
use mod_facetoface\totara_notification\seminar_notification_helper;

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

class event_handler {

    /**
     * Event that is triggered when a user is deleted.
     *
     * Cancels a user from any future sessions when they are deleted
     * this is to make sure deleted users aren't using space in sessions
     * when there is limited capacity.
     *
     * @param \core\event\user_deleted $event
     * @return bool True if successful, exception if not.
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;

        $userid = $event->objectid;
        if ($signups = $DB->get_records('facetoface_signups', array('userid' => $userid))) {
            foreach ($signups as $signupdata) {
                $seminarevent = new seminar_event($signupdata->sessionid);
                $signup = signup::create($signupdata->userid, $seminarevent);
                // We want to cancel only signup that learner can cancel themselves. E.g. we don't want to cancel past signups.
                $signup->set_actorid($signup->get_userid());
                if (signup_helper::can_user_cancel($signup)) {
                    signup_helper::user_cancel($signup);
                }
            }
        }

        // Brute force unenrol from Seminar session role.
        $DB->delete_records('facetoface_session_roles', array('userid' => $userid));

        $facilitator = \mod_facetoface\facilitator_user::seek_by_userid($userid);
        if ($facilitator->exists()) {
            $facilitator->hide();
            $facilitator->save();
        }
        return true;
    }

    /**
     * Event that is triggered when a user is suspended.
     *
     * Cancels a user from any future sessions when they are suspended
     * this is to make sure suspended users aren't using space in sessions
     * when there is limited capacity.
     *
     * @param \totara_core\event\user_suspended $event
     * @return bool True if successful, exception if not.
     */
    public static function user_suspended(\totara_core\event\user_suspended $event) {
        global $DB;

        $userid = $event->objectid;
        if ($signups = $DB->get_records('facetoface_signups', array('userid' => $userid))) {
            foreach ($signups as $signupdata) {
                $seminarevent = new seminar_event($signupdata->sessionid);
                $signup = signup::create($signupdata->userid, $seminarevent);
                // We want to cancel only signup that learner can cancel themselves. E.g. we don't want to cancel past signups.
                $signup->set_actorid($signup->get_userid());
                if (signup_helper::can_user_cancel($signup)) {
                    signup_helper::user_cancel($signup);
                }
            }
        }
        $facilitator = \mod_facetoface\facilitator_user::seek_by_userid($userid);
        if ($facilitator->exists()) {
            $facilitator->hide();
            $facilitator->save();
        }
        return true;
    }

    /**
     * Event that is triggered when a user is unenrolled from a course
     *
     * Cancels a user from any future sessions when they are unenrolled from a course,
     * this is to make sure unenrolled users aren't using space in sessions
     * when there is limited capacity
     *
     * @param \core\event\user_enrolment_deleted $event
     * @return true if no errors were encountered
     */
    public static function user_unenrolled(\core\event\user_enrolment_deleted $event) {
        global $DB;

        // Totara: Skip if containertype is not container_course.
        if ($event->other['containertype'] !== course::get_type()) {
            return true;
        }

        if (!$event->other['userenrolment']['lastenrol']) {
            // The user has another enrolment record for this course, so don't remove the f2f session.
            return true;
        }

        $uid = $event->relateduserid;
        $cid = $event->courseid;

        // Get all the facetofaces associated with the course.
        $f2fs = $DB->get_fieldset_select('facetoface', 'id', 'course = :cid', array('cid' => $cid));
        $user = \core_user::get_user($uid);

        if (!empty($f2fs)) {
            // Get all the sessions for the facetofaces.
            list($insql, $inparams) = $DB->get_in_or_equal($f2fs);
            $sql = "SELECT id FROM {facetoface_sessions} WHERE facetoface {$insql}";
            $sessids = $DB->get_fieldset_sql($sql, $inparams);
            $strvar = new \stdClass();
            $strvar->coursename = $DB->get_field('course', 'fullname', array('id' => $cid));

            foreach ($sessids as $sessid) {
                $seminarevent = new seminar_event($sessid);
                $signup = signup::create($uid, $seminarevent);

                // Check if user is enrolled on any sessions in the future. And cancel them.
                if ($signup->exists()) {
                    if (empty($strvar->username)) {
                        $strvar->username = fullname($user);
                    }

                    // We want to cancel only signup that learner can cancel themselves.
                    // E.g. we don't want to cancel past signups.
                    $signup->set_actorid($signup->get_userid());
                    if (signup_helper::can_user_cancel($signup)) {
                        signup_helper::user_cancel($signup);
                        notice_sender::signup_cancellation($signup);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Add calendar entry when user is booked
     *
     * @param \mod_facetoface\event\booking_booked $event
     */
    public static function add_calendar_booked_entry(\mod_facetoface\event\abstract_signup_event $event) {

        $seminarevent = $event->get_signup()->get_seminar_event();
        if ($seminarevent->get_seminar()->get_usercalentry()) {
            \mod_facetoface\calendar::add_seminar_event($seminarevent, 'user', $event->get_signup()->get_userid(), 'booking');
        }
    }

    /**
     * Send notifications when user cancelled their booking
     *
     * @param \mod_facetoface\event\booking_cancelled $event
     */
    public static function remove_calendar_booked_entry(\mod_facetoface\event\booking_cancelled $event) {
        $signup = $event->get_signup();
        $seminarevent = $signup->get_seminar_event();
        calendar::remove_seminar_event($seminarevent, 0, $signup->get_userid());
    }

    /**
     * Send notifications when user cancels booking
     *
     * @param \mod_facetoface\event\booking_cancelled $event
     */
    public static function send_notification_booking_cancelled(\mod_facetoface\event\booking_cancelled $event) {
        $signup = $event->get_signup();
        $seminarevent = $signup->get_seminar_event();
        $seminar = $seminarevent->get_seminar();

        $data = [
            'user_id' => $signup->get_userid(),
            'seminar_event_id' => $seminarevent->get_id(),
            'seminar_id' => $seminar->get_id(),
            'module_id' => $seminar->get_coursemodule()->id,
            'course_id' => $seminar->get_course()
        ];

        seminar_notification_helper::create_seminar_notifiable_event_queue(
            $seminar,
            new booking_cancelled($data)
        );
    }

    /**
     * Send notifications when user is booked
     *
     * @param \mod_facetoface\event\booking_booked $event
     */
    public static function send_notification_booked(\mod_facetoface\event\booking_booked $event) {
        $signup = $event->get_signup();
        $seminarevent = $signup->get_seminar_event();

        if ($seminarevent->is_first_started() || $signup->get_attendance_processed()) {
            return;
        }

        $seminar = $seminarevent->get_seminar();
        $data = [
            'user_id' => $signup->get_userid(),
            'seminar_event_id' => $seminarevent->get_id(),
            'session_id' => $signup->get_sessionid(),
            'seminar_id' => $seminar->get_id(),
            'module_id' => $seminar->get_coursemodule()->id,
            'course_id' => $seminar->get_course()
        ];

        notice_sender::confirm_booking($signup, static::get_ical_notification_type($signup));
        // New notification setup.
        seminar_notification_helper::create_seminar_notifiable_event_queue(
            $seminar,
            new booking_confirmed($data)
        );
    }

    /**
     * Send notifications when user is waitlisted
     *
     * @param \mod_facetoface\event\booking_waitlisted $event
     */
    public static function send_notification_waitlisted(\mod_facetoface\event\booking_waitlisted $event) {
        $signup = $event->get_signup();
        notice_sender::confirm_waitlist($signup, static::get_ical_notification_type($signup));
    }

    /**
     *  Send notifications when user requested booking approval
     *
     * @param \mod_facetoface\event\booking_requested $event
     */
    public static function send_notification_requested(\mod_facetoface\event\booking_requested $event) {
        $signup = $event->get_signup();
        $seminarevent = $signup->get_seminar_event();
        $seminar = $seminarevent->get_seminar();

        if ($seminar->get_approvaltype() == seminar::APPROVAL_ROLE) {
            // Send the booking requested message to the user.
            notice_sender::request_role($signup);
        } else if ($seminar->get_approvaltype() == seminar::APPROVAL_ADMIN) {
            // Send the booking requested message to the user.
            notice_sender::request_admin($signup);
        } else {
            notice_sender::request_manager($signup);
        }

        // Trigger the centralised notifications message.
        $data = [
            'user_id' => $signup->get_userid(),
            'course_id' => $seminar->get_course(),
            'module_id' => $seminar->get_coursemodule()->id,
            'seminar_id' => $seminar->get_id(),
            'seminar_event_id' => $seminarevent->get_id()
        ];

        seminar_notification_helper::create_seminar_notifiable_event_queue(
            $seminar,
            new booking_request_created($data)
        );
    }

    /**
     * Mark course completion to being in progress
     * @param event\abstract_signup_event $event
     */
    public static function mark_completion_in_progress(\mod_facetoface\event\abstract_signup_event $event) {
        global $DB;

        $seminar = $event->get_signup()->get_seminar_event()->get_seminar();
        $course = $DB->get_record('course', ['id' => $seminar->get_course()], '*', MUST_EXIST);

        $completion = new completion_info($course);
        if ($completion->is_enabled()) {

            $ccdetails = array(
                'course' => $course->id,
                'userid' => $event->get_signup()->get_userid(),
            );

            $cc = new completion_completion($ccdetails);
            $cc->mark_inprogress();
        }
    }

    /**
     * Update the event grade and the activity completion status when a signup is deleted.
     *
     * @param \mod_facetoface\event\signup_deleted $event
     * @return bool true on success
     */
    public static function signup_deleted(\mod_facetoface\event\signup_deleted $event) {
        $signup = $event->get_signup();
        // NOTE: grade_helper::grade_signup() can cope with non-existent signup
        // because it's only interested in sessionid and userid.
        $seminarevent = $signup->get_seminar_event();
        return \mod_facetoface\grade_helper::grade_signup($seminarevent, $signup);
    }

    /**
     * Update the event grade and the activity completion status when a signup status is updated.
     *
     * @param \mod_facetoface\event\signup_status_updated $event
     * @return bool true on success
     */
    public static function signup_status_updated(\mod_facetoface\event\signup_status_updated $event) {
        $signupstatus = $event->get_signupstatus();
        $signup = new \mod_facetoface\signup($signupstatus->get_signupid());
        $seminarevent = $signup->get_seminar_event();
        return \mod_facetoface\grade_helper::grade_signup($seminarevent, $signup);
    }

    /**
     * Triggered via job_assignment_deleted event.
     * - Removes facetoface signup jobassignmentid data
     *
     * @param \totara_job\event\job_assignment_deleted $event
     * @return bool true on success
     */
    public static function job_assignment_deleted(\totara_job\event\job_assignment_deleted $event) {
        global $DB;

        $sql = "UPDATE {facetoface_signups} SET jobassignmentid = NULL WHERE jobassignmentid = :jobassignmentid";
        $DB->execute($sql, ['jobassignmentid' => $event->objectid]);

        return true;
    }

    /**
     * The ical attachment type, or MDL_F2F_TEXT to disable ical attachments
     * @param signup $signup
     * @return int
     */
    private static function get_ical_notification_type(signup $signup): int {
        // MDL_F2F_NONE - send nothing
        // MDL_F2F_TEXT - send email only and no ical
        // MDL_F2F_BOTH - send email and ical
        return (
            $signup->get_notificationtype() == MDL_F2F_NONE ||
            $signup->get_notificationtype() == MDL_F2F_TEXT
        ) ? MDL_F2F_TEXT : MDL_F2F_BOTH;
    }

    /**
     * Triggered via role_deleted event.
     * - Removes deleted role from facetoface_session_roles config
     *
     * @param \core\event\role_deleted $event
     * @return bool true on success
     */
    public static function remove_role_from_facetoface_session_roles(\core\event\role_deleted $event) {
        global $CFG;

        // Remove role from facetoface_session_roles if present.
        $roleid = $event->objectid;
        if (!empty($CFG->facetoface_session_roles)) {
            $facetoface_session_roles = explode(',', $CFG->facetoface_session_roles);
            $session_roles = array_combine($facetoface_session_roles, $facetoface_session_roles);
            if (isset($session_roles[$roleid])) {
                unset($session_roles[$roleid]);
                $CFG->facetoface_session_roles = implode(',', $session_roles);
                set_config('facetoface_session_roles', $CFG->facetoface_session_roles);
            }
        }

        return true;
    }

}
