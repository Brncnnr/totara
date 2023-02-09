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
use mod_perform\state\state_helper;
use mod_perform\state\subject_instance\closed;
use mod_perform\state\subject_instance\complete;
use mod_perform\state\subject_instance\subject_instance_availability;
use mod_perform\state\subject_instance\subject_instance_manual_status;
use mod_perform\state\subject_instance\subject_instance_progress;
use rb_base_source;
use rb_column_option;
use rb_filter_option;
use rb_join;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/totara/reportbuilder/lib.php");

/**
 * Trait subject_instance_trait
 */
trait subject_instance_trait {
    /** @var string $subject_instance_join */
    protected $subject_instance_join = null;

    /**
     * Add subject instance info where subject_instance is the base table.
     *
     * @throws coding_exception
     */
    protected function add_subject_instance_to_base() {
        /** @var subject_instance_trait|rb_base_source $this */
        if (isset($this->subject_instance_join)) {
            throw new coding_exception('Subject instance info can be added only once!');
        }

        $this->subject_instance_join = 'base';

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_subject_instance_joins();
        $this->add_subject_instance_columns();
        $this->add_subject_instance_filters();
    }

    /**
     * Add subject instance info.
     * If a new join isn't specified then the existing join will be used.
     *
     * @param rb_join $join
     * @throws coding_exception
     */
    protected function add_subject_instance(rb_join $join = null): void {
        $join = $join ?? $this->get_join('subject_instance');

        /** @var subject_instance_trait|rb_base_source $this */
        if (isset($this->subject_instance_join)) {
            throw new coding_exception('Subject instance info can be added only once!');
        }

        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->subject_instance_join = $join->name;

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_subject_instance_joins();
        $this->add_subject_instance_columns();
        $this->add_subject_instance_filters();
    }

    /**
     * Add joins required for subject instance column and filter options to report.
     */
    protected function add_subject_instance_joins() {
        /** @var subject_instance_trait|rb_base_source $this */
        $join = $this->subject_instance_join;

        $this->joinlist[] = new rb_join(
            'subject_instance_job_assignment',
            'LEFT',
            "{job_assignment}",
            "{$join}.job_assignment_id = subject_instance_job_assignment.id",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            $join
        );

        $this->add_core_user_tables($this->joinlist, $join, 'subject_user_id', 'subject_user');
    }

