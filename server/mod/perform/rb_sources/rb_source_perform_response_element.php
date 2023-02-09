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
 * @author: Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package: mod_perform
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/perform/rb_sources/rb_source_perform_element.php');

/**
 * Report source for the element performance reporting embedded reports.
 */
class rb_source_perform_response_element extends rb_source_perform_element {

    /**
     * Constructor.
     *
     * @param mixed $groupid
     * @param rb_global_restriction_set|null $globalrestrictionset
     * @throws coding_exception
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        parent::__construct($groupid, $globalrestrictionset);

        $this->selectable = false;

        $this->sourcetitle = get_string('sourcetitle', 'rb_source_perform_response_element');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_perform_response_element');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_perform_response_element');
    }

    /**
     * Define the column options available for this report.
     *
     * @return array
     */
    protected function define_columnoptions() {
        global $DB;

        $columnoptions = parent::define_columnoptions();

        // Column for sorting that combines activity name, section and element sorts to get sensible overall order for elements
        $columnoptions[] = new rb_column_option(
            'element',
            'default_sort',
            get_string('default_sort', 'mod_perform'),
            // This will ensure elements are grouped by activity and order within but isn't perfect, particularly for
            // multiple identically named activities (which we don't prevent). Having an activity.sort_order would be better.
            $DB->sql_concat_join(
                "' '",
                ['perform.name', 'perform.id', 'perform_section.sort_order', 'section_element.sort_order']
            ),
            [
                'displayfunc' => 'format_string',
                'noexport' => true,
                'nosort' => true,
                'hidden' => true,
                'joins' => ['perform', 'perform_section', 'section_element'],
            ]
        );

        $columnoptions[] = new rb_column_option(
            'element',
            'actions',
            null,
            'base.id',
            [
                'dbdatatype' => 'integer',
                'displayfunc' => 'element_actions',
                'noexport' => true,
                'nosort' => true,
            ]
        );

        return $columnoptions;
    }
}
