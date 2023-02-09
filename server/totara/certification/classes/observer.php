<?php
/*
 * This file is part of Totara Learn
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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/certification/lib.php');

class totara_certification_observer {

    /**
     * Handler function called when a course_started event is triggered
     *
     * @param \core\event\course_in_progress $event
     */
    public static function course_in_progress(\core\event\course_in_progress $event) {
        global $DB;

        $userid = $event->relateduserid;

        $certificationids = find_certif_from_course($event->objectid);

        if (!count($certificationids)) {
            return;
        }

        // Could be multiple certification records so find the one this user is doing.
        list($insql, $params) = $DB->get_in_or_equal(array_keys($certificationids), SQL_PARAMS_NAMED);
        $sql = "SELECT cfc.id, cfc.status, cfc.renewalstatus, cfc.certifid, p.id AS programid
            FROM {certif_completion} cfc
            JOIN {prog} p ON p.certifid = cfc.certifid
            WHERE cfc.certifid $insql AND cfc.userid = :userid";
        $params['userid'] = $userid;
        $certcompletions = $DB->get_records_sql($sql, $params);

        // If 0 then this course & user is not in an assigned certification.
        if (count($certcompletions) == 0) {
            return;
        }

        // The user could be assigned to more than one certification containing this course, which would be bad
        // on its own, but doesn't actually affect out ability to set the status to in progress.
        $message = 'Certification set to in progress due to course progress';
        foreach ($certcompletions as $certcompletion) {
            // This function could be called when the user is in various states within the certification. For example,
            // if a certification has optional content, the user could be certified while the course is incomplete. Only
            // set to in progress if the user is in one of the states in the certification where they can make progress.
            if (($certcompletion->status < CERTIFSTATUS_INPROGRESS) || ($certcompletion->status == CERTIFSTATUS_EXPIRED)
                || ($certcompletion->status == CERTIFSTATUS_COMPLETED) && ($certcompletion->renewalstatus == CERTIFRENEWALSTATUS_DUE)) {
                certif_set_in_progress($certcompletion->programid, $userid, $message);
            }
        }
    }

    /**
     * Handler triggered when certification settings are changed, creates log which will show up on all users' transaction logs.
     *
     * @param \totara_certification\event\certification_updated $event
     */
    public static function certification_updated(\totara_certification\event\certification_updated $event) {
        global $DB;

        // Write to the certification completion log. Don't provide userid, so that it shows on all users' transaction lists.
        $cert = $DB->get_record('certif', array('id' => $event->get_instance()->certifid));
        $minimumactiveperiod = '';
        $recertification = '';
        switch ($cert->recertifydatetype) {
            case CERTIFRECERT_COMPLETION:
                $recertification = 'Use certification completion date';
                break;
            case CERTIFRECERT_EXPIRY:
                $recertification = 'Use certification expiry date';
                break;
            case CERTIFRECERT_FIXED:
                $recertification = 'Use fixed expiry date';
                $minimumactiveperiod = '<li>Minimum active period: ' . $cert->minimumactiveperiod . '</li>';
                break;
        }
        $description = 'Certification settings changed<br>' .
            '<ul><li>Recertification date: ' . $recertification . '</li>' .
            '<li>Active period: ' . $cert->activeperiod . '</li>' .
            $minimumactiveperiod .
            '<li>Window period: ' . $cert->windowperiod . '</li></ul>';

        prog_log_completion($event->objectid, null, $description);
    }
}
