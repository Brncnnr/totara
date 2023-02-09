<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Arshad Anwer <arshad.anwer@totaralearning.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\rb\display;

use rb_column;
use rb_column_option;
use reportbuilder;
use stdClass;
use totara_contentmarketplace\totara_catalog\provider;
use totara_reportbuilder\rb\display\base;

/**
 * Display the name of where the course is from, e.g. internal or the name of a content marketplace.
 */
class course_marketplace_provider extends base {

    /**
     * @param string $components_concat
     * @param string $format
     * @param stdClass $row
     * @param rb_column $column
     * @param reportbuilder $report
     * @return string
     */
    public static function display($components_concat, $format, stdClass $row, rb_column $column, reportbuilder $report): string {
        $components = explode('|', $components_concat);

        $provider_names = [];

        // If there is an internal provider, then we always show this first
        if (in_array(provider::INTERNAL, $components)) {
            $provider_names[] = get_string('provider_internal', 'totara_contentmarketplace');
            $components = array_diff($components, [provider::INTERNAL]);
        }

        // After internal, we then display the content marketplaces in alphabetical order
        $marketplace_plugin_names = array_map(function (string $component) {
            return get_string('pluginname', $component);
        }, $components);
        asort($marketplace_plugin_names);
        $provider_names += $marketplace_plugin_names;

        return implode(
            get_string('list_separator', 'totara_contentmarketplace'),
            $provider_names
        );
    }

    /**
     * @inheritDoc
     */
    public static function is_graphable(rb_column $column, rb_column_option $option, reportbuilder $report): bool {
        return false;
    }

}
