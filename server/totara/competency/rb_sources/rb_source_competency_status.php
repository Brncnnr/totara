<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_competency
 */

use totara_competency\entity\assignment;
use totara_competency\user_groups;
use totara_competency\entity\competency_achievement;
use totara_core\advanced_feature;
use totara_job\rb\source\report_trait;

defined('MOODLE_INTERNAL') || die();

class rb_source_competency_status extends rb_base_source {
    use report_trait;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'user_id');

        $this->base = '{totara_competency_achievement}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_competency_status');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_competency_status');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_competency_status');
        $this->usedcomponents[] = 'totara_plan';
        $this->usedcomponents[] = 'totara_hierarchy';
        $this->usedcomponents[] = 'totara_competency';

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    public static function is_source_ignored() {
        return !advanced_feature::is_enabled('competencies');
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    /**
     * Define table join list
     * @return array
     */
    protected function define_joinlist() {

        $joinlist = array(
            new rb_join(
                'competency',
                'LEFT',
                '{comp}',
                'competency.id = base.competency_id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'scale_assignments',
                'LEFT',
                '{comp_scale_assignments}',
                'scale_assignments.frameworkid = competency.frameworkid',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'competency'
            ),
            new rb_join(
                'scale',
                'LEFT',
                '{comp_scale}',
                'scale.id = scale_assignments.scaleid',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'scale_assignments'
            ),
            new rb_join(
                'scale_values',
                'LEFT',
                '{comp_scale_values}',
                'scale_values.id = base.scale_value_id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            // This is the default minimum proficiency level. The name is not very helpful.
            new rb_join(
                'scale_values_2',
                'LEFT',
                '{comp_scale_values}',
                'scale_values_2.id = scale.minproficiencyid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'scale'
            ),
            // This is the minimum proficiency override, which overrides the default if specified.
            new rb_join(
                'scale_values_proficiency_override',
                'LEFT',
                '{comp_scale_values}',
                'scale_values_proficiency_override.id = assignment.minproficiencyid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'assignment'
            ),
            new rb_join(
                'assignment',
                'INNER',
                "{totara_competency_assignments}",
                "base.assignment_id = assignment.id",
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
            new rb_join(
                'assignment_users',
                'LEFT',
                "{totara_competency_assignment_users}",
                "assignment_users.assignment_id = base.assignment_id AND assignment_users.user_id = base.user_id",
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'assignment_cohorts',
                'LEFT',
                "{cohort}",
                "assignment.user_group_type = '".user_groups::COHORT."' AND assignment.user_group_id = assignment_cohorts.id",
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'assignment'
            ),
            new rb_join(
                'assignment_positions',
                'LEFT',
                "{pos}",
                "assignment.user_group_type = '".user_groups::POSITION."' AND assignment.user_group_id = assignment_positions.id",
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'assignment'
            ),
            new rb_join(
                'pos_type',
                'LEFT',
                '{pos_type}',
                'assignment_positions.typeid = pos_type.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'assignment_positions'
            ),
            new rb_join(
                'assignment_organisations',
                'LEFT',
                "{org}",
                "assignment.user_group_type = '".user_groups::ORGANISATION."' AND assignment.user_group_id = assignment_organisations.id",
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'assignment'
            ),
            new rb_join(
                'org_type',
                'LEFT',
                '{org_type}',
                'assignment_organisations.typeid = org_type.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'assignment_organisations'
            ),
        );

        // include some standard joins
        $this->add_core_user_tables($joinlist, 'base', 'user_id');
        $this->add_totara_job_tables($joinlist, 'base', 'user_id');

        return $joinlist;
    }

    /**
     * Define column options
     * @return array
     */
    protected function define_columnoptions(): array {

        $columnoptions = array(
            new rb_column_option(
                'competency_status',  // Type.
                'scale_value_name',          // Value.
                get_string('achievement_level', 'totara_competency'), // Name.
                'scale_values.name',    // Field.
                [
                    'joins' => 'scale_values',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                ] // Options.
            ),
            new rb_column_option(
                'competency_status',
                'proficient',
                get_string('proficiency_status', 'totara_competency'),
                'base.proficient',
                [
                    'displayfunc' => 'competency_status_proficient',
                    'dbdatatype' => 'integer'
                ]
            ),
            new rb_column_option(
                'competency_status',
                'achievement_status',
                get_string('achievement_status', 'rb_source_competency_status'),
                'base.status',
                [
                    'displayfunc' => 'achievement_status',
                    'dbdatatype' => 'integer'
                ]
            ),
            new rb_column_option(
                'competency_status',
                'scale_value_numericscore',
                get_string('scale_value_numeric_score', 'rb_source_competency_status'),
                'scale_values.numericscore',
                [
                    'joins' => 'scale_values',
                    'dbdatatype' => 'float',
                    'displayfunc' => 'comp_scale_value_numericscore'
                ]
            ),
            new rb_column_option(
                'competency_status',
                'scale_value_id',
                get_string('achievement_level_related_id', 'rb_source_competency_status'),
                'base.scale_value_id',
                [
                    'displayfunc' => 'integer',
                    'dbdatatype' => 'integer'
                ]
            ),
            new rb_column_option(
                'competency',
                'fullname',
                get_string('competency_name', 'rb_source_competency_status'),
                'competency.fullname',
                [
                    'joins' => 'competency',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                ]
            ),
            new rb_column_option(
                'competency',
                'idnumber',
                get_string('competency_id_number', 'rb_source_competency_status'),
                'competency.idnumber',
                [
                    'joins' => 'competency',
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                ]
            ),
            new rb_column_option(
                'competency',
                'id',
                get_string('competency_id', 'rb_source_competency_status'),
                'base.competency_id',
                [
                    'displayfunc' => 'integer'
                ]
            ),
            new rb_column_option(
                'competency',
                'time_created',
                get_string('time_created', 'rb_source_competency_status'),
                'base.time_created',
                [
                    'displayfunc' => 'nice_date',
                    'dbdatatype' => 'timestamp'
                ]
            ),
            // This is the minimum proficiency level. The type/value is a bit misleading.
            new rb_column_option(
                'competency',
                'name',
                get_string('scale_values_name', 'rb_source_competency_status'),
                'COALESCE(scale_values_proficiency_override.name, scale_values_2.name)',
                [
                    'joins' => ['scale', 'scale_values_2', 'scale_values_proficiency_override', 'scale_assignments', 'competency'],
                    'displayfunc' => 'format_string',
                    'dbdatatype' => 'text'
                ]
            ),
        );

        if (advanced_feature::is_enabled('competency_assignment')) {
            $columnoptions = array_merge($columnoptions, [
                new rb_column_option(
                    'assignment',
                    'assignment_type',
                    get_string('label_assignment_type', 'rb_source_competency_assignment_users'),
                    "(
                        CASE WHEN type = '".assignment::TYPE_ADMIN."' AND user_group_type <> '".user_groups::USER."'
                        THEN user_group_type
                        ELSE type END
                    )",
                    [
                        'joins' => 'assignment',
                        'displayfunc' => 'display_assignment_type',
                    ]
                ),
                new rb_column_option(
                    'assignment',
                    'user_group',
                    get_string('label_user_group', 'rb_source_competency_assignment_users'),
                    'assignment.user_group_type',
                    [
                        'joins' => ['assignment_cohorts', 'assignment_positions', 'assignment_organisations', 'auser'],
                        'displayfunc' => 'display_user_group',
                        'extrafields' => [
                            'user_group_type' => 'assignment.user_group_type',
                            'user_id' => 'auser.id',
                            'user_firstname' => 'auser.firstname',
                            'user_lastname' => 'auser.lastname',
                            'user_firstnamephonetic' => 'auser.firstnamephonetic',
                            'user_lastnamephonetic' => 'auser.lastnamephonetic',
                            'user_middlename' => 'auser.middlename',
                            'user_alternatename' => 'auser.alternatename',
                            'user_idnumber' => 'auser.idnumber',
                            'pos_id' => 'assignment_positions.id',
                            'pos_name' => 'assignment_positions.fullname',
                            'pos_idnumber' => 'assignment_positions.idnumber',
                            'org_id' => 'assignment_organisations.id',
                            'org_name' => 'assignment_organisations.fullname',
                            'org_idnumber' => 'assignment_organisations.idnumber',
                            'coh_id' => 'assignment_cohorts.id',
                            'coh_name' => 'assignment_cohorts.name',
                            'coh_idnumber' => 'assignment_cohorts.idnumber',
                        ],
                    ]
                ),
                new rb_column_option(
                    'assignment',
                    'status',
                    get_string('assignment_status', 'rb_source_competency_status'),
                    'assignment.status',
                    [
                        'joins' => 'assignment',
                        'displayfunc' => 'competency_assignments_status',
                        'dbdatatype' => 'integer'
                    ]
                ),
                new rb_column_option(
                    'assignment',
                    'archived_at',
                    get_string('header_archived_date', 'totara_competency'),
                    'assignment.archived_at',
                    [
                        'joins' => 'assignment',
                        'displayfunc' => 'nice_date',
                        'dbdatatype' => 'timestamp'
                    ]
                ),
                new rb_column_option(
                    'assignment',
                    'created_at',
                    get_string('date_assigned', 'rb_source_competency_status'),
                    'assignment_users.created_at',
                    [
                        'joins' => ['assignment_users', 'assignment'],
                        'displayfunc' => 'nice_date',
                        'dbdatatype' => 'timestamp'
                    ]
                ),
                new rb_column_option(
                    'assignment',
                    'userid',
                    get_string('activity_log_link_text', 'rb_source_competency_status'),
                    'auser.id',
                    [
                        'joins' => 'auser',
                        'displayfunc' => 'assignment_activity_log_link',
                        'dbdatatype' => 'integer',
                        'extrafields' => [
                            'competency_id' => 'base.competency_id'
                        ]
                    ]
                )
            ]);
        }

        // include some standard columns
        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Define filter options
     * @return array
     */
    protected function define_filteroptions(): array {
        $filteroptions = array(
            new rb_filter_option(
                'competency',
                'fullname',
                get_string('competency_name', 'rb_source_competency_status'),
                'text'
            ),
            new rb_filter_option(
                'competency',
                'idnumber',
                get_string('competency_id_number', 'rb_source_competency_status'),
                'text'
            ),
            new rb_filter_option(
                'competency',
                'time_created',
                get_string('time_created', 'rb_source_competency_status'),
                'date'
            ),
            new rb_filter_option(
                'competency_status',
                'scale_value_name',
                get_string('achievement_level', 'totara_competency'),
                'text'
            ),
            new rb_filter_option(
                'competency_status',
                'achievement_status',
                get_string('achievement_status', 'rb_source_competency_status'),
                'select',
                [
                    'simplemode' => true,
                    'selectchoices' => [
                        competency_achievement::ACTIVE_ASSIGNMENT => get_string('status_active', 'totara_competency'),
                        competency_achievement::ARCHIVED_ASSIGNMENT => get_string('status_archived', 'totara_competency'),
                        competency_achievement::SUPERSEDED => get_string('superseded', 'totara_competency'),
                    ]
                ]
            ),
            new rb_filter_option(
                'competency_status',
                'proficient',
                get_string('proficiency_status', 'totara_competency'),
                'select',
                [
                    'simplemode' => true,
                    'selectchoices' => [
                        '0' => get_string('not_proficient', 'totara_competency'),
                        '1' => get_string('proficient', 'totara_competency')
                    ]
                ]
            ),
        );
        if (advanced_feature::is_enabled('competency_assignment')) {
            $filteroptions[] = new rb_filter_option(
                'assignment',
                'status',
                get_string('assignment_status', 'rb_source_competency_status'),
                'select',
                [
                    'simplemode' => true,
                    'selectchoices' => [
                        assignment::STATUS_ACTIVE => get_string('status_active', 'totara_competency'),
                        assignment::STATUS_ARCHIVED => get_string('status_archived', 'totara_competency')
                    ]
                ]
            );
            $filteroptions[] = new rb_filter_option(
                'assignment',
                'assignment_type',
                get_string('label_assignment_type', 'rb_source_competency_assignment_users'),
                'select',
                [
                    'simplemode' => true,
                    'selectchoices' => [
                        user_groups::COHORT => get_string('user_group_type_cohort', 'totara_competency'),
                        user_groups::ORGANISATION => get_string('user_group_type_organisation', 'totara_competency'),
                        user_groups::POSITION => get_string('user_group_type_position', 'totara_competency'),
                        assignment::TYPE_ADMIN => get_string('assignment_type_admin', 'totara_competency'),
                        assignment::TYPE_LEGACY  => get_string('assignment_type_legacy', 'totara_competency'),
                        assignment::TYPE_OTHER => get_string('assignment_type_other', 'totara_competency'),
                        assignment::TYPE_SELF => get_string('assignment_type_self', 'totara_competency'),
                        assignment::TYPE_SYSTEM => get_string('assignment_type_system', 'totara_competency')
                    ]
                ]
            );
        }

        // include some standard filters
        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions, 'base', 'user_id');

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',       // parameter name
                'base.user_id',  // field
                null            // joins
            ),
            new rb_param_option(
                'compid',
                'base.competency_id'
            ),
        );

        return $paramoptions;
    }

    /**
     * Define default columns
     * @return array
     */
    protected function define_defaultcolumns(): array {
        $defaultcolumns = array(
            // User name
            [
                'type' => 'user',
                'value' => 'namelink'
            ],
            // Competency name
            [
                'type' => 'competency',
                'value' => 'fullname'
            ],
            // Proficiency status
            [
                'type' => 'competency_status',
                'value' => 'proficient',
            ],
            // Achievement level
            [
                'type' => 'competency_status',
                'value' => 'scale_value_name',
            ],
            // Date achievement level achieved
            [
                'type' => 'competency',
                'value' => 'time_created',
            ],
            // Achievement status
            [
                'type' => 'competency_status',
                'value' => 'achievement_status',
            ],
        );
        if (advanced_feature::is_enabled('competency_assignment')) {
            // Link to the assignment Activity log
            $defaultcolumns[] = [
                'type' => 'assignment',
                'value' => 'userid',
            ];
            // Assignment status
            $defaultcolumns[] = [
                'type' => 'assignment',
                'value' => 'status',

            ];
        }
        return $defaultcolumns;
    }

    /**
     * Define default filters
     * @return array
     */
    protected function define_defaultfilters(): array {
        $defaultfilters = array(
            // Competency name.
            [
                'type' => 'competency',
                'value' => 'fullname',
            ],
            // Proficiency status.
            [
                'type' => 'competency_status',
                'value' => 'proficient',
            ],
            // Achievement status.
            [
                'type' => 'competency_status',
                'value' => 'achievement_status',
            ],
        );
        if (advanced_feature::is_enabled('competency_assignment')) {
            // Assignment status.
            $defaultfilters[] = [
                'type' => 'assignment',
                'value' => 'status',
            ];
        }

        return $defaultfilters;
    }

    /**
     * Inject column_test data into database.
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        $now = time();

        $assignment = new assignment();
        $assignment->type = assignment::TYPE_OTHER;
        $assignment->competency_id = 100;
        $assignment->user_group_type = 'position';
        $assignment->user_group_id = 201;
        $assignment->optional = false;
        $assignment->created_by = 301;
        $assignment->created_at = $now;
        $assignment->updated_at = $now;
        $assignment->save();

        $achievement = new competency_achievement();
        $achievement->competency_id = 100;
        $achievement->user_id = 200;
        $achievement->assignment_id = $assignment->id;
        $achievement->scale_value_id = 400;
        $achievement->proficient = 1;
        $achievement->status = competency_achievement::ACTIVE_ASSIGNMENT;
        $achievement->time_created = $now;
        $achievement->time_scale_value = $now;
        $achievement->time_status = $now;
        $achievement->last_aggregated = $now;
        $achievement->save();
    }

    /**
     * Returns expected result for column_test.
     *
     * @codeCoverageIgnore
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }

        global $DB;
        return $DB->count_records('totara_competency_achievement');
    }

}
