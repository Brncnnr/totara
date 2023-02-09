<?php
/*
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package engage_article
 */

namespace engage_article\totara_catalog\article\filter_factory;

defined('MOODLE_INTERNAL') || die();

use totara_engage\timeview\time_view as time;
use totara_catalog\datasearch\equal;
use totara_catalog\datasearch\in_or_equal;
use totara_catalog\filter;
use totara_catalog\filter_factory;
use totara_catalog\merge_select\multi;
use totara_catalog\merge_select\tree;

class timeview extends filter_factory {

    public static function get_filters(): array {
        $filters = [];

        // The panel filter can appear in the panel region.
        $paneldatafilter = new in_or_equal(
            'article_timeview_panel',
            'catalog',
            ['objectid', 'objecttype']
        );
        $paneldatafilter->add_source(
            'article.timeview',
            '{engage_article}',
            'article',
            ['objectid' => 'article.id', 'objecttype' => "'engage_article'"]
        );

        $panelselector = new multi(
            'article_timeview_panel',
            new \lang_string('filter:timeview', 'engage_article')
        );
        $panelselector->add_options_loader(self::get_multi_optionsloader());

        $filters[] = new filter(
            'article_timeview_multi',
            filter::REGION_PANEL,
            $paneldatafilter,
            $panelselector
        );

        // The browse filter can appear in the primary region.
        $browsedatafilter = new equal(
            'article_timeview_browse',
            'catalog',
            ['id']
        );
        $browsedatafilter->add_source(
            'article.timeview',
            '{engage_article}',
            'article',
            ['id' => 'article.id']
        );

        $browseselector = new tree(
            'article_timeview_browse',
            new \lang_string('filter:timeview', 'engage_article'),
            self::get_tree_optionsloader()
        );
        $browseselector->add_all_option();

        $filters[] = new filter(
            'article_timeview_tree',
            filter::REGION_BROWSE,
            $browsedatafilter,
            $browseselector
        );

        return $filters;
    }

    /**
     * @return callable
     */
    private static function get_tree_optionsloader(): callable {
        return function () {
            $items = [
                time::LESS_THAN_FIVE => get_string('filter:timeviewlow', 'engage_article'),
                time::FIVE_TO_TEN => get_string('filter:timeviewmed', 'engage_article'),
                time::MORE_THAN_TEN => get_string('filter:timeviewhigh', 'engage_article')
            ];

            $options = [];

            foreach ($items as $key => $name) {
                $option = new \stdClass();
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
    private static function get_multi_optionsloader(): callable {
        return function () {
            $options = [
                time::LESS_THAN_FIVE => get_string('filter:timeviewlow', 'engage_article'),
                time::FIVE_TO_TEN => get_string('filter:timeviewmed', 'engage_article'),
                time::MORE_THAN_TEN => get_string('filter:timeviewhigh', 'engage_article')
            ];

            return $options;
        };
    }
}
