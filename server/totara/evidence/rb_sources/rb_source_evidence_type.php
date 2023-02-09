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

/**
 * A report builder source for Totara Evidence
 */
class rb_source_evidence_type extends rb_base_source {

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

        $this->base = "{totara_evidence_type}";
        $this->joinlist       = $this->define_joinlist();
        $this->columnoptions  = $this->define_columnoptions();
        $this->filteroptions  = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions   = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();

        $this->requiredcolumns = [];
        $this->sourcetitle = get_string('source_title', 'rb_source_evidence_type');
        $this->sourcelabel = get_string('source_label', 'rb_source_evidence_type');
        $this->sourcesummary = get_string('source_summary', 'rb_source_evidence_type');

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

        $this->add_core_user_tables($joinlist, 'base', 'created_by', 'creator');
        $this->add_core_user_tables($joinlist, 'base', 'modified_by', 'modifier');

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
                'name',
                get_string('name', 'rb_source_evidence_type'),
                'base.name',
                [
                    'joins'       => 'base',
                    'displayfunc' => 'evidence_type_name',
                    'extrafields' => [
                        'typeid' => 'base.id'
                    ],
                    'extracontext' => [
                        'type_can_manage' => true,
                    ],
                    'graphable' => false
                ]
            ),
            new rb_column_option(
                'base',
                'idnumber',
                get_string('idnumber', 'rb_source_evidence_type'),
                'base.idnumber',
                [
                    'joins'        => 'base',
                    'displayfunc'  => 'format_string',
                    'graphable'    => false,
                    'capability'   => 'totara/evidence:managetype',
                ]
            ),
            new rb_column_option(
                'base',
                'forusein',
                get_string('for_use_in', 'rb_source_evidence_type'),
                'base.location',
                [
                    'joins'        => 'base',
                    'displayfunc' => 'evidence_type_forusein',
                    'graphable'    => false,
                    'capability'   => 'totara/evidence:managetype',
                ]
            ),
            new rb_column_option(
                'base',
                'description',
                get_string('description', 'rb_source_evidence_type'),
                'base.description',
                [
                    'joins'        => 'base',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'displayfunc'  => 'evidence_type_description',
                    'extrafields' => [
                        'id' => 'base.id',
                        'name' => 'base.name',
                        'idnumber' => 'base.idnumber',
                        'description' => 'base.description',
                        'descriptionformat' => 'base.descriptionformat',
                        'status' => 'base.status',
                        'location' => 'base.location',
                        'created_by' => 'base.created_by',
                        'created_at' => 'base.created_at',
                        'modified_by' => 'base.modified_by',
                        'modified_at' => 'base.modified_at',
                    ],
                    'graphable'    => false,
                    'capability'   => 'totara/evidence:managetype',
                ]
            ),
            new rb_column_option(
                'base',
                'created_at',
                get_string('date_created', 'rb_source_evidence_type'),
                'base.created_at',
                [
                    'joins'       => 'base',
                    'displayfunc' => 'nice_datetime',
                    'dbdatatype'  => 'timestamp',
                    'graphable'   => true,
                    'capability'  => 'totara/evidence:managetype',
                ]
            ),
            new rb_column_option(
                'base',
                'created_by',
                get_string('user_created', 'rb_source_evidence_type'),
                $DB->sql_concat_join("' '", totara_get_all_user_name_fields_join('creator', null, true)),
                [
                    'joins'        => 'creator',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'extrafields'  => array_merge(totara_get_all_user_name_fields_join('creator'), [
                        'id' => 'creator.id',
                        'deleted' => 'creator.deleted',
                    ]),
                    'displayfunc'  => 'user_link'
                ]
            ),
            new rb_column_option(
                'base',
                'modified_at',
                get_string('date_modified', 'rb_source_evidence_type'),
                'base.modified_at',
                [
                    'joins'       => 'base',
                    'displayfunc' => 'nice_datetime',
                    'dbdatatype'  => 'timestamp',
                    'graphable'   => true,
                    'capability'  => 'totara/evidence:managetype',
                ]
            ),
            new rb_column_option(
                'base',
                'modified_by',
                get_string('user_modified', 'rb_source_evidence_type'),
                $DB->sql_concat_join("' '", totara_get_all_user_name_fields_join('modifier', null, true)),
                [
                    'joins'        => 'modifier',
                    'dbdatatype'   => 'char',
                    'outputformat' => 'text',
                    'extrafields'  => array_merge(totara_get_all_user_name_fields_join('modifier'), [
                        'id' => 'modifier.id',
                        'deleted' => 'modifier.deleted',
                    ]),
                    'displayfunc'  => 'user_link'
                ]
            ),
            new rb_column_option(
                'base',
                'status',
                get_string('status', 'rb_source_evidence_type'),
                'base.status',
                [
                    'displayfunc' => 'evidence_type_status',
                    'dbdatatype' => 'integer',
                ]
            ),
            new rb_column_option(
                'base',
                'in_use',
                get_string('in_use', 'rb_source_evidence_type'),
                '(
                    SELECT 
                        CASE WHEN COUNT(t.id) > 0 THEN 1 ELSE 0 END 
                    FROM {totara_evidence_item} t 
                    WHERE t.typeid = base.id
                )',
                [
                    'issubquery' => true,
                    'displayfunc' => 'yes_or_no',
                    'dbdatatype' => 'boolean',
                ]
            ),
            new rb_column_option(
                'base',
                'field_count',
                get_string('field_count', 'rb_source_evidence_type'),
                '(
                    SELECT 
                        COUNT(cf.id) 
                    FROM {totara_evidence_type_info_field} cf 
                    WHERE cf.typeid = base.id
                )',
                [
                    'issubquery' => true,
                    'displayfunc' => 'integer',
                ]
            ),
            new rb_column_option(
                'base',
                'location',
                get_string('location', 'rb_source_evidence_type'),
                'base.location',
                [
                    'displayfunc' => 'evidence_type_location',
                    'graphable' => false,
                ]
            ),
            new rb_column_option(
                'base',
                'actions',
                get_string('actions'),
                'base.id',
                [
                    'joins'       => 'base',
                    'displayfunc' => 'evidence_type_actions',
                    'noexport'    => true,
                    'nosort'      => true,
                    'graphable'   => false,
                    'extrafields' => [
                        'id' => 'base.id',
                        'name' => 'base.name',
                        'idnumber' => 'base.idnumber',
                        'description' => 'base.description',
                        'descriptionformat' => 'base.descriptionformat',
                        'status' => 'base.status',
                        'location' => 'base.location',
                        'created_by' => 'base.created_by',
                        'created_at' => 'base.created_at',
                        'modified_by' => 'base.modified_by',
                        'modified_at' => 'base.modified_at',
                    ]
                ]
            ),
        ];

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
                get_string('name', 'rb_source_evidence_type'),
                'text'
            ),
            new rb_filter_option(
                'base',
                'idnumber',
                get_string('idnumber', 'rb_source_evidence_type'),
                'text'
            ),
            new rb_filter_option(
                'base',
                'description',
                get_string('description', 'rb_source_evidence_type'),
                'text'
            ),
            new rb_filter_option(
                'base',
                'created_at',
                get_string('date_created', 'rb_source_evidence_type'),
                'date'
            ),
            new rb_filter_option(
                'base',
                'created_by',
                get_string('user_created', 'rb_source_evidence_type'),
                'text'
            ),
            new rb_filter_option(
                'base',
                'modified_at',
                get_string('date_modified', 'rb_source_evidence_type'),
                'date'
            ),
            new rb_filter_option(
                'base',
                'modified_by',
                get_string('user_modified', 'rb_source_evidence_type'),
                'text'
            ),
            new rb_filter_option(
                'base',
                'status',
                get_string('status', 'rb_source_evidence_type'),
                'multicheck',
                [
                    'simplemode' => true,
                    'selectfunc' => 'evidence_type_status',
                ]
            ),
            new rb_filter_option(
                'base',
                'in_use',
                get_string('in_use', 'rb_source_evidence_type'),
                'multicheck',
                [
                    'simplemode' => true,
                    'selectfunc' => 'yesno_list',
                ]
            ),
            new rb_filter_option(
                'base',
                'field_count',
                get_string('field_count', 'rb_source_evidence_type'),
                'number'
            ),
            new rb_filter_option(
                'base',
                'location',
                get_string('location', 'rb_source_evidence_type'),
                'multicheck',
                [
                    'simplemode' => true,
                    'selectfunc' => 'evidence_type_location',
                ]
            ),
        ];

        return $filteroptions;
    }

    /**
     * @return array
     */
    public function rb_filter_evidence_type_location(): array {
        return [
            evidence_type::LOCATION_EVIDENCE_BANK      => get_string('evidence_bank', 'totara_evidence'),
            evidence_type::LOCATION_RECORD_OF_LEARNING => get_string('record_of_learning', 'totara_evidence'),
        ];
    }

    /**
     * @return string[][]
     */
    protected function define_defaultcolumns(): array {
        return [
            [
                'type'  => 'base',
                'value' => 'name'
            ],
            [
                'type'  => 'base',
                'value' => 'idnumber'
            ],
            [
                'type'  => 'base',
                'value' => 'forusein'
            ],
            [
                'type'  => 'base',
                'value' => 'actions'
            ],
        ];
    }

    /**
     * @return array
     */
    public function rb_filter_evidence_type_status(): array {
        return [
            evidence_type::STATUS_ACTIVE => get_string('status_active', 'rb_source_evidence_type'),
            evidence_type::STATUS_HIDDEN => get_string('status_hidden', 'rb_source_evidence_type'),
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
        ];
    }

    public function phpunit_column_test_expected_count($columnoption) {
        // There are two system generated types for completion import present
        return 3;
    }

    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        global $CFG, $DB;

        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        $evidence_generator = \totara_evidence\testing\generator::instance();
        $evidence_generator->create_evidence_type(['name' => 'Type']);
    }

}
