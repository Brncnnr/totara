<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_comment
 */
namespace totara_comment\userdata;

use totara_comment\comment_helper;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use totara_comment\comment as model_comment;
use totara_comment\entity\comment as comment_entity;

/**
 * User data implementation for comment. This is for record without parent's id
 */
final class comment extends item {
    /**
     * @param int $user_status
     * @return bool
     */
    public static function is_purgeable(int $user_status): bool {
        return ($user_status === target_user::STATUS_DELETED);
    }

    /**
     * @return bool
     */
    public static function is_exportable(): bool {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_countable(): bool {
        return true;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    public static function purge(target_user $user, \context $context): int {
        $repo = comment_entity::repository();

        $comment_entities = $repo->get_comments_of_user($user->id);
        foreach ($comment_entities as $comment_entity) {
            $comment = model_comment::from_entity($comment_entity);
            comment_helper::purge_comment($comment);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * @param target_user $user
     * @param \context    $context
     *
     * @return export
     */
    public static function export(target_user $user, \context $context): export {
        $export = new export();
        $export->data = [];

        $repo = comment_entity::repository();
        $comments = $repo->get_comments_of_user($user->id);

        foreach ($comments as $comment) {
            $export->data[] = [
                'id' => $comment->id,
                'content' => $comment->content,
                'content_format' => $comment->format,
                'time_created' => $comment->timecreated,
                'time_modified' => $comment->timemodified,
                'time_deleted' => $comment->timedeleted,
                'reason_deleted' => $comment->reasondeleted
            ];
        }

        return $export;
    }

    /**
     * @param target_user $user
     * @param \context    $context
     *
     * @return int
     */
    public static function count(target_user $user, \context $context): int {
        $repo = comment_entity::repository();
        return $repo->count_comments_for_user($user->id);
    }

    /**
     * @return array
     */
    public static function get_fullname_string(): array {
        return ['user_data_item_comment', 'totara_comment'];
    }
}