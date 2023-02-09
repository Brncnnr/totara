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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\basket;

use external_function_parameters;
use external_multiple_structure;
use external_value;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * DEPRECATED
 *
 * @deprecated since Totara 13
 */
class external extends \external_api {

    /**
     * @return external_function_parameters
     *
     * @deprecated since Totara 13
     */
    public static function show_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'basket' => new external_value(PARAM_ALPHANUMEXT, 'key of the basket', VALUE_REQUIRED),
            ]
        );
    }

    /**
     * @param string $basket_key
     * @return array
     *
     * @deprecated since Totara 13
     */
    public static function show(string $basket_key) {
        $basket = new session_basket($basket_key);
        return [
            'ids' => $basket->load(),
            'limit' => $basket->get_limit()
        ];
    }

    /**
     * @return null
     *
     * @deprecated since Totara 13
     */
    public static function show_returns() {
        return null;
    }

    /**
     * @return external_function_parameters
     *
     * @deprecated since Totara 13
     */
    public static function update_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'basket' => new external_value(PARAM_ALPHANUMEXT, 'key of the basket', VALUE_REQUIRED),
                'action' => new external_value(PARAM_ALPHANUM, 'action: add, remove or replace', VALUE_REQUIRED),
                'ids' => new external_multiple_structure(new external_value(PARAM_INT),'affected ids', VALUE_REQUIRED)
            ]
        );
    }

    /**
     * @param string $basket_key
     * @param string $action
     * @param array $ids
     * @return array
     *
     * @deprecated since Totara 13
     */
    public static function update(string $basket_key, string $action, array $ids) {
        $basket = new session_basket($basket_key);
        switch ($action) {
            case 'add':
                $basket->add($ids);
                break;
            case 'remove':
                $basket->remove($ids);
                break;
            case 'replace':
                $basket->replace($ids);
                break;
            default:
                throw new \coding_exception('Invalid action given for updating the basket');
                break;
        }
        return [
            'ids' => $basket->load(),
            'limit' => $basket->get_limit()
        ];
    }

    /**
     * @return null
     *
     * @deprecated since Totara 13
     */
    public static function update_returns() {
        return null;
    }

    /**
     * @return external_function_parameters
     *
     * @deprecated since Totara 13
     */
    public static function delete_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'basket' => new external_value(PARAM_ALPHANUMEXT, 'key of the basked', VALUE_REQUIRED),
            ]
        );
    }

    /**
     * @param string $basket_key
     * @return array
     *
     * @deprecated since Totara 13
     */
    public static function delete(string $basket_key) {
        $basket = new session_basket($basket_key);
        $basket->delete();
        return [
            'ids' => [],
            'limit' => $basket->get_limit()
        ];
    }

    /**
     * @return null
     *
     * @deprecated since Totara 13
     */
    public static function delete_returns() {
        return null;
    }

    /**
     * @return external_function_parameters
     *
     * @deprecated since Totara 13
     */
    public static function copy_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'sourcebasket' => new external_value(PARAM_ALPHANUMEXT, 'key of the basket', VALUE_REQUIRED),
                'targetbasket' => new external_value(PARAM_ALPHANUMEXT, 'key of the basket', VALUE_REQUIRED),
                'options' => new \external_single_structure(
                    [
                        'replace' => new external_value(PARAM_BOOL, 'false = add items, true = replace items', VALUE_OPTIONAL, 0),
                        'deletesource' => new external_value(PARAM_BOOL, 'true = for deleting the source basket', VALUE_OPTIONAL, 0)
                    ],
                    'Additional options',
                    VALUE_REQUIRED
                )
            ]
        );
    }

    /**
     * @param string $source_basket_key
     * @param string $target_basket_key
     * @param array $options
     * @return array
     *
     * @deprecated since Totara 13
     */
    public static function copy(string $source_basket_key, string $target_basket_key, array $options) {
        $source_basket = new session_basket($source_basket_key);
        $target_basket = new session_basket($target_basket_key);
        if (isset($options['replace']) && $options['replace'] === true) {
            $target_basket->replace($source_basket->load());
        } else {
            $target_basket->add($source_basket->load());
        }

        if (isset($options['deletesource']) && $options['deletesource'] === true) {
            $source_basket->delete();
        }

        return [
            'ids' => $target_basket->load(),
            'limit' => $target_basket->get_limit()
        ];
    }

    /**
     * @return null
     *
     * @deprecated since Totara 13
     */
    public static function copy_returns() {
        return null;
    }

}