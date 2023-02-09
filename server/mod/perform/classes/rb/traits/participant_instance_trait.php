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
use mod_perform\models\activity\participant_source;
use mod_perform\state\participant_instance\closed;
use mod_perform\state\participant_instance\complete;
use mod_perform\state\participant_instance\participant_instance_availability;
use mod_perform\state\participant_instance\participant_instance_progress;
use mod_perform\state\state_helper;
use rb_base_source;
use rb_column_option;
use rb_filter_option;
use rb_join;
use totara_core\relationship\relationship;
use totara_core\relationship\relationship_provider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->dirroot}/totara/reportbuilder/lib.php");

/**
 * Trait participant_instance_trait
 */
trait participant_instance_trait {
    /** @var string $participant_instance_join */
    protected $participant_instance_join = null;

    /**
     * Add participant instance info where participant_instance is the base table.
     *
     * @throws coding_exception
     */
    protected function add_participant_instance_to_base() {
        /** @var participant_instance_trait|rb_base_source $this */
        if (isset($this->participant_instance_join)) {
            throw new coding_exception('Participant instance info can be added only once!');
        }

        $this->participant_instance_join = 'base';

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_participant_instance_joins();
        $this->add_participant_instance_columns();
        $this->add_participant_instance_filters();
    }

    /**
     * Add participant instance info.
     * If a new join isn't specified then the existing join will be used.
     *
     * @param rb_join $join
     * @throws coding_exception
     */
    protected function add_participant_instance(rb_join $join = null): void {
        $join = $join ?? $this->get_join('participant_instance');

        /** @var participant_instance_trait|rb_base_source $this */
        if (isset($this->participant_instance_join)) {
            throw new coding_exception('Participant instance info can be added only once!');
        }

        if (!in_array($join, $this->joinlist, true)) {
            $this->joinlist[] = $join;
        }
        $this->participant_instance_join = $join->name;

        // Add component for lookup of display functions and other stuff.
        if (!in_array('mod_perform', $this->usedcomponents, true)) {
            $this->usedcomponents[] = 'mod_perform';
        }

        $this->add_participant_instance_joins();
        $this->add_participant_instance_columns();
        $this->add_participant_instance_filters();
    }

    /**
     * Add joins required for participant instance column and filter options to report.
     */
    protected function add_participant_instance_joins(): void {
        /** @var participant_instance_trait|rb_base_source $this */
        $join = $this->participant_instance_join;

        $this->joinlist[] = new rb_join(
            'totara_core_relationship',
            'INNER',
            '{totara_core_relationship}',
            "{$join}.core_relationship_id = totara_core_relationship.id",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            [$join]
        );

        $this->joinlist[] = new rb_join(
            'subject_instance',
            'INNER',
            '{perform_subject_instance}',
            "{$join}.subject_instance_id = subject_instance.id",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            [$join]
        );

        $this->add_activity_joins_for_participant_instance();

        $this->add_core_user_tables(
            $this->joinlist,
            $join,
            null, // Not necessary as we are specifying a custom condition.
            'participant_user',
            "participant_user.id = " . $this->get_anonymised_field_sql("$join.participant_id")
            . " AND {$join}.participant_source = " . participant_source::INTERNAL,
            ['perform']
        );

        $this->joinlist[] = new \rb_join(
            'external_participant',
            'LEFT',
            '{perform_participant_external}',
            "external_participant.id = $join.participant_id AND $join.participant_source = " . participant_source::EXTERNAL,
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            [$join]
        );
    }

    /**
     * We need to join a few tables in order to get the anonymity status on the activity.
     */
    private function add_activity_joins_for_participant_instance(): void {
        $join = $this->participant_instance_join;

        $this->joinlist[] = new rb_join(
            'track_user_assignment',
            'INNER',
            '{perform_track_user_assignment}',
            "subject_instance.track_user_assignment_id = track_user_assignment.id",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            [$join, 'subject_instance']
        );

        $this->joinlist[] = new rb_join(
            'track',
            'INNER',
            '{perform_track}',
            "track_user_assignment.track_id = track.id",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            [$join, 'subject_instance', 'track_user_assignment']
        );

        $this->joinlist[] = new rb_join(
            'perform',
            'INNER',
            '{perform}',
            "track.activity_id = perform.id",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            [$join, 'subject_instance', 'track_user_assignment', 'track']
        );
    }

