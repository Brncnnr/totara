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
namespace container_workspace\loader\workspace;

use container_workspace\member\status;
use container_workspace\query\workspace\access;
use container_workspace\query\workspace\sort;
use container_workspace\query\workspace\query;
use container_workspace\query\workspace\source;
use container_workspace\workspace;
use context_system;
use core\orm\pagination\offset_cursor_paginator;
use core\orm\query\builder;
use core\orm\query\table;

/**
 * Class workspace_loader
 * @package container_workspace\local
 */
final class loader {
    /**
     * Preventing any construction on this class
     * loader constructor.
     */
    private function __construct() {
    }

    /**
     * @param query $query
     * @return offset_cursor_paginator
     */
    public static function get_workspaces(query $query): offset_cursor_paginator {
        global $CFG, $DB;

        $builder = builder::table('course', 'c');
        $builder->join(['workspace', 'wo'], 'c.id', 'wo.course_id');

        $builder->select_raw(
            "DISTINCT c.*, wo.id AS w_id, wo.user_id, wo.private AS workspace_private, wo.timestamp AS timestamp"
        );

        $builder->map_to([workspace::class, 'from_record']);

        $source = $query->get_source();
        $user_id = $query->get_user_id();

        $source_is_member = source::is_member($source);
        if (source::is_member_and_owned($source) || $source_is_member) {
            // For member and owned, we can check on the enrollment here. as owner is also enrolled to the workspace
            // as well as the member.
            $builder->where('ue.userid', $user_id);
            $builder->join(['enrol', 'e'], 'c.id', 'e.courseid');
            $builder->join(['user_enrolments', 'ue'], 'e.id', 'ue.enrolid');

            $status = $query->get_member_status();

            if (null !== $status) {
                require_once("{$CFG->dirroot}/lib/enrollib.php");

                if (status::is_active($status)) {
                    $builder->where('ue.status', ENROL_USER_ACTIVE);
                } else if (status::is_suspended($status)) {
                    $builder->where('ue.status', ENROL_USER_SUSPENDED);
                }
            }

            // We want to keep all the ones we're a member of, but exclude the ones we own
            if ($source_is_member) {
                $builder->where('wo.user_id', '<>', $user_id);
            }
        } else if (source::is_other($source)) {
            // For getting other workspaces, we need to filter out those workspaces that this user
            // had already a part of. SQL for this piece of query will looks something similar to below:
            //
            // ----- SQL -----
            // SELECT DISTINCT c.*, wo.id AS w_id, wo.user_id, wo.private
            // FROM phpu_00course "c"
            //         INNER JOIN phpu_00workspace "wo" ON c.id = wo.course_id
            //         LEFT JOIN (SELECT c2.id
            //                    FROM phpu_00course "c2"
            //                             INNER JOIN phpu_00enrol "e" ON c2.id = e.courseid
            //                             INNER JOIN phpu_00user_enrolments "ue" ON e.id = ue.enrolid
            //                    WHERE ue.userid = $1) "jc" ON c.id = jc.id
            // WHERE jc.id IS NULL
            // ORDER BY c.timecreated DESC
            // ----- END SQL -----

            $sub_query = builder::table('course', 'c2');
            $sub_query->select('c2.id');
            $sub_query->join(['enrol', 'e'], 'c2.id', 'e.courseid');
            $sub_query->join(['user_enrolments', 'ue'], 'e.id', 'ue.enrolid');
            $sub_query->where('ue.userid', $user_id);

            $table = new table($sub_query);
            $table->as('jc');

            $builder->left_join($table, 'c.id', 'jc.id');
            $builder->where_null('jc.id');
        } else if (source::is_owned_only($source)) {
            // Owned only.
            $builder->where('wo.user_id', $user_id);
        }

        $sort = $query->get_sort();
        if (sort::is_recent($sort)) {
            $builder->order_by('c.timecreated', 'desc');
        } else if (sort::is_alphabet($sort)) {
            $builder->order_by('c.fullname');
        }

        $search_term = $query->get_search_term();
        if (null !== $search_term && '' !== $search_term) {
            $builder->where('c.fullname', 'ilike', $search_term);
        }

        $access = $query->get_access();
        if (null !== $access) {
            if (access::is_public($access)) {
                $builder->where('wo.private', 0);
            } else if (access::is_private($access)) {
                $builder->where('wo.private', 1);
            } else {
                debugging("No support for fetching hidden only yet", DEBUG_DEVELOPER);
            }
        }

        // Note: as of TL-25350 - this totara_visbility_where will support our hidden workspace.
        // We are not using $user_id in this torara_visiblity_where because $user_id is the target user's id
        // that we are trying to fetch against, and actor's id is the current user running this loader.
        // Hence we will have to filter out all the workspaces that the actor user cannot see against the
        // target user that is being fetched.
        require_once("{$CFG->dirroot}/totara/coursecatalog/lib.php");
        $actor_id = $query->get_actor_id();

        // Joining the context for tenancy support
        $actor_tenant_id = $DB->get_field('user', 'tenantid', ['id' => $actor_id], MUST_EXIST);
        $context_system = context_system::instance();

        if ($CFG->tenantsenabled && !has_capability('totara/tenant:config', $context_system, $actor_id)) {
            // Tenant is enabled and user does not have ability to manage tenant, hence they will fall to this path.
            if (!empty($CFG->tenantsisolated) && !empty($actor_tenant_id)) {
                $builder->left_join(
                    ['context', 'ctx'],
                    function (builder $join) use ($actor_tenant_id) {
                        $join->where_field('c.id', 'ctx.instanceid');
                        $join->where('ctx.contextlevel', CONTEXT_COURSE);
                        $join->where('ctx.tenantid', $actor_tenant_id);
                    }
                );
            } else if (empty($actor_tenant_id)) {
                // If we are here, meaning that our user is a system user.

                // --- SQL for context builder ---
                //
                // SELECT inner_ctx.* FROM "ttr_context" inner_ctx
                // LEFT JOIN "ttr_tenant" tenant ON inner_ctx.tenantid = tenant.id
                // LEFT JOIN "ttr_cohort_members" tcm ON tenant.cohortid = tcm.cohortid
                // WHERE inner_ctx.contextlevel = CONTEXT_COURSE
                // AND (inner_ctx.tenantid IS NULL OR tcm.id IS NOT NULL)
                //
                // --- END OF SQL ---
                $context_builder = builder::table('context', 'inner_ctx');
                $context_builder->select('inner_ctx.*');

                $context_builder->left_join(['tenant', 'tenant'], 'inner_ctx.tenantid', 'tenant.id');
                $context_builder->left_join(
                    ['cohort_members', 'tcm'],
                    function (builder $cohort_member_join) use ($actor_id): void {
                        $cohort_member_join->where_field('tenant.cohortid', 'tcm.cohortid');
                        $cohort_member_join->where('tcm.userid', $actor_id);
                    }
                );

                $context_builder->where('inner_ctx.contextlevel', CONTEXT_COURSE);
                $context_builder->where(
                    function (builder $inner_context_builder): void {
                        // Either we are looking for the context that is within a system
                        // or the context that this user is a participant of.
                        $inner_context_builder->where_null('inner_ctx.tenantid');
                        $inner_context_builder->where_not_null('tcm.id', true);
                    }
                );

                $builder->join([$context_builder, 'ctx'], 'c.id', 'ctx.instanceid');
            }
        } else {
            $builder->left_join(
                ['context', 'ctx'],
                function (builder $join): void {
                    $join->where_field('c.id', 'ctx.instanceid');
                    $join->where('ctx.contextlevel', CONTEXT_COURSE);
                }
            );
        }

        [$sql_where, $sql_params] = totara_visibility_where(
            $actor_id,
            'c.id',
            'c.visible',
            'c.audiencevisible',
            'c',
            'course'
        );

        $builder->where_raw($sql_where, $sql_params);

        $to_be_deleted = $query->get_to_be_deleted();
        $builder->when(
            (null !== $to_be_deleted),
            function (builder $builder) use ($to_be_deleted) {
                $builder->where('wo.to_be_deleted', $to_be_deleted);
            }
        );

        $cursor = $query->get_cursor();
        return new offset_cursor_paginator($builder, $cursor);
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function exists(int $id): bool {
        global $DB;
        return $DB->record_exists('course', ['id' => $id]);
    }
}