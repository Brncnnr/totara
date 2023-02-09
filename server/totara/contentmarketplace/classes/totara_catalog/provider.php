<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\totara_catalog;

use core_plugin_manager;
use lang_string;
use stdClass;
use totara_catalog\datasearch\equal;
use totara_catalog\datasearch\filter as datasearch_filter;
use totara_catalog\datasearch\in_or_equal;
use totara_catalog\filter;
use totara_catalog\merge_select\multi;
use totara_catalog\merge_select\tree;
use totara_contentmarketplace\local;
use totara_contentmarketplace\plugininfo\contentmarketplace;

class provider {
    /**
     * @var int
     */
    public const INTERNAL = '0';

    /**
     * @return array
     */
    public static function get_filters(): array {
        $filters = [];

        if (!local::is_enabled()) {
            return $filters;
        }

        // The panel filter can appear in the panel region.
        $filters[] = self::get_panel_filter();

        // The browse filter can appear in the primary region.
        $filters[] = self::get_browse_filter();

        return $filters;
    }

    /**
     * @return filter
     */
    private static function get_panel_filter(): filter {
        $panel_data_filter = new in_or_equal(
            'contentmarketplace_provider_panel',
            'catalog',
            ['objectid', 'objecttype']
        );

        self::add_source($panel_data_filter);

        $panel_selector = new multi(
            'contentmarketplace_provider_panel',
            new lang_string('provider', 'totara_contentmarketplace')
        );

        $panel_selector->add_options_loader(self::get_filter_options());

        return new filter(
            'contentmarketplace_provider_multi',
            filter::REGION_PANEL,
            $panel_data_filter,
            $panel_selector
        );
    }

    /**
     * @return filter
     */
    private static function get_browse_filter(): filter {
        $browse_data_filter = new equal(
            'contentmarketplace_provider_browse',
            'catalog',
            ['objecttype', 'objectid']
        );

        self::add_source($browse_data_filter);

        $browse_selector = new tree(
            'contentmarketplace_provider_browse',
            new lang_string('provider', 'totara_contentmarketplace'),
            self::get_tree_options()
        );

        $browse_selector->add_all_option();

        return new filter(
            'contentmarketplace_provider_tree',
            filter::REGION_BROWSE,
            $browse_data_filter,
            $browse_selector
        );
    }

    /**
     * @param datasearch_filter $filter
     */
    private static function add_source(datasearch_filter $filter): void {
        global $DB;
        $internal_component = $DB->sql_cast_2char(self::INTERNAL);

        // Add internal filter.
        $filter->add_source(
            'course.component',
            "(
                SELECT course.id, {$internal_component} AS component
                FROM {course} course
                LEFT JOIN {course_modules} cm ON course.id = cm.course
                LEFT JOIN {totara_contentmarketplace_course_module_source} cm_source ON cm.id = cm_source.cm_id
                WHERE cm.id IS NULL
                OR cm_source.id IS NULL
            )",
            'course',
            ['objectid' => 'course.id', 'objecttype' => "'course'"]
        );

        if (!empty(contentmarketplace::get_enabled_plugins())) {
            // Add subplugin filter.
            $filter->add_source(
                'course_modules.component',
                "(
                    SELECT cm.course, cm_source.marketplace_component AS component
                    FROM {course_modules} cm
                    LEFT JOIN {totara_contentmarketplace_course_module_source} cm_source ON cm.id = cm_source.cm_id
                )",
                'course_modules',
                ['objectid' => 'course_modules.course', 'objecttype' => "'course'"],
            );
        }
    }

    /**
     * @return callable
     */
    private static function get_tree_options(): callable {
        return function () {
            $options = [];
            foreach (self::get_options() as $key => $name) {
                $option = new stdClass();
                $option->key = $key;
                $option->name = $name;
                $options[] = $option;
            }

            return $options;
        };
    }

    /**
     * @return callable
     */
    private static function get_filter_options(): callable {
        return function () {
            return self::get_options();
        };
    }

    /**
     * @return array
     */
    private static function get_options(): array {
        $options[self::INTERNAL] = get_string('provider_internal', 'totara_contentmarketplace');

        /** @var contentmarketplace[] $plugins */
        $plugins = core_plugin_manager::instance()->get_plugins_of_type('contentmarketplace');
        foreach ($plugins as $plugin) {
            if ($plugin->is_enabled()) {
                $options[$plugin->component] = get_string('pluginname', $plugin->component);
            }
        }

        return $options;
    }

}
