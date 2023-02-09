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

namespace mod_approval\controllers\assignment;

use context;
use core\entity\user as user_entity;
use core\notification;
use container_approval\approval as approval_container;
use mod_approval\form\assignment_overrides_upload;
use mod_approval\controllers\workflow\edit as workflow_edit;
use mod_approval\controllers\workflow\dashboard;
use mod_approval\model\assignment\helper\csv_upload as csv_import_helper;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_stage_approval_level;
use mod_approval\entity\workflow\workflow as workflow_entity;
use totara_mvc\view;

/**
 * Class assignment overrides upload csv file
 */
class overrides extends base {

    /**
     * Workflow Id
     * @var null
     */
    private $workflow_id = null;

    /**
     * @inheritDoc
     */
    public function action() {

        $this->workflow_id = $this->get_required_param('workflow_id', PARAM_INT);
        $process_id = $this->get_optional_param('process_id', null, PARAM_INT);

        $workflow_entity = workflow_entity::repository()
            ->where('id', $this->workflow_id)
            ->one();
        if (!$workflow_entity) {
            notification::error(get_string('error:csv_workflow_missing', 'mod_approval'));
            return $this->view($this->cancel(), ['workflow_id' => $this->workflow_id]);
        }
        $workflow = workflow::load_by_id($this->workflow_id);
        $title = get_string('upload_assignment_overrides', 'mod_approval', (object)['name' => $workflow->name]);

        $role = csv_import_helper::get_role('approvalworkflowmanager');
        if (!$role) {
            notification::error(get_string('error:csv_role_missing', 'mod_approval'));
            return $this->view($this->cancel(), ['workflow_id' => $this->workflow_id], $title);
        }

        if (!$workflow->get_interactor(user_entity::logged_in()->id)->can_upload_approver_overrides()) {
            notification::error('Cannot upload assignment overrides for the given workflow');
            return $this->view($this->cancel(), ['workflow_id' => $this->workflow_id], $title);
        }

        $process_id = $process_id ?: csv_import_helper::get_new_iid(csv_import_helper::SRCTYPE);

        $params = ['workflow_id' => $this->workflow_id, 'process_id' => $process_id];
        $base_url = self::get_url($params);

        $process = csv_import_helper::instance($this->workflow_id, $process_id, $base_url);
        $mform_params = array_merge($params, ['help_substring' => static::get_help_substring($this->workflow_id)]);
        $mform = new assignment_overrides_upload(null, $mform_params);
        if ($mform->is_cancelled()) {
            $process->clean();
            redirect(workflow_edit::get_url(['workflow_id' => $this->workflow_id]));
        }
        if ($formdata = $mform->get_data()) {
            $process->upload_csv_content($formdata);
            redirect(overrides_confirm::get_url($params));
        }

        return $this->view($mform->render(), ['workflow_id' => $this->workflow_id, 'process_id' => $process_id], $title);
    }

    /**
     * @inheritDoc
     */
    public static function get_base_url(): string {
        return '/mod/approval/assignment/overrides.php';
    }

    /**
     * @inheritDoc
     */
    public function setup_context(): context {
        $workflow_id = $this->get_required_param('workflow_id', PARAM_INT);
        $workflow_entity = workflow_entity::repository()
            ->where('id', $workflow_id)
            ->one();
        if (!$workflow_entity) {
            // We need it to return UI error
            return approval_container::get_default_category_context();
        } else {
            return workflow::load_by_id($workflow_id)->get_context();
        }
    }

    /**
     * Get explanation for assignment overrides help description
     *
     * @param int $workflow_id
     * @return string
     */
    public static function get_help_substring(int $workflow_id): string {
        $records = csv_import_helper::get_approval_levels($workflow_id);
        $string = '';
        foreach ($records as $record) {
            /** @var workflow_stage_approval_level $record */
            $string .= '- ';
            $string .= csv_import_helper::get_stage_x_level_y($record->workflow_stage->ordinal_number, $record->ordinal_number);
            $string .= ': ';
            $string .= $record->name;
            $string .= "\n";
        }
        return $string;
    }

    /**
     * Return totara_mvc/view
     *
     * @param string $content
     * @param array $params - moodle_url params
     * @param string $title
     * @return view
     */
    private function view(string $content = '', array $params = [], string $title = ''): view {
        $this->set_url(self::get_url($params));
        return self::create_view(
            'mod_approval/assignment_override_form',
            [
                'title' => $title,
                'mform' => $content
            ]
        )->set_title($title);
    }

    /**
     * Render cancel button when error
     *
     * @return string
     */
    private function cancel(): string {
        global $OUTPUT;
        $url = new \moodle_url(dashboard::URL);
        return $OUTPUT->single_button($url, get_string('cancel'), 'get');
    }
}