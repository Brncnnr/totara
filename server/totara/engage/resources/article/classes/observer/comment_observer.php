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
 * @package engage_article
 */
namespace engage_article\observer;

use core\task\manager;
use totara_comment\comment;
use totara_comment\event\comment_created;
use engage_article\totara_engage\resource\article;
use totara_comment\event\comment_updated;
use totara_comment\event\reply_created;
use totara_core\content\content_handler;
use totara_engage\task\comment_notify_task;

/**
 * Observer for comment component
 */
final class comment_observer {
    /**
     * comment_observer constructor.
     */
    private function __construct() {
        // Preventing this class from construction
    }

    /**
     * @param comment_created $event
     * @return void
     */
    public static function on_comment_created(comment_created $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $comment = comment::from_record($record);
        static::handle_comment($comment, $event->get_user_id());
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
     * @param reply_created $event
     * @return void
     */
    public static function on_reply_created(reply_created $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $reply = comment::from_record($record);

        static::handle_comment($reply, $event->userid);
    }

    /**
     * Pass comment through content handlers
     * @param comment   $comment
     * @param int|null  $user_id
     *
     * @return void
     */
    private static function handle_comment(comment $comment, ?int $user_id = null):void {
        $component = $comment->get_component();
        if (article::get_resource_type() !== $component) {
            return;
        }

        $area = $comment->get_area();
        if ('comment' === $area) {
            $resource_id = $comment->get_instanceid();
            $resource = article::from_resource_id($resource_id);

            $handler = content_handler::create();
            $handler->handle_with_params(
                $resource->get_name(),
                $comment->get_content(),
                $comment->get_format(),
                $comment->get_id(),
                $comment->get_component(),
                $comment->get_area(),
                $resource->get_context()->id,
                $resource->get_url(),
                $user_id
            );

            self::create_owner_notification_task($comment, $resource);
        }
    }

    /**
     * @param comment $comment
     * @param article $article
     * @return void
     */
    protected static function create_owner_notification_task(comment $comment, article $article): void {
        // If commenter is not owner, task will be initialized.
        if ($comment->get_userid() !== $article->get_userid()) {
            $task = new comment_notify_task();
            $task->set_custom_data([
                'url' => $article->get_url(),
                'owner' => $article->get_userid(),
                // As article is part of engage resource, adhoc task will be triggered in the engage and we do not want
                // to set message setting for engage_article.
                'component' => 'totara_engage',
                'resourcetype' => get_string('message_resource', 'totara_engage'),
                'resource_info' => [
                    'stringkey' => 'message_resource',
                    'component' => 'totara_engage'
                ],
                'commenter' =>   $comment->get_userid(),
                'name' => $article->get_name(),
                'is_comment' => !$comment->is_reply()
            ]);

            manager::queue_adhoc_task($task);
        }
    }
}
