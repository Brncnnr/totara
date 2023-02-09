<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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

use core\entity\user;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\sql\query;
use core\orm\query\table;
use mod_approval\data_provider\application\role_map\role_map_controller;
use mod_approval\entity\assignment\assignment;
use mod_approval\entity\assignment\assignment_approver;
use mod_approval\model\application\application_state;

/**
 * A base class for application data_provider capability map implementations.
 */
abstract class capability_map_base {

    /** @var int */
    protected $user_id;

    /** @var bool */
    protected $has_capability = false;

    /**
     * Returns the full name of the capability that the map is implemented for.
     *
     * @return string
     */
    abstract public static function get_capability(): string;

    /**
     * Returns the name of the table to store the capability mappings in.
     *
     * @return string
     */
    abstract public static function get_table(): string;

    /**
     * Returns the table alias that could be used in database queries.
     *
     * @return string
     */
    abstract public static function get_table_alias(): string;

    /**
     * Indicates whether the capability checked by this map is 'pending', meaning the map includes approval_level.
     *
     * @return bool
     */
    public static function get_is_pending(): bool {
        return false;
    }

    /**
     * Create a new instance of the capability map for a user.
     *
     * @param int $user_id
     */
    public function __construct(int $user_id) {
        global $CFG;
        $user = new \core\entity\user($user_id);
        $this->user_id = $user->id;
        $this->has_capability = $this->has_mapped_capability();
    }

    /**
     * Discover whether the user has the capability at all.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->has_capability;
    }

    /**
     * Applies conditions to enforce multitenancy rules; to be used in a Closure.
     *
     * @param builder $builder
     * @param int $user_id
     */
    public static function tenant_visibility_where(builder $builder, int $user_id): void {
        global $CFG;
        $user = new user($user_id);
        $tenant_id = $user->tenantid;
        $tenantsisolated = $CFG->tenantsisolated;
        $builder->when(!empty($tenant_id), function (builder $condition) use ($tenant_id) {
                $condition->where('tenantid', '=', $tenant_id);
            })
            ->when(!empty($tenant_id) && empty($tenantsisolated), function (builder $condition) {
                $condition->or_where_null('tenantid');
            });
    }

    /**
     * Generates user-assignment-capability map records for a user, by finding all assignments where this
     *  user has a role that has the capability.
     *
     * @param int $user_id
     * @return bool
     */
    public static function generate_capability_map(int $user_id): bool {
        global $CFG;

        // First, get the role-capability map for this capability
        $role_map = role_map_controller::get(static::get_capability());

        // Find all assignments where this user has a role assignment with the capability.
        $assignments = builder::table(assignment::TABLE)
            ->as('assignment')
            ->select_raw("DISTINCT assignment.id as approval_id, {$user_id} as user_id");

        // If this is a pending capability, add an approval_level column to the select and join on the approvers table.
        if (static::get_is_pending()) {
            $assignments->add_select_raw("approver.workflow_stage_approval_level_id as workflow_stage_approval_level_id")
                ->join([assignment_approver::TABLE, 'approver'], function (builder $builder) use ($user_id) {
                    $builder->where_field('approval_id', '=', 'assignment.id')
                        ->where('type', '=', \mod_approval\model\assignment\approver_type\user::get_code())
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
                    $builder->from('user')
                        ->limit(1);
                });
            }

            // Keep building the main query.
            $assignments->join(['course_modules', 'cm'], function (builder $builder) {
                    $builder->where_field('course', '=', 'assignment.course')
                        ->where_field('instance', '=', 'assignment.id');
                })
                ->join(['context', 'ctx'], function (builder $builder) use ($user_id) {
                    $builder->where_field('instanceid', '=', 'cm.id')
                        ->where('contextlevel', '=', CONTEXT_MODULE)
                        ->where(function (builder $tvw) use ($user_id) {
                            self::tenant_visibility_where($tvw, $user_id);
                        });
                })
                ->join([$role_map->get_map_table_name(), 'role_map'], function (builder $builder) use ($role_map) {
                    $role_map->get_assigned_capability_sql($builder, 'cm');
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
        [$sql, $params] = query::from_builder($assignments)->build();

        // Delete the current contents of the table for this user
        builder::table(static::get_table())->where('user_id', '=', $user_id)->delete();

        // Columns for INSERT
        $columns = ['approval_id', 'user_id'];
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
     * Checks cache and capability system, then generates records if needed.
     *
     * @return bool
     */
    public function has_mapped_capability(): bool {
        // Existing records act as cache.
        return builder::table(static::get_table())
            ->where('user_id', '=', $this->user_id)
            ->exists();
    }

    /**
     * Applies the necessary left_join to the provided builder; to be used in a Closure.
     *
     * @param builder $builder
     */
    public function get_map_join(builder $builder): void {
        $builder->where('user_id', '=', $this->user_id)
            ->where_field('approval_id', '=', 'assignment.id');
        if (static::get_is_pending()) {
            $builder->where_field(
                'workflow_stage_approval_level_id',
                '=',
                'application.current_approval_level_id'
            );
        }
    }

    /**
     * Applies the necessary left_join to the repository, if the user has this capability.
     *
     * @param repository $repository
     * @return repository
     */
    public function apply_map_join(repository $repository): repository {
        if ($this->has_capability) {
            $repository->left_join([static::get_table(), static::get_table_alias()], function (builder $builder) {
                $this->get_map_join($builder);
            });
        }
        return $repository;
    }

    /**
     * Applies the necessary where conditions to the provided builder; to be used in a Closure.
     *
     * @param builder $builder
     */
    public function get_or_where_condition(builder $builder): void {
        $builder->or_where(function (builder $builder) {
            $builder->where_not_null(static::get_table_alias() . '.approval_id');
            $this->get_application_condition($builder);
        });
    }

    /**
     * Add a where clause that ensures the applications for this capability are in a particular condition.
     *
     * By default, applications should not be in draft condition.
     *
     * @param builder $builder
     */
    public function get_application_condition(builder $builder): void {
        $builder->where(
            'is_draft',
            '=',
            0
        );
    }

    /**
     * Deletes capability map records for the specified user.
     *
     * @param int $user_id
     */
    public static function reset_capability_maps_for_user(int $user_id): void {
        builder::table(static::get_table())
            ->where('user_id', '=', $user_id)
            ->delete();
    }
}