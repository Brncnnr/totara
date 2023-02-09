<?php
/*
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_hierarchy
 */

namespace totara_hierarchy\webapi\resolver\query;

use coding_exception;
use context_system;
use context_user;
use core\entity\user;

use core\pagination\cursor;
use core\webapi\execution_context;
use core\webapi\query_resolver;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_login;

use hierarchy_goal\personal_goal_assignment_type;
use hierarchy_goal\data_providers\goal_data_provider;
use hierarchy_goal\data_providers\personal_goals as personal_goals_provider;
use required_capability_exception;
use totara_core\hook\component_access_check;

/**
 * Handles the "totara_hierarchy_personal_goals" GraphQL query.
 */
class personal_goals extends query_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        $input = $args['input'] ?? [];
        // process filter data
        $filters = self::process_filters($input);
        // authorize
        $logged_on_user_id = user::logged_in()->id;
        $target_user_id = $filters['user_id'];
        self::authorize($logged_on_user_id, $target_user_id);

        // process the pagination
        $pagination = self::process_pagination($input);

        return self::create_ordered_provider($input)
            ->set_page_size($pagination['result_size'])
            ->set_filters($filters)
            ->fetch_paginated($pagination['cursor']);
    }

    /**
     * process the pagination
     *
     * @param array|null $input
     * @return array
     */
    private static function process_pagination(?array $input = null): array {
        return [
            'result_size' => $input['result_size'] ?? goal_data_provider::DEFAULT_PAGE_SIZE,
            'cursor' => empty($input['cursor']) ? null : cursor::decode($input['cursor']),
        ];
    }

    /**
     * Creates an ordered data provider.
     *
     * @param array|null $input external runtime arguments.
     *
     * @return goal_data_provider the ordered provider.
     *
     * @throws coding_exception
     */
    private static function create_ordered_provider(
        array $input
    ): goal_data_provider {
        $raw_sort_by_name = personal_goals_provider::SORT_GOAL_NAME;
        $raw_sort_by_goal_id = personal_goals_provider::SORT_GOAL_ID;
        $sorting_columns = personal_goals_provider::SORT_FIELDS;

        $raw_order_by = strtolower($input['order_by'] ?? $raw_sort_by_name);
        $order_by = $sorting_columns[$raw_order_by] ?? null;
        if (!$order_by) {
            throw new coding_exception("unknown sort order: $raw_order_by");
        }

        $provider = personal_goals_provider::create()
            ->set_order($order_by, $input['order_dir'] ?? 'ASC');

        // If the primary ordering is by target date, then it also has to be
        // sorted by name because there can be goals with the same target dates.
        $provider = $raw_order_by === personal_goals_provider::SORT_TARGET_DATE
            ? $provider->add_order($sorting_columns[$raw_sort_by_name], 'ASC')
            : $provider;

        // Finally, if the sorting is not already by goal id, then it needs to be
        // sorted by id as well for a consistent set of sorted results everytime,
        // especially in tests.
        return $raw_order_by === personal_goals_provider::SORT_GOAL_ID
            ? $provider
            : $provider->add_order($sorting_columns[$raw_sort_by_goal_id], 'ASC');


    }

    /**
     * process filters
     *
     * @param array|null $input
     * @return array
     * @throws coding_exception
     */
    private static function process_filters(?array $input = null): array {
        $result = [];
        if (!empty($input['filters'])) {
            $result = $input['filters'];
        }

        $result['user_id'] = $result['user_id'] ?? user::logged_in()->id;
        $result['deleted'] = $result['deleted'] ?? false;
        if (!empty($result['assignment_type'])) {
            $result['assignment_type'] = personal_goal_assignment_type::by_name($result['assignment_type'])->get_value();
        }

        return $result;
    }

    /**
     * Checks the user's authorization.
     *
     * @param int $logged_on_user_id currently logged on user.
     * @param int $target_user_id user whose goals are to be retrieved.
     * @throws \dml_exception
     * @throws coding_exception
     * @throws required_capability_exception
     */
    private static function authorize(int $logged_on_user_id, int $target_user_id): void {
        if (has_capability('totara/hierarchy:viewallgoals', context_system::instance())) {
            return;
        }

        if ($logged_on_user_id === $target_user_id) {
            $context = context_user::instance($logged_on_user_id);
            $capability = 'totara/hierarchy:viewownpersonalgoal';
        } else {
            $context = context_user::instance($target_user_id);
            $capability = 'totara/hierarchy:viewstaffpersonalgoal';
        }
        if (!has_capability($capability, $context)) {
            $hook = new component_access_check(
                'hierarchy_goal',
                $logged_on_user_id,
                $target_user_id,
                ['content_type' => 'personal_goal']
            );
            if (!$hook->execute()->has_permission()) {
                throw new required_capability_exception($context, $capability, 'nopermissions', '');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_advanced_feature('goals'),
            new require_login()
        ];
    }
}
