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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

use core\orm\query\builder;
use mod_facetoface\signup\state\booked;
use mod_facetoface\signup\state\waitlisted;
use mod_facetoface\totara_notification\resolver\booking_details_changed;
use mod_facetoface\totara_notification\resolver\booking_request_declined;
use mod_facetoface\totara_notification\resolver\facilitator_assigned;
use mod_facetoface\totara_notification\resolver\facilitator_unassigned;
use mod_facetoface\totara_notification\resolver\event_reservations_cancelled;
use mod_facetoface\totara_notification\resolver\signup_waitlisted;
use mod_facetoface\totara_notification\resolver\trainer_sessions_details_changed;
use mod_facetoface\totara_notification\seminar_notification_helper;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/notification/lib.php');


/**
 * Class notice_sender is just a wrapper that sends typical signup related notices and notifications to users.
 */
class notice_sender {
    /**
     * Send manager request notices
     *
     * @param signup $signup
     * @return string
     */
    public static function request_manager(signup $signup) {
        $managers = signup_helper::find_managers_from_signup($signup);

        $hasemail = false;
        foreach ($managers as $manager) {
            if (!empty($manager->email)) {
                $hasemail = true;
                break;
            }
        }

        if ($hasemail) {
            $params = [
                'type'          => MDL_F2F_NOTIFICATION_AUTO,
                'conditiontype' => MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER
            ];

            // Do not send ical attachment for request as it has not been approved yet.
            return static::send($signup, $params);
        }
        return 'error:nomanagersemailset';
    }

    /**
     * Send booking request notice to user and all users with the specified sessionrole
     *
     * @param signup $signup
     * @return string
     */
    public static function request_role(signup $signup) : string {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_REQUEST_ROLE
        ];

