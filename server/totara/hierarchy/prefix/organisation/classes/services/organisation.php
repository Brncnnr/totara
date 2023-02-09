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
use core\format;
use core\orm\paginator;
use core\orm\query\builder;
use core\webapi\formatter\field\string_field_formatter;
use core\webapi\formatter\field\text_field_formatter;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use hierarchy_organisation\entity\organisation as organisation_entity;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

class organisation extends \external_api {

    /**
     * @return external_function_parameters
     */
    public static function index_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'filters' => new external_single_structure(
                    [
                        'text' => new external_value(PARAM_TEXT, 'Text search field value', VALUE_OPTIONAL, null),
                        'framework' => new external_value(PARAM_INT, 'Organisation framework id', VALUE_OPTIONAL, null),
                        'parent' => new external_value(PARAM_INT, 'Filter children (one level only)', VALUE_OPTIONAL, null),
                        'path' => new external_value(PARAM_INT, 'Filter children (all levels below)', VALUE_OPTIONAL, null),
                        'basket' => new external_value(PARAM_ALPHANUMEXT, 'Search by basket key', VALUE_OPTIONAL, null),
                        'visible' => new external_value(PARAM_BOOL, 'Filter by visible', VALUE_OPTIONAL, true),
                        'type' => new external_multiple_structure(
                            new external_value(PARAM_INT, 'id of the type', VALUE_OPTIONAL),
                            'one or more organisation type ids',
                            VALUE_OPTIONAL
                        ),
                        'ids' => new external_multiple_structure(
                            new external_value(PARAM_INT, 'ids', VALUE_OPTIONAL),
                            'ids to filter by',
                            VALUE_OPTIONAL
                        ),
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
        advanced_feature::require('organisations');

        if (!has_capability('totara/hierarchy:vieworganisation', context_system::instance())
            || !has_capability('totara/hierarchy:vieworganisationframeworks', context_system::instance())
        ) {
            return paginator::new(builder::table(organisation_entity::TABLE)->where_raw('1 = 0'))->to_array();
        }

        if (!array_key_exists('visible', $filters)) {
            $filters['visible'] = true;
        }

        return organisation_entity::repository()
            ->reset_select()
            ->select_only_fields_for_picker()
            ->with_children_count()
            ->set_filters($filters)
            ->order_by($order, $direction)
            ->paginate($page)
            ->transform(function (organisation_entity $item) {
                $name = format_string($item->fullname);
                return [
                    'id' => $item->id,
                    'fullname' => $name,
                    'display_name' => $name,
                    'idnumber' => $item->idnumber,
                    'crumbtrail' => $item->crumbtrail
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
                            'id' => new external_value(PARAM_INT, 'org id'),
                            'display_name' => new external_value(PARAM_TEXT, 'org display name'),
                            'fullname'  => new external_value(PARAM_TEXT, 'org fullname'),
                            'idnumber' => new external_value(PARAM_TEXT, 'org idnumber'),
                            'crumbtrail' => new external_multiple_structure(
                                new external_single_structure(
                                    [
                                        'id' => new external_value(PARAM_INT, 'id'),
                                        'name' => new external_value(PARAM_TEXT, 'name'),
                                        'parent_id' => new external_value(PARAM_INT, 'parent_id'),
                                        'type' => new external_value(PARAM_INT, 'type'),
                                        'active' => new external_value(PARAM_INT, 'active'),
                                        'first' => new external_value(PARAM_INT, 'first'),
                                        'last' => new external_value(PARAM_INT, 'last')
                                    ],
                                    'organisations'
                                )
                            )
                        ],
                        'organisations'
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

    /**
     * @return external_function_parameters
     */
    public static function show_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'id' => new external_value(PARAM_INT, 'id', VALUE_REQUIRED),
                'include' => new external_single_structure(
                    [
                        'crumbs' => new external_value(PARAM_INT, 'with_crumbtrail', VALUE_OPTIONAL, 0),
                    ],
                    'options'
                )
            ]
        );
    }

    /**
     * Returns one entity
     *
     * @param int $id
     * @param array $options
     * @return array
     */
    public static function show(int $id, array $options) {
        advanced_feature::require('organisations');
        $context = context_system::instance();

        require_capability('totara/hierarchy:vieworganisation', $context);

        /** @var organisation_entity $item */
        $item = organisation_entity::repository()->find($id);
        if (empty($item)) {
            return [];
        }

        $string_formatter = new string_field_formatter(format::FORMAT_HTML, $context);
        $text_formatter = (new text_field_formatter(format::FORMAT_HTML, $context))
            ->set_pluginfile_url_options($context, 'totara_hierarchy', 'org', $id);

        $organisation = [
            'id' => $item->id,
            'fullname' => $string_formatter->format($item->fullname),
            'idnumber' => $item->idnumber,
            'description' => $text_formatter->format($item->description),
            'shortname' => $string_formatter->format($item->shortname),
            'visible' => $item->visible,
            'frameworkid' => $item->frameworkid
        ];

        if (!empty($options['crumbs'])) {
            $organisation['crumbtrail'] = $item->crumbtrail;
        }

        return $organisation;
    }

    /**
     * @return external_single_structure
     */
    public static function show_returns() {
        // Note: the fields are all optional here because the expected response
        // to retrieving non existent records is to return an empty result set.
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'org id', VALUE_OPTIONAL),
                'fullname' => new external_value(PARAM_TEXT, 'org fullname', VALUE_OPTIONAL),
                'idnumber' => new external_value(PARAM_TEXT, 'org idnumber', VALUE_OPTIONAL),
                'description' => new external_value(PARAM_RAW, 'org description', VALUE_OPTIONAL),
                'shortname' => new external_value(PARAM_TEXT, 'org shortname', VALUE_OPTIONAL),
                'visible' => new external_value(PARAM_INT, 'visible', VALUE_OPTIONAL),
                'frameworkid' => new external_value(PARAM_INT, 'org framework id', VALUE_OPTIONAL),
                'crumbtrail' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'id'),
                            'name' => new external_value(PARAM_TEXT, 'name'),
                            'parent_id' => new external_value(PARAM_INT, 'parent_id'),
                            'type' => new external_value(PARAM_TEXT, 'type'),
                            'active' => new external_value(PARAM_BOOL, 'active'),
                            'first' => new external_value(PARAM_BOOL, 'first'),
                            'last' => new external_value(PARAM_BOOL, 'last')
                        ],
                        'organisations',
                        VALUE_OPTIONAL
                    ),
                    'organisations',
                    VALUE_OPTIONAL
                )
            ],
            'item'
        );
    }
}