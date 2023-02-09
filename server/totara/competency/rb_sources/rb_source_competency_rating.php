<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_competency
 */

use pathway_manual\models\roles\appraiser;
use pathway_manual\models\roles\manager;
use pathway_manual\models\roles\self_role;
use totara_competency\entity\assignment;
use totara_competency\models\assignment_actions;
use totara_competency\user_groups;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * A report builder source for competency ratings
 */
class rb_source_competency_rating extends rb_base_source {

    use totara_cohort\rb\source\report_trait;
    use totara_job\rb\source\report_trait;

    /**
     * Constructor
     *
     * @param int $groupid (ignored)
     * @param rb_global_restriction_set $globalrestrictionset
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        global $DB;

        $this->usedcomponents[] = 'totara_competency';

        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }

        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        /*
         * Base consists of combinations of user/competency/rating-role/ratings.
         * - Exclude draft, but include archived and active assignments.
         * - Inner join to configured roles because we want at least one row per rating role.
         * - Left join to ratings because we also want to see missing ratings.
         * - Grouping to eliminate duplicates from multiple assignments and/or multiple manual pathways for one competency.
         *
         * 'manual_path' subqueries find all users assigned to competencies with at least 1 manual pathway.
         * We can't use totara_competency_assignment_users as it doesn't contain archived assigned users.
         */
        $uniqueid = $DB->sql_concat_join("','", [
            'manual_path.user_id', 'manual_path.competency_id', 'role.role', 'COALESCE(rating.id, 0)'
        ]);
        $this->base = "(
            SELECT {$uniqueid} AS id, manual_path.user_id, manual_path.competency_id, role.role,
                   rating.scale_value_id, rating.date_assigned, rating.assigned_by
              FROM 
              (
              " . self::get_expanded_manual_assignments_sql('job_assignment', 'positionid', 'position') . "

                  UNION

              " . self::get_expanded_manual_assignments_sql('job_assignment', 'organisationid', 'organisation') . "

                  UNION

              " . self::get_expanded_manual_assignments_sql('cohort_members', 'cohortid', 'cohort') . "
                    
                  UNION
                  
                    SELECT assignment.user_group_id user_id, assignment.competency_id, path.path_instance_id
                      FROM {totara_competency_assignments} assignment
                INNER JOIN {totara_competency_pathway} path
                        ON path.competency_id = assignment.competency_id
                       AND path.path_type = 'manual'
                     WHERE assignment.user_group_type = 'user'
                       AND assignment.status != " . assignment::STATUS_DRAFT . "
                  GROUP BY assignment.user_group_id, assignment.competency_id, path.path_instance_id
                  
              ) manual_path
            INNER JOIN {pathway_manual_role} role
                    ON role.path_manual_id = manual_path.path_instance_id
             LEFT JOIN {pathway_manual_rating} rating
                    ON rating.competency_id = manual_path.competency_id
                   AND rating.user_id = manual_path.user_id
                   AND rating.assigned_by_role = role.role 
              GROUP BY rating.id, manual_path.user_id, manual_path.competency_id, role.role,
                       rating.scale_value_id, rating.date_assigned, rating.assigned_by
        )";

        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = [];
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_competency_rating');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_competency_rating');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_competency_rating');

        $this->defaultsortcolumn = 'id';
        $this->defaultsortorder = SORT_ASC;

        parent::__construct();
    }

    /**
     * @param string $table
     * @param string $join_column
     * @param string $user_group_type
     * @return string
     */
    private static function get_expanded_manual_assignments_sql(string $table, string $join_column, string $user_group_type): string {
        return "SELECT expand_table.userid user_id, assignment.competency_id, path.path_instance_id
                  FROM {totara_competency_assignments} assignment
            INNER JOIN {totara_competency_pathway} path
                    ON path.competency_id = assignment.competency_id
                   AND path.path_type = 'manual'
            INNER JOIN {{$table}} expand_table
                    ON assignment.user_group_id = expand_table.{$join_column}
                   AND assignment.user_group_type = '{$user_group_type}'
                 WHERE assignment.status != " . assignment::STATUS_DRAFT . "
              GROUP BY expand_table.userid, assignment.competency_id, path.path_instance_id";
    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored or not.
     */
    public static function is_source_ignored() {
        return !advanced_feature::is_enabled('competencies');
    }

    /**
     * Are the global report restrictions implemented in the source?
     *
     * @return null|bool
     */
    public function global_restrictions_supported() {
        return true;
    }

    /**
     * @return array
     */
    protected function define_joinlist() {
        $joinlist = [
            new rb_join(
                'competency',
                'INNER',
                "{comp}",
                "base.competency_id = competency.id",
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
            new rb_join(
                'comp_type',
                'LEFT',
                '{comp_type}',
                'competency.typeid = comp_type.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'competency'
            ),
            new rb_join(
                'comp_framework',
                'INNER',
                '{comp_framework}',
                'competency.frameworkid = comp_framework.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'competency'
            ),
            new rb_join(
                'scale_value',
                'LEFT',
                '{comp_scale_values}',
                'base.scale_value_id = scale_value.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
            ),
            new rb_join(
                'scale_assignments',
                'LEFT',
                '{comp_scale_assignments}',
                'competency.frameworkid = scale_assignments.frameworkid',
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
            new rb_join(
                'scale',
                'LEFT',
                '{comp_scale}',
                'scale_assignments.scaleid = scale.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                'scale_assignments'
            ),
        ];

        $this->add_core_user_tables($joinlist, 'base','user_id');
        $this->add_core_user_tables($joinlist, 'base','assigned_by', 'ratinguser');
        $this->add_totara_job_tables($joinlist, 'base', 'user_id');

        return $joinlist;
    }

    protected function define_columnoptions() {
        $columnoptions = [
            new rb_column_option(
                'competency',
                'type',
                get_string('competency_type', 'rb_source_competency_rating'),
                'comp_type.fullname',
                [
                    'joins' => 'comp_type',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                ]
            ),
            new rb_column_option(
                'competency',
                'fullname',
                get_string('competency_name', 'rb_source_competency_rating'),
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
                get_string('competency_id_number', 'rb_source_competency_rating'),
                'competency.idnumber',
                [
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'plaintext',
                ]
            ),
            new rb_column_option(
                'framework',
                'fullname',
                get_string('competency_framework', 'rb_source_competency_rating'),
                'comp_framework.fullname',
                [
                    'joins' => 'comp_framework',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'framework',
                'idnumber',
                get_string('competency_framework_id_number', 'rb_source_competency_rating'),
                'comp_framework.idnumber',
                [
                    'joins' => 'comp_framework',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'plaintext',
                ]
            ),
            new rb_column_option(
                'scale',
                'name',
                get_string('competency_scale_name', 'rb_source_competency_rating'),
                'scale.name',
                [
                    'joins' => ['scale_assignments', 'scale'],
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_text',
                ]
            ),
            new rb_column_option(
                'rating',
                'rating',
                get_string('rating', 'rb_source_competency_rating'),
                'scale_value.name',
                [
                    'joins' => 'scale_value',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                ]
            ),
            new rb_column_option(
                'rating',
                'rolename',
                get_string('role', 'rb_source_competency_rating'),
                'base.role',
                [
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'rating_role_name'
                ]
            ),
            new rb_column_option(
                'rating',
                'rating_time',
                get_string('rating_time', 'rb_source_competency_rating'),
                'base.date_assigned',
                [
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'nice_datetime'
                ]
            ),
            new rb_column_option(
                'rating',
                'scale_value_numericscore',
                get_string('scale_value_numeric_score', 'rb_source_competency_rating'),
                'scale_value.numericscore',
                [
                    'joins' => 'scale_value',
                    'dbdatatype' => 'float',
                    'displayfunc' => 'comp_scale_value_numericscore'
                ]
            ),

        ];

        $this->add_core_user_columns($columnoptions, 'auser', 'user');
        $this->add_core_user_columns($columnoptions, 'ratinguser', 'rater');
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * Define filter options
     * @return array
     */
    protected function define_filteroptions(): array {
        $filter_options = [
            new rb_filter_option(
                'competency',
                'fullname',
                get_string('competency_name', 'rb_source_competency_rating'),
                'text'
            ),
            new rb_filter_option(
                'competency',
                'idnumber',
                get_string('competency_id_number', 'rb_source_competency_rating'),
                'text'
            ),
        ];

        // Include some standard filters.
        $this->add_core_user_filters($filter_options);
        $this->add_core_user_filters($filter_options, 'rater', true);
        $this->add_totara_job_filters($filter_options, 'base', 'user_id');

        return $filter_options;
    }

    protected function define_defaultcolumns() {
        $default_columns = [
            [
                'type' => 'user',
                'value' => 'namelink',
            ],
            [
                'type' => 'competency',
                'value' => 'fullname'
            ],
            [
                'type' => 'rating',
                'value' => 'rating'
            ],
            [
                'type' => 'rating',
                'value' => 'rolename'
            ],
            [
                'type' => 'rating',
                'value' => 'rating_time'
            ],
        ];

        return $default_columns;
    }

    /**
     * Define default filters
     * @return array
     */
    protected function define_defaultfilters(): array {
        return [
            [
                'type' => 'user',
                'value' => 'fullname',
            ],
            [
                'type' => 'competency',
                'value' => 'fullname',
            ],
        ];
    }

    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    /**
     * Inject column_test data into database.
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        global $CFG;

        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        $generator = \core\testing\generator::instance();
        $user1 = $generator->create_user(['lastname' => 'user1']);
        $comp_generator = \totara_competency\testing\generator::instance();
        $comp_generator->create_scale('scale1', 'scale1_description', [
            ['name' => 'scale_value1', 'proficient' => false, 'default' => true, 'sortorder' => 1],
        ]);
        $competency = $comp_generator->create_competency();
        $comp_generator->create_manual($competency, [manager::class, appraiser::class, self_role::class]);

        // Add an individual user assignment.
        $actions = new assignment_actions();
        $actions->create_from_competencies(
            [$competency->id],
            [user_groups::USER => [$user1->id]],
            assignment::TYPE_ADMIN,
            assignment::STATUS_ACTIVE
        );
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

        return 3;
    }
}