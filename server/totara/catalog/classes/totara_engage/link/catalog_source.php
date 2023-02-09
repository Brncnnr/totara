<?php
/**
 * This file is part of Totara Learn
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\totara_engage\link;

use totara_catalog\local\filter_handler;
use totara_engage\link\source_generator;

/**
 * Tracking clicks from the catalog
 *
 * @package totara_catalog\totara_engage\link
 */
final class catalog_source extends source_generator {
    /**
     * @return string
     */
    public static function get_source_key(): string {
        return 'ct';
    }

    /**
     * @param array $source_params
     * @return array
     */
    public static function convert_source_to_attributes(array $source_params): array {
        $filter_handler = filter_handler::instance();

        // First element should always be an empty string.
        $query_string = reset($source_params);
        $query_string = urldecode($query_string);

        $variables = [];
        parse_str($query_string, $variables);

        // Decoding full text catalog search value.
        $fts_filter = $filter_handler->get_full_text_search_filter();

        if (isset($variables[$fts_filter->key])) {
            $fts_value = $variables[$fts_filter->key];
            $variables[$fts_filter->key] = str_replace("%2E", '.', $fts_value);
        }

        // We are going to filter out all the invalid variables that do not exist in the filter handler.
        // This is just to make sure that we do not support any additional data - as they can be malicious data.
        $active_filters = $filter_handler->get_active_filters();
        $variables_return = [];

        foreach ($active_filters as $filter) {
            if (isset($variables[$filter->key])) {
                $variables_return[$filter->key] = $variables[$filter->key];
            }
        }

        // Hackingly added order key and item style.
        $variables_return['orderbykey'] = $variables['orderbykey'] ?? 'featured';
        $variables_return['itemstyle'] = $variables['itemstyle'] ?? 'narrow';

        return $variables_return;
    }

    /**
     * @param array $attributes
     * @return array
     */
    protected function convert_attributes_to_source(array $attributes): array {
        // We are going to fetch all the current filter data of any catalog filter.
        $filter_handler = filter_handler::instance();
        $filters = $filter_handler->get_active_filters();

        $fts_filter = $filter_handler->get_full_text_search_filter();
        $source_attributes = [];

        foreach ($filters as $filter) {
            $current_data = $filter->selector->get_data();

            if (!$current_data) {
                continue;
            }

            // For catalog full-text search, we will have to encoded the dot to make sure that link builder
            // will not explode on that dot. Which it will make the url destination wrong for filter.
            if ($fts_filter->key === $filter->key) {
                $current_data = str_replace(".", '%2E', $current_data);
            }

            $source_attributes[$filter->key] = $current_data;
        }

        // Hackingly add orderkey and item style.
        $source_attributes['orderbykey'] = urlencode(optional_param('orderbykey', 'featured', PARAM_ALPHA));
        $source_attributes['itemstyle'] = urlencode(optional_param('itemstyle', 'narrow', PARAM_ALPHA));

        return [http_build_query($source_attributes, '', '&')];
    }
}