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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\controllers\workflow\form_view;

use context;
use mod_approval\interactor\application_interactor;
use mod_approval\model\application\application;
use mod_approval\model\application\application_state;
use mod_approval\model\form\approvalform_base;
use moodle_exception;
use core\entity\user;
use container_approval\approval as container_approval;
use totara_mvc\tui_view;
use mod_approval\controllers\workflow_controller;
use mod_approval\exception\access_denied_exception;
use mod_approval\form_schema\form_schema;
use mod_approval\interactor\category_interactor;
use mod_approval\model\workflow\workflow;

/**
 * Preview form.
 */
class preview extends workflow_controller {

    /**
     * @inheritDoc
     */
    protected $layout = 'webview';

    /**
     * @return tui_view
     */
    public function action(): tui_view {
        $workflow_id = $this->get_required_param('workflow_id', PARAM_INT);
        $stage_id = $this->get_required_param('stage_id', PARAM_INT);
        $this->set_url(self::get_url(['workflow_id' => $workflow_id, 'stage_id' => $stage_id]));

        if (!$this->get_category_interactor()->can_manage_workflows()) {
            throw access_denied_exception::manage_workflows();
        }

        // TODO TL-33313: Move this into a form_schema class, and cover with tests.
        //   form_schema::temporary_stage_schema_for_user(workflow_id, stage_id, user_id) ?
        $workflow = workflow::load_by_id($workflow_id);
        $workflow_version = $workflow->latest_version;
        $form_schema = form_schema::from_form_version($workflow_version->form_version);
        $stage = $workflow_version->stages->find('id', $stage_id);
        if (empty($stage)) {
            throw new moodle_exception('Workflow stage not found.');
        }
        $stage_schema = $form_schema->apply_formviews($stage->formviews);

        // Now create a temporary draft application, and use it to allow the approvalform plugin to adjust the schema.
        $start_state = new application_state($stage_id, true);
        $interactor_user_id = user::logged_in()->id;
        $temp_application = application::create_admin_preview(
            $start_state,
            $workflow_version,
            $workflow->default_assignment,
            $interactor_user_id
        );
        $application_interactor = application_interactor::from_application_model($temp_application, $interactor_user_id);
        $plugin = approvalform_base::from_plugin_name($workflow_version->form_version->form->plugin_name);
        $stage_schema = $plugin->adjust_form_schema_for_application($application_interactor, $stage_schema);
        // Then delete the application.
        $temp_application->delete();

        $props = [
            'workflow-id' => $workflow_id,
            'stage-id' => $stage_id,
            'schema-json' => $stage_schema->to_json(),
        ];

        // Allow behat to locate the window.
        if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
            $this->get_page()->requires->js_init_code('window.name = "totara_approval_workflow_form_view_preview";');
        }

        return workflow_controller::create_tui_view('mod_approval/pages/WorkflowFormViewPreview', $props)
            ->set_title(get_string('form_preview', 'mod_approval'));
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
        return '/mod/approval/workflow/form_view/preview.php';
    }

    /**
     * @return category_interactor
     */
    private function get_category_interactor(): category_interactor {
        return new category_interactor(
            container_approval::get_default_category_context(),
            user::logged_in()->id
        );
    }
}
