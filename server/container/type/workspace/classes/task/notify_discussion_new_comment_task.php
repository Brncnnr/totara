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

use container_workspace\notification\workspace_notification;
use container_workspace\output\comment_on_discussion;
use container_workspace\workspace;
use core\message\message;
use core\orm\query\exceptions\record_not_found_exception;
use core_user;
use totara_comment\comment;
use container_workspace\discussion\discussion;
use core\task\adhoc_task;

final class notify_discussion_new_comment_task extends adhoc_task {
    /**
     * @param int $comment_id
     * @return notify_discussion_new_comment_task
     */
    public static function from_comment(int $comment_id): notify_discussion_new_comment_task {
        $task = new static();
        $task->set_custom_data(['comment_id' => $comment_id]);

        return $task;
    }

    /**
     * @return void
     */
    public function execute(): void {
        global $OUTPUT;

        $data = $this->get_custom_data();
        if (null === $data || !property_exists($data, 'comment_id')) {
            throw new \coding_exception("No comment's id was set");
        }

        try {
            $comment = comment::from_id($data->comment_id);
        } catch (record_not_found_exception $e) {
            // If the comment is deleted, we stop execution.
            return;
        }

        $area = $comment->get_area();
        $component = $comment->get_component();

        $workspace_type = workspace::get_type();

        if ($workspace_type !== $component || discussion::AREA !== $area) {
            throw new \coding_exception("Expecting comment to be a part workspace's discussion");
        }

        $discussion_id = $comment->get_instanceid();
        $discussion = discussion::from_id($discussion_id);

        $workspace_id = $discussion->get_workspace_id();
        $discussion_owner_id = $discussion->get_user_id();

        if (workspace_notification::is_off($workspace_id, $discussion_owner_id)) {
            // User had turned off the workspace notification. Skip the process of sending message out.
            return;
        }

        $comment_author_id = $comment->get_userid();
        if ($comment_author_id == $discussion_owner_id) {
            // If the author of the comment is the same owner as the discussion, then we skip the notification part.
            // Same user origin.
            return;
        }

        $recipient = core_user::get_user($discussion_owner_id);
        if (!$recipient) {
            // Ignore if user doesn't exist.
            debugging('Skipped sending notification to non-existent user with id ' . $discussion_owner_id);
            return;
        }
        cron_setup_user($recipient);

        $template = comment_on_discussion::create($discussion, $comment);
        $rendered_content = $OUTPUT->render($template);
        $author = $comment->get_user();

        $message = new message();
        $message->subject = get_string('comment_on_discussion_title', 'container_workspace', fullname($author));
        $message->userto = $recipient;
        $message->userfrom = core_user::get_noreply_user();
        $message->fullmessage = html_to_text($rendered_content);
        $message->fullmessagehtml = $rendered_content;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->courseid = $discussion->get_workspace_id();
        $message->component = workspace::get_type();
        $message->name = 'comment_on_discussion';

        message_send($message);
    }
}