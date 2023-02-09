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
namespace container_workspace\userdata;

use container_workspace\discussion\discussion_helper;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use container_workspace\entity\workspace_discussion;
use totara_userdata\userdata\export;
use container_workspace\discussion\discussion as model_discussion;
use container_workspace\workspace;

/**
 * User data implementation for discussion.
 */
final class discussion extends item {
    /**
     * Note: all the workspaces can be purged from user(s), despite of their status
     * whether it is active or deleted.
     *
     * @param int $user_status
     * @return bool
     */
    public static function is_purgeable(int $user_status): bool {
        return true;
    }

    /**
     * @return bool
     */
    public static function is_exportable(): bool {
        return true;
    }

    /***
     * @return bool
     */
    public static function is_countable(): bool {
        return true;
    }

    /**
     * @param int $user_id
     * @param \context $context
     * @return workspace_discussion[]
     */
    private static function get_discussion_entities(int $user_id, \context $context): array {
        $repository = workspace_discussion::repository();

        if (CONTEXT_COURSE == $context->contextlevel) {
            $workspace_id = $context->instanceid;
            return $repository->fetch_by_user_within_workspace($user_id, $workspace_id);
        } else if (CONTEXT_COURSECAT == $context->contextlevel) {
            $category_id = $context->instanceid;
            return $repository->fetch_by_user_within_workspace_category($user_id, $category_id);
        }

        return $repository->fetch_by_user($user_id);
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    protected static function count(target_user $user, \context $context) {
        $repository = workspace_discussion::repository();

        if (CONTEXT_SYSTEM == $context->contextlevel) {
            return $repository->count_for_user($user->id);
        } else if (CONTEXT_COURSE == $context->contextlevel) {
            $workspace_id = $context->instanceid;
            return $repository->count_for_user_within_workspace($user->id, $workspace_id);
        } else if (CONTEXT_COURSECAT == $context->contextlevel) {
            $category_id = $context->instanceid;
            return $repository->count_for_user_within_workspace_category($user->id, $category_id);
        }

        debugging("Unsupported context level: '{$context->contextlevel}'", DEBUG_DEVELOPER);
        return 0;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return export
     */
    protected static function export(target_user $user, \context $context): export {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $discussions = static::get_discussion_entities($user->id, $context);
        $workspace_type = workspace::get_type();

        $export = new export();
        foreach ($discussions as $discussion) {
            $context = \context_course::instance($discussion->course_id);

            $rewritten_content = file_rewrite_pluginfile_urls(
                $discussion->content,
                'pluginfile.php',
                $context->id,
                $workspace_type,
                model_discussion::AREA,
                $discussion->id
            );

            $export->data[] = [
                'id' => $discussion->id,
                'content' => content_to_text($rewritten_content, $discussion->content_format),
                'time_created' => $discussion->time_created,
                'time_modified' => $discussion->time_modified,
                'user_id' => $discussion->user_id
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
        $entities = static::get_discussion_entities($user->id, $context);

        foreach ($entities as $entity) {
            $discussion = model_discussion::from_entity($entity);
            discussion_helper::do_delete_discussion($discussion);
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * @return array
     */
    public static function get_fullname_string(): array {
        return ['user_data_item_discussion', 'container_workspace'];
    }

    /**
     * @return array
     */
    public static function get_compatible_context_levels(): array {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE];
    }
}