    /**
     * Add columnoptions for participant instances to report.
     */
    protected function add_participant_instance_columns(): void {
        /** @var participant_instance_trait|rb_base_source $this */
        $join = $this->participant_instance_join;

        $this->columnoptions[] = new rb_column_option(
            'participant_instance',
            'progress',
            get_string('progress', 'mod_perform'),
            "{$join}.progress",
            [
                'joins' => [$join],
                'dbdatatype' => 'integer',
                'displayfunc' => 'state_display_name',
                'extracontext' => [
                    'object_type' => 'participant_instance',
                    'state_type' => participant_instance_progress::get_type(),
                ],
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'participant_instance',
            'availability',
            get_string('availability', 'mod_perform'),
            "{$join}.availability",
            [
                'joins' => [$join],
                'dbdatatype' => 'integer',
                'displayfunc' => 'state_display_name',
                'extracontext' => [
                    'object_type' => 'participant_instance',
                    'state_type' => participant_instance_availability::get_type(),
                ],
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'participant_instance',
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
            'participant_instance',
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
            'participant_instance',
            'overdue',
            get_string('overdue', 'mod_perform'),
            "CASE
                    WHEN
                        subject_instance.due_date <= " . time() . "
                        AND NOT (
                            {$join}.progress = " . complete::get_code() . "
                            OR {$join}.availability = " . closed::get_code() . "
                        )
                    THEN 1
                    ELSE 0
                END",
            [
                'joins' => [$join, 'subject_instance'],
                'dbdatatype' => 'boolean',
                'displayfunc' => 'yes_or_no',
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'participant_instance',
            'relationship_name',
            get_string('relationship_name', 'mod_perform'),
            $this->get_anonymised_field_sql('totara_core_relationship.idnumber'),
            [
                'joins' => [$join, 'totara_core_relationship', 'perform'],
                'displayfunc' => 'relationship_name',
                'extrafields' => [
                    'anonymous_responses' => "perform.anonymous_responses",
                ],
            ]
        );
        $this->columnoptions[] = new rb_column_option(
            'participant_instance',
            'relationship_id',
            get_string('relationship_id', 'mod_perform'),
            $this->get_anonymised_field_sql('totara_core_relationship.id'),
            [
                'joins' => [$join, 'totara_core_relationship', 'perform'],
                'displayfunc' => 'integer',
                'selectable' => false,
            ]
        );
        $this->columnoptions[] = new rb_column_option(
            'participant_instance',
            'relationship_sort_order',
            get_string('relationship_sort_order', 'mod_perform'),
            $this->get_anonymised_field_sql('totara_core_relationship.sort_order'),
            [
                'joins' => [$join, 'totara_core_relationship', 'perform'],
                'displayfunc' => 'integer',
            ]
        );

        // Add columns for participants (internal / external)
        $this->add_participant_columns($join);

        $this->add_core_user_columns($this->columnoptions, 'participant_user', 'participant_user', true);
    }

    private function add_participant_columns(string $join): void {
        global $CFG, $DB;

        $this->columnoptions[] = new rb_column_option(
            'participant_instance',
            'participant_source',
            get_string('participant_source', 'rb_source_perform_participation_participant_instance'),
            $this->get_anonymised_field_sql("$join.participant_source"),
            [
                'joins' => $join,
                'dbdatatype' => 'char',
                'displayfunc' => 'participant_source',
                'extrafields' => [
                    'anonymous_responses' => "perform.anonymous_responses",
                ],
            ]
        );

        $usednamefields = totara_get_all_user_name_fields_join('participant_user', null, true);
        $allnamefields = totara_get_all_user_name_fields_join('participant_user');

        $this->columnoptions[] = new rb_column_option(
            'participant_instance',
            'participant_name',
            get_string('participant_name', 'rb_source_perform_participation_participant_instance'),
            $this->get_anonymised_field_sql(
                "CASE
                    WHEN
                        {$join}.participant_source = " . participant_source::EXTERNAL . "
                    THEN external_participant.name
                    ELSE ".$DB->sql_concat_join("' '", $usednamefields)."
                END"
            ),
            [
                'joins' => [$join, 'external_participant', 'participant_user', 'perform'],
                'dbdatatype' => 'char',
                'displayfunc' => 'participant_link',
                'extrafields' => array_merge(
                    [
                        'participant_source' => "{$join}.participant_source",
                        'id' => "participant_user.id",
                        'deleted' => "participant_user.deleted",
                        'anonymous_responses' => "perform.anonymous_responses",
                    ],
                    $allnamefields
                ),
            ]
        );

        $this->columnoptions[] = new rb_column_option(
            'participant_instance',
            'participant_email',
            get_string('participant_email', 'rb_source_perform_participation_participant_instance'),
            // use CASE to include/exclude email in SQL
            // so search won't reveal hidden results
            $this->get_anonymised_field_sql(
                "CASE
                    WHEN
                        {$join}.participant_source = " . participant_source::EXTERNAL . "
                    THEN external_participant.email
                    ELSE CASE WHEN participant_user.maildisplay <> 1 THEN '-' ELSE participant_user.email END
                END"
            ),
            [
                'joins' => [$join, 'external_participant', 'participant_user', 'perform'],
                'displayfunc' => 'participant_email',
                'extrafields' => [
                    'participant_source' => "{$join}.participant_source",
                    'emailstop' => "participant_user.emailstop",
                    'maildisplay' => "participant_user.maildisplay",
                    'anonymous_responses' => "perform.anonymous_responses",
                ],
                'dbdatatype' => 'char',
                'outputformat' => 'text',
            ]
        );

        // Only include this column if email is among fields allowed by showuseridentity setting or
        // if the current user has the 'moodle/site:config' capability.
        $canview = !empty($CFG->showuseridentity) && in_array('email', explode(',', $CFG->showuseridentity));
        $canview |= has_capability('moodle/site:config', \context_system::instance());
        if ($canview) {
            $this->columnoptions[] = new \rb_column_option(
                'participant_instance',
                'participant_email_unobscured',
                get_string('participant_email_unobscured', 'rb_source_perform_participation_participant_instance'),
                $this->get_anonymised_field_sql(
                    "CASE
                        WHEN
                            {$join}.participant_source = " . participant_source::EXTERNAL . "
                        THEN external_participant.email
                        ELSE participant_user.email
                    END"
                ),
                [
                    'joins' => [$join, 'external_participant', 'participant_user', 'perform'],
                    'displayfunc' => 'participant_email_unobscured',
                    'extrafields' => [
                        'participant_source' => "{$join}.participant_source",
                        'anonymous_responses' => "perform.anonymous_responses",
                    ],
                    // Users must have viewuseridentity to see the
                    // unobscured email address.
                    'capability' => 'moodle/site:viewuseridentity',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                ]
            );
        }
    }

    /**
     * Add filteroptions for participant instances to report.
     */
    protected function add_participant_instance_filters() {
        global $CFG;

        $this->filteroptions[] = new rb_filter_option(
            'participant_instance',
            'progress',
            get_string('progress', 'mod_perform'),
            'select',
            [
                'selectchoices' => state_helper::get_all_display_names(
                    'participant_instance', participant_instance_progress::get_type()
                ),
            ]
        );

        $this->filteroptions[] = new rb_filter_option(
            'participant_instance',
            'availability',
            get_string('availability', 'mod_perform'),
            'select',
            [
                'selectchoices' => state_helper::get_all_display_names(
                    'participant_instance', participant_instance_availability::get_type()
                ),
                'simplemode' => true
            ]
        );

        $this->filteroptions[] = new rb_filter_option(
            'participant_instance',
            'overdue',
            get_string('overdue', 'mod_perform'),
            'multicheck',
            [
                'simplemode' => true,
                'selectfunc' => 'yesno_list',
            ]
        );

        $this->filteroptions[] = new rb_filter_option(
            'participant_instance',
            'created_at',
            get_string('date_created', 'mod_perform'),
            'date'
        );

        $this->filteroptions[] = new rb_filter_option(
            'participant_instance',
            'updated_at',
            get_string('date_updated', 'mod_perform'),
            'date'
        );

        $this->filteroptions[] = new rb_filter_option(
            'participant_instance',
            'relationship_id',
            get_string('participant_relationship', 'mod_perform'),
            'perform_relationship_name',
        );

        $this->filteroptions[] = new rb_filter_option(
            'participant_instance',
            'participant_source',
            get_string('participant_source', 'rb_source_perform_participation_participant_instance'),
            'select',
            [
                'selectchoices' => [
                    participant_source::EXTERNAL => get_string('participant_source_external', 'rb_source_perform_participation_participant_instance'),
                    participant_source::INTERNAL => get_string('participant_source_internal', 'rb_source_perform_participation_participant_instance'),
                ]
            ]
        );

        $this->filteroptions[] = new rb_filter_option(
            'participant_instance',
            'participant_name',
            get_string('participant_name', 'rb_source_perform_participation_participant_instance'),
            'text'
        );

        $this->filteroptions[] = new rb_filter_option(
            'participant_instance',
            'participant_email',
            get_string('participant_email', 'rb_source_perform_participation_participant_instance'),
            'text'
        );

        // Only include this column if email is among fields allowed by showuseridentity setting or
        // if the current user has the 'moodle/site:config' capability.
        $canview = !empty($CFG->showuseridentity) && in_array('email', explode(',', $CFG->showuseridentity));
        $canview |= has_capability('moodle/site:config', \context_system::instance());
        if ($canview) {
            $this->filteroptions[] = new rb_filter_option(
                'participant_instance',
                'participant_email_unobscured',
                get_string('participant_email_unobscured', 'rb_source_perform_participation_participant_instance'),
                'text'
            );
        }

        $this->add_core_user_filters($this->filteroptions, 'participant_user', true);
    }

    /**
     * Wrap field SQL with a CASE clause in order to anonymise the participant's details.
     * This is needed on the database level in order to prevent filtering by anonymised fields.
     *
     * @param string $field
     * @return string
     */
    protected function get_anonymised_field_sql(string $field): string {
        return "CASE
            WHEN perform.anonymous_responses = 0
            THEN $field
            ELSE NULL
        END";
    }

    /**
     * @depreacted since Totara 17.0
     * @return array
     */
    private function get_relationship_type_options() {
        debugging(
            'mod_perform\rb\traits\participant_instance_trait::get_relationship_type_options() is deprecated,
            use mod_perform\rb\filter\relationship_name::get_item_options()',
            DEBUG_DEVELOPER
        );
        $options = [];
        $relationships = (new relationship_provider())
            ->filter_by_component('mod_perform', true)
            ->get_compatible_relationships(['user_id']);
        /** @var relationship $relationship */
        foreach ($relationships as $relationship) {
            $options[$relationship->id] = $relationship->get_name();
        }
        return $options;
    }
}
