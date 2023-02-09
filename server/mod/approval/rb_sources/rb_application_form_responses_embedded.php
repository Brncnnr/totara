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

use mod_approval\controllers\workflow\report;
use mod_approval\entity\workflow\workflow_stage;
use mod_approval\entity\workflow\workflow_stage_formview;
use mod_approval\entity\workflow\workflow_version;
use mod_approval\form_schema\form_schema;
use mod_approval\model\workflow\workflow;
use totara_core\advanced_feature;

global $CFG;
require_once($CFG->dirroot . '/mod/approval/rb_sources/rb_source_approval_workflow_applications.php');

/**
 * Embedded report for application form responses
 */
class rb_application_form_responses_embedded extends rb_base_embedded {

    /**
     * @inheritDoc
     */
    public function __construct($data) {
        $this->source = 'approval_workflow_applications';
        $this->shortname = 'application_form_responses';
        $this->fullname = get_string('embedded_application_form_responses_export', 'mod_approval');
        $this->filters = $this->define_filters();
        $this->columns = rb_source_approval_workflow_applications::get_default_columns();

        if (!empty($data['workflow_id'])) {
            $this->url = report::get_url(['workflow_id' => $data['workflow_id']]);
            $workflow = workflow::load_by_id($data['workflow_id']);

            $field_keys = workflow_stage_formview::repository()
                ->as('fv')
                ->join([workflow_stage::TABLE, 's'], 'fv.workflow_stage_id', 's.id')
                ->join([workflow_version::TABLE, 'v'], 's.workflow_version_id', 'v.id')
                ->where('v.workflow_id', $workflow->id)
                ->select_raw('DISTINCT field_key')
                ->get()
                ->keys();

            $this->columns = array_merge($this->columns, $this->define_columns($workflow, $field_keys));
            $this->requiredcolumns = $this->define_required_columns($workflow, $field_keys);
            $this->embeddedparams['workflow'] = $data['workflow'];
            $this->embeddedparams['workflow_id'] = $data['workflow_id'];
        }

        parent::__construct();
    }

    /**
     * Define the available columns.
     *
     * @param workflow $workflow
     * @param array $field_keys
     * @return array
     */
    private function define_columns(workflow $workflow, array $field_keys): array {
        $columns = [];
        foreach ($workflow->versions as $version) {
            $schema = $version->form_version->json_schema;
            $fields = form_schema::from_json($schema)->get_fields();

            foreach ($fields as $field) {
                $key = $field->get_field_key();

                if (in_array($key, $field_keys)) {
                    $columns[] = [
                        'type' => 'application',
                        'value' => $key,
                        'heading' => $field->label,
                    ];
                }
            }
        }
        return $columns;
    }

    /**
     * The required columns
     *
     * @param workflow $workflow
     * @param array $field_keys
     * @return array
     */
    private function define_required_columns(workflow $workflow, array $field_keys): array {
        $columns = [];

        foreach ($workflow->versions as $version) {
            $schema = $version->form_version->json_schema;
            $fields = form_schema::from_json($schema)->get_fields();

            foreach ($fields as $field) {
                $key = $field->get_field_key();
                if (in_array($key, $field_keys)) {
                    $columns[] = new rb_column(
                        'application',
                        $key,
                        $field->label,
                        'base.id',
                        [
                            'displayfunc' => 'application_form_response',
                        ]
                    );
                }
            }
        }

        return $columns;
    }

    /**
     * Define the filters available
     *
     * @return array
     */
    private function define_filters(): array {
        return [
            [
                'type' => 'applicant',
                'value' => 'fullname',
            ],
            [
                'type' => 'application',
                'value' => 'status',
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public static function is_search_saving_allowed(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function is_cloning_allowed(): bool {
        return false;
    }

    /**
     * Check if the user is capable of accessing this report.
     *
     * @param int $reportfor userid of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return boolean true if the user can access this report
     */
    public function is_capable($reportfor, $report): bool {
        if (!empty($report->embedobj->embeddedparams['workflow'])) {
            /** @var workflow $workflow*/
            $workflow = $report->embedobj->embeddedparams['workflow'];
            $workflow_interactor = $workflow->get_interactor($reportfor);

            return $workflow_interactor->can_view_applications_report();
        }

        return true;
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_source_ignored() {
        return advanced_feature::is_disabled('approval_workflows');
    }
}