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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\webapi\resolver\query;

use Exception;
use coding_exception;
use context_user;
use core\entity\user;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use moodle_exception;
use totara_competency\data_providers\assignments;
use totara_competency\entity\assignment as assignment_entity;
use totara_competency\helpers\capability_helper;
use totara_competency\models\assignment as assignment_model;
use totara_competency\models\profile\filter;
use totara_competency\performelement_linked_review\competency_assignment;
use totara_core\advanced_feature;
use totara_core\hook\component_access_check;

class user_assignments extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        // Set current user as default if there is no user_id provided
        $user_id = $args['input']['user_id'] ?? user::logged_in()->id;

        try {
            // Require those features being enabled but return an empty result if not
            advanced_feature::require('competencies');
            advanced_feature::require('competency_assignment');
            // In case the user does not have permission to view we return an empty result to not fail requests
            self::require_view_capability($user_id, $ec);
        } catch (Exception $e) {
            return [
                'items' => [],
                'filters' => [],
                'total' => 0,
                'next_cursor' => '',
            ];
        }

        $status_filter = ['status' => assignment_entity::STATUS_ACTIVE];
        $query_filters = array_merge($status_filter, $args['input']['filters'] ?? []);

        // Get competency assignments
        $result = assignments::for($user_id)
            ->set_filters($query_filters)
            ->fetch_paginated($args['input']['cursor'] ?? null, $args['input']['result_size'] ?? null)
            ->transform(static function (assignment_entity $assignment) {
                return assignment_model::load_by_entity($assignment);
            })
            ->get();

        // Get assignment filter list
        $assignments = assignments::for($user_id)
            ->set_filters($status_filter)
            ->fetch();

        $result['filters'] = filter::build_from_assignments_provider($assignments);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
        ];
    }

    /**
     * Require view capability
     *
     * @param int $user_id
     * @param execution_context $ec
     * @throws coding_exception
     * @throws moodle_exception
     */
    private static function require_view_capability(int $user_id, execution_context $ec) {
        $context = context_user::instance($user_id);
        $ec->set_relevant_context($context);

        try {
            capability_helper::require_can_view_profile($user_id, $context);
        } catch (moodle_exception $e) {
            $hook = new component_access_check(
                'totara_competency',
                user::logged_in()->id,
                $user_id,
                []
            );

            if (!$hook->execute()->has_permission()) {
                throw $e;
            }
        }
    }

}
