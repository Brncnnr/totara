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
namespace container_workspace\loader\member;

use container_workspace\member\status;
use container_workspace\query\member\non_member_query;
use core\entity\user;
use core\orm\entity\repository;
use core\orm\pagination\offset_cursor_paginator;
use core\orm\query\builder;
use core\tenant_orm_helper;

/**
 * A loader classes to load users that are not a member of a workspace.
 *
 * @deprecated since Totara 16. Use container_workspace\data_providers\non_members
 * instead.
 */
final class non_member_loader {
    /**
     * non_member_loader constructor.
     * Preventing this class from being constructed.
     */
    private function __construct() {
    }

    /**
     * @param non_member_query $query
     * @return offset_cursor_paginator
     */
    public static function get_non_members(non_member_query $query): offset_cursor_paginator {
        debugging(
            'Class non_member_loader is deprecated; use container_workspace\data_providers\non_members instead',
            DEBUG_DEVELOPER
        );

        $workspace_id = $query->get_workspace_id();

        $search_term = $query->get_search_term();

        $exists_query = builder::table('user_enrolments', 'ue')
            ->join(['enrol', 'e'], 'ue.enrolid', 'e.id')
            ->where_field('ue.userid', '"user".id')
            ->where('e.courseid', $workspace_id)
            ->where('status', status::get_active());

        $user_repository = user::repository()
            ->filter_by_not_deleted()
            ->filter_by_not_guest()
            ->filter_by_not_suspended()
            ->when(!empty($search_term), function (repository $repository) use ($search_term) {
                $repository->filter_by_full_name($search_term);
            })
            ->where_not_exists($exists_query)
            ->when(true, function (repository $repository) use ($workspace_id) {
                $alias = $repository->get_builder()->get_alias_sql();

                // Apply tenant query.
                $context = \context_course::instance($workspace_id);
                tenant_orm_helper::restrict_users(
                    $repository->get_builder(),
                    "{$alias}.id",
                    $context
                );
            })
            ->order_by_full_name();

        $cursor = $query->get_cursor();
        return new offset_cursor_paginator($user_repository, $cursor);
    }
}