<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package container_workspace
 */

namespace container_workspace\loader\audience;

use stdClass;
use core\entity\cohort as cohort_entity;
use core\entity\enrol;
use core\orm\pagination\offset_cursor_paginator;
use container_workspace\query\audience\query;
use container_workspace\workspace;
use core\orm\query\builder;

/**
 * Loader class for audiences synced with a workspace
 */
class loader {

    /**
     * Get paginator of audiences synced with workspace.
     *
     * @param query $query
     * @return offset_cursor_paginator
     */
    public static function get_audiences(query $query): offset_cursor_paginator {
        $builder = cohort_entity::repository()->as('c')
            ->preload_members_count()
            ->join([enrol::TABLE, 'e'], 'id','customint1')
            ->where('e.courseid', $query->get_workspace_id())
            ->where('e.enrol', 'cohort')
            ->where('e.status', ENROL_INSTANCE_ENABLED)
            ->order_by('c.name')
            ->select_raw("c.*")
            ->get_builder();

        $cohort_name = $query->get_name_filter();
        if (!empty($cohort_name)) {
            self::filter_by_name($cohort_name, $builder);
        }

        return new offset_cursor_paginator($builder, $query->get_cursor());
    }

    /**
     * Get IDs of audiences synced with workspace.
     * 
     * @param workspace $workspace
     * @return int[]
     */
    public static function get_audience_ids(workspace $workspace): array {
        return cohort_entity::repository()->as('c')
            ->join([enrol::TABLE, 'e'], 'id','customint1')
            ->where('e.courseid', $workspace->id)
            ->where('e.enrol', 'cohort')
            ->where('e.status', ENROL_INSTANCE_ENABLED)
            ->order_by('c.name')
            ->select_raw("c.id")
            ->get()
            ->pluck('id');
    }

    /**
     * Filter the builder by name.
     *
     * @param string $cohort_name
     * @param builder $builder
     * @return void
     */
    private static function filter_by_name(string $cohort_name, builder $builder): void {
        $db = builder::get_db();
        $escaped_name = $db->sql_like_escape($cohort_name);

        $builder->where_raw(
            $db->sql_like('c.name', ':cohort_name_search', false, false),
            [
                'cohort_name_search' => "%$escaped_name%"
            ]
        );
    }
}