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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\task;

use mod_facetoface\{notice_sender,
    seminar_event,
    signup,
    totara_notification\resolver\booking_request_expired,
    totara_notification\seminar_notification_helper};
use mod_facetoface\signup\state\{requested, requestedrole, requestedadmin, declined};

/**
 * Check for sessions where the registration period has recently ended,
 * cancel any pending requests for the session and send the users a
 * notification so they know to try sign up to another session.
 */
class close_registrations_task extends \core\task\scheduled_task {
    // Test mode.
    public $testing = false;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('closeregistrationstask', 'mod_facetoface');
    }

    /**
     * Finds all facetoface sessions that have a closed registration period and cancels all pending requests.
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/mod/facetoface/lib.php');

        if (!$this->testing) {
            mtrace('Checking for Face-to-face sessions with expired registration periods...');
        }

        $time = time();

        $sql = "SELECT s.*
                  FROM {facetoface_sessions} s
                 WHERE registrationtimefinish < :now
                   AND registrationtimefinish > 0
                   AND EXISTS (
                       SELECT fs.id
                         FROM {facetoface_signups} fs
                         JOIN {facetoface_signups_status} fss
                           ON fss.signupid = fs.id
                        WHERE (fss.statuscode = :req OR fss.statuscode = :roreq OR fss.statuscode = :adreq)
                          AND fs.sessionid = s.id
                       )
              ORDER BY s.facetoface, s.id";
        $params = array(
            'now'      => $time,
            'req' => requested::get_code(),
            'roreq' => requestedrole::get_code(),
            'adreq' => requestedadmin::get_code(),
        );

        $sessions = $DB->get_records_sql($sql, $params);

        foreach ($sessions as $session) {
            $seminarevent = new seminar_event();
            $seminarevent->from_record($session);

            $this->cancel_pending_requests($seminarevent);
        }

        return true;
    }

    /**
     * Cancel all pending requests for a given session.
     *
     * @param seminar_event $seminarevent
     * @return void
     */
    private function cancel_pending_requests(seminar_event $seminarevent): void {
        global $DB;

        // Find any pending requests for the given session.
        $sql = "SELECT fss.*, fs.userid as recipient
                     FROM {facetoface_signups} fs
               INNER JOIN {facetoface_signups_status} fss
                       ON fss.signupid = fs.id AND fss.superceded = 0
                    WHERE fs.sessionid = :sess
                      AND (statuscode = :req OR statuscode = :roreq OR statuscode = :adreq)";

        $params = [
            'req' => requested::get_code(),
            'roreq' => requestedrole::get_code(),
            'adreq' => requestedadmin::get_code(),
            'sess' => $seminarevent->get_id(),
        ];

        $records = $DB->get_records_sql($sql, $params);
        if (empty($records)) {
            return;
        }

        // Loop through all the pending requests, cancel them, and send a notification to the user.
        foreach ($records as $record) {
            // Mark the request as declined so they can no longer be approved.
            $signup = new signup($record->signupid);

            if ($signup->can_switch(declined::class)) {
                $signup->switch_state(declined::class);
                // Send a registration expiration message to the user (and their manager).
                notice_sender::registration_closure($seminarevent, $record->recipient);

                // Centralised notifications
                $seminar = $seminarevent->get_seminar();
                $data = [
                    'course_id' => $seminar->get_course(),
                    'seminar_id' => $seminar->get_id(),
                    'user_id' => $record->recipient,
                    'module_id' => $seminar->get_coursemodule()->id,
                    'seminar_event_id' => $seminarevent->get_id(),
                ];

                seminar_notification_helper::create_seminar_notifiable_event_queue(
                    $seminar,
                    new booking_request_expired($data)
                );
            } else {
                $failures = $signup->get_failures(declined::class);
                debugging(implode("\n", $failures), DEBUG_DEVELOPER);
            }
        }
    }
}
