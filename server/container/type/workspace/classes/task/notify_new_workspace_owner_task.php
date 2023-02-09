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

use container_workspace\output\transfer_ownership_notification;
use container_workspace\workspace;
use core\task\adhoc_task;
use core_container\factory;
use core\message\message;
use core_user;

/**
 * An adhoc task to notify the new user owner, that they had been added as
 * a new owner of the workspace.
 */
final class notify_new_workspace_owner_task extends adhoc_task {
    /**
     * @param int $workspace_id
     * @param int $author_id
     * @return notify_new_workspace_owner_task
     */
    public static function from_workspace(int $workspace_id, int $author_id): notify_new_workspace_owner_task {
        $task = new static();
        $task->set_custom_data([
            'workspace_id' => $workspace_id,
            'author_id' => $author_id
        ]);

        return $task;
    }

    /**
     * @return void
     */
    public function execute(): void {
        global $OUTPUT;
        $data = $this->get_custom_data();

        if (null === $data || !property_exists($data, 'workspace_id') || !property_exists($data, 'author_id')) {
            throw new \coding_exception("No workspace's id or author's id was set");
        }

        /** @var workspace $workspace */
        $workspace = factory::from_id($data->workspace_id);
        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new \coding_exception("Cannot find the workspace by id '{$data->workspace_id}'");
        }

        $new_owner_id = $workspace->get_user_id();
        if (null === $new_owner_id) {
            throw new \coding_exception("Workspace does not have owner record");
        }

        $recipient = core_user::get_user($new_owner_id);
        if (!$recipient) {
            // Ignore if user doesn't exist.
            debugging('Skipped sending notification to non-existent user with id ' . $new_owner_id);
            return;
        }
        cron_setup_user($recipient);

        $template = transfer_ownership_notification::create($workspace, $data->author_id);
        $rendered_content = $OUTPUT->render($template);

        $message = new message();
        $message->userfrom = core_user::get_noreply_user();
        $message->userto = $recipient;
        $message->subject = get_string('transfer_ownership_title', 'container_workspace');
        $message->fullmessage = html_to_text($rendered_content);
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = $rendered_content;
        $message->component = workspace::get_type();
        $message->name = 'transfer_ownership';
        $message->courseid = $workspace->get_id();

        message_send($message);
    }
}
