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
namespace container_workspace\repository;

use container_workspace\entity\workspace_discussion;
use core\orm\entity\repository;
use core\orm\query\builder;

/**
 * Repository for discussion
 */
final class discussion_repository extends repository {
    /**
     * Count the total of discussions created by the user.
     *
     * @param int $user_id
     * @return int
     */
    public function count_for_user(int $user_id): int {
        $builder = builder::table(static::get_table());
        $builder->where('user_id', $user_id);

        return $builder->count();
    }

    /**
     * @param int $user_id
     * @param int $workspace_id
     *
     * @return int
     */
    public function count_for_user_within_workspace(int $user_id, int $workspace_id): int {
        $builder = builder::table(static::get_table());
        $builder->where('user_id', $user_id);
        $builder->where('course_id', $workspace_id);

        return $builder->count();
    }

    /**
     * @param int $user_id
     * @param int $category_id
     *
     * @return int
     */
    public function count_for_user_within_workspace_category(int $user_id, int $category_id): int {
        $builder = builder::table(static::get_table(), 'wd');
        $builder->join(['course', 'c'], 'wd.course_id', 'c.id');

        $builder->where('c.category', $category_id);
        $builder->where('wd.user_id', $user_id);

        return $builder->count();
    }

    /**
     * @param int       $user_id
     * @param int       $workspace_id
     * @param int|null  $page
     *
     * @return workspace_discussion[]
     */
    public function fetch_by_user_within_workspace(int $user_id, int $workspace_id, ?int $page = null): array {
        $builder = builder::table(static::get_table());
        $builder->where('user_id', $user_id);
        $builder->where('course_id', $workspace_id);

        $builder->map_to(workspace_discussion::class);

        if (null !== $page && 0 !== $page) {
            $paginator = $builder->paginate($page);
            return $paginator->get_items()->all();
        }

        return $builder->fetch();
    }

    /**
     * @param int       $user_id
     * @param int       $category_id
     * @param int|nulL  $page
     *
     * @return workspace_discussion[]
     */
    public function fetch_by_user_within_workspace_category(int $user_id, int $category_id,
                                                            ?int $page = null): array {
        $builder = builder::table(static::get_table(), 'wd');
        $builder->join(['course', 'c'], 'wd.course_id', 'c.id');

        $builder->select('wd.*');
        $builder->where('c.category', $category_id);
        $builder->where('wd.user_id', $user_id);

        $builder->map_to(workspace_discussion::class);
        if (null !== $page && 0 !== $page) {
            $paginator = $builder->paginate($page);
            return $paginator->get_items()->all();
        }

        return $builder->fetch();
    }

    /**
     * @param int $user_id
     * @param int|null $page
     *
     * @return workspace_discussion[]
     */
    public function fetch_by_user(int $user_id, ?int $page = null): array {
        $builder = builder::table(static::get_table());

        $builder->where('user_id', $user_id);
        $builder->map_to(workspace_discussion::class);

        if (null !== $page && 0 !== $page) {
            $paginator = $builder->paginate($page);
            return $paginator->get_items()->all();
        }

        return $builder->fetch();
    }

    /**
     * @param int $workspace_id
     * @return int
     */
    public function count_for_workspace(int $workspace_id): int {
        $builder = builder::table(static::get_table());
        $builder->where('course_id', $workspace_id);

        return $builder->count();
    }

    /**
     * Return a count of non-deleted workspace discussions across the whole site.
     *
     * @return int
     */
    public function count_all_non_deleted(): int {
        $builder = builder::table(static::get_table());
        $builder->where_null('time_deleted');

        return $builder->count();
    }

    /**
     * This function will try to fetch for the current content_format of
     * the given $discussion_id.
     *
     * @param int $discussion_id
     * @return int
     */
    public function get_content_format_of_discussion(int $discussion_id): int {
        $builder = builder::table(static::get_table());
        $builder->where('id', $discussion_id);

        $builder->select('content_format');
        $record = $builder->one(true);

        return $record->content_format;
    }
}