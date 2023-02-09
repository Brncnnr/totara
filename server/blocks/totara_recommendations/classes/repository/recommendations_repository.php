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
 * @package block_totara_recommendations
 */

namespace block_totara_recommendations\repository;

use core\entity\enrol;
use core\orm\query\builder;
use core\orm\query\table;
use ml_recommender\recommendations;
use totara_engage\access\access;
use totara_engage\timeview\time_view;

/**
 * Repo to provide interaction events
 *
 * @package block_totara_recently_viewed\repository
 */
final class recommendations_repository {
    /**
     * @var recommendations|null
     */
    protected static $recommendations_helper;

    /**
     * Fetch the micro-learning items for the provided user.
     *
     * @param int $max_count
     * @param int|null $user_id
     * @return array|\stdClass[]
     */
    public static function get_recommended_micro_learning(int $max_count, int $user_id = null): array {
        global $USER, $CFG, $DB;
        if (!$user_id) {
            $user_id = $USER->id;
        }

        // Handle both use cases
        $helper = self::$recommendations_helper ?? recommendations::make($user_id);
        $recommendations = $helper->get_user_recommendations('engage_microlearning');
        if (null !== $recommendations) {
            // If we're loading from the Ml service, then don't connect to the base table
            $builder = builder::table('engage_resource', 'er');
            $builder->select_raw($builder::concat('er.resourcetype', 'er.id') . ' AS unique_id');
            $builder->add_select_raw('er.id AS item_id');
            $builder->add_select_raw('er.resourcetype AS component');
            $builder->add_select_raw('null AS area');
            $builder->add_select_raw('0 AS seen');

            $builder->where('er.resourcetype', 'engage_article');
            $builder->where_in('er.id', $recommendations);

            $helper->apply_sort_by_recommendations($builder, 'er.id', $recommendations);
        } else {
            // Use the existing builder
            $builder = self::get_base_builder();
            $builder->join(['engage_resource', 'er'], 'er.id', 'ru.item_id');
            $builder->where('ru.user_id', $user_id);
            $builder->where('ru.component', 'engage_article');
            $builder->order_by_raw('ru.seen ASC, ru.score DESC, ru.time_created DESC');
        }

        $builder->join(['engage_article', 'ea'], 'ea.id', 'er.instanceid');
        $builder->where('ea.timeview', time_view::LESS_THAN_FIVE);
        $builder->where('er.access', access::PUBLIC);

        // Multi-tenancy rules, if a user is in a tenant, then restrict to just system & their tenant resources.
        // If isolation is enabled, then restrict to just tenant resources.
        // If you are a system user, see all resources in tenants. If isolation is enabled, just see system resources.
        // If you have the ability to manage engage, all tenant rules are ignored.
        $context_system = \context_system::instance();
        if ($CFG->tenantsenabled && !has_capability('totara/engage:manage', $context_system, $user_id)) {
            $tenant_id = $DB->get_field('user', 'tenantid', ['id' => $user_id], MUST_EXIST);

            if (!empty($tenant_id)) {
                // Users are limited to their tenancy + system level (or just tenancy if isolated).
                $builder->join(
                    ['context', 'ctx'],
                    function (builder $join) use ($CFG, $tenant_id): void {
                        $join->where_field("er.contextid", "ctx.id");
                        $join->where("ctx.contextlevel", CONTEXT_USER);

                        $join->nested_where(function (builder $nested) use ($CFG, $tenant_id) {
                            // Filter against the user's own tenant id
                            $nested->where("ctx.tenantid", $tenant_id);

                            // If isolation is off, include system-level resources
                            if (empty($CFG->tenantsisolated)) {
                                $nested->or_where_null("ctx.tenantid");
                            }
                        });
                    }
                );
            } else if (!empty($CFG->tenantsisolated)) {
                // System user + isolation
                $context_builder = builder::table('context', 'inner_ctx');
                $context_builder->select('inner_ctx.*');

                $context_builder->left_join(['tenant', 'tenant'], 'inner_ctx.tenantid', 'tenant.id');
                $context_builder->left_join(
                    ['cohort_members', 'tcm'],
                    function (builder $cohort_member_join) use ($user_id): void {
                        $cohort_member_join->where_field('tenant.cohortid', 'tcm.cohortid');
                        $cohort_member_join->where('tcm.userid', $user_id);
                    }
                );

                $context_builder->where('inner_ctx.contextlevel', CONTEXT_USER);
                $context_builder->where(
                    function (builder $inner_context_builder): void {
                        $inner_context_builder->where_null('inner_ctx.tenantid');
                        $inner_context_builder->where_not_null('tcm.id', true);
                    }
                );

                $builder->join([$context_builder, 'ctx'], 'er.contextid', 'ctx.id');
            }
        }

        $builder->limit($max_count);

        return $builder->fetch();
    }

    /**
     * @param int $max_count
     * @param int|null $user_id
     * @return array
     */
    public static function get_recommended_courses(int $max_count, int $user_id = null): array {
        global $USER;

        if (!$user_id) {
            $user_id = $USER->id;
        }

        $builder = self::get_recommended_container($max_count, $user_id, 'container_course');

        // Exclude courses that don't have self-enrollment enabled
        $builder->join([enrol::TABLE, 'e'], function (builder $joining) {
            $joining->where_raw('c.id = e.courseid')
                ->where('e.enrol', 'self')
                ->where('e.status', ENROL_INSTANCE_ENABLED);
        });

        return $builder->fetch();
    }

