<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A scheduled task.
 *
 * @package    core
 * @copyright  2013 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\task;

use coding_exception;
use core\entity\notification;
use core_message\hook\purge_check_notification_hook;

/**
 * Simple task to delete old messaging records.
 */
class messaging_cleanup_task extends scheduled_task {
    /**
     * This property is to help us having unit tests easier.
     * @var int|null
     */
    private $time_now = null;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskmessagingcleanup', 'admin');
    }

    /**
     * @param int $time_now
     */
    public function phpunit_set_time_now(int $time_now) {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            throw new coding_exception("The set time now should only be done in the phpunit environment");
        }

        $this->time_now = $time_now;
    }

    /**
     * @return int
     */
    protected function get_time_now(): int {
        if (isset($this->time_now)) {
            return $this->time_now;
        }

        return time();
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;

        if (empty($CFG->messagingdeletereadnotificationsdelay)) {
            return;
        }

        // Cleanup messaging.
        $time_now = $this->get_time_now();
        $notification_delete_time = $time_now - $CFG->messagingdeletereadnotificationsdelay;

        $notification_records = $DB->get_records_select(
            notification::TABLE,
            'timeread < :notification_delete_time',
            ['notification_delete_time' => $notification_delete_time]
        );

        foreach ($notification_records as $record) {
            $notification = new notification($record);

            $hook = new purge_check_notification_hook($notification);
            $hook->execute();

            if ($hook->is_skip_purge()) {
                continue;
            }

            $notification->delete();
        }
    }
}