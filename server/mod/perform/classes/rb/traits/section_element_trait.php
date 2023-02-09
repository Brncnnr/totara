<?php
/**
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\rb\traits;

use coding_exception;
use rb_base_source;
use rb_column_option;
use rb_filter_option;
use rb_join;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/totara/reportbuilder/lib.php");

/**
 * Trait section_element_trait
 */
trait section_element_trait {
    /** @var string $section_element_join */
    protected $section_element_join = null;

    /**
     * Add section element info where element is the base table.
     *
     * @throws coding_exception
     */
    protected function add_section_element_to_base() {
        /** @var section_element_trait|rb_base_source $this */
        if (isset($this->section_element_join)) {
            throw new coding_exception('Section element info can be added only once!');
        }

        $this->section_element_join = 'base';

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_section_element_joins();
        $this->add_section_element_columns();
        $this->add_section_element_filters();
    }

    /**
     * Add section element info where section element is a joined table.
     *
     * @param rb_join $join
     * @throws coding_exception
     */
    protected function add_section_element(rb_join $join) {
        /** @var section_element_trait|rb_base_source $this */
        if (isset($this->section_element_join)) {
            throw new coding_exception('Section element info can be added only once!');
        }

        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->section_element_join = $join->name;

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_section_element_joins();
        $this->add_section_element_columns();
        $this->add_section_element_filters();
    }

    /**
     * Add joins required for section element column and filter options to report.
     */
    protected function add_section_element_joins() {
        /** @var section_element_trait|rb_base_source $this */
        $join = $this->section_element_join;

        // None at present.
    }

    /**
     * Add columnoptions for section element to report.
     */
    protected function add_section_element_columns() {
        /** @var section_element_trait|rb_base_source $this */
        $join = $this->section_element_join;

        // Putting sort order in the 'element' type instead of a 'section_element' one as it will
        // make more sense to the user.
        $this->columnoptions[] = new rb_column_option(
            'element',
            'sort_order',
            get_string('section_element_sort_order', 'mod_perform'),
            "{$join}.sort_order",
            [
                'displayfunc' => 'integer',
                'dbdatatype' => 'integer',
                'joins' => [$join],
            ]
        );
    }

    /**
     * Add filteroptions for section elements to report.
     */
    protected function add_section_element_filters() {
        $this->filteroptions[] = new rb_filter_option(
            'element',
            'sort_order',
            get_string('section_element_sort_order', 'mod_perform'),
            'number'
        );
    }
}
