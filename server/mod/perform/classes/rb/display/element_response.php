<?php
/*
 * This file is part of Totara Perform
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\rb\display;

use core\format;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\formatter\response\element_response_formatter;
use mod_perform\models\activity\element;
use mod_perform\models\activity\helpers\displays_responses;
use mod_perform\models\activity\element_plugin;
use rb_column;
use rb_column_option;
use reportbuilder;
use stdClass;
use totara_reportbuilder\rb\display\base;

class element_response extends base {

    /**
     * Handles the display
     *
     * @param string $response_data
     * @param string $format
     * @param stdClass $row
     * @param rb_column $column
     * @param reportbuilder $report
     * @return string
     */
    public static function display($response_data, $format, stdClass $row, rb_column $column, reportbuilder $report) {
        if ($response_data === null) {
            return '';
        }

        $extrafields = self::get_extrafields_row($row, $column);

        $element = self::get_element_for_row($extrafields);
        $element_plugin = $element->get_element_plugin();

        if (!$element_plugin instanceof displays_responses) {
            // Nothing to display!
            return '';
        }

        if ($format === 'html') {
            $output_format = format::FORMAT_HTML;
        } else {
            $output_format = format::FORMAT_PLAIN;
        }

        // Convert response data into actual answer.
        $formatted_response_data = element_response_formatter::get_instance($element, $output_format)
            ->set_response_id($extrafields->response_id)
            ->set_element($element)
            ->format($response_data);
        $response = $element_plugin->decode_response($formatted_response_data, $element->data);

        if (is_array($response)) {
            $response = implode(', ', $response);
        } else if (is_string($response)) {
            $response = trim($response);
        }

        return $response;
    }

    /**
     * Is this column graphable?
     *
     * @param rb_column $column
     * @param rb_column_option $option
     * @param reportbuilder $report
     * @return bool
     */
    public static function is_graphable(rb_column $column, rb_column_option $option, reportbuilder $report) {
        return false;
    }

    /**
     * Get element for given row, either by the default fields passed in or overwritten by the plugin of the current element
     *
     * @param stdClass $extra_fields
     * @return element
     */
    private static function get_element_for_row(stdClass $extra_fields): element {
        $element_fields = self::get_element_fields($extra_fields);

        return element::load_by_entity(new element_entity($element_fields));
    }

    /**
     * A plugin can override the element fields used here, it needs to provide the extra fields
     * matching the keys of the element fields above but with the plugin name prefixed,
     * i.e. linked_review_id, linked_review_plugin_name, linked_review_data, etc.
     *
     * @param stdClass $extra_fields
     * @return array
     */
    private static function get_element_fields(stdClass $extra_fields): array {
        $element_fields = [
            'id' => $extra_fields->element_id,
            'context_id' => $extra_fields->element_context_id,
            'plugin_name' => $extra_fields->element_type,
            'data' => $extra_fields->element_data,
        ];

        $plugin = element_plugin::load_by_plugin($extra_fields->element_type);
        if ($helper = $plugin->get_response_report_builder_helper()) {
            if ($plugin_extra_fields = $helper->get_response_extra_fields()) {
                foreach ($element_fields as $key => $value) {
                    $search_key = $plugin->get_plugin_name() . '_' . $key;
                    if (array_key_exists($search_key, $plugin_extra_fields)
                        && isset($extra_fields->{$search_key})
                    ) {
                        $element_fields[$key] = $extra_fields->{$search_key};
                    }
                }
            }
        }

        return $element_fields;
    }

}
