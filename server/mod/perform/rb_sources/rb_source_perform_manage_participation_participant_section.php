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

use mod_perform\rb\util;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/perform/rb_sources/rb_source_perform_participation_participant_section.php');

/**
 * Participant section manage participation report.
 *
 * Class rb_source_perform_manage_participation_participant_section
 */
class rb_source_perform_manage_participation_participant_section extends rb_source_perform_participation_participant_section {

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

        $this->sourcetitle = get_string('sourcetitle', 'rb_source_perform_manage_participation_participant_section');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_perform_manage_participation_participant_section');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_perform_manage_participation_participant_section');
    }

    /**
     * Use post_config() to add restrictions based on the viewing user. Must be done here as viewing user not known until
     * after config is finalised.
     *
     * @param reportbuilder $report
     */
    public function post_config(reportbuilder $report) {
        if (!in_array('ctx', $this->sourcejoins)) {
            $this->sourcejoins[] = 'ctx';
        }
        [$this->sourcewhere, $this->sourceparams] = util::get_manage_participation_sql($report->reportfor, "$this->subject_instance_join.subject_user_id");
    }

    /**
     * Define the column options available for this report.
     *
     * @return array
     */
    protected function define_columnoptions() {
        global $DB;

        $columnoptions = parent::define_columnoptions();

        // Add actions
        $columnoptions[] = new rb_column_option(
            'participant_section',
            'actions',
            get_string('actions', 'rb_source_perform_manage_participation_participant_section'),
            "base.id",
            [
                'displayfunc' => 'participant_section_manage_participation_actions',
                'noexport' => true,
                'nosort' => true,
                'joins' => 'track',
                'extrafields' => [
                    'activity_id' => 'track.activity_id',
                    'participant_section_id' => 'base.id',
                    'participant_section_availability' => 'base.availability',
                ],
            ]
        );

        // Add column for default sorting on participant user, subject user, instance_number (desc) and section sort order
        $seq_sql = "(SELECT 99999999 - COUNT('x')
                FROM {perform_participant_instance} ppi
                WHERE ppi.subject_instance_id = participant_instance.subject_instance_id
                  AND ppi.created_at <= participant_instance.created_at)";
        $participant_usednamefields = totara_get_all_user_name_fields_join('participant_user', null, true);
        $subject_usednamefields = totara_get_all_user_name_fields_join('subject_user', null, true);
        $columnoptions[] = new rb_column_option(
            'participant_section',
            'default_sort',
            get_string('default_sort', 'mod_perform'),
            $DB->sql_concat_join("' '", array_merge(
                array_values($participant_usednamefields),
                array_values($subject_usednamefields),
                [$seq_sql, 'section.sort_order']
            )),
            [
                'displayfunc' => 'format_string',
                'noexport' => true,
                'nosort' => true,
                'hidden' => true,
                'joins' => ['participant_instance', 'subject_user', 'participant_user'],
            ]
        );

        return $columnoptions;
    }

    /**
     * The default columns for this and embedded reports.
     *
     * @return array
     */
    public static function get_default_columns() {
        return [
            [
                'type' => 'participant_instance',
                'value' => 'participant_name',
                'heading' => get_string('participant_name', 'rb_source_perform_participation_participant_instance'),
            ],
            [
                'type' => 'section',
                'value' => 'title',
                'heading' => get_string('section_title', 'mod_perform')
            ],
            [
                'type' => 'subject_user',
                'value' => 'namelink',
                'heading' => get_string('subject_name', 'rb_source_perform_participation_subject_instance')
            ],
            [
                'type' => 'participant_instance',
                'value' => 'relationship_name',
                'heading' => get_string('relationship_name', 'mod_perform')
            ],
            [
                'type' => 'participant_instance',
                'value' => 'created_at',
                'heading' => get_string('date_created', 'mod_perform')
            ],
            [
                'type' => 'participant_section',
                'value' => 'progress',
                'heading' => get_string('progress', 'mod_perform')
            ],
            [
                'type' => 'participant_section',
                'value' => 'availability',
                'heading' => get_string('availability', 'mod_perform')
            ],
            [
                'type' => 'participant_section',
                'value' => 'actions',
                'heading' => ' '
            ],
            [
                'type' => 'participant_section',
                'value' => 'default_sort',
                'heading' => get_string('default_sort', 'mod_perform'),
                'hidden' => true,
            ],
        ];
    }

    /**
     * The default filters for this and embedded reports.
     *
     * @return array
     */
    public static function get_default_filters() {
        return [
            [
                'type' => 'participant_instance',
                'value' => 'participant_name',
                'heading' => get_string('participant_name', 'rb_source_perform_participation_participant_instance'),
            ],
            [
                'type' => 'participant_instance',
                'value' => 'relationship_id',
            ],
            [
                'type' => 'section',
                'value' => 'title',
            ],
            [
                'type' => 'subject_user',
                'value' => 'fullname',
            ],
            [
                'type' => 'participant_section',
                'value' => 'progress',
            ],
            [
                'type' => 'participant_section',
                'value' => 'availability',
            ],
            [
                'type' => 'participant_instance',
                'value' => 'created_at',
            ],
            [
                'type' => 'participant_user',
                'value' => 'deleted',
                'defaultvalue' => ['value' => 0]
            ],
        ];
    }
}
