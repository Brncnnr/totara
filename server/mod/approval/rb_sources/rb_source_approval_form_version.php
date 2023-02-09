<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

defined('MOODLE_INTERNAL') || die();

use mod_approval\model\status;
// For phpunit tests
use approvalform_simple\installer;
use mod_approval\plugininfo\approvalform;
use totara_core\advanced_feature;

class rb_source_approval_form_version extends rb_base_source {

    public function __construct(rb_global_restriction_set $globalrestrictionset = null) {
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;
        $this->base = '{approval_form_version}';
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_approval_form_version');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_approval_form_version');
        $this->sourcelabel = get_string('sourcelabel', 'rb_source_approval_form_version');
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
     * Define approval form version join list
     *
     * @return array
     */
    protected function define_joinlist() {
        $joinlist = [
            new rb_join(
                'form',
                'INNER',
                '{approval_form}',
                'form.id = base.form_id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'base'
            ),
        ];
        return $joinlist;
    }

    /**
     * Define approval form version column options
     *
     * @return array
     */
    protected function define_columnoptions() {
        $columnoptions = [
            new rb_column_option(
                'form_version',
                'id',
                get_string('version', 'rb_source_approval_form_version'),
                'base.id',
                [
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'form',
                'form_id',
                get_string('form_id', 'rb_source_approval_form_version'),
                'form.id',
                [
                    'joins' => 'form',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'form',
                'form_title',
                get_string('form_title', 'rb_source_approval_form_version'),
                'form.title',
                [
                    'joins' => 'form',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'form',
                'form_plugin_name',
                get_string('form_plugin_name', 'rb_source_approval_form_version'),
                'form.plugin_name',
                [
                    'joins' => 'form',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'form_plugin_name',
                ]
            ),
            new rb_column_option(
                'form_version',
                'status',
                get_string('status', 'rb_source_approval_form_version'),
                'base.status',
                [
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'form_version_status',
                ]
            ),
            new rb_column_option(
                'form_version',
                'form_version',
                get_string('form_version', 'rb_source_approval_form_version'),
                'base.version',
                [
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'form_version',
                'created',
                get_string('created', 'rb_source_approval_form_version'),
                'base.created',
                [
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'nice_date',
                ]
            ),
            new rb_column_option(
                'form_version',
                'updated',
                get_string('updated', 'rb_source_approval_form_version'),
                'base.updated',
                [
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'nice_date',
                ]
            ),
            new rb_column_option(
                'form_version',
                'published',
                get_string('published', 'rb_source_approval_form_version'),
                'base.updated',
                [
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'form_published_date',
                    'extrafields' => [
                        'status' => 'base.status',
                    ],
                ]
            ),
            new rb_column_option(
                'form_version',
                'archived',
                get_string('archived', 'rb_source_approval_form_version'),
                'base.updated',
                [
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'form_archived_date',
                    'extrafields' => [
                        'status' => 'base.status',
                    ],
                ]
            ),
        ];
        return $columnoptions;
    }

    /**
     * Define approval form version filter options
     *
     * @return array
     */
    protected function define_filteroptions() {
        $filteroptions = [
            new rb_filter_option(
                'form',
                'form_title',
                get_string('form_title', 'rb_source_approval_form_version'),
                'text',
                [
                    'hiddenoperator' => [rb_filter_type::RB_FILTER_ISEMPTY, rb_filter_type::RB_FILTER_ISNOTEMPTY]
                ]
            ),
            new rb_filter_option(
                'form_version',
                'status',
                get_string('status', 'rb_source_approval_form_version'),
                'select',
                [
                    'selectchoices' => [
                        status::DRAFT => status::label(status::DRAFT)->out(),
                        status::ACTIVE => status::label(status::ACTIVE)->out(),
                        status::ARCHIVED => status::label(status::ARCHIVED)->out(),
                    ],
                    'simplemode' => true
                ]
            ),
        ];
        return $filteroptions;
    }

    /**
     * Define approval form version default columns
     *
     * @return string[][]
     */
    protected function define_defaultcolumns() {
        return [
            [
                'type' => 'form_version',
                'value' => 'id'
            ],
            [
                'type' => 'form',
                'value' => 'form_title'
            ],
            [
                'type' => 'form_version',
                'value' => 'published'
            ],
            [
                'type' => 'form_version',
                'value' => 'archived'
            ],
            [
                'type' => 'form_version',
                'value' => 'status'
            ],
        ];
    }

    /**
     * Define form type default filters
     *
     * @return string[][]
     */
    protected function define_defaultfilters() {
        return [
            [
                'type' => 'form',
                'value' => 'form_title'
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