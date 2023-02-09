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

use totara_catalog\filter;
use totara_catalog\local\filter_handler as core_handler;
use totara_catalog\local\full_text_search_filter;
use mobile_findlearning\provider_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Filter handler.
 */
class filter_handler extends core_handler {

    private static $instance;

    private $activefilters = null;

    private $fulltextsearchfilter = null;

    /**
     * Return a singleton instance.
     *
     * @return filter_handler
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * Reset the singleton's internal cache, so that the values will be loaded again next time they are accessed.
     *
     * This function should be used after data relating to filters has changed, including during testing.
     */
    public function reset_cache() {
        $this->activefilters = null;
        $this->fulltextsearchfilter = null;

        parent::reset_cache();
    }

    private function __construct() {
    }

    /**
     * Get an array of all the active filters.
     *
     * @return filter[]
     */
    public function get_mobile_filters() {
        if (is_null($this->activefilters)) {
            $this->activefilters = [];
            $this->activefilters[] = $this->get_full_text_search_filter();
        }

        return $this->activefilters;
    }


    /**
     * Get the full text search filter
     *
     * @return filter
     */
    public function get_full_text_search_filter() {
        if (is_null($this->fulltextsearchfilter)) {
            $this->fulltextsearchfilter = full_text_search_filter::create();
        }

        return $this->fulltextsearchfilter;
    }

    /**
     * @internal
     */
    public static function phpunit_reset() {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('Cannot reset file handler outside of phpunit tests!');
        }
        $instance = static::instance();
        $instance->reset_cache();
    }
}
