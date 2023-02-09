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

use container_workspace\discussion\discussion;
use container_workspace\loader\member\loader;
use container_workspace\member\status;
use container_workspace\output\create_new_discussion;
use container_workspace\query\member\query;
use container_workspace\workspace;
use core\pagination\offset_cursor;
use core\task\adhoc_task;
use core_container\factory;
use container_workspace\member\member;
use core_user;
use core\message\message;
use stdClass;
use coding_exception;
use core\orm\query\exceptions\record_not_found_exception;

/**
 * This task is to notify the members who are in the workspace.
 */
final class notify_create_new_discussion_task extends adhoc_task {

    /**
     * @param int $discussion_id
     * @return notify_create_new_discussion_task
     */
    public static function from_discussion(int $discussion_id): notify_create_new_discussion_task {
        $task = new static();
        $task->set_custom_data(['discussion_id' => $discussion_id]);
        return $task;
    }
    /**
     * @return void
     */
    public function execute(): void {
        global $OUTPUT;
        $data = $this->get_custom_data();

        if (null === $data || !property_exists($data, 'discussion_id')) {
            throw new coding_exception("No discussion_id was set");
        }

        try {
            $discussion = discussion::from_id($data->discussion_id);
        } catch (record_not_found_exception $e) {
            // If the discussion is deleted, we stop execution.
            return;
        }

        $workspace_id = $discussion->get_workspace_id();
        /** @var workspace $workspace */
        $workspace = factory::from_id($workspace_id);
        if (!$workspace->is_typeof(workspace::get_type())) {
            throw new coding_exception("Cannot find the workspace by id '{$data->workspace_id}'");
        }

        $query = new query($workspace_id);
        $query->set_member_status(status::get_active());
        $cursor = new offset_cursor();
        $cursor->set_limit(loader::count_members($workspace_id));
        $query->set_cursor($cursor);
        $paginator = loader::get_members($query);

        $subject = new stdClass();
        $subject->name = fullname($discussion->get_user());
        $subject->workspace = $workspace->fullname;

        /** @var member $member */
        foreach ($paginator->get_items()->all() as $member) {
            $recipient = core_user::get_user($member->get_user_id());
            if (!$recipient) {
                // Ignore if user doesn't exist.
                debugging('Skipped sending notification to non-existent user with id ' . $member->get_user_id());
                return;
            }

            // Skipped to send message to the poster.
            if ($member->get_user_id() == $discussion->get_user_id()) {
                continue;
            }

            cron_setup_user($recipient);

            $template = create_new_discussion::create($discussion, $workspace->fullname);
            $rendered_content = $OUTPUT->render($template);

            $message = new message();
            $message->userfrom = core_user::get_noreply_user();
            $message->userto = $recipient;
            $message->subject = get_string('create_new_discussion_title', 'container_workspace', $subject);
            $message->fullmessage = html_to_text($rendered_content);
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = $rendered_content;
            $message->component = workspace::get_type();
            $message->name = 'create_new_discussion';
            $message->courseid = $workspace->get_id();

            message_send($message);
        }
    }
}