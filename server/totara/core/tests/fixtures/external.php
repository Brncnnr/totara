<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * These external functions are only used to facilitate using the test pages for lists.
 * They provide fake data so that the test pages can be tested by behat or by developers to see a working example.
 * They won't work for production. They also only provide static fake data.
 */
class totara_core_tests_fixtures_external extends \external_api {

    /**
     * @return external_function_parameters
     */
    public static function test_list_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'filters' => new external_single_structure([], VALUE_REQUIRED),
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
    public static function test_list(array $filters, int $page, string $order, string $direction) {
        require_capability('moodle/site:config', \context_system::instance());

        if ((!defined('BEHAT_SITE_RUNNING') || !BEHAT_SITE_RUNNING) && !debugging()) {
            throw new coding_exception('Invalid access detected.');
        }

        $items = [
            [
                'id' => 11,
                'column1' => 'a11',
                'column2' => 'b11',
                'column3' => 'c11',
                'column4' => 'd11',
            ], [
                'id' => 12,
                'column1' => 'a12',
                'column2' => 'b12',
                'column3' => 'c12',
                'column4' => 'd12',
            ], [
                'id' => 13,
                'column1' => 'a13',
                'column2' => 'b13',
                'column3' => 'c13',
                'column4' => 'd13',
            ], [
                'id' => 14,
                'column1' => 'a14',
                'column2' => 'b14',
                'column3' => 'c14',
                'column4' => 'd14',
            ], [
                'id' => 15,
                'column1' => 'a15',
                'column2' => 'b15',
                'column3' => 'c15',
                'column4' => 'd15',
            ], [
                'id' => 21,
                'column1' => 'a21',
                'column2' => 'b21',
                'column3' => 'c21',
                'column4' => 'd21',
            ], [
                'id' => 22,
                'column1' => 'a22',
                'column2' => 'b22',
                'column3' => 'c22',
                'column4' => 'd22',
            ], [
                'id' => 23,
                'column1' => 'a23',
                'column2' => 'b23',
                'column3' => 'c23',
                'column4' => 'd23',
            ], [
                'id' => 24,
                'column1' => 'a24',
                'column2' => 'b24',
                'column3' => 'c24',
                'column4' => 'd24',
            ], [
                'id' => 25,
                'column1' => 'a25',
                'column2' => 'b25',
                'column3' => 'c25',
                'column4' => 'd25',
            ], [
                'id' => 31,
                'column1' => 'a31',
                'column2' => 'b31',
                'column3' => 'c31',
                'column4' => 'd31',
            ], [
                'id' => 32,
                'column1' => 'a32',
                'column2' => 'b32',
                'column3' => 'c32',
                'column4' => 'd32',
            ], [
                'id' => 33,
                'column1' => 'a33',
                'column2' => 'b33',
                'column3' => 'c33',
                'column4' => 'd33',
            ], [
                'id' => 34,
                'column1' => 'a34',
                'column2' => 'b34',
                'column3' => 'c34',
                'column4' => 'd34',
            ], [
                'id' => 35,
                'column1' => 'a35',
                'column2' => 'b35',
                'column3' => 'c35',
                'column4' => 'd35',
            ], [
                'id' => 41,
                'column1' => 'a41',
                'column2' => 'b41',
                'column3' => 'c41',
                'column4' => 'd41',
            ], [
                'id' => 42,
                'column1' => 'a42',
                'column2' => 'b42',
                'column3' => 'c42',
                'column4' => 'd42',
            ], [
                'id' => 43,
                'column1' => 'a43',
                'column2' => 'b43',
                'column3' => 'c43',
                'column4' => 'd43',
            ], [
                'id' => 44,
                'column1' => 'a44',
                'column2' => 'b44',
                'column3' => 'c44',
                'column4' => 'd44',
            ], [
                'id' => 45,
                'column1' => 'a45',
                'column2' => 'b45',
                'column3' => 'c45',
                'column4' => 'd45',
            ],
        ];

        if (!empty($order)) {
            usort($items, function ($a, $b) use ($order, $direction) {
                if ($order == 'column1_desc') {
                    return strcmp($b['column1'], $a['column1']);
                } else {
                    return strcmp($a['column1'], $b['column1']);
                }
            });
        }

        $total = count($items);

        if ($page === 0) {
            return [
                'items' => $items,
                'page' => 1,
                'pages' => 1,
                'items_per_page' => $total,
                'next' => null,
                'prev' => null,
                'total' => $total
            ];
        } else {
            $per_page = 5;
            $pages = ceil($total / $per_page);
            $next = $page + 1 > $pages ? null : $page + 1;
            $prev = $page - 1 <= 0 ? null : $page - 1;

            return [
                'items' => array_chunk($items, 5)[$page - 1],
                'page' => $page,
                'pages' => $pages,
                'items_per_page' => $per_page,
                'next' => $next,
                'prev' => $prev,
                'total' => $total
            ];
        }
    }

    /**
     * @return null
     */
    public static function test_list_returns() {
        return null;
    }

    /**
     * @return external_function_parameters
     */
    public static function test_show_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'id' => new external_value(PARAM_INT, 'id of the row', VALUE_REQUIRED),
            ]
        );
    }

    /**
     * @param int $id
     * @return array
     */
    public static function test_show(int $id) {
        require_capability('moodle/site:config', \context_system::instance());

        if ((!defined('BEHAT_SITE_RUNNING') || !BEHAT_SITE_RUNNING) && !debugging()) {
            throw new coding_exception('Invalid access detected.');
        }

        return [
            'id' => $id
        ];
    }

    /**
     * @return null
     */
    public static function test_show_returns() {
        return null;
    }

}