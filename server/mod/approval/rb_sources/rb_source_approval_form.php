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

use approvalform_simple\installer;
use mod_approval\plugininfo\approvalform;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

class rb_source_approval_form extends rb_base_source {

    public function __construct(rb_global_restriction_set $globalrestrictionset = null) {
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;
        $this->base = '{approval_form}';
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_approval_form');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_approval_form');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_approval_form');
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
     * Define approval form join list
     *
     * @return array
     */
    protected function define_joinlist() {
        return [
            new rb_join(
                'form_version',
                'INNER',
                '{approval_form_version}',
                'form_version.form_id = base.id',
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'base'
            ),
            new rb_join(
                'workflow_usage',
                'LEFT',
                '(SELECT COUNT(w.id) AS totaluse, w.form_id FROM {approval_workflow} w GROUP BY w.form_id)',
                'base.id = workflow_usage.form_id',
                REPORT_BUILDER_RELATION_ONE_TO_MANY
            ),
        ];
    }

    /**
     * Define approval form column options
     *
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = [
            new rb_column_option(
                'form',
                'plugin_name',
                get_string('plugin_name', 'rb_source_approval_form'),
                'base.plugin_name',
                [
                    'dbdatatype' => 'text',
                    'displayfunc' => 'form_plugin_name',
                ]
            ),
            new rb_column_option(
                'form',
                'plugin_schema_version',
                get_string('plugin_schema_version', 'rb_source_approval_form'),
                'base.plugin_name',
                [
                    'nosort' => true,
                    'dbdatatype' => 'text',
                    'displayfunc' => 'form_plugin_schema_version',
                ]
            ),
            new rb_column_option(
                'form',
                'title',
                get_string('title', 'rb_source_approval_form'),
                'base.title',
                [
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'form',
                'active',
                get_string('active', 'rb_source_approval_form'),
                'base.active',
                [
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'yes_or_no',
                ]
            ),
            new rb_column_option(
                'form',
                'created',
                get_string('created', 'rb_source_approval_form'),
                'base.created',
                [
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'nice_date',
                ]
            ),
            new rb_column_option(
                'form',
                'updated',
                get_string('updated', 'rb_source_approval_form'),
                'base.updated',
                [
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'nice_date',
                ]
            ),
            new rb_column_option(
                'form_version',
                'id',
                get_string('version_id', 'rb_source_approval_form'),
                'form_version.id',
                [
                    'joins' => 'form_version',
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'form_version',
                'updated',
                get_string('version_updated', 'rb_source_approval_form'),
                'form_version.updated',
                [
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'nice_date',
                ]
            ),
            new rb_column_option(
                'form_version',
                'status',
                get_string('version_status', 'rb_source_approval_form'),
                'form_version.status',
                [
                    'joins' => 'form_version',
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'form_version_status',
                ]
            ),
            new rb_column_option(
                'form_version',
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
                'form',
                'inuse',
                get_string('in_use', 'rb_source_approval_form'),
                'workflow_usage.totaluse',
                [
                    'joins' => ['workflow_usage'],
                    'graphable' => false,
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'form_inuse',
                ]
            ),
            new rb_column_option(
                'form',
                'actions',
                get_string('actions', 'rb_source_approval_form'),
                'base.id',
                [
                    'graphable' => false,
                    'noexport' => true,
                    'nosort' => true,
                    'displayfunc' => 'form_actions',
                    'capability' => 'mod/approval:manage_workflows',
                    'joins' => ['workflow_usage'],
                    'extrafields' => [
                        'inuse' => 'workflow_usage.totaluse',
                        'active' => 'base.active',
                        'title' => 'base.title'
                    ],
                ]
            ),
        ];
        return $columnoptions;
    }

    /**
     * Define approval form filter options
     *
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = [
            new rb_filter_option(
                'form',
                'title',
                get_string('title', 'rb_source_approval_form'),
                'text',
                [
                    'hiddenoperator' => [rb_filter_type::RB_FILTER_ISEMPTY, rb_filter_type::RB_FILTER_ISNOTEMPTY]
                ]
            ),
            new rb_filter_option(
                'form',
                'plugin_name',
                get_string('plugin_name', 'rb_source_approval_form'),
                'text',
                [
                    'hiddenoperator' => [rb_filter_type::RB_FILTER_ISEMPTY, rb_filter_type::RB_FILTER_ISNOTEMPTY]
                ]
            ),
            new rb_filter_option(
                'form',
                'active',
                get_string('active', 'rb_source_approval_form'),
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
     * Define approval form default columns
     *
     * @return string[][]
     */
    protected function define_defaultcolumns() {
        return [
            [
                'type' => 'form',
                'value' => 'title'
            ],
            [
                'type' => 'form',
                'value' => 'plugin_name'
            ],
            [
                'type' => 'form',
                'value' => 'active'
            ],
            [
                'type' => 'form',
                'value' => 'updated'
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
                'type' => 'form',
                'value' => 'title'
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
    public function phpunit_column_test_expected_count($columnoption) {
        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_expected_count() cannot be used outside of unit tests');
        }
        // Unit tests create a few test reports, so this source will find them.
        return 1;
    }

    private function setUp(): void {
        $installer = new installer();
        /** @var approvalform $plugin */
        $plugin = approvalform::from_plugin_name('simple');
        if (!$plugin->is_enabled()) {
            approvalform::enable_plugin('simple');
        }
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