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
 * @package core
 */

namespace totara_competency\services;

use context_system;
use context_user;
use core\entity\user as user_entity;
use core\orm\entity\repository;
use core\orm\query\field;
use core\tenant_orm_helper;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

class user extends \external_api {

    /**
     * @return external_function_parameters
     */
    public static function index_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'filters' => new external_single_structure(
                    [
                        'text' => new external_value(PARAM_TEXT, 'Search by username, full name ', VALUE_OPTIONAL, null),
                        'basket' => new external_value(PARAM_ALPHANUMEXT, 'Search by basket key', VALUE_OPTIONAL, null),
                        'ids' => new external_multiple_structure(
                            new external_value(PARAM_INT, 'ids', VALUE_OPTIONAL),
                            'ids to filter by',
                            VALUE_OPTIONAL
                        ),
                    ],
                    VALUE_REQUIRED
                ),
                'page' => new external_value(PARAM_INT, 'pagination: page to load', VALUE_REQUIRED),
                'order' => new external_value(PARAM_ALPHANUMEXT, 'Name of column to order by - not used currently', VALUE_REQUIRED),
                'direction' => new external_value(PARAM_ALPHA, 'either ASC or DESC - not used currently', VALUE_REQUIRED),
            ]
        );
    }

    /**
     * List user for the picker
     *
     * @param array $filters
     * @param int $page
     * @param string $order
     * @param string $direction
     * @return array
     */
    public static function index(array $filters, int $page, string $order, string $direction) {
        advanced_feature::require('competency_assignment');
        require_capability('totara/competency:manage_assignments', context_system::instance());
        require_capability('moodle/user:viewdetails', context_system::instance());

        global $PAGE;
        $context = context_system::instance();
        $PAGE->set_context($context);

        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = 'desc';
        }

        // Force ordering by name at this stage. Attribute reserved for uniformity and future use
        $name_order = totara_get_all_user_name_fields(true, '', null, null, true);
        $order = "{$name_order} {$direction}, id asc";

        return user_entity::repository()
            ->select_full_name_fields()
            ->filter_by_not_deleted()
            ->filter_by_not_guest()
            ->set_filters($filters)
            ->order_by_raw($order)
            ->paginate($page)
            ->transform(function (user_entity $item) {
                $user_name_fields = totara_get_all_user_name_fields();
                $user = new \stdClass();
                foreach ($user_name_fields as $field) {
                    $user->$field = isset($item->$field) ? $item->$field : '';
                }

                return [
                    'id' => $item->id,
                    'display_name' => format_string(fullname($user))
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
