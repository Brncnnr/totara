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
use core\entity\cohort as cohort_entity;
use core\orm\paginator;
use core\orm\query\builder;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

class cohort extends \external_api {

    /**
     * @return external_function_parameters
     */
    public static function index_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'filters' => new external_single_structure(
                    [
                        'text' => new external_value(PARAM_TEXT, 'Search by audience name', VALUE_OPTIONAL, null),
                        'visible' => new external_value(PARAM_BOOL, 'Visibility of the items', VALUE_OPTIONAL, true),
                        'basket' => new external_value(PARAM_ALPHANUMEXT, 'selection in the basket', VALUE_OPTIONAL, null),
                        'ids' => new external_multiple_structure(
                            new external_value(PARAM_INT, 'ids', VALUE_OPTIONAL),
                            'ids to filter by',
                            VALUE_OPTIONAL
                        ),
                    ],
                    VALUE_REQUIRED
                ),
                'page' => new external_value(PARAM_INT, 'pagination: page to load', VALUE_REQUIRED),
                'order' => new external_value(PARAM_ALPHANUMEXT, 'name of column to order by', VALUE_REQUIRED),
                'direction' => new external_value(PARAM_ALPHA, 'direction of ordering (either ASC or DESC)', VALUE_REQUIRED),
            ]
        );
    }

    /**
     * @param array $filters
     * @param int $page
     * @param string $order
     * @param string $direction
     * @return array
     */
    public static function index(array $filters, int $page, string $order, string $direction) {
        advanced_feature::require('competency_assignment');
        require_capability('totara/competency:manage_assignments', context_system::instance());

        if (!has_any_capability(['moodle/cohort:manage', 'moodle/cohort:view'], context_system::instance())) {
            return paginator::new(builder::table(cohort_entity::TABLE)->where_raw('1 = 0'))->to_array();
        }

        if (!array_key_exists('visible', $filters)) {
            $filters['visible'] = true;
        }

        $allowed_order_columns = [
            'id',
            'name',
            'idnumber',
            'timecreated',
            'timemodified',
        ];
        if (!in_array($order, $allowed_order_columns)) {
            $order = 'id';
        }

        return cohort_entity::repository()
            ->select_only_fields_for_picker()
            ->set_filters($filters)
            // At the moment we only load the system audiences
            ->where('contextid', SYSCONTEXTID)
            ->order_by($order, $direction)
            ->paginate($page)
            ->transform(function (cohort_entity $item) {
                return [
                    'id' => $item->id,
                    'display_name' => format_string($item->name),
                    'idnumber' => $item->idnumber,
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