<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\task;

use coding_exception;
use container_workspace\member\member_request;
use container_workspace\output\decline_request_join_notification;
use container_workspace\workspace;
use core\task\adhoc_task;
use core\task\manager;
use core\message\message;
use core_phpunit\language_pack_faker_trait;
use core_user;

class notify_decline_join_request_task extends adhoc_task {
    /**
     * @param int $member_request_id
     * @return int
     */
    public static function enqueue(int $member_request_id): int {
        $task = new static();
        $task->set_custom_data(['member_request_id' => $member_request_id]);

        return manager::queue_adhoc_task($task);
    }

    /**
     * @return void
     */
    public function execute(): void {
        global $OUTPUT;

        $data = $this->get_custom_data();

        if (null === $data || !property_exists($data, 'member_request_id')) {
            throw new coding_exception("Required member request id was missing");
        }

        $member_request = member_request::from_id($data->member_request_id);
        if ($member_request->is_cancelled()) {
            return;
        }

        $workspace = $member_request->get_workspace();
        if (!$workspace->is_private()) {
            return;
        }

        $user_id = $member_request->get_user_id();
        $recipient = core_user::get_user($user_id);
        if (!$recipient) {
            // Ignore if user doesn't exist.
            debugging('Skipped sending notification to non-existent user with id ' . $user_id);
            return;
        }

        cron_setup_user($recipient);

        $template = decline_request_join_notification::create($member_request);
        $rendered_content = $OUTPUT->render($template);

        $message = new message();
        $message->subject = get_string('decline_request_subject', 'container_workspace', $workspace->get_name());
        $message->userto = $recipient;
        $message->userfrom = core_user::get_noreply_user();
        $message->component = workspace::get_type();
        $message->name = 'decline_request_join';
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->courseid = $workspace->get_id();
        $message->fullmessage = html_to_text($rendered_content);
        $message->fullmessagehtml = $rendered_content;

        message_send($message);
    }
}