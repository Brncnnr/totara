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

defined('MOODLE_INTERNAL') || die();

use mod_approval\data_provider\workflow\workflow as workflow_provider;
use mod_approval\model\workflow\stage_type\finished;
// For phpunit tests
use approvalform_simple\installer;
use totara_core\advanced_feature;

class rb_source_approval_workflow extends rb_base_source {

    public function __construct(rb_global_restriction_set $globalrestrictionset = null) {
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;
        $this->base = '{approval_workflow}';
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_approval_workflow');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_approval_workflow');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_approval_workflow');
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
     * Set the database base query
     *
     * @return string
     */
    private function define_join_on(): string {
        $join_on = (new workflow_provider())->build_join_on();
        return str_replace('workflow.id', 'base.id', $join_on);
    }

    /**
     * Define approval workflow join list
     *
     * @return array
     */
    protected function define_joinlist(): array {
        $joinlist = [
            new rb_join(
                'workflow_type',
                'INNER',
                '{approval_workflow_type}',
                'base.workflow_type_id = workflow_type.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'base'
            ),
            new rb_join(
                'form',
                'INNER',
                '{approval_form}',
                'base.form_id = form.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'base'
            ),
            new rb_join(
                'workflow_version',
                'INNER',
                '{approval_workflow_version}',
                'workflow_version.id = ' . $this->define_join_on(),
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'base'
            ),
            new rb_join(
                'form_version',
                'INNER',
                '{approval_form_version}',
                'form_version.id = workflow_version.form_version_id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'workflow_version'
            ),
            new rb_join(
                'application',
                'LEFT',
                '{approval_application}',
                'workflow_version.id = application.workflow_version_id',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'workflow_version'
            ),
            new rb_join(
                'course',
                'INNER',
                '{course}',
                'base.course_id = course.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'base'
            ),
            new rb_join(
                'assignment',
                'INNER',
                '{approval}',
                'assignment.course = course.id AND assignment.is_default = 1',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'course'
            ),
        ];
        return $joinlist;
    }

