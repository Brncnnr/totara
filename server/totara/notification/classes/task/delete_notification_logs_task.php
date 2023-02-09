<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_notification
 */

namespace totara_notification\task;

use core\task\scheduled_task;

/**
 * Task to delete notification logs.
 */
class delete_notification_logs_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('delete_notification_logs', 'totara_notification');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        /** @var \core_config $CFG */
        /** @var \moodle_database $DB */
        global $CFG, $DB;

        if (empty($CFG->totara_notification_log_days_to_keep)) {
            return;
        }

        // Delete notification log records
        $time_created = time() - ($CFG->totara_notification_log_days_to_keep * DAYSECS);
        $params = ['time_created' => $time_created];

        $transaction = $DB->start_delegated_transaction();

        // Delete from notification_delivery_log.
        $sql = "DELETE FROM {notification_delivery_log}
                      WHERE notification_log_id IN (SELECT nl.id
                                     FROM {notification_event_log} nel
                                     JOIN {notification_log} nl ON nl.notification_event_log_id = nel.id
                                    WHERE nel.time_created < :time_created)";
        $DB->execute($sql, $params);

        // Delete from notification_log.
        $sql = "DELETE FROM {notification_log}
                      WHERE notification_event_log_id IN (SELECT nel.id
                                     FROM {notification_event_log} nel
                                    WHERE nel.time_created < :time_created)";
        $DB->execute($sql, $params);

        // Delete from notification_log.
        $sql = "DELETE FROM {notification_event_log}
                      WHERE time_created < :time_created";
        $DB->execute($sql, $params);

        $transaction->allow_commit();

        mtrace("    Deleted old notification log records from 'notification_event_log', 'notification_log' and 'notification_delivery_log'");
    }
}