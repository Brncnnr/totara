<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

use totara_competency\entity\assignment;
use totara_competency\user_groups;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * A report builder source for the competency assignments
 */
class rb_source_competency_assignment_users extends rb_base_source {

    use totara_cohort\rb\source\report_trait;
    use totara_job\rb\source\report_trait;

    /**
     * Constructor
     *
     * @param int $groupid (ignored)
     * @param rb_global_restriction_set $globalrestrictionset
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        $this->usedcomponents[] = 'totara_competency';

        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }

        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        $this->base = "{totara_competency_assignment_users}";
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = [];
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_competency_assignment_users');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_competency_assignment_users');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_competency_assignment_users');

        $this->defaultsortcolumn = 'id';
        $this->defaultsortorder = SORT_ASC;

        parent::__construct();
    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public static function is_source_ignored() {
        return !advanced_feature::is_enabled('competency_assignment');
    }

    /**
     * Are the global report restrictions implemented in the source?
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
                'assignment',
                'INNER',
                "{totara_competency_assignments}",
                "base.assignment_id = assignment.id",
                REPORT_BUILDER_RELATION_MANY_TO_ONE
            ),
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
        ];

        $this->add_core_user_tables($joinlist, 'base','user_id');
        $this->add_core_user_tables($joinlist, 'assignment','created_by', 'assignment_created_by');
        $this->add_totara_job_tables($joinlist, 'base', 'user_id');

        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB, $CFG;

        $columnoptions = [
            new rb_column_option(
                'competency',
                'type',
                get_string('label_competency_type', 'rb_source_competency_assignment_users'),
                'comp_type.fullname',
                array(
                    'joins' => 'comp_type',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
            ),
            new rb_column_option(
                'competency',
                'fullname',
                get_string('label_competency', 'rb_source_competency_assignment_users'),
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
                'competencylink',
                get_string('label_competency_linkname', 'rb_source_competency_assignment_users'),
                'competency.fullname',
                [
                    'joins' => 'competency',
                    'displayfunc' => 'display_competency_link',
                    'defaultheading' => get_string('label_competency', 'rb_source_competency_assignment_users'),
                    'extrafields' => ['competency_id' => 'competency.id'],
                ]
            ),
            new rb_column_option(
                'competency',
                'idnumber',
                get_string('label_competency_id_number', 'rb_source_competency_assignment_users'),
                'competency.idnumber',
                [
                    'joins' => 'competency',
                    'displayfunc' => 'format_string',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                ]
            ),
            new rb_column_option(
                'competency',
                'type_id',
                get_string('label_competency_type_id', 'rb_source_competency_assignment_users'),
                'competency.typeid',
                array(
                    'joins' => 'competency',
                    'displayfunc' => 'integer',
                    'hidden' => 1,
                    'selectable' => false
                )
            ),
            new rb_column_option(
                'competency',
                'framework',
                get_string('label_competency_framework', 'rb_source_competency_assignment_users'),
                'comp_framework.fullname',
                [
                    'joins' => 'comp_framework',
                    'displayfunc' => 'format_string',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                ]
            ),
            new rb_column_option(
                'competency',
                'framework_idnumber',
                get_string('label_competency_framework_id_number', 'rb_source_competency_assignment_users'),
                'comp_framework.idnumber',
                [
                    'joins' => 'comp_framework',
                    'displayfunc' => 'format_string',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                ]
            ),
            new rb_column_option(
                'competency',
                'framework_id',
                get_string('label_competency_framework_id', 'rb_source_competency_assignment_users'),
                'competency.frameworkid',
                array(
                    'joins' => 'competency',
                    'displayfunc' => 'integer',
                    'hidden' => 1,
                    'selectable' => false
                )
            ),
            new rb_column_option(
                'position',
                'type',
                get_string('label_position_type', 'rb_source_competency_assignment_users'),
                'pos_type.fullname',
                array(
                    'joins' => 'pos_type',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
            ),
            new rb_column_option(
                'position',
                'type_id',
                get_string('label_position_type_id', 'rb_source_competency_assignment_users'),
                'assignment_positions.typeid',
                array(
                    'joins' => 'assignment_positions',
                    'displayfunc' => 'integer',
                    'hidden' => 1,
                    'selectable' => false
                )
            ),
            new rb_column_option(
                'position',
                'name',
                get_string('label_position_name', 'rb_source_competency_assignment_users'),
                'assignment_positions.fullname',
                [
                    'joins' => 'assignment_positions',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                ]
            ),
            new rb_column_option(
                'position',
                'idnumber',
                get_string('label_position_id_number', 'rb_source_competency_assignment_users'),
                'assignment_positions.idnumber',
                [
                    'joins' => 'assignment_positions',
                    'displayfunc' => 'plaintext',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                ]
            ),
            new rb_column_option(
                'organisation',
                'type',
                get_string('label_organisation_type', 'rb_source_competency_assignment_users'),
                'org_type.fullname',
                array(
                    'joins' => 'org_type',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                )
            ),
            new rb_column_option(
                'organisation',
                'type_id',
                get_string('label_organisation_type_id', 'rb_source_competency_assignment_users'),
                'assignment_positions.typeid',
                array(
                    'joins' => 'assignment_positions',
                    'displayfunc' => 'integer',
                    'hidden' => 1,
                    'selectable' => false
                )
            ),
            new rb_column_option(
                'organisation',
                'name',
                get_string('label_organisation_name', 'rb_source_competency_assignment_users'),
                'assignment_organisations.fullname',
                [
                    'joins' => 'assignment_organisations',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                ]
            ),
            new rb_column_option(
                'organisation',
                'idnumber',
                get_string('label_organisation_idnumber', 'rb_source_competency_assignment_users'),
                'assignment_organisations.idnumber',
                [
                    'joins' => 'assignment_organisations',
                    'displayfunc' => 'format_string',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                ]
            ),
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
                'user_group_type',
                get_string('label_user_group_type', 'rb_source_competency_assignment_users'),
                'assignment.user_group_type',
                [
                    'joins' => 'assignment',
                    'displayfunc' => 'display_user_group_type',
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
                'created_at',
                get_string('label_assignment_created_at', 'rb_source_competency_assignment_users'),
                'assignment.created_at',
                [
                    'joins' => 'assignment',
                    'displayfunc' => 'nice_datetime'
                ]
            ),
            new rb_column_option(
                'cohort',
                'name',
                get_string('label_cohort', 'rb_source_competency_assignment_users'),
                'assignment_cohorts.name',
                [
                    'joins' => 'assignment_cohorts',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'displayfunc' => 'format_string'
                ]
            ),
            new rb_column_option(
                'cohort',
                'idnumber',
                get_string('label_cohort_id_number', 'rb_source_competency_assignment_users'),
                'assignment_cohorts.idnumber',
                [
                    'joins' => 'assignment_cohorts',
                    'displayfunc' => 'format_string',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text'
                ]
            )
        ];

        // Now add columns for user who created the assignment
        // We explicitly don't use add_core_user_columns() for those to reduce
        // number of columns

        $usednamefields = totara_get_all_user_name_fields_join('assignment_created_by', null, true);
        $allnamefields = totara_get_all_user_name_fields_join('assignment_created_by');

        $created_by_columns = [
            new rb_column_option(
                'assignment_created_by',
                'fullname',
                get_string('userfullname', 'totara_reportbuilder'),
                $DB->sql_concat_join("' '", $usednamefields),
                [
                    'joins' => 'assignment_created_by',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'extrafields' => $allnamefields,
                    'displayfunc' => 'user',
                    'addtypetoheading' => true
                ]
            ),
            new rb_column_option(
                'assignment_created_by',
                'namelink',
                get_string('usernamelink', 'totara_reportbuilder'),
                $DB->sql_concat_join("' '", $usednamefields),
                [
                    'joins' => 'assignment_created_by',
                    'displayfunc' => 'user_link',
                    'defaultheading' => get_string('userfullname', 'totara_reportbuilder'),
                    'extrafields' => array_merge(
                        [
                            'id' => 'assignment_created_by.id',
                            'deleted' => 'assignment_created_by.deleted'
                        ],
                        $allnamefields
                    ),
                    'addtypetoheading' => true
                ]
            ),
            new rb_column_option(
                'assignment_created_by',
                'namelinkicon',
                get_string('usernamelinkicon', 'totara_reportbuilder'),
                $DB->sql_concat_join("' '", $usednamefields),
                [
                    'joins' => 'assignment_created_by',
                    'displayfunc' => 'user_icon_link',
                    'defaultheading' => get_string('userfullname', 'totara_reportbuilder'),
                    'extrafields' => array_merge([
                        'id' => "assignment_created_by.id",
                        'picture' => "assignment_created_by.picture",
                        'imagealt' => "assignment_created_by.imagealt",
                        'email' => "assignment_created_by.email"
                    ], $allnamefields),
                    'style' => ['white-space' => 'nowrap'],
                    'addtypetoheading' => true
                ]
            ),
            new rb_column_option(
                'assignment_created_by',
                'email',
                get_string('useremail', 'totara_reportbuilder'),
                // use CASE to include/exclude email in SQL
                // so search won't reveal hidden results
                "CASE WHEN assignment_created_by.maildisplay <> 1 THEN '-' ELSE assignment_created_by.email END",
                [
                    'joins' => 'assignment_created_by',
                    'displayfunc' => 'user_email',
                    'extrafields' => [
                        'emailstop' => "assignment_created_by.emailstop",
                        'maildisplay' => "assignment_created_by.maildisplay",
                    ],
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'addtypetoheading' => true
                ]
            )
        ];

        // Only include this column if email is among fields allowed by showuseridentity setting or
        // if the current user has the 'moodle/site:config' capability.
        $canview = !empty($CFG->showuseridentity) && in_array('email', explode(',', $CFG->showuseridentity));
        $canview |= has_capability('moodle/site:config', \context_system::instance());
        if ($canview) {
            $created_by_columns[] = new rb_column_option(
                'assignment_created_by',
                'emailunobscured',
                get_string('useremailunobscured', 'totara_reportbuilder'),
                "assignment_created_by.email",
                [
                    'joins' => 'assignment_created_by',
                    'displayfunc' => 'user_email_unobscured',
                    // Users must have viewuseridentity to see the
                    // unobscured email address.
                    'capability' => 'moodle/site:viewuseridentity',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'addtypetoheading' => true
                ]
            );
        }

        // auto-generate columns for user fields
        $fields = array(
            'firstname' => get_string('userfirstname', 'totara_reportbuilder'),
            'lastname' => get_string('userlastname', 'totara_reportbuilder'),
            'username' => get_string('username', 'totara_reportbuilder'),
        );
        foreach ($fields as $field => $name) {
            $columnoptions[] = new rb_column_option(
                'assignment_created_by',
                $field,
                $name,
                "assignment_created_by.$field",
                [
                    'joins' => 'assignment_created_by',
                    'displayfunc' => 'format_string',
                    'dbdatatype' => 'char',
                    'outputformat' => 'text',
                    'addtypetoheading' => true
                ]
            );
        }

        $created_by_columns[] = new rb_column_option(
            'assignment_created_by',
            'idnumber',
            get_string('useridnumber', 'totara_reportbuilder'),
            "assignment_created_by.idnumber",
            [
                'joins' => 'assignment_created_by',
                'displayfunc' => 'format_string',
                'dbdatatype' => 'char',
                'outputformat' => 'text'
            ]
        );

        $columnoptions = array_merge($columnoptions, $created_by_columns);

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }

    public function rb_group_custom($field) {
        return $field;
    }

    protected function define_filteroptions() {
        global $CFG;

        $filteroptions = [
            new rb_filter_option(
                'competency',
                'fullname',
                get_string('label_competency', 'rb_source_competency_assignment_users'),
                'text'
            ),
            new rb_filter_option(
                'competency',
                'idnumber',
                get_string('label_competency_id_number', 'rb_source_competency_assignment_users'),
                'text'
            ),
            new rb_filter_option(
                'competency',
                'framework_id',
                get_string('label_competency_framework', 'rb_source_competency_assignment_users'),
                'select',
                [
                    'selectfunc' => 'competency_frameworks',
                    'attributes' => rb_filter_option::select_width_limiter(),
                ]
            ),
            new rb_filter_option(
                'competency',
                'framework_idnumber',
                get_string('label_competency_framework_id_number', 'rb_source_competency_assignment_users'),
                'text'
            ),
            new rb_filter_option(
                'assignment',
                'assignment_type',
                get_string('label_assignment_type', 'rb_source_competency_assignment_users'),
                'select',
                [
                    'selectfunc' => 'assignment_types',
                    'attributes' => rb_filter_option::select_width_limiter(),
                ]
            ),
            new rb_filter_option(
                'assignment',
                'user_group_type',
                get_string('label_user_group_type', 'rb_source_competency_assignment_users'),
                'select',
                [
                    'selectfunc' => 'user_group_types',
                    'attributes' => rb_filter_option::select_width_limiter(),
                ]
            ),
            new rb_filter_option(
                'competency',
                'type_id',
                get_string('label_competency_type', 'rb_source_competency_assignment_users'),
                'select',
                [
                    'selectfunc' => 'competency_type_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                ]
            ),
            new rb_filter_option(
                'position',
                'type_id',
                get_string('label_position_type', 'rb_source_competency_assignment_users'),
                'select',
                [
                    'selectfunc' => 'position_type_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                ]
            ),
            new rb_filter_option(
                'organisation',
                'type_id',
                get_string('label_organisation_type', 'rb_source_competency_assignment_users'),
                'select',
                array(
                    'selectfunc' => 'organisation_type_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'cohort',
                'name',
                get_string('label_cohort', 'rb_source_competency_assignment_users'),
                'text'
            ),
            new rb_filter_option(
                'cohort',
                'idnumber',
                get_string('label_cohort_id_number', 'rb_source_competency_assignment_users'),
                'text'
            ),
            new rb_filter_option(
                'position',
                'name',
                get_string('label_position_name', 'rb_source_competency_assignment_users'),
                'text'
            ),
            new rb_filter_option(
                'position',
                'idnumber',
                get_string('label_position_id_number', 'rb_source_competency_assignment_users'),
                'text'
            ),
            new rb_filter_option(
                'organisation',
                'name',
                get_string('label_organisation_name', 'rb_source_competency_assignment_users'),
                'text'
            ),
            new rb_filter_option(
                'organisation',
                'idnumber',
                get_string('label_organisation_idnumber', 'rb_source_competency_assignment_users'),
                'text'
            ),
            new rb_filter_option(
                'assignment',
                'created_at',
                get_string('label_assignment_created_at', 'rb_source_competency_assignment_users'),
                'date'
            ),
        ];

        // Filters for user assigned by
        $fields = [
            'fullname' => get_string('userfullname', 'totara_reportbuilder'),
            'firstname' => get_string('userfirstname', 'totara_reportbuilder'),
            'lastname' => get_string('userlastname', 'totara_reportbuilder'),
            'username' => get_string('username'),
            'idnumber' => get_string('useridnumber', 'totara_reportbuilder'),
            'email' => get_string('useremail', 'totara_reportbuilder'),
        ];
        // Only include this filter if email is among fields allowed by showuseridentity setting or
        // if the current user has the 'moodle/site:config' capability.
        $canview = !empty($CFG->showuseridentity) && in_array('email', explode(',', $CFG->showuseridentity));
        $canview |= has_capability('moodle/site:config', \context_system::instance());
        if ($canview) {
            $fields['emailunobscured'] = get_string('useremailunobscured', 'totara_reportbuilder');
        }

        $created_by_filters = [];
        foreach ($fields as $field => $name) {
            $created_by_filters[] = new rb_filter_option(
                'assignment_created_by',
                $field,
                $name,
                'text',
                ['addtypetoheading' => true]
            );
        }
        $filteroptions = array_merge($filteroptions, $created_by_filters);

        $this->add_core_user_filters($filteroptions);
        $this->add_totara_job_filters($filteroptions);

        return $filteroptions;
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

    protected function define_defaultcolumns() {
        $defaultcolumns = [
            [
                'type' => 'user',
                'value' => 'namelink',
            ],
            [
                'type' => 'competency',
                'value' => 'competencylink',
            ],
            [
                'type' => 'assignment',
                'value' => 'assignment_type',
            ],
            [
                'type' => 'assignment',
                'value' => 'user_group',
            ],
            [
                'type' => 'assignment_created_by',
                'value' => 'namelink',
            ],
            [
                'type' => 'assignment',
                'value' => 'created_at',
            ]
        ];

        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = [
            [
                'type' => 'user',
                'value' => 'fullname',
            ],
            [
                'type' => 'competency',
                'value' => 'fullname',
            ],
            [
                'type' => 'assignment',
                'value' => 'assignment_type',
            ],
            [
                'type' => 'assignment',
                'value' => 'created_at',
                'advanced' => 1
            ],
            [
                'type' => 'competency',
                'value' => 'type_id',
                'advanced' => 1
            ],
            [
                'type' => 'cohort',
                'value' => 'name',
                'advanced' => 1
            ],
            [
                'type' => 'cohort',
                'value' => 'idnumber',
                'advanced' => 1
            ],
            [
                'type' => 'position',
                'value' => 'type_id',
                'advanced' => 1
            ],
            [
                'type' => 'position',
                'value' => 'name',
                'advanced' => 1
            ],
            [
                'type' => 'position',
                'value' => 'idnumber',
                'advanced' => 1
            ],
            [
                'type' => 'organisation',
                'value' => 'type_id',
                'advanced' => 1
            ],
            [
                'type' => 'organisation',
                'value' => 'name',
                'advanced' => 1
            ],
            [
                'type' => 'organisation',
                'value' => 'idnumber',
                'advanced' => 1
            ],
        ];

        return $defaultfilters;
    }

    public function rb_filter_assignment_types() {
        // TODO extract this as it can be used by the other filters
        return [
            user_groups::POSITION     => get_string('filter_user_group_position', 'totara_competency'),
            user_groups::ORGANISATION => get_string('filter_user_group_organisation', 'totara_competency'),
            user_groups::COHORT       => get_string('filter_user_group_cohort', 'totara_competency'),
            assignment::TYPE_ADMIN    => get_string('assignment_type_admin', 'totara_competency'),
            assignment::TYPE_SELF     => get_string('assignment_type_self', 'totara_competency'),
            assignment::TYPE_OTHER    => get_string('assignment_type_other', 'totara_competency'),
            assignment::TYPE_SYSTEM   => get_string('assignment_type_system', 'totara_competency'),
        ];
    }

    public function rb_filter_user_group_types() {
        return [
            user_groups::COHORT => get_string('cohort', 'totara_cohort'),
            user_groups::POSITION => get_string('position', 'totara_hierarchy'),
            user_groups::ORGANISATION => get_string('organisation', 'totara_hierarchy'),
            user_groups::USER => get_string('user', 'moodle'),
        ];
    }

    public function rb_filter_competency_frameworks() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/hierarchy/prefix/competency/lib.php');

        $comp = new competency();
        $records = $comp->get_frameworks();
        $frameworks = [];
        foreach ($records as $id => $record) {
            $frameworks[$id] = $record->fullname;
        }
        return $frameworks;
    }

    /**
     * Inject column_test data into database.
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        global $CFG, $DB;

        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        $data_generator = \core\testing\generator::instance();
        $competency_generator = \totara_competency\testing\generator::instance();

        $user1 = $data_generator->create_user();
        $user2 = $data_generator->create_user();

        $fw = $competency_generator->hierarchy_generator()->create_comp_frame([]);
        $fw2 = $competency_generator->hierarchy_generator()->create_comp_frame([]);

        $type1 = $competency_generator->hierarchy_generator()->create_comp_type(['idnumber' => 'type1']);
        $type2 = $competency_generator->hierarchy_generator()->create_comp_type(['idnumber' => 'type2']);

        $comp1 = $competency_generator->create_competency(null, $fw->id, [
            'shortname' => 'acc',
            'fullname' => 'Accounting',
            'description' => 'Counting profits',
            'idnumber' => 'accc',
            'typeid' => $type1,
        ]);

        $comp2 = $competency_generator->create_competency(null, $fw2->id, [
            'shortname' => 'c-chef',
            'fullname' => 'Chef proficiency',
            'description' => 'Bossing around',
            'idnumber' => 'cook-chef-c',
            'typeid' => $type1,
        ]);

        $comp3 = $competency_generator->create_competency(null, $fw->id, [
            'shortname' => 'des',
            'fullname' => 'Designing interiors',
            'description' => 'Decorating things',
            'idnumber' => 'des',
            'parentid' => $comp1->id,
            'typeid' => $type2,
        ]);

        $comp4 = $competency_generator->create_competency(null, $fw2->id, [
            'shortname' => 'c-baker',
            'fullname' => 'Baking skill-set',
            'description' => 'Baking amazing things',
            'idnumber' => 'cook-baker',
            'typeid' => $type2,
        ]);

        $comp5 = $competency_generator->create_competency(null, $fw->id, [
            'shortname' => 'c-cook',
            'fullname' => 'Cooking',
            'description' => 'More cooking',
            'idnumber' => 'cook',
            'parentid' => $comp3->id,
            'typeid' => $type2,
        ]);

        $comp6 = $competency_generator->create_competency(null, $fw2->id, [
            'shortname' => 'c-inv',
            'fullname' => 'Invisible',
            'description' => 'More hidden cooking',
            'idnumber' => 'cook-hidden',
            'visible' => false,
            'parentid' => $comp1->id,
            'typeid' => $type2,
        ]);

        // Create an assignment for a competency
        $competency_generator->assignment_generator()->create_user_assignment($comp1->id, $user1->id, ['status' => assignment::STATUS_ACTIVE]);
        $competency_generator->assignment_generator()->create_user_assignment($comp3->id, $user2->id, ['status' => assignment::STATUS_ACTIVE]);

        (new \totara_competency\expand_task($DB))->expand_all();
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
        return 2;
    }

}
