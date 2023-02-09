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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

defined('MOODLE_INTERNAL') || die();

use core\orm\query\order;
use mod_approval\entity\workflow\workflow as workflow_entity;
use mod_approval\model\workflow\workflow as workflow_model;
use totara_core\advanced_feature;

/**
 * Workflow applications report source
 */
class rb_source_approval_workflow_applications extends rb_base_source {

    use totara_job\rb\source\report_trait;
    use core_course\rb\source\report_trait;

    /**
     * @inheritDoc
     */
    public function __construct(rb_global_restriction_set $globalrestrictionset = null) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/approval/classes/rb/filter/rb_filter_application_status.php');
        require_once($CFG->dirroot . '/mod/approval/classes/rb/filter/rb_filter_workflow_stage_type.php');
        require_once($CFG->dirroot . '/mod/approval/classes/rb/filter/rb_filter_workflow_stage.php');

        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;
        $this->base = '{approval_application}';
        $this->sourcetitle   = get_string('sourcetitle', 'rb_source_approval_workflow_applications');
        $this->sourcesummary = get_string('sourcesummary', 'rb_source_approval_workflow_applications');
        $this->sourcelabel   = get_string('sourcelabel', 'rb_source_approval_workflow_applications');
        $this->sourcewhere = 'base.is_draft = 0 AND base.submitted IS NOT NULL';

        $this->joinlist        = $this->define_joinlist();
        $this->columnoptions   = $this->define_columnoptions();
        $this->filteroptions   = $this->define_filteroptions();
        $this->defaultcolumns  = $this->define_defaultcolumns();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->defaultfilters  = $this->define_defaultfilters();

        $this->usedcomponents[] = 'mod_approval';

