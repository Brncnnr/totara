<?php
/*
 * This file is part of Totara LMS
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\notification;

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\signup\state\{attendance_state, booked, waitlisted};
use mod_facetoface\task\send_notifications_task;


/**
 * A notification helper class that helps to sending notifications within f2f modules
 *
 * Class notification_helper
 *
 * @package mod_facetoface\notification
 */
class notification_helper {
    /**
     * @var bool
     */
    private $iscli;

    /**
     * @var bool
     */
    private $istest;

    /**
     * A config value for $CFG->facetoface_session_rolesnotify
     * @var string|mixed
     */
    private $roles;

    /**
     * notification_helper constructor.
     */
    public function __construct() {
        global $CFG;

        $this->roles = $CFG->facetoface_session_rolesnotify;
    }

    /**
     * Send out email notifications for all sessions that are under capacity at the cut-off of any event.
     *
     * @return void
     */
    public function notify_under_capacity(): void {
        global $DB;

        // If there are no recipients, don't bother to send email !
        if (empty($this->roles)) {
            return;
        }

        $now = time();
        $lastcron = $this->get_notification_task_last_cron();

        $params = [
            'lastcron' => $lastcron,
            'now1' => $now,
            'now2' => $now,
        ];

        $sql = "
            SELECT s.*, dt.minstart
            FROM {facetoface_sessions} s
            INNER JOIN (
              SELECT s.id as sessid, MIN(d.timestart) AS minstart
              FROM {facetoface_sessions} s
              INNER JOIN {facetoface_sessions_dates} d ON s.id = d.sessionid
              WHERE timestart >= :now1
              GROUP BY s.id
            ) dt ON dt.sessid = s.id
            WHERE s.mincapacity > 0
              AND (dt.minstart - s.cutoff) < :now2
              AND (dt.minstart - s.cutoff) >= :lastcron
              AND s.cancelledstatus = 0";

        $records = $DB->get_recordset_sql($sql, $params);

        foreach ($records as $record) {
            $notification = new \facetoface_notification((array)$record, false);
            $notification->send_notification_session_under_capacity($record);
        }
        $records->close();
    }

    /**
     * Getting the last crontime run here, so that our query can be based on it.
     * @return int
     */
    private function get_notification_task_last_cron(): int {
        global $DB;

        $conditions = [
            'component' => 'mod_facetoface',
            'classname' => '\\' . send_notifications_task::class
        ];

        $lastcron = $DB->get_field('task_scheduled', 'lastruntime', $conditions);
        return (int) $lastcron;
    }

    /**
     * Send out email notifications for all sessions where registration period has ended.
     *
     * @return void
     */
    public function notify_registration_ended(): void {
        global $DB;

        if (empty($this->roles)) {
            return;
        }

        $now = time();
        $lastcron = $this->get_notification_task_last_cron();

        $params = [
            'now' => $now,
            'lastcron' => $lastcron
        ];

        $sql = "SELECT s.*, dates.minstart
            FROM {facetoface_sessions} s
                INNER JOIN (
                    SELECT s.id AS sessid, MIN(d.timestart) AS minstart
                    FROM {facetoface_sessions} s
                    INNER JOIN {facetoface_sessions_dates} d ON s.id = d.sessionid
                    GROUP BY s.id
                ) dates ON dates.sessid = s.id
            WHERE s.registrationtimefinish < :now
            AND s.registrationtimefinish >= :lastcron
            AND s.registrationtimefinish != 0";

        $records = $DB->get_records_sql($sql, $params);

        foreach ($records as $record) {
            $notification = new \facetoface_notification((array) $record, false);
            $notification->send_notification_registration_expired($record);
        }
    }
}