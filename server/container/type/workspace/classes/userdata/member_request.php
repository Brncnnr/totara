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

use container_workspace\entity\workspace_member_request;
use core\orm\query\builder;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * Member request GDPR implementation.
 */
final class member_request extends item {
    /**
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
     * @return export
     */
    protected static function export(target_user $user, \context $context): export {
        $repository = workspace_member_request::repository();
        $export = new export();

        switch ($context->contextlevel) {
            case CONTEXT_COURSECAT:
                $entities = $repository->get_requests_of_user_in_category($user->id, $context->instanceid);
                break;

            case CONTEXT_COURSE:
                $entities = $repository->get_requests_of_user_in_workspace($user->id, $context->instanceid);
                break;

            default:
                $entities = $repository->get_requests_of_user($user->id);
                break;
        }

        $export->data = array_map(
            function (workspace_member_request $entity): array {
                return [
                    'id' => $entity->id,
                    'course_id' => $entity->course_id,
                    'user_id' => $entity->user_id,
                    'time_created' => $entity->time_created,
                    'time_declined' => $entity->time_declined ?? '',
                    'time_accepted' => $entity->time_accepted ?? '',
                    'time_cancelled' => $entity->time_cancelled ?? ''
                ];
            },
            $entities
        );

        return $export;
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    protected static function count(target_user $user, \context $context): int {
        $builder = builder::table(workspace_member_request::TABLE, 'wmr');
        $builder->where('wmr.user_id', $user->id);

        switch ($context->contextlevel) {
            case CONTEXT_COURSECAT:
                $builder->join(['course', 'c'], 'wmr.course_id', 'c.id');
                $builder->where('c.category', $context->instanceid);

                break;

            case CONTEXT_COURSE:
                $builder->where('wmr.course_id', $context->instanceid);
                break;
        }

        return $builder->count();
    }

    /**
     * @param target_user $user
     * @param \context $context
     *
     * @return int
     */
    protected static function purge(target_user $user, \context $context): int {
        global $DB;
        $builder = builder::table(workspace_member_request::TABLE);

        switch ($context->contextlevel) {
            case CONTEXT_COURSECAT:
                $course_ids = $DB->get_fieldset_select(
                    'course',
                    'id',
                    'category = :category_id',
                    ['category_id' => $context->instanceid]
                );

                $builder->where_in('course_id', $course_ids);
                break;

            case CONTEXT_COURSE:
                $builder->where('course_id', $context->instanceid);
                break;
        }

        $builder->where('user_id', $user->id);
        $builder->delete();

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * @return string[]
     */
    public static function get_fullname_string() {
        return ['user_data_item_member_request', 'container_workspace'];
    }

    /**
     * @return array
     */
    public static function get_compatible_context_levels(): array {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE];
    }
}