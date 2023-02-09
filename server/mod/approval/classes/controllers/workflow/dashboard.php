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

use container_approval\approval as container_approval;
use core\entity\user;
use mod_approval\controllers\workflow_controller;
use mod_approval\exception\access_denied_exception;
use mod_approval\interactor\category_interactor;
use mod_approval\model\assignment\assignment_type\provider;
use mod_approval\model\status;
use totara_mvc\tui_view;

/**
 * Manage approval workflow site admin
 */
class dashboard extends base {

    public const URL =  '/mod/approval/workflow/index.php';

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $user = user::logged_in();
        $category_interactor = new category_interactor(
            container_approval::get_default_category_context(),
            $user->id
        );

        if (!$category_interactor->can_manage_workflows()) {
            throw access_denied_exception::manage_workflows();
        }

        $props = [
            'context-id' => container_approval::get_default_category_context()->id,
            'can-create-workflow' => $category_interactor->can_create_workflow(),
            'filter-options' => [
                'assignment_types' => $this->parse_assignment_types(),
                'status' => status::get_list(),
                'workflow_types' => $this->load_workflow_types(),
            ],
        ];

        $title = get_string('workflow_dashboard_title', 'mod_approval');
        return workflow_controller::create_tui_view('mod_approval/pages/WorkflowDashboard', $props)
            ->set_title($title);
    }

    /**
     * Parses the assignment types to a format used in the front end.
     *
     * @return array
     */
    private function parse_assignment_types(): array {
        $result = [
            [
                'label' => get_string('filter_all', 'mod_approval'),
                'enum' => null,
            ],
        ];
        $assignment_types = provider::get_all();

        foreach ($assignment_types as $assignment_type) {
            $result[] = [
                'label' => $assignment_type::get_label(),
                'enum' => $assignment_type::get_enum(),
            ];
        }

        return $result;
    }

    /**
     * Query workflow types.
     *
     * @return array
     */
    private function load_workflow_types(): array {

        $result = $this->execute_graphql_operation('mod_approval_load_workflow_types', ['input' => ['require_active_workflow' => false]]);

        if (empty($result['data']['mod_approval_load_workflow_types']['workflow_types'])) {
            return [];
        }

        return $result['data']['mod_approval_load_workflow_types']['workflow_types'];
    }

}
