<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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

use container_workspace\member\member;
use container_workspace\loader\member\loader;
use container_workspace\output\added_to_workspace_notification;
use container_workspace\workspace;
use core\message\message;
use core\task\adhoc_task;
use core_user;

/**
 * This task is to notify the user who had been added to a specific workspace.
 * Note that this task does not respect the settings in workspace notification.
 * @deprecated since Totara 16.0
 */
final class notify_added_to_workspace_task extends adhoc_task {

    /**
     * @deprecated since Totara 16.0
     */
    public function __construct() {
        debugging('The notify_added_to_workspace_task task is deprecated, please check your code.', DEBUG_DEVELOPER);
    }

    /**
     * @deprecated since Totara 16.0
     * @param member $member
     * @return notify_added_to_workspace_task
     */
    public static function from_member(member $member): notify_added_to_workspace_task {
        $task = new static();
        $task->set_custom_data([
            'user_id' => $member->get_user_id(),
            'workspace_id' => $member->get_workspace_id()
        ]);

        return $task;
    }

    /**
     * @return void
     */
    public function execute(): void {
        global $OUTPUT;
        $data = $this->get_custom_data();

        if (null === $data || !property_exists($data, 'user_id') || !property_exists($data, 'workspace_id')) {
            throw new \coding_exception("There was no user's id or workspace's id was set");
        }

        $member = loader::get_for_user($data->user_id, $data->workspace_id);
        if (!$member) {
            // Skip if member doesn't exist - unlikely, but kind of possible.
            debugging('Skipped sending notification to non-existent member with id ' . $data->user_id, DEBUG_DEVELOPER);
            return;
        }

        $recipient = core_user::get_user($member->get_user_id());
        if (!$recipient) {
            // Skip if user doesn't exist.
            debugging('Skipped sending notification to non-existent user with id ' . $member->get_user_id());
            return;
        }
        cron_setup_user($recipient);

        $template = added_to_workspace_notification::create($member);
        $rendered_content = $OUTPUT->render($template);

        $message = new message();
        $message->subject = get_string('member_added_title', 'container_workspace');
        $message->courseid = $member->get_workspace_id();
        $message->component = workspace::get_type();
        $message->name = 'added_to_workspace';
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessage = html_to_text($rendered_content);
        $message->fullmessagehtml = $rendered_content;
        $message->userto = $recipient;
        $message->userfrom = \core_user::get_noreply_user();

        message_send($message);
    }
}