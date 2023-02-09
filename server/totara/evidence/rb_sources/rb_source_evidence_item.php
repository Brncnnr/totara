<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

use totara_core\advanced_feature;
use totara_evidence\models\evidence_type;
use totara_job\rb\source\report_trait;

/**
 * A report builder source for Totara Evidence
 */
class rb_source_evidence_item extends rb_base_source {

    use report_trait;

    /**
     * Constructor
     *
     * @param int $groupid (ignored)
     * @param rb_global_restriction_set $globalrestrictionset
     */
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        $this->usedcomponents[] = 'totara_evidence';

        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }

        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        $this->base = "{totara_evidence_item}";
        $this->joinlist       = $this->define_joinlist();
        $this->columnoptions  = $this->define_columnoptions();
        $this->filteroptions  = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions   = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'user_id');

        $this->requiredcolumns = [];
        $this->sourcetitle = get_string('source_title', 'rb_source_evidence_item');
        $this->sourcelabel = get_string('source_label', 'rb_source_evidence_item');
        $this->sourcesummary = get_string('source_summary', 'rb_source_evidence_item');

        $this->defaultsortcolumn = 'id';
        $this->defaultsortorder  = SORT_ASC;

        parent::__construct();
    }

    /**
     * Check if the report source is disabled and should be ignored.
     *
     * @return bool If the report should be ignored of not.
     */
    public static function is_source_ignored(): bool {
        return advanced_feature::is_disabled('evidence');
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported(): bool {
        return true;
    }

    /**
     * @return array
     */
    public function get_required_jss(): array {
        global $PAGE;
        $PAGE->requires->js_call_amd('totara_evidence/evidence_report', 'init');
        return [];
    }

    /**
     * @return rb_join[]
     */
    protected function define_joinlist(): array {
        $joinlist = [];

        $this->add_core_user_tables($joinlist, 'base','user_id');
        $this->add_totara_job_tables($joinlist, 'base', 'user_id');
        $this->add_core_user_tables($joinlist, 'base','created_by', 'creator');
        $this->add_core_user_tables($joinlist, 'base','modified_by', 'modifier');

        $joinlist = array_merge($joinlist, [
            new rb_join(
                'type',
                'INNER',
                '{totara_evidence_type}',
                'base.typeid = type.id',
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                ['base']
            ),
            new rb_join(
                'creator_hierarchy',
                'LEFT',
                "(SELECT job.userid userid,
                pos.id pos_id, pos.fullname pos_name, pos.typeid pos_typeid, pos.idnumber pos_idnumber, pos_type.fullname pos_type,
                org.id org_id, org.fullname org_name, org.typeid org_typeid, org.idnumber org_idnumber, org_type.fullname org_type
                FROM {job_assignment} job 
                INNER JOIN {pos} pos ON job.positionid = pos.id LEFT JOIN {pos_type} pos_type ON pos.typeid = pos_type.id
                INNER JOIN {org} org ON job.organisationid = org.id LEFT JOIN {org_type} org_type ON org.typeid = org_type.id)",
                "base.created_by = creator_hierarchy.userid",
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                ['base']
            ),
            new rb_join(
                'modifier_hierarchy',
                'LEFT',
                "(SELECT job.userid userid,
                pos.id pos_id, pos.fullname pos_name, pos.typeid pos_typeid, pos.idnumber pos_idnumber, pos_type.fullname pos_type,
                org.id org_id, org.fullname org_name, org.typeid org_typeid, org.idnumber org_idnumber, org_type.fullname org_type
                FROM {job_assignment} job 
                INNER JOIN {pos} pos ON job.positionid = pos.id LEFT JOIN {pos_type} pos_type ON pos.typeid = pos_type.id
                INNER JOIN {org} org ON job.organisationid = org.id LEFT JOIN {org_type} org_type ON org.typeid = org_type.id)",
                "base.modified_by = modifier_hierarchy.userid",
                REPORT_BUILDER_RELATION_MANY_TO_ONE,
                ['base']
            ),
        ]);

        return $joinlist;
    }

    /**
     * @return rb_column_option[]
     */
    protected function define_columnoptions(): array {
        global $DB;

        $columnoptions = [
            new rb_column_option(
                'base',
                'actions',
                get_string('actions'),
                'base.id',
                [
                    'displayfunc' => 'evidence_item_actions',
                    'noexport'    => true,
                    'nosort'      => true,
                    'graphable'   => false,
                    'extrafields' => [
                        'id' => 'base.id',
                        'typeid' => 'base.typeid',
                        'user_id' => 'base.user_id',
                        'name' => 'base.name',
                        'status' => 'base.status',
                        'created_by' => 'base.created_by',
                        'created_at' => 'base.created_at',
                        'modified_by' => 'base.modified_by',
                        'modified_at' => 'base.modified_at',
                        'imported' => 'base.imported',
                    ]
                ]
            ),
            new rb_column_option(
                'base',
                'name',
                get_string('name', 'rb_source_evidence_item'),
                'base.name',
                [
                    'joins' => 'type',
                    'displayfunc' => 'evidence_item_name',
                    'extrafields' => [
                        'item_id' => 'base.id',
                    ],
                    'graphable'   => false,
                ]
            ),
            new rb_column_option(
                'base',
                'in_use',
                get_string('in_use', 'rb_source_evidence_item'),
                '(
                    SELECT 
                        CASE WHEN COUNT(r.id) > 0 THEN 1 ELSE 0 END 
                    FROM {dp_plan_evidence_relation} r
                    WHERE r.evidenceid = base.id
                )',
                [
                    'issubquery' => true,
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                ]
            ),
            new rb_column_option(
                'base',
                'created_at',
                get_string('creation_date', 'rb_source_evidence_item'),
                'base.created_at',
                [
                    'displayfunc' => 'nice_datetime',
                    'dbdatatype'  => 'timestamp',
                    'graphable'   => true,
                ]
            ),
            new rb_column_option(
                'base',
                'modified_at',
                get_string('modified_date', 'rb_source_evidence_item'),
                'base.modified_at',
                [
                    'displayfunc' => 'nice_datetime',
                    'dbdatatype'  => 'timestamp',
                    'graphable'   => true,
                ]
            ),
            new rb_column_option(
                'base',
                'source',
                get_string('source', 'rb_source_evidence_item'),
                'base.imported',
                [
                    'displayfunc' => 'evidence_item_source',
                    'graphable' => false,
                ]
            ),
            new rb_column_option(
                'type',
                'name',
                get_string('type_name', 'rb_source_evidence_item'),
                'type.name',
                [
                    'joins' => 'type',
                    'displayfunc' => 'evidence_type_name',
                    'extrafields' => [
                        'typeid' => 'type.id'
                    ],
                    'graphable' => false,
                    'extracontext' => [
                        'type_can_manage' => evidence_type::can_manage(),
                    ],
                ]
            ),
            new rb_column_option(
                'type',
                'idnumber',
                get_string('type_idnumber', 'rb_source_evidence_item'),
                'type.idnumber',
                [
                    'joins'        => 'type',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                    'graphable'    => false,
                    'capability'   => 'totara/evidence:managetype',
                ]
            ),
            new rb_column_option(
                'type',
                'description',
                get_string('type_description', 'rb_source_evidence_item'),
                'type.description',
                [
                    'joins'        => 'type',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                    'graphable'    => false,
                    'capability'   => 'totara/evidence:managetype',
                ]
            ),
            new rb_column_option(
                'type',
                'location',
                get_string('location', 'rb_source_evidence_type'),
                'type.location',
                [
                    'deprecated' => true,
                    'joins' => 'type',
                    'displayfunc' => 'evidence_type_location',
                    'graphable' => false,
                ]
            ),
            new rb_column_option(
                'type',
                'created_at',
                get_string('type_date_created', 'rb_source_evidence_item'),
                'type.created_at',
                [
                    'joins'       => 'type',
                    'displayfunc' => 'nice_datetime',
                    'dbdatatype'  => 'timestamp',
                    'graphable'   => true,
                    'capability'  => 'totara/evidence:managetype',
                ]
            ),
            new rb_column_option(
                'type',
                'modified_at',
                get_string('type_date_modified', 'rb_source_evidence_item'),
                'type.modified_at',
                [
                    'joins'       => 'type',
                    'displayfunc' => 'nice_datetime',
                    'dbdatatype'  => 'timestamp',
                    'graphable'   => true,
                    'capability'  => 'totara/evidence:managetype',
                ]
            ),
        ];

        $this->add_core_user_columns($columnoptions);
        $this->add_totara_job_columns($columnoptions);

        $columnoptions = array_merge($columnoptions, [
            new rb_column_option(
                'creator',
                'name',
                get_string('creator', 'rb_source_evidence_item'),
                $DB->sql_concat_join("' '", totara_get_all_user_name_fields_join('creator', null, true)),
                [
                    'joins'        => 'creator',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'extrafields'  => array_merge(
                        [
                            'id' => 'creator.id',
                            'deleted' => 'creator.deleted'
                        ],
                        totara_get_all_user_name_fields_join('creator')
                    ),
                    'displayfunc'  => 'user_link',
                ]
            ),
            new rb_column_option(
                'creator',
                'position_name',
                get_string('creator_position_name', 'rb_source_evidence_item'),
                'creator_hierarchy.pos_name',
                [
                    'joins'        => 'creator_hierarchy',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                ]
            ),
            new rb_column_option(
                'creator',
                'position_idnumber',
                get_string('creator_position_idnumber', 'rb_source_evidence_item'),
                'creator_hierarchy.pos_idnumber',
                [
                    'joins'        => 'creator_hierarchy',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                ]
            ),
            new rb_column_option(
                'creator',
                'position_type',
                get_string('creator_position_type', 'rb_source_evidence_item'),
                'creator_hierarchy.pos_type',
                [
                    'joins'        => 'creator_hierarchy',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                ]
            ),
            new rb_column_option(
                'creator',
                'organisation_name',
                get_string('creator_organisation_name', 'rb_source_evidence_item'),
                'creator_hierarchy.org_name',
                [
                    'joins'        => 'creator_hierarchy',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                ]
            ),
            new rb_column_option(
                'creator',
                'organisation_idnumber',
                get_string('creator_organisation_idnumber', 'rb_source_evidence_item'),
                'creator_hierarchy.pos_idnumber',
                [
                    'joins'        => 'creator_hierarchy',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                ]
            ),
            new rb_column_option(
                'creator',
                'organisation_type',
                get_string('creator_organisation_type', 'rb_source_evidence_item'),
                'creator_hierarchy.pos_type',
                [
                    'joins'        => 'creator_hierarchy',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                ]
            ),
            new rb_column_option(
                'modifier',
                'name',
                get_string('modifier', 'rb_source_evidence_item'),
                $DB->sql_concat_join("' '", totara_get_all_user_name_fields_join('modifier', null, true)),
                [
                    'joins'        => 'modifier',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'extrafields'  => array_merge(
                        [
                            'id' => 'modifier.id',
                            'deleted' => 'modifier.deleted'
                        ],
                        totara_get_all_user_name_fields_join('modifier')
                    ),
                    'displayfunc'  => 'user_link',
                ]
            ),
            new rb_column_option(
                'modifier',
                'position_name',
                get_string('modifier_position_name', 'rb_source_evidence_item'),
                'modifier_hierarchy.pos_name',
                [
                    'joins'        => 'modifier_hierarchy',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                ]
            ),
            new rb_column_option(
                'modifier',
                'position_idnumber',
                get_string('modifier_position_idnumber', 'rb_source_evidence_item'),
                'modifier_hierarchy.pos_idnumber',
                [
                    'joins'        => 'modifier_hierarchy',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                ]
            ),
            new rb_column_option(
                'modifier',
                'position_type',
                get_string('modifier_position_type', 'rb_source_evidence_item'),
                'modifier_hierarchy.pos_type',
                [
                    'joins'        => 'modifier_hierarchy',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                ]
            ),
            new rb_column_option(
                'modifier',
                'organisation_name',
                get_string('modifier_organisation_name', 'rb_source_evidence_item'),
                'modifier_hierarchy.org_name',
                [
                    'joins'        => 'modifier_hierarchy',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                ]
            ),
            new rb_column_option(
                'modifier',
                'organisation_idnumber',
                get_string('modifier_organisation_idnumber', 'rb_source_evidence_item'),
                'modifier_hierarchy.pos_idnumber',
                [
                    'joins'        => 'modifier_hierarchy',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                ]
            ),
            new rb_column_option(
                'modifier',
                'organisation_type',
                get_string('modifier_organisation_type', 'rb_source_evidence_item'),
                'modifier_hierarchy.pos_type',
                [
                    'joins'        => 'modifier_hierarchy',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'format_string',
                ]
            ),
        ]);

        return $columnoptions;
    }

    /**
     * @return rb_filter_option[]
     */
    protected function define_filteroptions(): array {
        $filteroptions = [
            new rb_filter_option(
                'base',
                'name',
                get_string('name', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'base',
                'in_use',
                get_string('in_use', 'rb_source_evidence_item'),
                'multicheck',
                [
                    'simplemode' => true,
                    'selectfunc' => 'yesno_list',
                ]
            ),
            new rb_filter_option(
                'base',
                'created_at',
                get_string('creation_date', 'rb_source_evidence_item'),
                'date'
            ),
            new rb_filter_option(
                'base',
                'modified_at',
                get_string('modified_date', 'rb_source_evidence_item'),
                'date'
            ),
            new rb_filter_option(
                'base',
                'source',
                get_string('source', 'rb_source_evidence_item'),
                'select',
                [
                    'selectfunc' => 'evidence_item_source',
                    'simplemode' => true,
                ]
            ),
            new rb_filter_option(
                'type',
                'name',
                get_string('type_name', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'type',
                'idnumber',
                get_string('type_idnumber', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'type',
                'description',
                get_string('type_description', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'type',
                'created_at',
                get_string('type_date_created', 'rb_source_evidence_item'),
                'date'
            ),
            new rb_filter_option(
                'type',
                'modified_at',
                get_string('type_date_modified', 'rb_source_evidence_item'),
                'date'
            ),
        ];

        $this->add_core_user_filters($filteroptions, 'user', true);
        $this->add_totara_job_filters($filteroptions, 'base', 'user_id');

        $filteroptions = array_merge($filteroptions, [
            new rb_filter_option(
                'creator',
                'name',
                get_string('creator', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'creator',
                'position_name',
                get_string('creator_position_name', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'creator',
                'position_idnumber',
                get_string('creator_position_idnumber', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'creator',
                'position_type',
                get_string('creator_position_type', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'creator',
                'organisation_name',
                get_string('creator_organisation_name', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'creator',
                'organisation_idnumber',
                get_string('creator_organisation_idnumber', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'creator',
                'organisation_type',
                get_string('creator_organisation_type', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'modifier',
                'name',
                get_string('modifier', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'modifier',
                'position_name',
                get_string('modifier_position_name', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'modifier',
                'position_idnumber',
                get_string('modifier_position_idnumber', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'modifier',
                'position_type',
                get_string('modifier_position_type', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'modifier',
                'organisation_name',
                get_string('modifier_organisation_name', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'modifier',
                'organisation_idnumber',
                get_string('modifier_organisation_idnumber', 'rb_source_evidence_item'),
                'text'
            ),
            new rb_filter_option(
                'modifier',
                'organisation_type',
                get_string('modifier_organisation_type', 'rb_source_evidence_item'),
                'text'
            ),
        ]);

        return $filteroptions;
    }

    /**
     * @return array
     */
    public function rb_filter_evidence_item_source(): array {
        return [
            0 => get_string('source_manual', 'rb_source_evidence_item'),
            1 => get_string('source_uploaded', 'rb_source_evidence_item'),
        ];
    }

    /**
     * Creates the array of rb_content_option object required for $this->contentoptions
     * @return array
     */
    protected function define_contentoptions(): array {
        $contentoptions = [];

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);

        return $contentoptions;
    }

    protected function define_paramoptions(): array {
        return [
            new rb_param_option(
                'user_id',
                'base.user_id',
                null
            ),
            new rb_param_option(
                'userid',
                'base.user_id',
                null
            ),
            new rb_param_option(
                'created_by',
                'base.created_by',
                null
            ),
        ];
    }

    /**
     * @return array[]
     */
    protected function define_defaultcolumns(): array {
        return [
            [
                'type'  => 'base',
                'value' => 'name'
            ],
            [
                'type'  => 'type',
                'value' => 'name'
            ],
            [
                'type'  => 'base',
                'value' => 'created_at'
            ],
            [
                'type'  => 'creator',
                'value' => 'name'
            ],
            [
                'type'  => 'base',
                'value' => 'actions'
            ],
        ];
    }

    /**
     * @return array[]
     */
    protected function define_defaultfilters(): array {
        return [
            [
                'type'  => 'base',
                'value' => 'name'
            ],
            [
                'type'  => 'base',
                'value' => 'source'
            ],
            [
                'type'  => 'type',
                'value' => 'name'
            ],
            [
                'type'  => 'creator',
                'value' => 'name'
            ],
            [
                'type'  => 'base',
                'value' => 'created_at'
            ],
        ];
    }

    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) : void {
        global $CFG, $DB;

        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        $evidence_generator = \totara_evidence\testing\generator::instance();
        $evidence_generator->create_evidence_type(['name' => 'Type']);
        $evidence_generator->create_evidence_item(['type' => 'Type']);
    }

}
