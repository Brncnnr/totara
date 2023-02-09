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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\task;

use mod_facetoface\notification\notification_helper;
use core\task\scheduled_task;

/**
 * Send facetoface notifications
 */
class send_notifications_task extends scheduled_task {
    // Test mode.
    public $testing = false;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendnotificationstask', 'mod_facetoface');
    }

    /**
     * Finds all facetoface notifications that have yet to be mailed out, and mails them.
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/mod/facetoface/lib.php');

        // Adhoc messages (i.e. messages in facetoface_notifications with type MDL_F2F_NOTIFICATION_MANUAL) must be sent
        // regardless of the notification system currently in use.

        // Find "instant" manual notifications that haven't yet been sent.
        if (!$this->testing) {
            mtrace('Checking for instant Face-to-face notifications');
        }

        $manual = $DB->get_records_select(
            'facetoface_notification',
            'type = ? AND issent <> ? AND status = 1',
            array(MDL_F2F_NOTIFICATION_MANUAL, MDL_F2F_NOTIFICATION_STATE_FULLY_SENT),
            '',
            'id'
            );
        if ($manual) {
            foreach ($manual as $notif) {
                $notification = new \facetoface_notification((array)$notif);
                $notification->send_to_users();
                unset($notification);
            }
        }
        unset($manual);

        // Scheduled notifications should only be sent if legacy is allowed and enabled
        $notificationdisable = get_config(null, 'facetoface_notificationdisable');
        if (facetoface_site_allows_legacy_notifications() && empty($notificationdisable)) {
            // Find scheduled notifications that haven't yet been sent.
            if (!$this->testing) {
                mtrace('Checking for scheduled Face-to-face notifications');
            }
            $sql =
                "SELECT fn.id
                   FROM {facetoface_notification} fn
                   JOIN {facetoface} f ON fn.facetofaceid = f.id 
                  WHERE fn.scheduletime IS NOT NULL
                    AND fn.scheduletime IS NOT NULL
                    AND (fn.type = :scheduled OR fn.type = :auto)
                    AND status = :status
                    AND f.legacy_notifications = :legacy";
            $params = [
                'scheduled' => MDL_F2F_NOTIFICATION_SCHEDULED,
                'auto' => MDL_F2F_NOTIFICATION_AUTO,
                'status' => 1,
                'legacy' => 1,
            ];
            $sched = $DB->get_records_sql($sql, $params);
            if ($sched) {
                foreach ($sched as $notif) {
                    $notification = new \facetoface_notification((array) $notif);
                    $notification->send_scheduled();
                    unset($notification);
                }
            }
            unset($sched);
        }

        $helper = new notification_helper();

        // Find finish Sign-Up dates that expired to send notifications to.
        if (!$this->testing) {
            mtrace('Checking for expired Face-to-face sign-up period dates');
        }

        $helper->notify_registration_ended();

        // Find any reservations that are too close to the start of the session and delete them.
        \mod_facetoface\reservations::remove_after_deadline($this->testing);

        // Notify of sessions that are under capacity.
        if (!$this->testing) {
            mtrace("Checking for sessions below minimum bookings");
        }

        $helper->notify_under_capacity();
        return true;
    }
}