    /**
     * Define approval workflow column options
     *
     * @return array
     */
    protected function define_columnoptions(): array {
        $columnoptions = [
            new rb_column_option(
                'workflow',
                'name_link',
                get_string('workflow_namelink', 'rb_source_approval_workflow'),
                'base.name',
                [
                    'dbdatatype' => 'text',
                    'displayfunc' => 'workflow_name_link',
                    'extrafields' => [
                        'workflow_id' => 'base.id',
                    ],
                ]
            ),
            new rb_column_option(
                'workflow',
                'id',
                get_string('workflow_id', 'rb_source_approval_workflow'),
                'base.id',
                [
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'workflow',
                'updated',
                get_string('updated', 'rb_source_approval_workflow'),
                'base.updated',
                [
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'nice_date',
                ]
            ),
            new rb_column_option(
                'workflow',
                'active',
                get_string('active', 'rb_source_approval_workflow'),
                'base.active',
                [
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'yes_or_no',
                ]
            ),
            new rb_column_option(
                'workflow_type',
                'name',
                get_string('workflow_type', 'rb_source_approval_workflow'),
                'workflow_type.name',
                [
                    'joins' => 'workflow_type',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'assignment',
                'type',
                get_string('assignment_type', 'rb_source_approval_workflow'),
                'assignment.assignment_type',
                [
                    'joins' => 'assignment',
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'assignment_type',
                ]
            ),
            new rb_column_option(
                'assignment',
                'assigned_to',
                get_string('assigned_to', 'rb_source_approval_workflow'),
                'assignment.name',
                [
                    'joins' => 'assignment',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'workflow',
                'form_title',
                get_string('form_title', 'rb_source_approval_workflow'),
                'form.title',
                [
                    'joins' => 'form',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'workflow_version',
                'id',
                get_string('version', 'rb_source_approval_workflow'),
                'workflow_version.id',
                [
                    'joins' => 'workflow_version',
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'workflow_version',
                'status',
                get_string('version_status', 'rb_source_approval_workflow'),
                'workflow_version.status',
                [
                    'joins' => 'workflow_version',
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'workflow_version_status',
                ]
            ),
            new rb_column_option(
                'workflow_version',
                'form_version',
                get_string('form_version', 'rb_source_approval_workflow'),
                'form_version.version',
                [
                    'joins' => 'form_version',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'application',
                'count_draft',
                get_string('count_draft', 'rb_source_approval_workflow'),
                "(SELECT COUNT('x') FROM {approval_application} aa
                         WHERE aa.is_draft = 1
                           AND aa.workflow_version_id = workflow_version.id)",
                [
                    'joins' => 'workflow_version',
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'application',
                'count_completed',
                get_string('count_completed', 'rb_source_approval_workflow'),
                "(SELECT COUNT('x') FROM {approval_application} aa
                         JOIN {approval_workflow_stage} aas
                           ON aas.id = aa.current_stage_id
                        WHERE aas.type_code = " . finished::get_code() . "
                           AND aa.workflow_version_id = workflow_version.id)",
                [
                    'joins' => 'workflow_version',
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'format_string',
                ]
            ),
            // TODO need to exclude rejected last action
            new rb_column_option(
                'application',
                'count_in_progress',
                get_string('count_in_progress', 'rb_source_approval_workflow'),
                "(SELECT COUNT('x') FROM {approval_application} aa
                          JOIN {approval_workflow_stage} aas
                            ON aas.id = aa.current_stage_id
                         WHERE aa.is_draft = 0
                           AND aas.type_code != " . finished::get_code() . "
                           AND aa.workflow_version_id = workflow_version.id)",
                [
                    'joins' => 'workflow_version',
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'format_string',
                ]
            ),
        ];
        return $columnoptions;
    }

    /**
     * Define approval workflow filter options
     *
     * @return array
     */
    protected function define_filteroptions(): array {
        $filteroptions = [
            new rb_filter_option(
                'workflow',
                'name_link',
                get_string('workflow_name', 'rb_source_approval_workflow'),
                'text',
                [
                    'hiddenoperator' => [rb_filter_type::RB_FILTER_ISEMPTY, rb_filter_type::RB_FILTER_ISNOTEMPTY]
                ]
            ),
        ];
        return $filteroptions;
    }

    /**
     * Define approval workflow default columns
     *
     * @return string[][]
     */
    protected function define_defaultcolumns(): array {
        return [
            [
                'type' => 'workflow',
                'value' => 'id'
            ],
            [
                'type' => 'workflow',
                'value' => 'name_link'
            ],
            [
                'type' => 'workflow_type',
                'value' => 'name'
            ],
            [
                'type' => 'workflow',
                'value' => 'updated'
            ],
            [
                'type' => 'assignment',
                'value' => 'type'
            ],
            [
                'type' => 'assignment',
                'value' => 'assigned_to'
            ],
            [
                'type' => 'workflow_version',
                'value' => 'status'
            ],
        ];
    }

    /**
     * Define approval workflow default filters
     *
     * @return string[][]
     */
    protected function define_defaultfilters(): array {
        return [
            [
                'type' => 'workflow',
                'value' => 'name_link'
            ],
        ];
    }

    /**
     * Global restrictions
     *
     * @return bool
     */
    public function global_restrictions_supported(): bool {
        return true;
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
        $this->setUp();
    }

    /**
     * Returns expected result for column_test.
     *
     * @param rb_column_option $columnoption
     * @return int
     */
    public function phpunit_column_test_expected_count($columnoption): int {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }
        // Unit tests create a few test reports, so this source will find them.
        return 1;
    }

    private function setUp(): void {
        $installer = new installer();
        $cohort = $installer->install_demo_cohort();
        $workflow = $installer->install_demo_workflow($cohort, 'Simple');
        list($applicant, $ja) = $installer->install_demo_assignment($cohort);
        $installer->install_demo_applications($workflow, $applicant, $ja);
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return advanced_feature::is_disabled('approval_workflows');
    }
}