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

use container_workspace\entity\workspace_member_request;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\order;

/**
 * Repository to fetch records only related to table "ttr_workspace_member_request"
 */
final class workspace_member_request_repository extends repository {
    /**
     * @param int $workspace_id
     * @param int $user_id
     *
     * @return workspace_member_request|null
     */
    public function get_current_pending_request(int $workspace_id, int $user_id): ?workspace_member_request {
        $builder = builder::table(static::get_table());
        $builder->where('course_id', $workspace_id);
        $builder->where('user_id', $user_id);

        $builder->where_null('time_accepted');
        $builder->where_null('time_declined');
        $builder->where_null('time_cancelled');

        $builder->map_to(workspace_member_request::class);
        $builder->order_by('time_created', order::DIRECTION_DESC);

        /** @var workspace_member_request $entity */
        $entity = $builder->first();
        return $entity;
    }

    /**
     * @param int $category_id
     * @param int $user_id
     *
     * @return workspace_member_request[]
     */
    public function get_requests_of_user_in_category(int $user_id, int $category_id): array {
        $builder = builder::table(static::get_table(), 'wmr');
        $builder->join(['course', 'c'], 'wmr.course_id', 'c.id');

        $builder->where('c.category', $category_id);
        $builder->where('wmr.user_id', $user_id);

        $builder->map_to(workspace_member_request::class);
        return $builder->fetch();
    }

    /**
     * @param int $user_id
     * @return workspace_member_request[]
     */
    public function get_requests_of_user(int $user_id): array {
        $builder = builder::table(static::get_table());
        $builder->where('user_id', $user_id);

        $builder->map_to(workspace_member_request::class);
        return $builder->fetch();
    }

    /**
     * @param int $user_id
     * @param int $workspace_id
     *
     * @return workspace_member_request[]
     */
    public function get_requests_of_user_in_workspace(int $user_id, int $workspace_id): array {
        $builder = builder::table(static::get_table(), 'wmr');

        $builder->where('wmr.user_id', $user_id);
        $builder->where('wmr.course_id', $workspace_id);

        $builder->map_to(workspace_member_request::class);
        return $builder->fetch();
    }
}