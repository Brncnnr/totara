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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package container_workspace
 */
namespace container_workspace\task;

use container_workspace\member\member;
use container_workspace\output\added_to_workspace_notification;
use container_workspace\workspace;
use core\message\message;
use core\task\adhoc_task;
use core_user;
use dml_missing_record_exception;

/**
 * This task is to send notification to users who had been added to a workspace in bulk.
 * Note that this task does not respect the settings in workspace notification.
 * @deprecated since Totara 16.0
 */
final class notify_added_to_workspace_bulk_task extends adhoc_task {

    /**
     * @deprecated since Totara 16.0
     * @param workspace $workspace
     * @param int[] $member_ids
     * @return self
     */
    public static function from_members(workspace $workspace, array $member_ids): self {
        debugging("notify_added_to_workspace_bulk_task::from_members has been deprecated.", DEBUG_DEVELOPER);
        $task = new static();
        $task->set_custom_data([
            'member_ids' => $member_ids,
            'workspace_id' => $workspace->id
        ]);

        return $task;
    }

    /**
     * @deprecated since Totara 16.0
     * @return void
     */
    public function execute(): void {
        global $OUTPUT;

        debugging('The notify_added_to_workspace_bulk_task task is deprecated, please check your code.', DEBUG_DEVELOPER);

        $data = $this->get_custom_data();

        if (null === $data
            || !property_exists($data, 'member_ids')
            || !property_exists($data, 'workspace_id')
        ) {
            throw new \coding_exception("There was no user ids or workspace's id was set");
        }

        try {
            $workspace = workspace::from_id($data->workspace_id);
        } catch (dml_missing_record_exception $exception) {
            // The workspace might have been deleted in the meanwhile
            return;
        }

        if ($workspace->is_to_be_deleted()) {
            // We don't want to send out notifications for a workspace which is marked as deleted
            return;
        }

        foreach ($data->member_ids as $id) {
            $member = member::from_id($id, $data->workspace_id);
            $user_to = core_user::get_user($member->get_user_id());

            if (!$user_to) {
                // Let's just ignore non-existent users
                continue;
            }

            // Setup this user so that environment matches receiving user.
            cron_setup_user($user_to);

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
            $message->userto = $user_to;
            $message->userfrom = \core_user::get_noreply_user();

            message_send($message);
        }

        cron_setup_user();
    }
}