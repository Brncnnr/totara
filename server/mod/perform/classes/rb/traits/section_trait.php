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
 * Trait section_trait
 */
trait section_trait {
    /** @var string $section_join */
    protected $section_join = null;

    /**
     * Add section info where section is the base table.
     *
     * @throws coding_exception
     */
    protected function add_section_to_base() {
        /** @var section_trait|rb_base_source $this */
        if (isset($this->section_join)) {
            throw new coding_exception('Section info can be added only once!');
        }

        $this->section_join = 'base';

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_section_joins();
        $this->add_section_columns();
        $this->add_section_filters();
    }

    /**
     * Add section info where section is a joined table.
     *
     * @param rb_join $join
     * @throws coding_exception
     */
    protected function add_section(rb_join $join) {
        /** @var section_trait|rb_base_source $this */
        if (isset($this->section_join)) {
            throw new coding_exception('Section info can be added only once!');
        }

        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->section_join = $join->name;

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_section_joins();
        $this->add_section_columns();
        $this->add_section_filters();
    }

    /**
     * Add joins required for section column and filter options to report.
     */
    protected function add_section_joins() {
        /** @var section_trait|rb_base_source $this */
        $join = $this->section_join;
    }

    /**
     * Add columnoptions for section to report.
     */
    protected function add_section_columns() {
        /** @var section_trait|rb_base_source $this */
        $join = $this->section_join;

        $this->columnoptions[] = new rb_column_option(
            'section',
            'id',
            get_string('element_reporting_title_section_id', 'mod_perform'),
            "{$join}.id",
            [
                'joins' => [$join],
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer',
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'section',
            'title',
            get_string('element_reporting_title_section_title', 'mod_perform'),
            "{$join}.title",
            [
                'joins' => [$join],
                'dbdatatype' => 'text',
                'outputformat' => 'text',
                'displayfunc' => 'format_string'
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'section',
            'sort_order',
            get_string('section_sort_order', 'mod_perform'),
            "{$join}.sort_order",
            [
                'joins' => [$join],
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer',
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'section',
            'created_at',
            get_string('date_created', 'mod_perform'),
            "{$join}.created_at",
            [
                'joins' => [$join],
                'dbdatatype' => 'timestamp',
                'displayfunc' => 'nice_date'
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'section',
            'updated_at',
            get_string('date_updated', 'mod_perform'),
            "{$join}.updated_at",
            [
                'joins' => [$join],
                'dbdatatype' => 'timestamp',
                'displayfunc' => 'nice_date'
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'section',
            'responding_relationship_count',
            get_string('element_reporting_title_responding_relationships', 'mod_perform'),
            "(SELECT COUNT('x')
            FROM {perform_section_relationship} sr
            WHERE sr.section_id = {$join}.id
            AND sr.can_answer = 1)",
            [
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
            ]
        );
    }

    /**
     * Add filteroptions for sections to report.
     */
    protected function add_section_filters() {
        $this->filteroptions[] = new rb_filter_option(
            'section',
            'id',
            get_string('element_reporting_title_section_title_by_activity', 'mod_perform'),
            'perform_section_title'
        );

        $this->filteroptions[] = new rb_filter_option(
            'section',
            'title',
            get_string('element_reporting_title_section_title', 'mod_perform'),
            'text'
        );

        $this->filteroptions[] = new rb_filter_option(
            'section',
            'created_at',
            get_string('date_created', 'mod_perform'),
            'date'
        );

        $this->filteroptions[] = new rb_filter_option(
            'section',
            'updated_at',
            get_string('date_updated', 'mod_perform'),
            'date'
        );

        $this->filteroptions[] = new rb_filter_option(
            'section',
            'sort_order',
            get_string('section_sort_order', 'mod_perform'),
            'number'
        );
    }
}