    /**
     * Add columnoptions for subject instances to report.
     */
    protected function add_subject_instance_columns() {
        /** @var subject_instance_trait|rb_base_source $this */
        $join = $this->subject_instance_join;

        $this->columnoptions[] = new rb_column_option(
            'subject_instance',
            'progress',
            get_string('progress', 'mod_perform'),
            "{$join}.progress",
            [
                'joins' => [$join],
                'dbdatatype' => 'integer',
                'displayfunc' => 'state_display_name',
                'extracontext' => [
                    'object_type' => 'subject_instance',
                    'state_type' => subject_instance_progress::get_type(),
                ]
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'subject_instance',
            'availability',
            get_string('availability', 'mod_perform'),
            "{$join}.availability",
            [
                'joins' => [$join],
                'dbdatatype' => 'integer',
                'displayfunc' => 'state_display_name',
                'extracontext' => [
                    'object_type' => 'subject_instance',
                    'state_type' => subject_instance_availability::get_type(),
                ],
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'subject_instance',
            'overdue',
            get_string('overdue', 'mod_perform'),
            "CASE
                    WHEN
                        " . time() . " >= {$join}.due_date
                        AND NOT (
                            {$join}.progress = " . complete::get_code() . "
                            OR {$join}.availability = " . closed::get_code() . "
                        )
                    THEN 1
                    ELSE 0
                END",
            [
                'joins' => [$join],
                'dbdatatype' => 'boolean',
                'displayfunc' => 'yes_or_no',
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'subject_instance',
            'due_date',
            get_string('due_date', 'mod_perform'),
            "{$join}.due_date",
            [
                'joins' => [$join],
                'dbdatatype' => 'timestamp',
                'displayfunc' => 'nice_date'
            ]
        );

        // Job assignment columns.
        if (self::multiple_jobs_allowed()) {
            $this->columnoptions[] = new rb_column_option(
                'subject_instance',
                'job_assignment_name',
                get_string('activity_job_title', 'mod_perform'),
                'subject_instance_job_assignment.fullname',
                [
                    'joins' => [$join, 'subject_instance_job_assignment'],
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string',
                ]
            );
            $this->columnoptions[] = new rb_column_option(
                'subject_instance',
                'job_assignment_idnumber',
                get_string('activity_job_idnumber', 'mod_perform'),
                'subject_instance_job_assignment.idnumber',
                [
                    'joins' => [$join, 'subject_instance_job_assignment'],
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string',
                ]
            );
        }

        $this->columnoptions[] = new rb_column_option(
            'subject_instance',
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
            'subject_instance',
            'completed_at',
            get_string('date_completed', 'mod_perform'),
            "{$join}.completed_at",
            [
                'joins' => [$join],
                'dbdatatype' => 'timestamp',
                'displayfunc' => 'nice_date'
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'subject_instance',
            'updated_at',
            get_string('date_updated', 'mod_perform'),
            "{$join}.updated_at",
            [
                'joins' => [$join],
                'dbdatatype' => 'timestamp',
                'displayfunc' => 'nice_date'
            ]
        );

        // Add instance number
        // Using <= created_at to start at 1
        $this->columnoptions[] = new rb_column_option(
            'subject_instance',
            'instance_number',
            get_string('instance_number', 'mod_perform'),
            "(SELECT COUNT('x')
                FROM {perform_subject_instance} psi
                WHERE psi.track_user_assignment_id = {$join}.track_user_assignment_id
                  AND psi.created_at <= {$join}.created_at)",
            [
                'joins' => [$join],
                'dbdatatype' => 'integer',
                'displayfunc' => 'integer',
                'iscompound' => true,
                'issubquery' => true,
            ]
        );

        $this->add_core_user_columns($this->columnoptions, 'subject_user', 'subject_user', true);
    }

    /**
     * Add filteroptions for subject instances to report.
     */
    protected function add_subject_instance_filters() {
        /** @var subject_instance_trait|rb_base_source $this */
        $join = $this->subject_instance_join;

        $this->filteroptions[] = new rb_filter_option(
            'subject_instance',
            'due_date',
            get_string('due_date', 'mod_perform'),
            'date'
        );

        $this->filteroptions[] = new rb_filter_option(
            'subject_instance',
            'overdue',
            get_string('overdue', 'mod_perform'),
            'multicheck',
            [
                'simplemode' => true,
                'selectfunc' => 'yesno_list',
            ]
        );

        $this->filteroptions[] = new rb_filter_option(
            'subject_instance',
            'progress',
            get_string('progress', 'mod_perform'),
            'select',
            [
                'selectchoices' => state_helper::get_all_display_names(
                    'subject_instance', subject_instance_progress::get_type()
                ),
            ]
        );

        $this->filteroptions[] = new rb_filter_option(
            'subject_instance',
            'availability',
            get_string('availability', 'mod_perform'),
            'select',
            [
                'selectchoices' => state_helper::get_all_display_names(
                    'subject_instance', subject_instance_availability::get_type()
                ),
                'simplemode' => true,
            ]
        );

        $this->filteroptions[] = new rb_filter_option(
            'subject_instance',
            'created_at',
            get_string('date_created', 'mod_perform'),
            'date'
        );

        $this->filteroptions[] = new rb_filter_option(
            'subject_instance',
            'updated_at',
            get_string('date_updated', 'mod_perform'),
            'date'
        );

        $this->filteroptions[] = new rb_filter_option(
            'subject_instance',
            'completed_at',
            get_string('date_completed', 'mod_perform'),
            'date'
        );

        // Job assignment filters.
        if (self::multiple_jobs_allowed()) {
            $this->filteroptions[] = new rb_filter_option(
                'subject_instance',
                'job_assignment_name',
                get_string('activity_job_title', 'mod_perform'),
                'text'
            );

            $this->filteroptions[] = new rb_filter_option(
                'subject_instance',
                'job_assignment_idnumber',
                get_string('activity_job_idnumber', 'mod_perform'),
                'text'
            );
        }

        $this->filteroptions[] = new rb_filter_option(
            'subject_instance',
            'status',
            get_string('subject_instance_status', 'mod_perform'),
            'select',
            [
                'selectchoices' => state_helper::get_all_display_names(
                    'subject_instance', subject_instance_manual_status::get_type()
                ),
                'simplemode' => true,
            ],
            "{$join}.status"
        );

        $this->add_core_user_filters($this->filteroptions, 'subject_user', true);
    }

    /**
     * Are multiple job assignments allowed?
     * @return bool
     */
    protected static function multiple_jobs_allowed(): bool {
        return get_config(null, 'totara_job_allowmultiplejobs');
    }
}
