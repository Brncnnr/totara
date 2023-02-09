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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

class rb_source_approval_workflow_type extends rb_base_source {

    public function __construct(rb_global_restriction_set $globalrestrictionset = null) {
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;
        $this->base = '{approval_workflow_type}';
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_approval_workflow_type');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_approval_workflow_type');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_approval_workflow_type');
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->usedcomponents[] = 'mod_approval';
        parent::__construct();
    }

    /**
     * Define workflow type join list
     *
     * @return array
     */
    protected function define_joinlist() {
        return [
            new rb_join(
                'workflow_usage',
                'LEFT',
                '(SELECT COUNT(w.id) AS totaluse, w.workflow_type_id FROM {approval_workflow} w GROUP BY w.workflow_type_id)',
                'base.id = workflow_usage.workflow_type_id',
                REPORT_BUILDER_RELATION_ONE_TO_MANY
            )
        ];
    }

    /**
     * Define workflow type column options
     *
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = [
            new rb_column_option(
                'workflow_type',
                'name',
                get_string('name', 'rb_source_approval_workflow_type'),
                'base.name',
                [
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'workflow_type',
                'description',
                get_string('description', 'rb_source_approval_workflow_type'),
                'base.description',
                [
                    'graphable' => false,
                    'nosort' => true,
                    'dbdatatype' => 'text',
                    'displayfunc' => 'workflow_type_description',
                    'extrafields' => [
                        'id' => 'base.id'
                    ]
                ]
            ),
            new rb_column_option(
                'workflow_type',
                'active',
                get_string('active', 'rb_source_approval_workflow_type'),
                'base.active',
                [
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'yes_or_no',
                ]
            ),
            new rb_column_option(
                'workflow_type',
                'created',
                get_string('created', 'rb_source_approval_workflow_type'),
                'base.created',
                [
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'nice_date',
                ]
            ),
            new rb_column_option(
                'workflow_type',
                'inuse',
                get_string('in_use', 'rb_source_approval_workflow_type'),
                'workflow_usage.totaluse',
                [
                    'joins' => ['workflow_usage'],
                    'graphable' => false,
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'workflow_type_inuse',
                ]
            ),
            new rb_column_option(
                'workflow_type',
                'actions',
                get_string('actions', 'rb_source_approval_workflow_type'),
                'base.id',
                [
                    'graphable' => false,
                    'noexport' => true,
                    'nosort' => true,
                    'displayfunc' => 'workflow_type_actions',
                    'capability' => 'mod/approval:manage_workflows',
                    'joins' => ['workflow_usage'],
                    'extrafields' => [
                        'inuse' => 'workflow_usage.totaluse',
                        'active' => 'base.active',
                        'name' => 'base.name'
                    ],
                ]
            )

        ];
        return $columnoptions;
    }

    /**
     * Define workflow type filter options
     *
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = [
            new rb_filter_option(
                'workflow_type',
                'name',
                get_string('name', 'rb_source_approval_workflow_type'),
                'text',
                [
                    'hiddenoperator' => [rb_filter_type::RB_FILTER_ISEMPTY, rb_filter_type::RB_FILTER_ISNOTEMPTY]
                ]
            ),
            new rb_filter_option(
                'workflow_type',
                'active',
                get_string('active', 'rb_source_approval_workflow_type'),
                'select',
                [
                    'selectchoices' => [0 => get_string('no'), 1 => get_string('yes')],
                    'simplemode' => true
                ]
            )
        ];
        return $filteroptions;
    }

    /**
     * Define workflow type default columns
     *
     * @return string[][]
     */
    protected function define_defaultcolumns() {
        return [
            [
                'type' => 'workflow_type',
                'value' => 'name'
            ],
            [
                'type' => 'workflow_type',
                'value' => 'description'
            ],
        ];
    }

    /**
     * Define workflow type default filters
     *
     * @return string[][]
     */
    protected function define_defaultfilters() {
        return [
            [
                'type' => 'workflow_type',
                'value' => 'name'
            ],
        ];
    }

    /**
     * Global restrictions
     *
     * @return bool
     */
    public function global_restrictions_supported() {
        return true;
    }

    /**
     * Inject column_test data into database.
     *
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        global $DB;

        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        $form = [
            'plugin_name' => 'simple', 'title' => 'Standard Form', 'active' => '1', 'created' => 1627447558, 'updated' => 1627447558
        ];
        $form_id = $DB->insert_record('approval_form', $form);

        $workflow_type = [
            'name' => 'Test WF type', 'description' => 'Lorem ipsum dolor sit amet', 'active' => 1, 'created' => 1627447558
        ];
        $workflow_type_id = $DB->insert_record('approval_workflow_type', $workflow_type);

        $course = [
            'fullname' => 'Test Course 1', 'shortname' => 'TC1', 'category' => 1, 'idnumber' => 'ID1', 'startdate' => 1627447558, 'icon' => '',
            'visible' => 1, 'audiencevisible' => 2, 'summary' => 'Course Summary', 'coursetype' => 0, 'lang' => 'en',
        ];
        $course_id = $DB->insert_record('course', $course);

        $workflow = [
            'course_id' => $course_id,
            'workflow_type_id' => $workflow_type_id,
            'name' => 'Default Workflow 1',
            'id_number' => 'workflow6100e106ca75d',
            'form_id' => $form_id,
            'template_id' => 0,
            'active' => 1,
            'created' => 1627447558,
            'updated' => 1627447558,
            'to_be_deleted' => 0,
        ];
        $DB->insert_record('approval_workflow', $workflow);

        $course = [
            'fullname' => 'Test Course 2', 'shortname' => 'TC2', 'category' => 1, 'idnumber' => 'ID2', 'startdate' => 1627447558, 'icon' => '',
            'visible' => 1, 'audiencevisible' => 2, 'summary' => 'Course Summary', 'coursetype' => 0, 'lang' => 'en',
        ];
        $course_id = $DB->insert_record('course', $course);

        $workflow = [
            'course_id' => $course_id,
            'workflow_type_id' => $workflow_type_id,
            'name' => 'Default Workflow 2',
            'id_number' => 'workflow6100e106ca75e',
            'form_id' => $form_id,
            'template_id' => 0,
            'active' => 1,
            'created' => 1627447558,
            'updated' => 1627447558,
            'to_be_deleted' => 0,
        ];
        $DB->insert_record('approval_workflow', $workflow);

        $totara_report_builder_data = [
            'fullname' => 'Approval Workflow Types', 'shortname' => 'approval_workflow_type', 'source' => 'mod_approval',
            'hidden' => 0, 'cache' => 0, 'accessmode' => 0, 'contentmode' => 0, 'description' => 'Report description', 'recordsperpage' => 40,
            'defaultsortcolumn' => 'name', 'defaultsortorder' => 4, 'embedded' => 1, 'initialdisplay' => 0, 'toolbarsearch' => 1,
            'globalrestriction' => 0, 'timemodified' => 0, 'showtotalcount' => 0, 'useclonedb' => 0
        ];
        $DB->insert_record('report_builder', $totara_report_builder_data);
    }

    /**
     * Returns expected result for column_test.
     *
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }
        // Unit tests create a few test reports, so this source will find them.
        return 1;
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return advanced_feature::is_disabled('approval_workflows');
    }
}