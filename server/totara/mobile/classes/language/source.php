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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_mobile
 */

namespace totara_mobile\language;

/**
 * Pepares and filters strings using string_manager_standard
 */
class source {
    /**
     * @var string Component to be processed
     */
    private $component;

    /**
     * @var string Language to be processed
     */
    private $lang;

    /**
     * @var string Prefix filter
     */
    private $prefix = '';

    /**
     * @var bool Use local customised string values instead of official
     */
    private $uselocal = true;

    public function __construct(string $component, string $lang) {
        $this->component = $component;
        $this->lang = $lang;
    }

    /**
     * Get instance
     * @param string $component
     * @param string $lang
     * @return source
     */
    public static function instance(string $component, string $lang) {
        return new self($component, $lang);
    }

    /**
     * Add customised strings to the results
     *
     * @param bool $uselocal
     * @return $this
     */
    public function use_local(bool $uselocal = true) {
        $this->uselocal = $uselocal;
        return $this;
    }

    /**
     * Set filter of stringid by prefix
     * @param string $prefix
     * @return $this
     */
    public function filter_prefix(string $prefix) {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Get all strings without any filters
     * @return array
     */
    public function get_all_strings(): array {
        /**
         * @var \core_string_manager_standard $stringman
         */
        $stringman = get_string_manager();
        return $stringman->load_component_strings(
            $this->component,
            $this->lang,
            false,
            !$this->uselocal
        );
    }

    /**
     * Get filtered strings for a component on a chosen language
     * @return array
     */
    public function get_strings(): array {
        $strings = $this->get_all_strings();
        // Filter by prefix.
        if (!empty($this->prefix)) {
            $prefix = $this->prefix;
            $strings = array_filter(
                $strings,
                function($item) use ($prefix) {
                    if (strpos($item, $prefix) === 0) {
                        return true;
                    }
                    return false;
                },
                ARRAY_FILTER_USE_KEY
            );
        }
        return $strings;
    }
}