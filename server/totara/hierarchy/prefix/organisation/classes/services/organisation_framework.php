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
 * @package hierarchy_organisation
 */

namespace hierarchy_organisation\services;

use context_system;
use core\orm\paginator;
use core\orm\query\builder;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use hierarchy_organisation\entity\organisation_framework as organisation_framework_entity;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

class organisation_framework extends \external_api {

    /**
     * @return external_function_parameters
     */
    public static function index_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'filters' => new external_single_structure(
                    [
                        'text' => new external_value(PARAM_TEXT, 'Text search field value', VALUE_OPTIONAL, null),
                        'visible' => new external_value(PARAM_BOOL, 'Filter by visible', VALUE_OPTIONAL, true),
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
     * @param array $filters
     * @param int $page
     * @param string $order
     * @param string $direction
     * @return array
     */
    public static function index(array $filters, int $page, string $order, string $direction) {
        if (!has_capability('totara/hierarchy:vieworganisationframeworks', context_system::instance())) {
            return paginator::new(builder::table(organisation_framework_entity::TABLE)->where_raw('1 = 0'))->to_array();
        }

        if (!array_key_exists('visible', $filters)) {
            $filters['visible'] = true;
        }

        return organisation_framework_entity::repository()
            ->set_filters($filters)
            ->order_by($order, $direction)
            ->paginate($page)
            ->transform(function (organisation_framework_entity $item) {
                $fullname = format_string($item->fullname);
                return [
                    'id' => $item->id,
                    'display_name' => $fullname,
                    'fullname' => $fullname
                ];
            })->to_array();
    }

    /**
     * @return external_single_structure
     */
    public static function index_returns() {
        return new external_single_structure(
            [
                'items' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'org framework id'),
                            'display_name' => new external_value(PARAM_TEXT, 'org framework display name'),
                            'fullname'  => new external_value(PARAM_TEXT, 'org framework fullname name')
                        ],
                        'org frameworks'
                    )
                ),
                'page' => new external_value(PARAM_INT, 'current page no'),
                'pages' => new external_value(PARAM_INT, 'total no of pages'),
                'per_page' => new external_value(PARAM_INT, 'orgs per page'),
                'next' => new external_value(PARAM_TEXT, 'next page cursor'),
                'prev' => new external_value(PARAM_TEXT, 'previous page cursor'),
                'total' => new external_value(PARAM_INT, 'total no of orgs')
            ],
            'items'
        );
    }
}