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
 * @author: Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package: mod_perform
 */

defined('MOODLE_INTERNAL') || die();

use core\entity\user;
use mod_perform\models\activity\participant_source;
use mod_perform\state\subject_instance\pending;
use mod_perform\rb\traits\activity_trait;
use mod_perform\rb\traits\subject_instance_trait;
use totara_core\advanced_feature;
use totara_job\rb\source\report_trait;
use totara_reportbuilder\rb\source\report_trait as core_report_trait;

/**
 * Performance subject instance report.
 *
 * Class rb_source_perform_participation_subject_instance
 */
class rb_source_perform_participation_subject_instance extends rb_base_source {
    use core_report_trait;
    use report_trait;
    use subject_instance_trait;
    use activity_trait;

    /**
     * Constructor.
     *
     * @param mixed $groupid
     * @param rb_global_restriction_set|null $globalrestrictionset
     * @throws coding_exception
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'subject_user_id');

        $this->sourcetitle = get_string('sourcetitle', 'rb_source_perform_participation_subject_instance');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_perform_participation_subject_instance');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_perform_participation_subject_instance');

        $this->base = '{perform_subject_instance}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();

        $this->add_subject_instance_to_base();

        $this->add_activity(
            new rb_join(
                'perform',
                'INNER',
                '{perform}',
                'track.activity_id = perform.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'track'
            )
        );

        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->usedcomponents[] = 'mod_perform';

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     *
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    /**
     * Define join table list.
     *
     * @return array
     */
    protected function define_joinlist() {
        $joinlist = [
            new rb_join(
                'track_user_assignment',
                'INNER',
                '{perform_track_user_assignment}',
                "track_user_assignment.id = base.track_user_assignment_id",
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'track',
                'INNER',
                '{perform_track}',
                'track.id = track_user_assignment.track_id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'track_user_assignment'
            ),
        ];

        $this->add_context_tables($joinlist, 'base', 'subject_user_id', CONTEXT_USER);

        return $joinlist;
    }

    /**
     * Define the column options available for this report.
     *
     * @return array
     */
    protected function define_columnoptions() {
        $pending_state = pending::get_code();

        $filter_hidden_users = 'ppc.deleted = 0';
        if (get_config(null, 'perform_hide_suspended_users')) {
            $filter_hidden_users = '(ppc.deleted = 0 and ppc.suspended = 0)';
        }

        $participant_count_sql_fragment = "
        CASE
            WHEN base.status = $pending_state THEN -1
            ELSE (
                SELECT COUNT('x')
                FROM {perform_participant_instance} ppi
                LEFT JOIN {user} ppc ON ppi.participant_id = ppc.id
                    AND ppi.participant_source = " . participant_source::INTERNAL . "
                WHERE ppi.subject_instance_id = base.id AND (
                    ppi.participant_source = " . participant_source::EXTERNAL . "
                    OR {$filter_hidden_users}
                )
            )
        END
        ";

        $columnoptions = [
            new rb_column_option(
                'subject_instance',
                'participant_count',
                get_string('participant_count', 'rb_source_perform_participation_subject_instance'),
                "($participant_count_sql_fragment)",
                [
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'participant_count',
                    'iscompound' => true,
                    'issubquery' => true,
                    'extrafields' => [
                        'subject_instance_id' => "base.id",
                        'status' => 'base.status'
                    ]
                ]
            ),
        ];

        return $columnoptions;
    }

    /**
     * Define the filter options available for this report.
     *
     * @return array
     */
    protected function define_filteroptions() {
        return [];
    }

    /**
     * Define the default columns for this report.
     *
     * @return array
     */
    protected function define_defaultcolumns() {
        return self::get_default_columns();
    }

    /**
     * Define the default filters for this report.
     *
     * @return array
     */
    protected function define_defaultfilters() {
        return self::get_default_filters();
    }

    /**
     * The default columns for this and embedded reports.
     *
     * @return array
     */
    public static function get_default_columns() {
        return [
            [
                'type' => 'subject_user',
                'value' => 'namelink',
                'heading' => get_string('subject_name', 'rb_source_perform_participation_subject_instance')
            ],
            [
                'type' => 'activity',
                'value' => 'name',
                'heading' => get_string('activity_name', 'mod_perform')
            ],
            [
                'type' => 'activity',
                'value' => 'type',
                'heading' => get_string('activity_type', 'mod_perform')
            ],
            [
                'type' => 'subject_instance',
                'value' => 'created_at',
                'heading' => get_string('date_created', 'mod_perform')
            ],
            [
                'type' => 'subject_instance',
                'value' => 'progress',
                'heading' => get_string('progress', 'mod_perform')
            ],
            [
                'type' => 'subject_instance',
                'value' => 'availability',
                'heading' => get_string('availability', 'mod_perform')
            ],
            [
                'type' => 'subject_instance',
                'value' => 'participant_count',
                'heading' => get_string('participant_count', 'rb_source_perform_participation_subject_instance')
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
                'type' => 'subject_user',
                'value' => 'fullname',
            ],
            [
                'type' => 'subject_instance',
                'value' => 'created_at',
            ],
            [
                'type' => 'activity',
                'value' => 'type'
            ],
            [
                'type' => 'subject_instance',
                'value' => 'progress',
            ],
            [
                'type' => 'subject_instance',
                'value' => 'availability',
            ],
            [
                'type' => 'subject_instance',
                'value' => 'status',
            ],
            [
                'type' => 'subject_user',
                'value' => 'deleted',
                'defaultvalue' => ['value' => 0]
            ],
        ];
    }

    /**
     * Define the available content options for this report.
     *
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = [];

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions, 'subject_user');

        return $contentoptions;
    }

    /**
     * Define the available param options for this report.
     *
     * @return array
     */
    protected function define_paramoptions() {
        $paramoptions = [
            new rb_param_option(
                'activity_id',
                'track.activity_id',
                'track'
            ),
            new rb_param_option(
                'subject_user_id',
                'base.subject_user_id'
            ),
        ];
        return $paramoptions;
    }

    /**
     * Disable subject reports if the performance activities feature is disabled.
     *
     * @return bool
     */
    public static function is_source_ignored() {
        return advanced_feature::is_disabled('performance_activities');
    }

    /**
     * Inject column_test data into database.
     *
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        $perform_generator = \mod_perform\testing\generator::instance();
        $perform_generator->create_subject_instance([
            'activity_name' => 'Weekly catchup',
            'subject_is_participating' => true,
            'subject_user_id' => user::repository()->get()->last()->id,
            'other_participant_id' => user::logged_in()->id,
        ]);
    }
}
