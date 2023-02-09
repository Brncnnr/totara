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

use context;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use core\orm\query\builder;
use container_workspace\entity\workspace_off_notification as entity;

final class workspace_off_notification extends item {
    /**
     * @return boolean
     */
    public static function is_exportable(): bool {
        return false;
    }

    /**
     * @return boolean
     */
    public static function is_countable(): bool {
        return false;
    }

    /**
     * Note that admin can set up the purge even when user is still active.
     *
     * @param integer $user_status
     * @return boolean
     */
    public static function is_purgeable(int $user_status): bool {
        return true;
    }

    /**
     * @param target_user $user
     * @param context $context
     *
     * @return int
     */
    protected static function purge(target_user $user, context $context): int {
        global $DB;

        $builder = builder::table(entity::TABLE, 'won');
        $builder->where('user_id', $user->id);

        if (CONTEXT_COURSE == $context->contextlevel) {
            $builder->where('course_id', $context->instanceid);
        } else if (CONTEXT_COURSECAT == $context->contextlevel) {
            $category_id = $context->instanceid;
            $course_ids = $DB->get_fieldset_select(
                'course',
                'id',
                'category = :category_id',
                ['category_id' => $category_id]
            );

            $builder->where_in('course_id', $course_ids);
        }

        $builder->delete();
        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * @return array
     */
    public static function get_fullname_string(): array {
        return ['user_data_item_workspace_off_notification', 'container_workspace'];
    }

    /**
     * @return array
     */
    public static function get_compatible_context_levels(): array {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE];
    }
}