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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_perform
*/

namespace mod_perform\rb\traits;

use mod_perform\entity\activity\activity_type as activity_type_entity;
use mod_perform\models\activity\activity_type as activity_type_model;
use rb_column_option;
use rb_filter_option;
use rb_join;

defined('MOODLE_INTERNAL') || die();

trait participant_subject_instance_source {

    /**
     * Add joints.
     *
     * @param array $joinlist
     * @param string $join
     */
    protected function add_to_joinlist(array &$joinlist, string $join = 'base') {
        $joinlist[] = new rb_join(
            'track_user_assignment',
            'INNER',
            '{perform_track_user_assignment}',
            "track_user_assignment.id = {$join}.track_user_assignment_id",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $join
        );
        $joinlist[] = new rb_join(
            'track',
            'INNER',
            '{perform_track}',
            'track.id = track_user_assignment.track_id',
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'track_user_assignment'
        );
        $joinlist[] = new rb_join(
            'perform',
            'INNER',
            "{perform}",
            "perform.id = track.activity_id",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            'track'
        );
        $joinlist[] = new rb_join(
            'perform_type',
            'INNER',
            "{perform_type}",
            "perform_type.id = perform.type_id",
            REPORT_BUILDER_RELATION_ONE_TO_MANY,
            'perform'
        );
        $joinlist[] = new rb_join(
            'job_assignment',
            'LEFT',
            "{job_assignment}",
            "job_assignment.id = {$join}.job_assignment_id",
            REPORT_BUILDER_RELATION_MANY_TO_MANY,
            $join
        );
    }

    /**
     * Define the column options available for this report.
     *
     * @param array $columnoptions
     * @param string $join
     */
    protected function add_fields_to_columns(array &$columnoptions, string $join = 'base') {
        $columnoptions[] = new rb_column_option(
            'perform',
            'name',
            get_string('activity_name', 'mod_perform'),
            'perform.name',
            [
                'joins' => ['perform', 'track', 'track_user_assignment'],
                'dbdatatype' => 'text',
                'outputformat' => 'text',
                'displayfunc' => 'format_string'
            ]
        );
        $columnoptions[] = new rb_column_option(
            'perform',
            'type',
            get_string('activity_type', 'mod_perform'),
            'perform_type.id',
            [
                'joins' => ['perform_type', 'perform', 'track', 'track_user_assignment'],
                'dbdatatype' => 'integer',
                'outputformat' => 'text',
                'displayfunc' => 'activity_type_name',
                'extrafields' => [
                    'name' => 'perform_type.name',
                    'is_system' => 'perform_type.is_system',
                ],
            ]
        );
        $columnoptions[] = new rb_column_option(
            'subject_instance',
            'due_date',
            get_string('due_date', 'mod_perform'),
            "{$join}.due_date",
            [
                'dbdatatype' => 'timestamp',
                'displayfunc' => 'nice_date'
            ]
        );

        // Job assignment columns.
        if (self::multiple_jobs_allowed()) {
            $columnoptions[] = new rb_column_option(
                'subject_instance',
                'job_assignment_name',
                get_string('activity_job_title', 'mod_perform'),
                'job_assignment.fullname',
                [
                    'joins' => [$join, 'job_assignment'],
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string',
                ]
            );
            $columnoptions[] = new rb_column_option(
                'subject_instance',
                'job_assignment_idnumber',
                get_string('activity_job_idnumber', 'mod_perform'),
                'job_assignment.idnumber',
                [
                    'joins' => [$join, 'job_assignment'],
                    'dbdatatype' => 'text',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string',
                ]
            );
        }
    }

    /**
     * Define the filter options available for this report.
     *
     * @param array $filteroptions
     */
    protected function add_fields_to_filters(array &$filteroptions) {
        // Performance activity name
        $filteroptions[] = new rb_filter_option(
            'perform',
            'name',
            get_string('activity_name', 'mod_perform'),
            'text'
        );
        $filteroptions[] = new rb_filter_option(
            'perform',
            'type',
            get_string('activity_type', 'mod_perform'),
            'select',
            ['selectchoices' => $this->get_activity_type_options()]
        );
        $filteroptions[] = new rb_filter_option(
            'subject_instance',
            'due_date',
            get_string('due_date', 'mod_perform'),
            'date'
        );

        // Job assignment filters.
        if (self::multiple_jobs_allowed()) {
            $filteroptions[] = new rb_filter_option(
                'subject_instance',
                'job_assignment_name',
                get_string('activity_job_title', 'mod_perform'),
                'text'
            );
            $filteroptions[] = new rb_filter_option(
                'subject_instance',
                'job_assignment_idnumber',
                get_string('activity_job_idnumber', 'mod_perform'),
                'text'
            );
        }
    }

    /**
     * Get an array of activity type options to use for filtering.
     *
     * @return string[] of [ID => Display Name]
     */
    private function get_activity_type_options(): array {
        return activity_type_entity::repository()
            ->select(['id', 'name', 'is_system'])
            ->get()
            ->map_to(activity_type_model::class)
            ->map(static function (activity_type_model $activity_type) {
                return $activity_type->display_name;
            })
            ->sort(static function (string $a, string $b) {
                return $a <=> $b;
            })
            ->all(true);
    }

    /**
     * Are multiple job assignments allowed?
     * @return bool
     */
    protected static function multiple_jobs_allowed(): bool {
        return get_config(null, 'totara_job_allowmultiplejobs');
    }

}