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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\rb\traits;

use coding_exception;
use mod_perform\state\participant_section\participant_section_availability;
use mod_perform\state\participant_section\participant_section_progress;
use mod_perform\state\state_helper;
use rb_base_source;
use rb_column_option;
use rb_filter_option;
use rb_join;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/totara/reportbuilder/lib.php");

/**
 * Trait participant_section_trait
 */
trait participant_section_trait {
    /** @var string $participant_section_join */
    protected $participant_section_join = null;

    /**
     * Add participant section info where participant_section is the base table.
     *
     * @throws coding_exception
     */
    protected function add_participant_section_to_base() {
        /** @var participant_section_trait|rb_base_source $this */
        if (isset($this->participant_section_join)) {
            throw new coding_exception('Participant section info can be added only once!');
        }

        $this->participant_section_join = 'base';

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_participant_section_joins();
        $this->add_participant_section_columns();
        $this->add_participant_section_filters();
    }

    /**
     * Add participant section info where participant_section is a joined table.
     *
     * @param rb_join $join
     * @throws coding_exception
     */
    protected function add_participant_section(rb_join $join) {
        /** @var participant_section_trait|rb_base_source $this */
        if (isset($this->participant_section_join)) {
            throw new coding_exception('Participant section info can be added only once!');
        }

        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->participant_section_join = $join->name;

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_participant_section_joins();
        $this->add_participant_section_columns();
        $this->add_participant_section_filters();
    }

    /**
     * Add joins required for participant section column and filter options to report.
     */
    protected function add_participant_section_joins() {
        $this->joinlist[] = new rb_join(
            'participant_instance',
            'INNER',
            '{perform_participant_instance}',
            'base.participant_instance_id = participant_instance.id',
            REPORT_BUILDER_RELATION_MANY_TO_ONE
        );
    }

    /**
     * Add columnoptions for participant sections to report.
     */
    protected function add_participant_section_columns() {
        /** @var participant_section_trait|rb_base_source $this */
        $join = $this->participant_section_join;

        $this->columnoptions[] = new rb_column_option(
            'participant_section',
            'progress',
            get_string('progress', 'mod_perform'),
            "{$join}.progress",
            [
                'joins' => [$join],
                'dbdatatype' => 'integer',
                'displayfunc' => 'state_display_name',
                'extracontext' => [
                    'object_type' => 'participant_section',
                    'state_type' => participant_section_progress::get_type(),
                ],
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'participant_section',
            'availability',
            get_string('availability', 'mod_perform'),
            "{$join}.availability",
            [
                'joins' => [$join],
                'dbdatatype' => 'integer',
                'displayfunc' => 'state_display_name',
                'extracontext' => [
                    'object_type' => 'participant_section',
                    'state_type' => participant_section_availability::get_type(),
                ],
            ]
        );
    }

    /**
     * Add filteroptions for participant sections to report.
     */
    protected function add_participant_section_filters() {
        $this->filteroptions[] = new rb_filter_option(
            'participant_section',
            'progress',
            get_string('progress', 'mod_perform'),
            'select',
            [
                'selectchoices' => state_helper::get_all_display_names(
                    'participant_section', participant_section_progress::get_type()
                ),
            ]
        );

        $this->filteroptions[] = new rb_filter_option(
            'participant_section',
            'availability',
            get_string('availability', 'mod_perform'),
            'select',
            [
                'selectchoices' => state_helper::get_all_display_names(
                    'participant_section', participant_section_availability::get_type()
                ),
                'simplemode' => true
            ]
        );
    }
}
