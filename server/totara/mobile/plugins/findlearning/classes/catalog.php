<?php
/*
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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mobile_findlearning
 */

namespace mobile_findlearning;

use totara_catalog\output\catalog as core_catalog;
use totara_catalog\local\full_text_search_filter;
use mobile_findlearning\item_mobile as mobile_item;
use mobile_findlearning\catalog_retrieval;
use mobile_findlearning\provider_handler;
use mobile_findlearning\config;

defined('MOODLE_INTERNAL') || die();

/**
 * Note: The original idea of overriding the catalog output class has ended up with
 *       custom static functions, however I'm leaving it like this so we can track it
 *       back to the original and incase we ever want to revisit overriding parts of the output
 */
class catalog extends core_catalog {

    /**
     * A function to get the all the mobile catalog items.
     * Unfiltered except for the item_type, limit to course, playlist, resource.
     *
     * @param int $limitfrom - The number to start checking records from, loosely a pagenum.
     * @return object - The page object containing the items we want.
     */
    public static function load_catalog_page_objects($limitfrom = 0) {
        // Hardcoded for now but could easily be an admin setting
        $itemsper = 20;

        // TODO - when it's a setting remove this and set the setting in tests.
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            $itemsper = 10;
        }

        $maxcount = -1;
        $orderbykey = 'alpha'; // also accepts 'featured'... guessing that's a setting

        $catalog = new catalog_retrieval();

        $page = $catalog->get_page_of_objects($itemsper, $limitfrom, $maxcount, $orderbykey);
        $objects = $page->objects;

        $providerhandler = provider_handler::instance();

        $requireddataholders = [];
        foreach ($objects as $object) {
            if (empty($requireddataholders[$object->objecttype])) {
                $provider = $providerhandler->get_provider($object->objecttype);
                $requireddataholders[$object->objecttype] = mobile_item::get_required_dataholders($provider);
            }
        }

        // load all the required data.
        $page->objects = $providerhandler->get_data_for_objects($objects, $requireddataholders);
        return $page;
    }

    /**
     * Filter the page items
     *
     * @param int $limitfrom - Where in the objects to start looking for another page
     * @param arrat $filterparams - The paramaters used to define filters.
     * @return object - The page object containing the items we want.
     */
    public static function load_filtered_page_objects($limitfrom = 0, $filterparams = []) {
        // Hardcoded for now but could easily be an admin setting
        $itemsper = 20;

        // TODO - when it's a setting remove this and set the setting in tests.
        if (defined('PHPUNIT_TEST') && PHPUNIT_TEST) {
            $itemsper = 10;
        }

        $maxcount = -1;
        $orderbykey = 'alpha'; // also accepts 'featured'... guessing that's a setting

        // Setup the filters.
        $paramdata = [];
        $filterhandler = filter_handler::instance();
        $filters = $filterhandler->get_mobile_filters();
        foreach ($filters as $filter) {
            $optionalparams = $filter->selector->get_optional_params();
            foreach ($optionalparams as $optionalparam) {
                if (isset($filterparams[$optionalparam->key])) {
                    $paramdata[$optionalparam->key] = $filterparams[$optionalparam->key];
                }
            }

            $filter->selector->set_current_data($paramdata);
            $standarddata = $filter->selector->get_data();
            $filter->datafilter->set_current_data($standarddata);
        }

        $catalog = new catalog_retrieval();
        $page = $catalog->get_page_of_objects($itemsper, $limitfrom, $maxcount, $orderbykey);
        $objects = $page->objects;

        $requireddataholders = [];
        $providerhandler = provider_handler::instance();
        foreach ($objects as $object) {
            if (empty($requireddataholders[$object->objecttype])) {
                $provider = $providerhandler->get_provider($object->objecttype);
                $requireddataholders[$object->objecttype] = mobile_item::get_required_dataholders($provider);
            }
        }

        // load all the required data.
        $objects = $providerhandler->get_data_for_objects($objects, $requireddataholders);
        return $page;
    }

    /**
     * Get a list of sorting options.
     *
     * @return \stdClass[]
     */
    private static function get_order_by_options() {
        $options = [];

        // If there is an active full text search then relevance becomes the first order by option.
        if (filter_handler::instance()->get_full_text_search_filter()->datafilter->is_active()) {
            $score = new \stdClass();
            $score->key = 'score';
            $score->name = get_string('sort_score', 'totara_catalog');
            $options['score'] = $score;
        }

        // Ordering by featured learning is only possible if some featured learning has been specified.
        if (config::instance()->get_value('featured_learning_enabled')) {
            $featured = new \stdClass();
            $featured->key = 'featured';
            $featured->name = get_string('sort_featured', 'totara_catalog');
            $options['featured'] = $featured;
        }

        $alpha = new \stdClass();
        $alpha->key = 'text';
        $alpha->name = get_string('sort_text', 'totara_catalog');
        $options['text'] = $alpha;

        $latest = new \stdClass();
        $latest->key = 'time';
        $latest->name = get_string('sort_time', 'totara_catalog');
        $options['time'] = $latest;

        reset($options);
        $firstkey = key($options);
        $options[$firstkey]->default = true;

        return $options;
    }
}
