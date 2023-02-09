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
 * Base trait for message popup tests.
 *
 * @package    message_popup
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_message\api;


trait message_popup_test_helper {
    /**
     * Send a fake unread popup notification.
     *
     * {@link message_send()} does not support transaction, this function will simulate a message
     * sent from a user to another. We should stop using it once {@link message_send()} will support
     * transactions. This is not clean at all, this is just used to add rows to the table.
     *
     * @param stdClass $userfrom user object of the one sending the message.
     * @param stdClass $userto user object of the one receiving the message.
     * @param string $message message to send.
     * @param int $timecreated time the message was created.
     * @return int the id of the message
     */
    protected function send_fake_unread_popup_notification(stdClass $userfrom, stdClass $userto,
                                                           string $message = 'Hello world!', int $timecreated = 0): int {
        global $DB;

        $notification_record = new stdClass();
        $notification_record->useridfrom = $userfrom->id;
        $notification_record->useridto = $userto->id;
        $notification_record->notification = 1;
        $notification_record->subject = 'No subject';
        $notification_record->fullmessage = $message;
        $notification_record->fullmessagehtml = $message;
        $notification_record->smallmessage = $message;
        $notification_record->timecreated = $timecreated ? $timecreated : time();
        $notification_record->contexturl = 'https://www.totaralearning.com/';

        $notification_id = $DB->insert_record('notifications', $notification_record);

        // Insert a record of notification popup.
        $popup_record = new stdClass();
        $popup_record->notificationid = $notification_id;
        $DB->insert_record('message_popup_notifications', $popup_record);

        return $notification_id;
    }

    /**
     * Send a fake read popup notification.
     *
     * {@link message_send()} does not support transaction, this function will simulate a message
     * sent from a user to another. We should stop using it once {@link message_send()} will support
     * transactions. This is not clean at all, this is just used to add rows to the table.
     *
     * @param stdClass $userfrom user object of the one sending the message.
     * @param stdClass $userto user object of the one receiving the message.
     * @param string $message message to send.
     * @param int $timecreated time the message was created.
     * @param int $timeread the the message was read
     * @return int the id of the message
     */
    protected function send_fake_read_popup_notification(stdClass $userfrom, stdClass $userto, string $message = 'Hello world!',
                                                         int $timecreated = 0, int $timeread = 0): int {
        global $DB;

        $notification_record = new stdClass();
        $notification_record->useridfrom = $userfrom->id;
        $notification_record->useridto = $userto->id;
        $notification_record->notification = 1;
        $notification_record->subject = 'No subject';
        $notification_record->fullmessage = $message;
        $notification_record->fullmessagehtml = $message;
        $notification_record->smallmessage = $message;
        $notification_record->timecreated = $timecreated ? $timecreated : time();
        $notification_record->timeread = $timeread ? $timeread : time();
        $notification_record->contexturl = 'https://www.totaralearning.com/';

        $notification_record->id = $DB->insert_record('notifications', $notification_record);

        $popup_record = new stdClass();
        $popup_record->notificationid = $notification_record->id;
        $DB->insert_record('message_popup_notifications', $popup_record);

        // Mark it as read.
        api::mark_notification_as_read($notification_record);

        return $notification_record->id;
    }
}
