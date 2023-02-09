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
 * @package totara_comment
 */
namespace totara_comment\userdata;

use totara_comment\comment_helper;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use totara_comment\entity\comment as reply_entity;
use totara_comment\comment as model_reply;

/**
 * User data support for reply. This will be done separately from comment.
 */
final class reply extends item {
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
    protected static function count(target_user $user, \context $context): int {
        $repository = reply_entity::repository();
        return $repository->count_replies_for_user($user->id);
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return export
     */
    protected static function export(target_user $user, \context $context): export {
        $repository = reply_entity::repository();
        $export = new export();

        $replies = $repository->get_replies_of_user($user->id);
        foreach ($replies as $reply) {
            $export->data[] = [
                'id' => $reply->id,
                'content' => $reply->content,
                'content_format' => $reply->format,
                'time_created' => $reply->timecreated,
                'time_modified' => $reply->timemodified,
                'time_deleted' => $reply->timedeleted,
                'reason_deleted' => $reply->reasondeleted
            ];
        }

        return $export;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    protected static function purge(target_user $user, \context $context): int {
        $repository = reply_entity::repository();
        $reply_entities = $repository->get_replies_of_user($user->id);

        foreach ($reply_entities as $reply_entity) {
            $reply = model_reply::from_entity($reply_entity);
            comment_helper::purge_reply($reply);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * @return array
     */
    public static function get_fullname_string(): array {
        return ['user_data_item_reply', 'totara_comment'];
    }
}