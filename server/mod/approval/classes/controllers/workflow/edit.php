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

namespace mod_approval\controllers\workflow;

use mod_approval\model\assignment\assignment_approver_type;
use mod_approval\model\workflow\stage_type\provider;
use totara_core\extended_context;
use container_approval\approval as approval_container;
use context;
use mod_approval\model\workflow\workflow;
use moodle_exception;
use totara_mvc\tui_view;
use mod_approval\controllers\workflow_controller;

/**
 * The edit workflow.
 */
class edit extends workflow_controller {

    /**
     * @inheritDoc
     */
    protected $layout = 'legacynolayout';

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        global $CFG;
        $workflow_id = $this->get_required_param('workflow_id', PARAM_INT);
        $this->set_url(self::get_url(['workflow_id' => $workflow_id]));
        $initial_data = $this->load_workflow_query($workflow_id);

        if (empty($initial_data['data'])) {
            if (!empty($CFG->debugdeveloper)) {
                $errors = array_reduce($initial_data['errors'] ?? [], function ($errors, $error) {
                    return $errors . "\n" . $error['message'];
                }, "\n");
                throw new moodle_exception('GraphQL resulted in errors' . $errors);
            }
            throw new moodle_exception('Workflow not found.');
        }

        $workflow = workflow::load_by_id((int)$workflow_id);
        $workflow_version = $workflow->get_latest_version();
        $stages = $workflow_version->get_stages();

        $stages_extended_contexts = [];
        foreach ($stages as $workflow_stage) {
            $extended_context = extended_context::make_with_context(
                $workflow->get_context(),
                'mod_approval',
                'workflow_stage',
                $workflow_stage->id
            );

            $stages_extended_contexts[] = [
                'component'   => $extended_context->get_component(),
                'area'        => $extended_context->get_area(),
                'itemId'      => $extended_context->get_item_id(),
                'contextId'   => $extended_context->get_context_id()
            ];
        }

        $props = [
            'context-id' => approval_container::get_default_category_context()->id,
            'stages-extended-contexts' => $stages_extended_contexts,
            'back-url' => dashboard::get_url()->out(false),
            'approver-types' => assignment_approver_type::get_list(),
            'stage-types' => $this->get_stage_types(),
            'query-results' => $initial_data['data'],
        ];

        return workflow_controller::create_tui_view('mod_approval/pages/WorkflowEdit', $props)
            ->set_title(get_string('workflow_edit', 'mod_approval'));
    }

    /**
     * Loads the workflow data by resolving a graphQL query.
     *
     * @param int $workflow_id
     * @return array
     */
    private function load_workflow_query(int $workflow_id): array {
        return $this->execute_graphql_operation(
            'mod_approval_load_workflow',
            [
                'input' => [
                    'workflow_id' => $workflow_id,
                ],
            ]
        );
    }

    /**
     * Get formatted list of stage types that can be used as a page property.
     *
     * @return array
     */
    private function get_stage_types(): array {
        $list = [];

        foreach (provider::get_types() as $type) {
            $list[] = [
                'label' => $type::get_label(),
                'enum' => $type::get_enum(),
            ];
        }

        return $list;
    }

    /**
     * @inheritDoc
     */
    protected function setup_context(): context {
        $workflow_id = $this->get_required_param('workflow_id', PARAM_INT);

        return workflow::load_by_id($workflow_id)->get_context();
    }

    /**
     * @inheritDoc
     */
    public static function get_base_url(): string {
        return '/mod/approval/workflow/edit.php';
    }
}
