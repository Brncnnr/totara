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
namespace container_workspace\observer;

use container_workspace\discussion\discussion;
use container_workspace\task\notify_discussion_new_comment_task;
use container_workspace\local\workspace_helper;
use totara_comment\event\comment_created;
use container_workspace\workspace;
use totara_comment\comment;
use totara_comment\event\comment_soft_deleted;
use totara_comment\event\comment_updated;
use totara_comment\event\reply_created;
use totara_comment\event\reply_soft_deleted;
use totara_core\content\content_handler;
use core\task\manager as task_manager;

/**
 * Observer class for comment's events.
 */
final class comment_observer {
    /**
     * comment_observer constructor.
     */
    private function __construct() {
        // Preventing this class from construction
    }

    /**
     * @param comment   $comment
     * @param int|null  $actor_id
     * @return void
     */
    private static function touch_discussion(comment $comment, ?int $actor_id = null): void {
        $component = $comment->get_component();

        if (workspace::get_type() !== $component) {
            return;
        }

        $area = $comment->get_area();
        if (discussion::AREA === $area) {
            $discussion_id = $comment->get_instanceid();
            $discussion = discussion::from_id($discussion_id);

            $discussion->touch();

            // Update workspace timestamp as well.
            $workspace = $discussion->get_workspace();
            workspace_helper::update_workspace_timestamp($workspace, $actor_id);
        }
    }

    /**
     * @param comment_created $event
     * @return void
     */
    public static function on_comment_created(comment_created $event): void {
        $user_id = $event->get_user_id();

        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $comment = comment::from_record($record);

        static::touch_discussion($comment, $user_id);
        static::handle_comment($comment, $user_id);

        $component = $comment->get_component();
        $area = $comment->get_area();

        if (workspace::get_type() !== $component || discussion::AREA !== $area) {
            return;
        }

        // Queue adhoc task to notify the owner of discussion.
        $comment_id = $comment->get_id();
        $task = notify_discussion_new_comment_task::from_comment($comment_id);
        task_manager::queue_adhoc_task($task);
    }

    /**
     * @param comment_updated $event
     * @return void
     */
    public static function on_comment_updated(comment_updated $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $comment = comment::from_record($record);

        static::touch_discussion($comment, $event->userid);
        static::handle_comment($comment, $event->userid);
    }

    /**
     * Process comment through content handler.
     *
     * @param comment   $comment
     * @param int|null  $user_id    This is the user's id of whoever is responsible for creating the content.
     *
     * @return void
     */
    private static function handle_comment(comment $comment, ?int $user_id): void {
        $component = $comment->get_component();
        if (workspace::get_type() !== $component) {
            return;
        }

        $area = $comment->get_area();
        if (discussion::AREA === $area) {
            $discussion_id = $comment->get_instanceid();
            $discussion = discussion::from_id($discussion_id);

            $workspace = workspace::from_id($discussion->get_workspace_id());

            $handler = content_handler::create();
            $handler->handle_with_params(
                $workspace->get_name(),
                $comment->get_content(),
                $comment->get_format(),
                $comment->get_id(),
                $comment->get_component(),
                $comment->get_area(),
                $discussion->get_context()->id,
                $workspace->get_view_url(),
                $user_id
            );
        }
    }

    /**
     * @param reply_created $event
     * @return void
     */
    public static function on_reply_created(reply_created $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $reply = comment::from_record($record);

        static::touch_discussion($reply, $event->userid);
        static::handle_comment($reply, $event->userid);
    }

    /**
     * @param reply_soft_deleted $event
     * @return void
     */
    public static function on_reply_soft_deleted(reply_soft_deleted $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $reply = comment::from_record($record);

        static::touch_discussion($reply, $event->userid);
    }

    /**
     * @param comment_soft_deleted $event
     * @return void
     */
    public static function on_comment_soft_deleted(comment_soft_deleted $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $comment = comment::from_record($record);

        static::touch_discussion($comment, $event->userid);
    }
}