    /**
     * @param int $max_count
     * @param int|null $user_id
     * @return array
     */
    public static function get_recommended_workspaces(int $max_count, int $user_id = null): array {
        global $USER, $CFG, $DB;

        if (!$user_id) {
            $user_id = $USER->id;
        }

        $builder = self::get_recommended_container($max_count, $user_id, 'container_workspace');

        // Exclude workspaces that are deleted or are private
        $builder->join(['workspace', 'w'],
            function (builder $join): void {
                $join->where_field('c.id', 'w.course_id');

                // We are filtering out those deleted items.
                $join->where('w.to_be_deleted', 0);
                $join->where('w.private', 0);
            }
        );

        // Apply any multi-tenancy filters. Workspace differ from courses slightly.
        // See server/container/type/workspace/classes/loader/workspace/loader.php::get_workspaces for the logic.
        $tenant_id = $CFG->tenantsenabled ? $DB->get_field('user', 'tenantid', ['id' => $user_id], MUST_EXIST) : null;
        $context_system = \context_system::instance();

        // The default rules handled by totara_visibility_where cover most situations, however workspaces slightly
        // differ around system-level users.
        if ($CFG->tenantsenabled && empty($tenant_id) && !has_capability('totara/tenant:config', $context_system, $user_id)) {
            $context_builder = builder::table('context', 'inner_ctx');
            $context_builder->select('inner_ctx.*');

            $context_builder->left_join(['tenant', 'tenant'], 'inner_ctx.tenantid', 'tenant.id');
            $context_builder->left_join(
                ['cohort_members', 'tcm'],
                function (builder $cohort_member_join) use ($user_id): void {
                    $cohort_member_join->where_field('tenant.cohortid', 'tcm.cohortid');
                    $cohort_member_join->where('tcm.userid', $user_id);
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

        return $builder->fetch();
    }

    /**
     * @param int $max_count
     * @param int $user_id
     * @param string $container_type
     * @return builder
     */
    private static function get_recommended_container(int $max_count, int $user_id, string $container_type): builder {
        global $CFG;
        require_once($CFG->dirroot . "/lib/enrollib.php");
        require_once($CFG->dirroot . "/totara/coursecatalog/lib.php");

        $helper = self::$recommendations_helper ?? recommendations::make($user_id);
        $recommendations = $helper->get_user_recommendations($container_type);
        if (null !== $recommendations) {
            // Load from the recommenders service (if it's enabled)
            $builder = builder::table('course', 'c');
            $builder->select_raw($builder::concat('c.containertype', 'c.id') . ' AS unique_id');
            $builder->add_select_raw('c.id AS item_id');
            $builder->add_select_raw('c.containertype AS component');
            $builder->add_select_raw('null AS area');
            $builder->add_select_raw('0 AS seen');

            [$totara_visibility_sql, $totara_visibility_params] = totara_visibility_where(
                $user_id,
                'c.id',
                'c.visible',
                'c.audiencevisible',
                'c'
            );
            $builder->where_raw($totara_visibility_sql, $totara_visibility_params);

            $builder->where_in('c.id', $recommendations);
            $helper->apply_sort_by_recommendations($builder, 'c.id', $recommendations);
        } else {
            // Fallback to the legacy service
            $builder = self::get_base_builder();
            $builder->join(['course', 'c'], function (builder $joining) use ($container_type, $user_id) {
                [$totara_visibility_sql, $totara_visibility_params] = totara_visibility_where(
                    $user_id,
                    'c.id',
                    'c.visible',
                    'c.audiencevisible',
                    'c'
                );

                $joining->where_raw('c.id = ru.item_id')
                    ->where('ru.component', $container_type)
                    ->where_raw('(c.containertype = ru.component OR c.containertype IS NULL)')
                    ->where_raw($totara_visibility_sql, $totara_visibility_params);
            });

            $builder->where('ru.user_id', $user_id);
            $builder->order_by_raw('ru.time_created DESC');
        }


        // Visibility rules require a join to the context table
        $builder->left_join(
            ['context', 'ctx'],
            function (builder $builder): void {
                $builder->where_field('c.id', 'ctx.instanceid');
                $builder->where('ctx.contextlevel', CONTEXT_COURSE);
            }
        );

        // We have to exclude courses/workspaces already enrolled in/joined
        $sub_query = builder::table('course', 'c2');
        $sub_query->select('c2.id');
        $sub_query->join(['enrol', 'e'], 'c2.id', 'e.courseid');
        $sub_query->join(['user_enrolments', 'ue'], 'e.id', 'ue.enrolid');
        $sub_query->where('ue.userid', $user_id);
        $sub_query->where('ue.status', ENROL_USER_ACTIVE);

        $table = new table($sub_query);
        $table->as('jc');

        $builder->left_join($table, 'c.id', 'jc.id');
        $builder->where_null('jc.id');

        $builder->limit($max_count);

        return $builder;
    }

    /**
     * @return builder
     */
    private static function get_base_builder(): builder {
        $builder = builder::table('ml_recommender_users', 'ru');
        $builder->select([
            'ru.unique_id',
            'ru.item_id',
            'ru.component',
            'ru.area',
            'ru.seen',
        ]);

        return $builder;
    }
}