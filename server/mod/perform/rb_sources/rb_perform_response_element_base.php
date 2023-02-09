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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package: mod_perform
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/perform/rb_sources/rb_source_perform_response_element.php');

use totara_core\advanced_feature;

abstract class rb_perform_response_element_base extends rb_base_embedded {

    public function __construct() {
        $this->url = $this->get_url();
        $this->source = 'perform_response_element';
        $this->shortname = $this->get_short_name();
        $this->fullname = $this->get_full_name();
        $this->columns = $this->define_columns();
        $this->filters = $this->define_filters();
        $this->defaultsortcolumn = 'element_default_sort';

        parent::__construct();
    }

    abstract protected function get_url(): string;
    abstract protected function get_short_name(): string;
    abstract protected function get_full_name(): string;
    abstract protected function define_columns(): array;

    /**
     * Define the default filters for this report.
     *
     * @return array
     */
    protected function define_filters(): array {
        return [
            [
                'type' => 'section',
                'value' => 'title',
            ],
            [
                'type' => 'element',
                'value' => 'type',
            ],
            [
                'type' => 'section',
                'value' => 'involved_relationships',
            ],
            [
                'type' => 'element',
                'value' => 'title',
            ]
        ];
    }

    /**
     * Clarify if current embedded report support global report restrictions.
     * Override to true for reports that support GRR
     *
     * @return boolean
     */
    public function embedded_global_restrictions_supported(): bool {
        return true;
    }

    /**
     * Can searches be saved?
     *
     * @return bool
     */
    public static function is_search_saving_allowed(): bool {
        return false;
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return advanced_feature::is_disabled('performance_activities');
    }

}