        // Do not send ical attachment for request as it has not been approved yet.
        return static::send($signup, $params);
    }

    /**
     * Send booking request notice to user, manager, all session admins.
     *
     * @param signup $signup
     * @return string
     */
    public static function request_admin(signup $signup) : string {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN
        ];

        // Do not send ical attachment for request as it has not been approved yet.
        return static::send($signup, $params);
    }

    /**
     * Send a booking confirmation email to the user and manager
     *
     * @param signup $signup Signup
     * @param int $notificationtype Type of notifications to be sent @see {{MDL_F2F_INVITE}}
     * @param stdClass $fromuser User object describing who the email is from.
     * @return string Error message (or empty string if successful)
     */
    public static function confirm_booking(signup $signup, int $notificationtype, stdClass $fromuser = null) : string {
        global $DB;

        $params = [
            'type' => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_CONFIRMATION
        ];

        $fromuser = $signup->get_fromuser();
        if (empty($fromuser) && !empty($signup->get_managerid())) {
            $fromuser = \core_user::get_user($signup->get_managerid()) ?: null;
        }

        return static::send($signup, $params, $notificationtype, MDL_F2F_INVITE, $fromuser);
    }

    /**
     * Send a waitlist confirmation email to the user and manager
     *
     * @param signup $signup Signup
     * @param int $notificationtype Type of notifications to be sent @see {{MDL_F2F_INVITE}}
     * @param stdClass $fromuser User object describing who the email is from.
     * @return string Error message (or empty string if successful)
     */
    public static function confirm_waitlist(signup $signup, int $notificationtype, stdClass $fromuser = null) : string {
        // Centralised notifications.
        $seminar_event = $signup->get_seminar_event();
        $seminar = $seminar_event->get_seminar();
        $data = [
            'user_id' => $signup->get_userid(),
            'seminar_event_id' => $seminar_event->get_id(),
            'seminar_id' => $seminar->get_id(),
            'module_id' => $seminar->get_coursemodule()->id,
            'course_id' => $seminar->get_course()
        ];
        seminar_notification_helper::create_seminar_notifiable_event_queue(
            $seminar,
            new signup_waitlisted($data)
        );

        // Legacy notification.
        $params = [
            'type' => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION
        ];

        $fromuser = $signup->get_fromuser();
        if (empty($fromuser) && !empty($signup->get_managerid())) {
            $fromuser = \core_user::get_user($signup->get_managerid());
        }

        return static::send($signup, $params, $notificationtype, MDL_F2F_INVITE, $fromuser);
    }


    /**
     * Send a confirmation email to the user and manager regarding the
     * cancellation
     *
     * @param signup $signup Signup
     * @return string Error message (or empty string if successful)
     */
    public static function decline(signup $signup) {
        global $CFG;

        // Centralised notifications.
        $seminar_event = $signup->get_seminar_event();
        $seminar = $seminar_event->get_seminar();
        $data = [
            'user_id' => $signup->get_userid(),
            'seminar_event_id' => $seminar_event->get_id(),
            'seminar_id' => $seminar->get_id(),
            'module_id' => $seminar->get_coursemodule()->id,
            'course_id' => $seminar->get_course()
        ];
        seminar_notification_helper::create_seminar_notifiable_event_queue(
            $seminar,
            new booking_request_declined($data)
        );

        // Legacy notification.
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_DECLINE_CONFIRMATION
        ];

        $includeical = empty($CFG->facetoface_disableicalcancel);
        return static::send($signup, $params, $includeical ? MDL_F2F_BOTH : MDL_F2F_TEXT, MDL_F2F_CANCEL);
    }

    /**
     * Send a email to the not signed up attendees (e.g. roles)
     *
     * @param integer $recipientid ID of the recipient of the email
     * @param seminar_event $seminarevent
     * @param array $olddates array of previous dates
     * @param bool $include_cn include triggering CN notifications
     * @param array $sessions_cancelled
     * @return string Error message (or empty string if successful)
     */
    public static function event_datetime_changed(
        int $recipientid,
        seminar_event $seminarevent,
        array $olddates,
        bool $include_cn = false,
        array $sessions_cancelled = []
    ) : string {
        // NOTE: We are ignoring $include_cn as create_seminar_notifiable_event_queue does this check for us.
        $seminar = $seminarevent->get_seminar();
        $data = [
            'trainer_user_id' => $recipientid,
            'seminar_event_id' => $seminarevent->get_id(),
            'seminar_id' => $seminar->get_id(),
            'module_id' => $seminar->get_coursemodule()->id,
            'course_id' => $seminar->get_course(),
            'sessions_cancelled' => $sessions_cancelled,
        ];
        seminar_notification_helper::create_seminar_notifiable_event_queue(
            $seminar,
            new trainer_sessions_details_changed($data)
        );

        // Legacy notification.
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE
        ];

        return static::send_event($recipientid, $seminarevent, $params, MDL_F2F_BOTH, MDL_F2F_INVITE, null, $olddates);
    }

    /**
     * Send a email to the user and manager regarding the
     * session date/time change
     *
     * @param signup $signup
     * @param array $olddates
     * @param bool $include_legacy include sending legacy notifications
     * @param bool $force_invite if true then an ical attachment will be sent, regardless of signup state (legacy only)
     * @param array $sessions_cancelled
     * @return string Error message or empty string if success
     */
    public static function signup_datetime_changed(
        signup $signup,
        array $olddates,
        bool $include_legacy = true,
        bool $force_invite = false,
        array $sessions_cancelled = []
    ) : string {
        // Centralised notifications.
        $seminar_event = $signup->get_seminar_event();
        $seminar = $seminar_event->get_seminar();
        $data = [
            'user_id' => $signup->get_userid(),
            'seminar_event_id' => $seminar_event->get_id(),
            'seminar_id' => $seminar->get_id(),
            'module_id' => $seminar->get_coursemodule()->id,
            'course_id' => $seminar->get_course(),
            'sessions_cancelled' => $sessions_cancelled,
        ];
        seminar_notification_helper::create_seminar_notifiable_event_queue(
            $seminar,
            new booking_details_changed($data)
        );

        // Legacy notification.
        if ($include_legacy) {
            $params = [
                'type'          => MDL_F2F_NOTIFICATION_AUTO,
                'conditiontype' => MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE
            ];

            if ($force_invite) {
                $invite = MDL_F2F_BOTH;
            } else {
                $invite = ($signup->get_state() instanceof waitlisted) ? MDL_F2F_TEXT : MDL_F2F_BOTH;
            }
            return static::send($signup, $params, $invite, MDL_F2F_INVITE, null, $olddates);
        } else {
            return '';
        }
    }

    /**
     * Send a message to a user who has just had their waitlisted signup cancelled due to the event starting
     * and the automatic waitlist cleaner cancelling all waitlisted records.
     *
     * @param signup $signup
     * @return string
     */
    public static function signup_waitlist_autoclean(signup $signup) : string {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_WAITLIST_AUTOCLEAN
        ];

        return static::send($signup, $params);
    }

    /**
     * Send a confirmation email to the trainer
     *
     * @param integer $recipientid ID of the recipient of the email
     * @param seminar_event $seminarevent
     * @return string Error message (or empty string if successful)
     */
    public static function trainer_confirmation(int $recipientid, seminar_event $seminarevent) {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_TRAINER_CONFIRMATION
        ];

        return static::send_event($recipientid, $seminarevent, $params, MDL_F2F_BOTH, MDL_F2F_INVITE);
    }

    /**
     * Send a cancellation email to the trainer
     *
     * @param integer $recipientid ID of the recipient of the email
     * @param seminar_event $seminarevent
     * @return string Error message (or empty string if successful)
     */
    public static function event_trainer_cancellation(int $recipientid, seminar_event $seminarevent) {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_TRAINER_SESSION_CANCELLATION
        ];
        return static::send_event($recipientid, $seminarevent, $params, MDL_F2F_BOTH, MDL_F2F_CANCEL);
    }

    /**
     * Send a unassignment email to the trainer
     *
     * @param integer $recipientid ID of the recipient of the email
     * @param seminar_event $seminarevent
     * @return string Error message (or empty string if successful)
     */
    public static function event_trainer_unassigned(int $recipientid, seminar_event $seminarevent) {
        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_TRAINER_SESSION_UNASSIGNMENT
        ];

        return static::send_event($recipientid, $seminarevent, $params, MDL_F2F_BOTH, MDL_F2F_CANCEL);
    }

    /**
     * Send a confirmation email to the user and manager regarding the
     * signup cancellation
     *
     * @param signup $signup Signup
     * @param bool $attachical Should cancellation ical be attached
     * @return string Error message (or empty string if successful)
     */
    public static function signup_cancellation(signup $signup, $attachical = true) {
        global $CFG;

        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION
        ];

        $icalattachmenttype = (empty($CFG->facetoface_disableicalcancel) && $attachical) ? MDL_F2F_BOTH : MDL_F2F_TEXT;
        return static::send($signup, $params, $icalattachmenttype, MDL_F2F_CANCEL);
    }

    /**
     * Checking whether the notification will be cc to manager or not.
     * This function will only check the flag "ccmanager" in table "ttr_facetoface_notification" to see
     * whether it is zero or one. True if it is one, otherwise false.
     *
     * @param int $seminar_id
     * @return bool
     */
    public static function is_cc_to_manager_when_cancel_signup(int $seminar_id): bool {
        $db = builder::get_db();
        $field = $db->get_field(
            "facetoface_notification",
            "ccmanager",
            [
                "type" => MDL_F2F_NOTIFICATION_AUTO,
                "conditiontype" => MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION,
                "facetofaceid" => $seminar_id
            ]
        );

        return (1 == $field);
    }

    /**
     * Send a confirmation email to the recepient regarding seminar event cancellation
     *
     * @param integer $recipientid ID of the recipient of the email
     * @param seminar_event $seminarevent
     * @param bool $attachical Should cancellation ical be attached
     * @return string Error message (or empty string if successful)
     */
    public static function event_cancellation(int $recipientid, seminar_event $seminarevent, bool $attachical = true) {
        global $CFG;

        $params = [
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_SESSION_CANCELLATION
        ];

        $icalattachmenttype = (empty($CFG->facetoface_disableicalcancel) && $attachical) ? MDL_F2F_BOTH : MDL_F2F_TEXT;
        return static::send_event($recipientid, $seminarevent, $params, $icalattachmenttype, MDL_F2F_CANCEL);
    }

    /**
     * Notify managers that a session they had reserved spaces on has been deleted.
     *
     * @param seminar_event $seminarevent
     */
    public static function reservation_cancelled(seminar_event $seminarevent) {
        global $CFG, $DB;

        $helper = new attendees_helper($seminarevent);
        $attendees = $helper->get_reservations();

        $reservedids = array();
        foreach ($attendees as $attendee) {
            if ($attendee->has_bookedby() && !$attendee->is_valid()) {
                // If the attendee is booked by a manager, and the reservation is not a valid record, because it has
                // no user to fill up this reservation yet. Then we add the manager id here.
                // Managers can already get booking cancellation notices - just adding reserve cancellation notices.
                $reservedids[] = $attendee->get_bookedby();
            }
        }
        if (!$reservedids) {
            return;
        }
        $reservedids = array_unique($reservedids);

        $facetoface = $DB->get_record('facetoface', ['id' => $seminarevent->get_facetoface()]);
        $facetoface->ccmanager = false; // Never Cc the manager's manager (that would just be too much).

        // Notify all managers that have reserved spaces for their team.
        $params = array(
            'facetofaceid'  => $facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_RESERVATION_CANCELLED
        );

        $includeical = empty($CFG->facetoface_disableicalcancel);
        foreach ($reservedids as $reservedid) {
            // Legacy notify all reservation managers.
            static::send_notice($seminarevent, $reservedid, $params, $includeical ? MDL_F2F_BOTH : MDL_F2F_TEXT, MDL_F2F_CANCEL);
        }

        // Centralised notifications.
        $seminar = $seminarevent->get_seminar();
        $data = [
            'seminar_event_id' => $seminarevent->get_id(),
            'seminar_id' => $seminar->get_id(),
            'module_id' => $seminar->get_coursemodule()->id,
            'course_id' => $seminar->get_course(),
            'reservation_manager_ids' => $reservedids,
        ];
        seminar_notification_helper::create_seminar_notifiable_event_queue(
            $seminar,
            new event_reservations_cancelled($data)
        );
    }

    /**
     * Notify facilitators when seminar session(s) are deleted.
     *
     * @param integer $recipientid
     * @param seminar_event $seminarevent
     */
    public static function session_facilitator_cancellation(int $recipientid, seminar_event $seminarevent, bool $attachical = true): void {
        global $CFG;

        // Notify all facilitators.
        $params = array(
            'facetofaceid'  => $seminarevent->get_facetoface(),
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_FACILITATOR_SESSION_CANCELLATION
        );

        $icalattachmenttype = (empty($CFG->facetoface_disableicalcancel) && $attachical) ? MDL_F2F_BOTH : MDL_F2F_TEXT;
        static::send_notice($seminarevent, $recipientid, $params, $icalattachmenttype);
    }

    /**
     * Notify facilitators when seminar session(s) are deleted.
     *
     * @param integer $recipientid
     * @param seminar_event $seminarevent
     */
    public static function session_facilitator_datetime_changed(int $recipientid, seminar_event $seminarevent, array $olddates, bool $attachical = true): void {
        global $CFG;

        // Notify all facilitators.
        $params = array(
            'facetofaceid'  => $seminarevent->get_facetoface(),
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_FACILITATOR_SESSION_DATETIME_CHANGE
        );

        $icalattachmenttype = (empty($CFG->facetoface_disableicalcancel) && $attachical) ? MDL_F2F_BOTH : MDL_F2F_TEXT;
        static::send_notice($seminarevent, $recipientid, $params, $icalattachmenttype, MDL_F2F_INVITE, null, $olddates);
    }

    /**
     * Notify facilitators when facilitator(s) are assigned to a seminar event.
     *
     * @param integer $recipientid
     * @param seminar_event $seminarevent
     */
    public static function session_facilitator_assigned(int $recipientid, seminar_event $seminarevent, bool $attachical = true): void {
        global $CFG;

        // Centralised notifications.
        $seminar = $seminarevent->get_seminar();
        $data = [
            'facilitator_user_id' => $recipientid,
            'seminar_event_id' => $seminarevent->get_id(),
            'seminar_id' => $seminar->get_id(),
            'module_id' => $seminar->get_coursemodule()->id,
            'course_id' => $seminar->get_course()
        ];
        seminar_notification_helper::create_seminar_notifiable_event_queue(
            $seminar,
            new facilitator_assigned($data)
        );

        // Legacy notify all facilitators.
        $params = array(
            'facetofaceid'  => $seminarevent->get_facetoface(),
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_FACILITATOR_SESSION_ASSIGNED
        );

        $icalattachmenttype = (empty($CFG->facetoface_disableicalcancel) && $attachical) ? MDL_F2F_BOTH : MDL_F2F_TEXT;
        static::send_notice($seminarevent, $recipientid, $params, $icalattachmenttype);
    }

    /**
     * Notify facilitators when facilitator(s) are unassigned to a seminar event.
     *
     * @param integer $recipientid
     * @param seminar_event $seminarevent
     */
    public static function session_facilitator_unassigned(int $recipientid, seminar_event $seminarevent, bool $attachical = true): void {
        global $CFG;

        // Notify all facilitators.
        $params = array(
            'facetofaceid'  => $seminarevent->get_facetoface(),
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_FACILITATOR_SESSION_UNASSIGNED
        );

        $icalattachmenttype = (empty($CFG->facetoface_disableicalcancel) && $attachical) ? MDL_F2F_BOTH : MDL_F2F_TEXT;
        static::send_notice($seminarevent, $recipientid, $params, $icalattachmenttype);

        // Centralised notifications.
        $seminar = $seminarevent->get_seminar();
        $data = [
            'seminar_event_id' => $seminarevent->get_id(),
            'facilitator_user_id' => $recipientid,
            'seminar_id' => $seminar->get_id(),
            'module_id' => $seminar->get_coursemodule()->id,
            'course_id' => $seminar->get_course(),
            'sessions_cancelled' => $seminarevent->get_sessions()->sort('timestart')->to_records(false)
        ];
        seminar_notification_helper::create_seminar_notifiable_event_queue(
            $seminar,
            new facilitator_unassigned($data)
        );
    }

    /**
     * Send a notice (all session dates in one message).
     *
     * @param seminar_event $seminarevent
     * @param integer $userid ID of the recipient of the email
     * @param array $params The parameters for the notification
     * @param int $icalattachmenttype The ical attachment type, or MDL_F2F_TEXT to disable ical attachments
     * @param int $icalattachmentmethod The ical method type: MDL_F2F_INVITE or MDL_F2F_CANCEL
     * @param object $fromuser User object describing who the email is from.
     * @param array $olddates array of previous dates
     * @param bool $notifyuser Send user notification
     * @param bool $notifymanager Send manager notification
     * @return string Error message (or empty string if successful)
     */
    public static function send_notice(seminar_event $seminarevent, $userid, $params, $icalattachmenttype = MDL_F2F_TEXT,
                                       $icalattachmentmethod = MDL_F2F_INVITE, $fromuser = null, array $olddates = array(),
                                       $notifyuser = true, $notifymanager = true) {
        global $DB;

        // Not sending new notifications when globally not allowing legacy notifications
        if (!facetoface_site_allows_legacy_notifications()) {
            return false;
        }

        $notificationdisable = get_config(null, 'facetoface_notificationdisable');
        if (!empty($notificationdisable)) {
            return false;
        }

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
        if (!$user) {
            return 'userdoesnotexist';
        }

        // Make it not fail if more then one notification found. Just use one.
        // Other option is to change data_object, but so far it's facetoface issue that we hope to fix soon and remove workaround
        // code from here.
        $checkrows = $DB->get_records('facetoface_notification', $params);
        if (count($checkrows) > 1) {
            $params['id'] = reset($checkrows)->id;
            debugging("Duplicate notifications found for (excluding id): " . json_encode($params), DEBUG_DEVELOPER);
        }

        // By definition, the send one email per day feature works on sessions with
        // dates. However, the current system allows sessions to be created without
        // dates and it allows people to sign up to those sessions. In this cases,
        // the sign ups still need to get email notifications; hence the checking of
        // the existence of dates before allowing the send one email per day part.
        // Note, that's not always the case, if all dates have been deleted from a
        // seminar event we still need to send the emails to cancel the dates,
        // thus need to check whether old dates have been supplied.
        if (get_config(null, 'facetoface_oneemailperday')
            && ($seminarevent->is_sessions() || !empty($olddates))) {
            return static::send_notice_oneperday($seminarevent, $userid, $params, $icalattachmenttype, $icalattachmentmethod,
                $olddates, $notifyuser, $notifymanager);
        }

        $seminareventid = $seminarevent->get_id();

        $notice = new \facetoface_notification($params);
        $notice->facetofaceid = $seminarevent->get_facetoface();
        $notice->set_newevent($user, $seminarevent, null, $fromuser);

        if ($notifyuser) {
            $icaldata = [];
            if ((int)$icalattachmenttype == MDL_F2F_BOTH && $notice->conditiontype != MDL_F2F_CONDITION_DECLINE_CONFIRMATION) {
                // add_ical_attachment needs session dates on the session stdClass object
                $session = $seminarevent->to_record();
                $session->sessiondates = $seminarevent->get_sessions()->sort('timestart')->to_records(false);
                $notice->add_ical_attachment($user, $session, $icalattachmentmethod, null, $olddates);
                $icaldata = [
                    'dates' => $session->sessiondates,
                    'olddates' => $olddates,
                    'method' => $icalattachmentmethod
                ];
            }
            $notice->send_to_user($user, $seminareventid, null, $icaldata);
        }
        if ($notifymanager) {
            $notice->send_to_manager($user, $seminareventid);
        }
        $notice->send_to_thirdparty($user, $seminareventid);
        $notice->send_to_roleapprovers_adhoc($user, $seminareventid);
        $notice->send_to_adminapprovers_adhoc($user, $seminareventid);
        $notice->delete_ical_attachment();

        return '';
    }

    /**
     * Send registration closure notice to user, manager, all session admins.
     *
     * @param seminar_event $seminarevent   Session instance
     * @param int           $recipientid    The id of the user requesting a booking
     */
    public static function registration_closure(seminar_event $seminarevent, $recipientid) {
        global $DB, $USER;

        // Not sending new notifications when globally not allowing legacy notifications
        if (!facetoface_site_allows_legacy_notifications()) {
            return false;
        }

        $notificationdisable = get_config(null, 'facetoface_notificationdisable');
        if (!empty($notificationdisable)) {
            return false;
        }

        $recipient = \core_user::get_user($recipientid);
        if (!$recipient) {
            return 'userdoesnotexist';
        }

        $params = array(
            'facetofaceid'  => $seminarevent->get_facetoface(),
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BEFORE_REGISTRATION_ENDS
        );

        $seminareventid = $seminarevent->get_id();
        $notice = new \facetoface_notification($params);
        $notice->facetofaceid = $seminarevent->get_facetoface();
        $notice->set_newevent($recipient, $seminareventid, null, $USER);
        $notice->send_to_user($recipient, $seminareventid);
        $notice->send_to_manager($recipient, $seminareventid);

        return '';
    }

    /**
     * Send message to signed up attendee
     * @param signup $signup
     * @param array $params
     * @param int $icalattachmenttype
     * @param int $icalattachmentmethod
     * @param stdClass $fromuser
     * @param array $olddates
     * @return string
     */
    protected static function send(signup $signup, array $params, int $icalattachmenttype = MDL_F2F_TEXT, int $icalattachmentmethod = MDL_F2F_INVITE, stdClass $fromuser = null, array $olddates = []) : string {
        $recipientid = $signup->get_userid();
        $seminarevent = $signup->get_seminar_event();
        $params['facetofaceid']  = $seminarevent->get_facetoface();

        $notifyuser = true;
        $notifymanager = true;
        if ($signup->get_skipusernotification() || $signup->get_notificationtype() == MDL_F2F_NONE) {
            $notifyuser = false;
        }

        if ($signup->get_skipmanagernotification()) {
            $notifymanager = false;
        }

        if ($notifymanager || $notifyuser || $seminarevent->is_first_started()) {
            return static::send_notice($seminarevent, $recipientid, $params, $icalattachmenttype, $icalattachmentmethod,
                $fromuser, $olddates, $notifyuser, $notifymanager);
        }

        return '';
    }

    /**
     * Send message to not signed up event attendee (e.g. role)
     * @param int $recipientid
     * @param seminar_event $seminarevent
     * @param array $params
     * @param int $icalattachmenttype
     * @param int $icalattachmentmethod
     * @param stdClass $fromuser
     * @param array $olddates
     * @return string
     */
    protected static function send_event(int $recipientid, seminar_event $seminarevent, array $params, int $icalattachmenttype = MDL_F2F_TEXT,
                                         int $icalattachmentmethod = MDL_F2F_INVITE, stdClass $fromuser = null, array $olddates = []) : string {
        global $DB;
        $params['facetofaceid']  = $seminarevent->get_facetoface();

        return static::send_notice($seminarevent, $recipientid, $params, $icalattachmenttype, $icalattachmentmethod, $fromuser, $olddates);
    }

    /**
     * Send a notice (one message per session date).
     *
     * @param seminar_event $seminarevent
     * @param integer $userid ID of the recipient of the email
     * @param array $params The parameters for the notification
     * @param int $icalattachmenttype The ical attachment type, or MDL_F2F_TEXT to disable ical attachments
     * @param int $icalattachmentmethod The ical method type: MDL_F2F_INVITE or MDL_F2F_CANCEL
     * @param array $olddates array of previous dates
     * @param bool $notifyuser Send user notification
     * @param bool $notifymanager Send manager notification
     * @return string Error message (or empty string if successful)
     */
    private static function send_notice_oneperday(seminar_event $seminarevent, $userid, $params, $icalattachmenttype = MDL_F2F_TEXT,
                                                  $icalattachmentmethod = MDL_F2F_INVITE, array $olddates = [],
                                                  $notifyuser = true, $notifymanager = true) {
        global $CFG;

        $notificationdisable = get_config(null, 'facetoface_notificationdisable');
        if (!empty($notificationdisable)) {
            return false;
        }

        $user = \core_user::get_user($userid);
        if (!$user) {
            return 'userdoesnotexist';
        }

        // Get sessions and convert to an array of stdClass objects
        // to fit in with the rest of the code down the line.
        $eventsessions = $seminarevent->get_sessions();
        $session = $seminarevent->to_record();
        $session->sessiondates = [];
        foreach ($eventsessions as $eventsession) {
            $session_date_id = $eventsession->get_id();
            $session->sessiondates[] = (object)[
                'id' => $session_date_id,
                'sessionid' => $eventsession->get_sessionid(),
                'sessiontimezone' => $eventsession->get_sessiontimezone(),
                'timestart' => $eventsession->get_timestart(),
                'timefinish' => $eventsession->get_timefinish(),
                'roomids' => room_helper::get_room_ids_sorted($session_date_id),
                'facilitatorids' => facilitator_helper::get_facilitator_ids_sorted($session_date_id),
            ];
        }

        // Filtering dates.
        // "Key by" date id.
        $get_id = function ($item) {
            return $item->id;
        };
        $olds = array_combine(array_map($get_id, $olddates), $olddates);

        $dates = array_filter($session->sessiondates, function ($date) use (&$olds) {
            if (isset($olds[$date->id])) {
                // Remove from the list of old dates. We only want to send for those old date ids that don't have a corresponding
                // new date - that's the case when a date is deleted.
                $old = $olds[$date->id];
                unset($olds[$date->id]);

                // If both old and new date have been passed in, it means we are looking at a notification where we only want to
                // send it for a particular date when relevant change is detected. So exclude those dates that don't have any changes.
                if (!seminar_session_list::has_date_changed($old, $date)) {
                    return false;
                }
            }

            return true;
        });

        $send = function ($dates, $cancel = false, $notifyuser = true, $notifymanager = true) use ($seminarevent, $session,
            $icalattachmenttype, $icalattachmentmethod, $user, $params, $CFG
        ) {
            $seminareventid = $seminarevent->get_id();
            foreach ($dates as $date) {
                if ($cancel) {
                    $params['conditiontype'] = MDL_F2F_CONDITION_CANCELLATION_CONFIRMATION;
                }
                $sendical =  (int)$icalattachmenttype == MDL_F2F_BOTH &&
                    (!$cancel || ($cancel && empty($CFG->facetoface_disableicalcancel)));

                $notice = new \facetoface_notification($params);
                $notice->facetofaceid = $seminarevent->get_facetoface();
                // Send original notice for this date.
                $notice->set_newevent($user, $seminareventid, $date);
                $icaldata = [];
                if ($sendical) {
                    $notice->add_ical_attachment($user, $session, $icalattachmentmethod, !$cancel ? $date : [], $cancel ? $date : []);
                    $icaldata = [
                        'dates' => !$cancel ? $date : [],
                        'olddates' => $cancel ? $date : [],
                        'method' => $icalattachmentmethod
                    ];
                }
                if ($notifyuser) {
                    $notice->send_to_user($user, $seminareventid, $date, $icaldata);
                }
                if ($notifymanager) {
                    $notice->send_to_manager($user, $seminareventid);
                }

                $notice->send_to_thirdparty($user, $seminareventid);
                $notice->send_to_roleapprovers_adhoc($user, $seminareventid);
                $notice->send_to_adminapprovers_adhoc($user, $seminareventid);

                $notice->delete_ical_attachment();
            }
        };

        $send($dates, false, $notifyuser, $notifymanager);
        $send($olds, true, $notifyuser, $notifymanager);

        return '';
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
}