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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\data_provider\application\capability_map;

use core\orm\query\builder;
use core\orm\query\sql\query;
use core\orm\query\table;
use mod_approval\data_provider\application\role_map\role_map_controller;
use mod_approval\entity\assignment\assignment_approver;

/**
 * A trait for application data_provider capability map implementations where capability is allowed in a user context.
 */
trait user_capability_map_trait {

    /**
     * @inheritDoc
     */
    public static function generate_capability_map(int $user_id): bool {
        global $CFG;

        // First, get the role-capability map for this capability
        $role_map = role_map_controller::get(static::get_capability());

        // Find all users where this user has a role assignment with the capability.
        $users = builder::table(\core\entity\user::TABLE)
            ->as('u')
            ->select_raw("DISTINCT u.id as applicant_id, {$user_id} as user_id");

        // If this is a pending capability, add an approval_level column to the select and join on the approvers table.
        if (static::get_is_pending()) {
            $users->add_select_raw("approver.workflow_stage_approval_level_id as workflow_stage_approval_level_id")
                ->join([assignment_approver::TABLE, 'approver'], function (builder $builder) use ($user_id) {
                    $builder->where('type', '=', \mod_approval\model\assignment\approver_type\user::get_code())
                        ->where('identifier', '=', $user_id);
                });
        }

        // Admins can see everything, but we need to limit the rest.
        if (!is_siteadmin($user_id)) {
            // Create a subquery to use for matching role assignments.
            $role_assignments_subquery = builder::table('role_assignments')
                ->as('ra')
                ->select(['roleid', 'contextid'])
                ->where('userid', '=', $user_id);
            // Include the authenticated user role, which doesn't have a record in the role_assignments table.
            if (!empty($CFG->defaultuserroleid)) {
                $role_assignments_subquery->union(function (builder $builder) use ($CFG) {
                    $defaultuserroleid = intval($CFG->defaultuserroleid);
                    $system_context = \context_system::instance();
                    $builder->select_raw("{$defaultuserroleid} AS roleid, {$system_context->id} AS contextid");
                    // Query builder complains if there is no table.
                    $builder->from('approval')
                        ->limit(1);
                });
            }

            // Keep building the main query.
            $users->join(['context', 'ctx'], function (builder $builder) use ($user_id) {
                    $builder->where_field('instanceid', 'u.id')
                        ->where('contextlevel', CONTEXT_USER)
                        ->where(function (builder $tvw) use ($user_id) {
                            self::tenant_visibility_where($tvw, $user_id);
                        });
                })
                ->join([$role_map->get_map_table_name(), 'role_map'], function (builder $builder) use ($role_map) {
                    $role_map->get_assigned_capability_sql($builder, 'u');
                })
                ->join((new table($role_assignments_subquery))->as('role_assignment'), function (builder $builder) {
                    $builder->where_field('roleid', '=', 'role_map.roleid');
                })
                ->join(['context', 'role_context'], function (builder $builder) {
                    $builder->where_field('id', '=', 'role_assignment.contextid');
                })
                ->where(function (builder $builder) {
                    $builder->where_raw("ctx.path LIKE CONCAT(role_context.path, '/%')")
                        ->or_where_field('ctx.path', '=', 'role_context.path');
                });
        }
        [$sql, $params] = query::from_builder($users)->build();

        // Delete the current contents of the table for this user
        builder::table(static::get_table())->where('user_id', '=', $user_id)->delete();

        // Columns for INSERT
        $columns = ['applicant_id', 'user_id'];
        if (static::get_is_pending()) {
            $columns[] = 'workflow_stage_approval_level_id';
        }

        // Combine the table, columns, and the select query into an INSERT INTO ... SELECT statement, and execute it.
        $query = sprintf(
            "INSERT INTO {%s} (%s) %s",
            static::get_table(),
            implode(', ', $columns),
            $sql
        );
        builder::get_db()->execute($query, $params);

        // Return whether any rows exist in the map.
        return builder::table(static::get_table())
            ->where('user_id', '=', $user_id)
            ->exists();
    }

    /**
     * @inheritDoc
     */
    public function get_map_join(builder $builder): void {
        $builder->where('user_id', '=', $this->user_id)
            ->where_field('applicant_id', '=', 'application.user_id');
        if (static::get_is_pending()) {
            $builder->where_field(
                'workflow_stage_approval_level_id',
                '=',
                'application.current_approval_level_id'
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function get_or_where_condition(builder $builder): void {
        $builder->or_where(function (builder $builder) {
            $builder->where_not_null(static::get_table_alias() . '.applicant_id');
            $this->get_application_condition($builder);
        });
    }
}