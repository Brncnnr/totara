<?php
/**
 * This file is part of Totara Learn
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\task;

use container_workspace\member\member_request;
use container_workspace\output\accept_notification;
use core\message\message;
use core\task\adhoc_task;
use container_workspace\workspace;
use core_user;

/**
 * An adhoc task to send an email out to the user when their request is accepted.
 */
final class send_accept_request_task extends adhoc_task {
    /**
     * @param int $id
     * @return void
     */
    public function set_member_request_id(int $id): void {
        $this->set_custom_data(['member_request_id' => $id]);
    }

    /**
     * @return void
     */
    public function execute(): void {
        global $OUTPUT;

        $data = $this->get_custom_data();
        $actor_id = $this->get_userid();

        if (null === $data || !property_exists($data, 'member_request_id')) {
            throw new \coding_exception("No member request id was set");
        } else if (null === $actor_id) {
            throw new \coding_exception("No actor's id was set");
        }

        $member_request = member_request::from_id($data->member_request_id);
        if (!$member_request->is_accepted()) {
            throw new \coding_exception("The member request was not yet accepted");
        }

        $target_user_id = $member_request->get_user_id();
        $workspace = $member_request->get_workspace();

        $recipient = core_user::get_user($target_user_id);
        if (!$recipient) {
            // Ignore if user doesn't exist.
            debugging('Skipped sending notification to non-existent user with id ' . $target_user_id);
            return;
        }
        cron_setup_user($recipient);

        $template = accept_notification::create($member_request);
        $content = $OUTPUT->render($template);

        $message_data = new message();
        $message_data->component = workspace::get_type();
        $message_data->name = 'accept_member_request';
        $message_data->subject = get_string('approved_request_title', 'container_workspace');
        $message_data->courseid = $workspace->get_id();
        $message_data->fullmessage = html_to_text($content);
        $message_data->fullmessageformat = FORMAT_PLAIN;
        $message_data->fullmessagehtml = $content;
        $message_data->userfrom = $actor_id;
        $message_data->userto = $recipient;

        message_send($message_data);
    }
}