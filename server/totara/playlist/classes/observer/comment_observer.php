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
 * @package totara_playlist
 */
namespace totara_playlist\observer;

use core\task\manager;
use totara_comment\comment;
use totara_comment\event\comment_created;
use totara_comment\event\comment_updated;
use totara_comment\event\reply_created;
use totara_core\content\content_handler;
use totara_engage\task\comment_notify_task;
use totara_playlist\playlist;

/**
 * Observer for comment component
 */
final class comment_observer {
    /**
     * comment_resolver constructor.
     */
    private function __construct() {
        // Preventing this class from construction.
    }

    /**
     * @param comment_created $event
     * @return void
     */
    public static function on_comment_created(comment_created $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $comment = comment::from_record($record);
        static::handle_comment($comment, $event->userid);
    }

    /**
     * @param reply_created $event
     * @return void
     */
    public static function on_reply_created(reply_created $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $reply = comment::from_record($record);
        static::handle_comment($reply, $event->userid);
    }

    /**
     * @param comment_updated $event
     * @return void
     */
    public static function on_comment_updated(comment_updated $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $comment = comment::from_record($record);
        static::handle_comment($comment, $event->userid);
    }

    /**
     * Pass comment through content handlers.
     *
     * @param comment   $comment
     * @param int|null  $user_id
     *
     * @return void
     */
    private static function handle_comment(comment $comment, ?int $user_id = null): void {
        $component = $comment->get_component();
        if ('totara_playlist' !== $component) {
            return;
        }

        $area = $comment->get_area();
        if ('comment' == $area) {
            $playlist_id = $comment->get_instanceid();
            $playlist = playlist::from_id($playlist_id);

            $handler = content_handler::create();
            $handler->handle_with_params(
                $playlist->get_name(),
                $comment->get_content(),
                $comment->get_format(),
                $comment->get_id(),
                'totara_playlist',
                'comment',
                $playlist->get_contextid(),
                $playlist->get_url(),
                $user_id
            );

            self::create_owner_notification_task($comment, $playlist);
        }
    }

    /**
     * @param comment $comment
     * @param playlist $playlist
     * @return void
     */
    protected static function create_owner_notification_task(comment $comment, playlist $playlist): void {
        if ($comment->get_userid() !== $playlist->get_userid()) {
            $task = new comment_notify_task();
            $task->set_custom_data([
                'url' => $playlist->get_url(),
                'owner' => $playlist->get_userid(),
                'component' => $playlist::get_resource_type(),
                'resourcetype' => get_string('message_playlist', 'totara_playlist'),
                'resource_info' => [
                    'stringkey' => 'message_playlist',
                    'component' => 'totara_playlist'
                ],
                'commenter' =>   $comment->get_userid(),
                'name' => $playlist->get_name(),
                'is_comment' => !$comment->is_reply()
            ]);
            manager::queue_adhoc_task($task);
        }
    }
}