        // This source is not cacheable, because it is used for the embedded 'application_form_responses' report,
        // which has dynamic columns. There is no way to create an appropriate table for caching.
        $this->cacheable = false;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function global_restrictions_supported(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function post_params(reportbuilder $report): void {
        if (!empty($report->embedobj->embeddedparams['workflow_id'])) {
            $workflow_id = $report->embedobj->embeddedparams['workflow_id'];
            $query = "workflow.id = $workflow_id";

            $source_where = $report->src->sourcewhere;
            $report->src->sourcewhere = empty($source_where)
                ? $query
                : "$source_where AND $query";
        }
    }

    /**
     * Define approval workflow join list
     *
     * @return array
     */
    protected function define_joinlist() {
        $joinlist = [
            new rb_join(
                'workflow_version',
                'INNER',
                '{approval_workflow_version}',
                'workflow_version.id = base.workflow_version_id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'base'
            ),
            new rb_join(
                'workflow_stage',
                'LEFT',
                '{approval_workflow_stage}',
                'workflow_stage.id = base.current_stage_id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'base'
            ),
            new rb_join(
                'application_approval_level',
                'LEFT',
                '{approval_workflow_stage_approval_level}',
                'application_approval_level.id = base.current_approval_level_id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'base'
            ),
            new rb_join(
                'form_version',
                'INNER',
                '{approval_form_version}',
                'workflow_version.form_version_id = form_version.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'workflow_version'
            ),
            new rb_join(
                'form',
                'INNER',
                '{approval_form}',
                'form.id = form_version.form_id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'form_version'
            ),
            new rb_join(
                'workflow',
                'INNER',
                '{approval_workflow}',
                'workflow_version.workflow_id = workflow.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'workflow_version'
            ),
        ];

        $this->add_core_user_tables($joinlist, 'base', 'user_id', 'applicant');
        $this->add_totara_job_tables($joinlist, 'base', 'user_id');

        return $joinlist;
    }

    /**
     * @inheritDoc
     */
    protected function define_columnoptions() {
        $columnoptions = [
            new rb_column_option(
                'workflow',
                'name',
                get_string('workflow_name', 'rb_source_approval_workflow_applications'),
                'workflow.name',
                [
                    'joins' => 'workflow',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'workflow_version',
                'id',
                get_string('workflow_version_id', 'rb_source_approval_workflow_applications'),
                'workflow_version.id',
                [
                    'joins' => 'base',
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'workflow_version',
                'status',
                get_string('workflow_version_status', 'rb_source_approval_workflow_applications'),
                'workflow_version.status',
                [
                    'joins' => 'base',
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'workflow_version_status',
                ]
            ),
            new rb_column_option(
                'application',
                'title',
                get_string('application_title', 'rb_source_approval_workflow_applications'),
                'base.title',
                [
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'application',
                'idnumber',
                get_string('application_idnumber', 'rb_source_approval_workflow_applications'),
                'base.id_number',
                [
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'application',
                'status',
                get_string('application_status', 'rb_source_approval_workflow_applications'),
                'base.completed',
                [
                    'dbdatatype' => 'text',
                    'displayfunc' => 'application_status',
                ]
            ),
            new rb_column_option(
                'workflow',
                'stage',
                get_string('workflow_stage', 'rb_source_approval_workflow_applications'),
                'workflow_stage.name',
                [
                    'joins' => 'workflow_stage',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'workflow',
                'stage_id',
                get_string('workflow_stage_id', 'rb_source_approval_workflow_applications'),
                'workflow_stage.id',
                [
                    'joins' => 'workflow_stage',
                    'dbdatatype' => 'integer',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'workflow',
                'stage_type',
                get_string('workflow_stage_type', 'rb_source_approval_workflow_applications'),
                'workflow_stage.type_code',
                [
                    'joins' => 'workflow_stage',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'stage_type',
                ]
            ),
            new rb_column_option(
                'application',
                'approval_level',
                get_string('application_approval_level', 'rb_source_approval_workflow_applications'),
                'application_approval_level.name',
                [
                    'joins' => 'application_approval_level',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'application',
                'submitted',
                get_string('application_submitted_date', 'rb_source_approval_workflow_applications'),
                'base.submitted',
                [
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'nice_date',
                ]
            ),
            new rb_column_option(
                'application',
                'completed',
                get_string('application_completed_date', 'rb_source_approval_workflow_applications'),
                'base.completed',
                [
                    'dbdatatype' => 'timestamp',
                    'displayfunc' => 'nice_date',
                ]
            ),
            new rb_column_option(
                'workflow_version',
                'form_version',
                get_string('form_version', 'rb_source_approval_workflow_version'),
                'form_version.version',
                [
                    'joins' => 'form_version',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'workflow',
                'form_title',
                get_string('form_title', 'rb_source_approval_workflow_version'),
                'form.title',
                [
                    'joins' => 'form',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'format_string',
                ]
            ),
            new rb_column_option(
                'workflow',
                'form_plugin_name',
                get_string('form_plugin_name', 'rb_source_approval_workflow_version'),
                'form.plugin_name',
                [
                    'joins' => 'form',
                    'dbdatatype' => 'text',
                    'displayfunc' => 'form_plugin_name',
                ]
            ),
        ];

        $this->add_core_user_columns($columnoptions, 'applicant', 'applicant');
        $this->add_totara_job_columns($columnoptions);

        return $columnoptions;
    }

    /**
     * @inheritDoc
     */
    protected function define_filteroptions() {
        $filteroptions = [];
        $filteroptions[] = $this->get_workflow_filter();
        $filteroptions[] = rb_filter_application_status::generate_filter_option();
        $filteroptions[] = rb_filter_workflow_stage_type::generate_filter_option();
        $filteroptions[] = rb_filter_workflow_stage::generate_filter_option();
        $this->add_core_user_filters($filteroptions, 'applicant');
        $this->add_totara_job_filters($filteroptions);
        return $filteroptions;
    }

    /**
     * Filter by workflow
     *
     * @return rb_filter_option
     */
    private function get_workflow_filter(): rb_filter_option {
        $workflows = workflow_entity::repository()
            ->order_by('name', order::DIRECTION_ASC)
            ->get()
            ->map(function (workflow_entity $workflow) {
                return workflow_model::load_by_entity($workflow)->name;
            })->all(true);

        return new rb_filter_option(
            'workflow',
            'name',
            get_string('workflow_name', 'rb_source_approval_workflow_applications'),
            'correlated_subquery_select',
            [
                'simplemode' => true,
                'cachingcompatible' => false,
                'selectchoices' => $workflows,
                'searchfield' => 'workflow.id',
                'subquery' => "%2\$s",
            ],
            'base.id',
            'base'
        );
    }

    /**
     * @inheritDoc
     */
    protected function define_defaultcolumns(): array {
        return self::get_default_columns();
    }

    /**
     * @inheritDoc
     */
    protected function define_defaultfilters() {
        return self::get_default_filters();
    }

    /**
     * Default columns for the report source
     *
     * @return array[]
     */
    public static function get_default_columns(): array {
        return [
            [
                'type' => 'workflow',
                'value' => 'name',
                'heading' => get_string('workflow_name', 'rb_source_approval_workflow_applications'),
            ],
            [
                'type' => 'application',
                'value' => 'title',
                'heading' => get_string('application_title', 'rb_source_approval_workflow_applications'),
            ],
            [
                'type' => 'application',
                'value' => 'idnumber',
                'heading' => get_string('application_idnumber', 'rb_source_approval_workflow_applications'),
            ],
            [
                'type' => 'workflow',
                'value' => 'stage_type',
                'heading' => get_string('workflow_stage_type', 'rb_source_approval_workflow_applications'),
            ],
            [
                'type' => 'workflow',
                'value' => 'stage',
                'heading' => get_string('workflow_stage', 'rb_source_approval_workflow_applications'),
            ],
            [
                'type' => 'application',
                'value' => 'approval_level',
                'heading' => get_string('application_approval_level', 'rb_source_approval_workflow_applications'),
            ],
            [
                'type' => 'application',
                'value' => 'completed',
                'heading' => get_string('application_completed_date', 'rb_source_approval_workflow_applications'),
            ],
            [
                'type' => 'applicant',
                'value' => 'fullname',
                'heading' => get_string('applicant_name', 'rb_source_approval_workflow_applications'),
            ],
        ];
    }

    /**
     * Default filters for the report source
     *
     * @return array
     */
    public static function get_default_filters(): array {
        return [
            [
                'type' => 'workflow',
                'value' => 'name',
            ],
            [
                'type' => 'applicant',
                'value' => 'fullname',
            ],
            [
                'type' => 'application',
                'value' => 'status',
            ],
            [
                'type' => 'workflow',
                'value' => 'stage_type',
            ],

        ];
    }

    /**
     * @inheritDoc
     */
    protected function define_contentoptions(): array {
        $contentoptions = [];
        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions);
        return $contentoptions;
    }

    /**
     * @inheritDoc
     */
    public function phpunit_column_test_expected_count($columnoption): int {
        return 0;
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return advanced_feature::is_disabled('approval_workflows');
    }
}