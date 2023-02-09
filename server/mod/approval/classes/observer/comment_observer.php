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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\observer;

use core\entity\user;
use mod_approval\model\application\activity\activity;
use mod_approval\model\application\activity\comment_created as comment_created_activity;
use mod_approval\model\application\activity\comment_deleted as comment_deleted_activity;
use mod_approval\model\application\activity\comment_replied as comment_replied_activity;
use mod_approval\model\application\activity\comment_updated as comment_updated_activity;
use mod_approval\model\application\application;
use mod_approval\model\application\application_activity;
use mod_approval\totara_notification\resolver\stage_comment_created;
use mod_approval\totara_notification\resolver\stage_comment_updated;
use totara_comment\comment;
use totara_comment\event\comment_created;
use totara_comment\event\comment_soft_deleted;
use totara_comment\event\comment_updated;
use totara_comment\event\reply_created;
use totara_comment\event\reply_soft_deleted;
use totara_notification\external_helper;
use totara_notification\resolver\resolver_helper;

/**
 * Event observer for totara_comment
 */
final class comment_observer {
    /**
     * Private constructor.
     */
    private function __construct() {
        // This is a static class
    }

    /**
     * @param comment_created $event
     */
    public static function comment_created(comment_created $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $comment = comment::from_record($record);
        self::handle_comment($comment, $event->get_user_id(), comment_created_activity::class);
    }

    /**
     * @param comment_updated $event
     */
    public static function comment_updated(comment_updated $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $comment = comment::from_record($record);
        self::handle_comment($comment, $event->userid, comment_updated_activity::class);
    }

    /**
     * @param comment_soft_deleted $event
     */
    public static function comment_soft_deleted(comment_soft_deleted $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $comment = comment::from_record($record);
        self::handle_comment($comment, $event->userid, comment_deleted_activity::class);
    }

    /**
     * @param reply_created $event
     */
    public static function reply_created(reply_created $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $reply = comment::from_record($record);
        self::handle_comment($reply, $event->userid, comment_replied_activity::class);
    }

    /**
     * @param reply_soft_deleted $event
     */
    public static function reply_soft_deleted(reply_soft_deleted $event): void {
        $record = $event->get_record_snapshot(comment::get_entity_table(), $event->objectid);
        $comment = comment::from_record($record);
        self::handle_comment($comment, $event->userid, comment_deleted_activity::class);
    }

    /**
     * @param comment $comment
     * @param integer $user_id
     * @param string|activity $activity_class
     */
    private static function handle_comment(comment $comment, int $user_id, string $activity_class): void {
        if (!($comment->get_component() === 'mod_approval' && $comment->get_area() === 'comment')) {
            return;
        }

        $application = application::load_by_id($comment->get_instanceid());

        // Record activity.
        application_activity::create(
            $application,
            $user_id,
            $activity_class
        );

        // Fire a notification.
        switch ($activity_class) {
            case comment_created_activity::class:
            case comment_replied_activity::class:
                $resolver_class = stage_comment_created::class;
                break;
            case comment_updated_activity::class:
                $resolver_class = stage_comment_updated::class;
                break;
            default:
                return;
        }

        $event_data = [
            'application_id' => $application->id,
            'workflow_stage_id' => $application->current_state->get_stage_id(),
            'comment_id' => $comment->get_id(),
            'event_time' => $comment->get_timecreated(),
        ];
        $resolver = resolver_helper::instantiate_resolver_from_class(
            $resolver_class,
            $event_data
        );
        external_helper::create_notifiable_event_queue($resolver);
    }
}
