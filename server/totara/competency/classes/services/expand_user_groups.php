<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\services;


use context_system;
use external_function_parameters;
use external_single_structure;
use external_value;
use totara_competency\expanded_users;
use totara_core\advanced_feature;
use totara_core\basket\session_basket;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

class expand_user_groups extends \external_api {

    /**
     * @return external_function_parameters
     */
    public static function index_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'baskets' => new external_single_structure(
                    [
                        'cohort' => new external_value(PARAM_ALPHANUMEXT, 'id of basket', VALUE_OPTIONAL, null),
                        'user' => new external_value(PARAM_ALPHANUMEXT, 'id of basket', VALUE_OPTIONAL, null),
                        'position' => new external_value(PARAM_ALPHANUMEXT, 'id of basket', VALUE_OPTIONAL, null),
                        'organisation' => new external_value(PARAM_ALPHANUMEXT, 'id of basket', VALUE_OPTIONAL, null),
                    ],
                    VALUE_REQUIRED
                ),
                'filters' => new external_single_structure(
                    [
                        'name' => new external_value(PARAM_TEXT, 'Search by username, full name ', VALUE_OPTIONAL, null),
                    ],
                    VALUE_REQUIRED
                ),
                'page' => new external_value(PARAM_INT, 'Pagination: page to load', VALUE_REQUIRED),
                'order' => new external_value(PARAM_ALPHANUMEXT, 'Name of column to order by - not used currently', VALUE_REQUIRED),
                'direction' => new external_value(PARAM_ALPHA, 'either ASC or DESC - not used currently', VALUE_REQUIRED),
            ]
        );
    }

    /**
     * List user for the picker
     *
     * @param array $baskets
     * @param array $filters
     * @param int $page
     * @param string $order
     * @param string $direction
     * @return array
     */
    public static function index(array $baskets, array $filters, int $page, string $order, string $direction) {
        advanced_feature::require('competency_assignment');
        require_capability('totara/competency:manage_assignments', context_system::instance());
        require_capability('moodle/user:viewdetails', context_system::instance());

        $user_ids = [];
        if (!empty($baskets)) {
            foreach ($baskets as $type => $key) {
                $can_view = false;

                switch ($type) {
                    case 'cohort':
                        $can_view = has_any_capability(['moodle/cohort:manage', 'moodle/cohort:view'], context_system::instance());
                        break;
                    case 'user':
                        // Viewing user details is essential for this already
                        $can_view = true;
                        break;
                    case 'organisation':
                        $can_view = has_capability('totara/hierarchy:vieworganisation', context_system::instance());
                        break;
                    case 'position':
                        $can_view = has_capability('totara/hierarchy:viewposition', context_system::instance());
                        break;
                }

                $user_ids[$type] = $can_view ? session_basket::fetch($key) : [];
            }
        }

        return (new expanded_users())
            ->set_audience_ids($user_ids['cohort'] ?? [])
            ->set_user_ids($user_ids['user'] ?? [])
            ->set_organisation_ids($user_ids['organisation'] ?? [])
            ->set_position_ids($user_ids['position'] ?? [])
            ->filter_by_name($filters['name'] ?? '')
            ->fetch_paginated($page)
            ->transform(function (array $item) {
                $user_name_fields = totara_get_all_user_name_fields();
                $user = new \stdClass();
                foreach ($user_name_fields as $field) {
                    $user->$field = isset($item[$field]) ? $item[$field] : '';
                }

                // Format strings and convert user_group_names to format usable by mustache
                return [
                    'user_id' => $item['user_id'],
                    'full_name' => format_string(fullname($user)),
                    'user_group_names' => array_map(
                        function ($item) {
                            return ['user_group_name' => format_string($item)];
                        },
                        $item['user_group_names']
                    )
                ];
            })->to_array();
    }

    /**
     * @return null
     */
    public static function index_returns() {
        return null;
    }
}
