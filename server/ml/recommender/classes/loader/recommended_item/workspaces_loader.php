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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_recommender
 */

namespace ml_recommender\loader\recommended_item;

use container_workspace\workspace;
use core\orm\pagination\offset_cursor_paginator;
use core\orm\query\builder;
use core\orm\query\table;
use ml_recommender\entity\recommended_item;
use ml_recommender\entity\recommended_user_item;
use ml_recommender\query\recommended_item\item_query;
use ml_recommender\query\recommended_item\user_query;
use ml_recommender\recommendations;

/**
 * Loader class for a recommended item
 */
final class workspaces_loader extends loader {
    /**
     * @var recommendations|null
     */
    protected static $recommendations_helper;

    /**
     * Select all related workspaces based on the provided component id
     *
     * @param item_query $query
     * @param int $actor_id
     * @return offset_cursor_paginator
     */
    public static function get_recommended(item_query $query, int $actor_id = 0): offset_cursor_paginator {
        $builder = self::get_base_workspace_query(recommended_item::TABLE, $actor_id);

        if (recommendations::is_ml_service_enabled()) {
            $helper = self::$recommendations_helper ?? recommendations::make($actor_id);
            $recommendations = $helper->get_similar_items($query->get_target_component(), $query->get_target_item_id());

            $builder->where_in('c.id', $recommendations ?? []);
            $helper->apply_sort_by_recommendations($builder, 'c.id', $recommendations);
        } else {
            $builder->where('r.target_item_id', $query->get_target_item_id());
            $builder->where('r.target_component', $query->get_target_component());
            $builder->where('r.target_area', $query->get_target_area());
        }

        $cursor = $query->get_cursor();
        return new offset_cursor_paginator($builder, $cursor);
    }

    /**
     * Select all recommended workspaces for the user
     *
     * @param user_query $query
     * @param int $actor_id
     * @return offset_cursor_paginator
     */
    public static function get_recommended_for_user(user_query $query, int $actor_id = 0): offset_cursor_paginator {
        $builder = self::get_base_workspace_query(recommended_user_item::TABLE, $actor_id);

        if (recommendations::is_ml_service_enabled()) {
            $helper = self::$recommendations_helper ?? recommendations::make($query->get_target_user_id());
            $recommendations = $helper->get_user_recommendations($query->get_target_component());
            $builder->where_in('c.id', $recommendations ?? []);
            $helper->apply_sort_by_recommendations($builder, 'c.id', $recommendations);
        } else {
            $builder->where('r.user_id', $query->get_target_user_id());
            $builder->where('r.component', $query->get_target_component());
            $builder->where('r.area', $query->get_target_area());
        }

        $cursor = $query->get_cursor();
        return new offset_cursor_paginator($builder, $cursor);
    }

    /**
     * @param string $table
     * @param int $actor_id
     * @return builder
     */
    private static function get_base_workspace_query(string $table, int $actor_id): builder {
        global $USER, $CFG, $DB;
        if (!$actor_id) {
            $actor_id = $USER->id;
        }

        // If we're using the new service, then don't join on the recommendations table
        if (recommendations::is_ml_service_enabled()) {
            $builder = builder::table('course', 'c');
            $builder->where('containertype', 'container_workspace');
        } else {
            // Fall back to the legacy service instead
            $builder = builder::table($table, 'r');

            // Join against workspaces
            $builder->join(['course', 'c'], 'r.item_id', 'c.id');
            $builder->where('r.component', 'container_workspace');

            $builder->order_by_raw('r.score DESC');
        }

        $builder->join(['workspace', 'w'],
            function (builder $join): void {
                $join->where_field('c.id', 'w.course_id');

                // We are filtering out those deleted items.
                $join->where('w.to_be_deleted', 0);
            }
        );

        $builder->select_raw('c.*, w.user_id, w.id AS w_id');

        // Exclude private workspaces
        $builder->where('w.private', 0);

        // Apply any multi-tenancy filters. Workspace differ from courses slightly.
        // See server/container/type/workspace/classes/loader/workspace/loader.php::get_workspaces for the logic.
        $tenant_id = $CFG->tenantsenabled ? $DB->get_field('user', 'tenantid', ['id' => $actor_id], MUST_EXIST) : null;
        $context_system = \context_system::instance();

        // The default rules handled by totara_visibility_where cover most situations, however workspaces slightly
        // differ around system-level users.
        if ($CFG->tenantsenabled && empty($tenant_id) && !has_capability('totara/tenant:config', $context_system, $actor_id)) {
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
                    $inner_context_builder->where_null('inner_ctx.tenantid');
                    $inner_context_builder->where_not_null('tcm.id', true);
                }
            );

            $builder->join([$context_builder, 'ictx'], 'c.id', 'ictx.instanceid');
        }

        // Exclude enrolled workspaces
        $sub_query = builder::table('course', 'c2');
        $sub_query->select('c2.id');
        $sub_query->join(['enrol', 'e'], 'c2.id', 'e.courseid');
        $sub_query->join(['user_enrolments', 'ue'], 'e.id', 'ue.enrolid');
        $sub_query->where('ue.userid', $actor_id);
        $sub_query->where('ue.status', ENROL_USER_ACTIVE);

        $table = new table($sub_query);
        $table->as('jc');

        $builder->left_join($table, 'c.id', 'jc.id');
        $builder->where_null('jc.id');

        // Handle hidden/inaccessible workspaces
        require_once("{$CFG->dirroot}/totara/coursecatalog/lib.php");
        [$sql_where, $sql_params] = totara_visibility_where(
            $actor_id,
            'c.id',
            'c.visible',
            'c.audiencevisible',
            'c',
            'course'
        );

        $builder->left_join(
            ['context', 'ctx'],
            function (builder $builder): void {
                $builder->where_field('c.id', 'ctx.instanceid');
                $builder->where('ctx.contextlevel', CONTEXT_COURSE);
            }
        );

        $builder->where_raw($sql_where, $sql_params);

        $builder->map_to(function (\stdClass $record) {
            return workspace::from_record($record);
        });

        return $builder;
    }
}