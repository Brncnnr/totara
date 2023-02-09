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
 * @author: Simon Coggins <simon.coggins@totaralearning.com>
 * @package: mod_perform
 */

use mod_perform\models\activity\participant_source;
use mod_perform\rb\traits\course_visibility_trait;
use mod_perform\rb\util;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/perform/rb_sources/rb_source_perform_participation_subject_instance.php');

/**
 * Subject instance for performance reporting.
 *
 * Class rb_source_perform_response_subject_instance
 */
class rb_source_perform_response_subject_instance extends rb_source_perform_participation_subject_instance {

    use course_visibility_trait;

    /**
     * Constructor.
     *
     * @param mixed $groupid
     * @param rb_global_restriction_set|null $globalrestrictionset
     * @throws coding_exception
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        // Caching is disabled because visibility needs to be calculated using live data that cannot be cached.
        $this->cacheable = false;

        parent::__construct($groupid, $globalrestrictionset);

        $this->sourcetitle = get_string('sourcetitle', 'rb_source_perform_response_subject_instance');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_perform_response_subject_instance');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_perform_response_subject_instance');

        $this->add_course_visibility('perform');

        // We need this join to be able to restrict the query to users
        // the current user can report on
        $this->joinlist[] = new \rb_join(
            'user_context',
            'INNER',
            '{context}',
            "user_context.instanceid = base.subject_user_id AND user_context.contextlevel = ".CONTEXT_USER,
            REPORT_BUILDER_RELATION_ONE_TO_ONE
        );

        // This source is not available for user selection - it is used by the embedded report only.
        $this->selectable = false;
    }

    /**
     * Use post_config() to add restrictions based on the viewing user. Must be done here as viewing user not known until
     * after config is finalised.
     *
     * @param reportbuilder $report
     * @throws coding_exception
     * @throws dml_exception
     */
    public function post_config(reportbuilder $report) {
        if (!in_array('user_context', $this->sourcejoins)) {
            $this->sourcejoins[] = 'user_context';
        }
        [$this->sourcewhere, $this->sourceparams] = util::get_report_on_subjects_sql(
            $report->reportfor,
            "$this->subject_instance_join.subject_user_id",
            "user_context"
        );

        $restrictions = $this->create_course_visibility_restrictions($report, []);

        $report->set_post_config_restrictions($restrictions);
    }

    protected function define_columnoptions() {
        $columnoptions = parent::define_columnoptions();

        $columnoptions[] = new rb_column_option(
            'activity',
            'nameviewformlink',
            get_string('activity_name_linked_to_view_form', 'mod_perform'),
            "perform.name",
            [
                'displayfunc' => 'subject_instance_name_linked_to_view_form',
                'extrafields' => [
                    'subject_instance_id' => "base.id",
                    'status' => 'base.status',
                ],
                'defaultheading' => get_string('activity_name', 'mod_perform'),
                'joins' => 'perform',
            ]
        );

        // Participant count
        $columnoptions[] = new rb_column_option(
            'subject_instance',
            'participant_count_performance_reporting',
            get_string('participants', 'rb_source_perform_response_subject_instance'),
            "(
                SELECT COUNT('x')
                FROM {perform_participant_instance} ppi
                LEFT JOIN {user} ppc ON ppi.participant_id = ppc.id 
                    AND ppi.participant_source = " . participant_source::INTERNAL . "
                WHERE ppi.subject_instance_id = base.id AND (
                    ppi.participant_source = " . participant_source::EXTERNAL . " 
                    OR ppc.deleted = 0
                )
            )",
            [
                'dbdatatype' => 'integer',
                'displayfunc' => 'participant_count_performance_reporting',
                'iscompound' => true,
                'issubquery' => true,
                'extrafields' => [
                    'status' => "base.status"
                ],
            ]
        );

        $columnoptions[] = new rb_column_option(
            'subject_instance',
            'actions',
            null,
            "base.id",
            [
                'displayfunc' => 'subject_instance_reporting_actions',
                'noexport' => true,
                'nosort' => true,
                'extrafields' => [
                    'status' => "base.status"
                ],
            ]
        );

        return $columnoptions;
    }
}