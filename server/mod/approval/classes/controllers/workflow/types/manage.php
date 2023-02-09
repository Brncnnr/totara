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

namespace mod_approval\controllers\workflow\types;

use core\notification;
use mod_approval\entity\workflow\workflow_type as workflow_type_entity;
use mod_approval\model\workflow\workflow_type;
use mod_approval\form\workflow_type_manage as workflow_type_form;
use mod_approval\controllers\workflow_controller;
use totara_mvc\view;

/**
 * Add/Update workflow type
 */
class manage extends base {

    public const URL =  '/mod/approval/workflow/types/manage.php';

    /**
     * @inheritDoc
     */
    public function action() {
        $this->can_manage_workflows();
        $id = $this->get_optional_param('id', 0, PARAM_INT);
        $rid = $this->get_optional_param('rid', 0, PARAM_INT);
        if ($id == 0) {
            $workflow_type = new workflow_type_entity();
        } else {
            $workflow_type = workflow_type::load_by_id($id);
        }
        $mform = new workflow_type_form(null, ['workflow_type' => $workflow_type, 'rid' => $rid, 'post']);
        if ($mform->is_cancelled()) {
            redirect($this->get_report_url());
        }
        if ($data = $mform->get_data()) {
            $this->save($data);
            redirect(
                $this->get_report_url(),
                get_string('success:update_workflow_type', 'mod_approval'),
                null,
                notification::SUCCESS
            );
        }
        $header = ($id) ? 'update' : 'add';
        return $this->view($mform->render(), [], get_string($header.'_workflow_type_header', 'mod_approval'));
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
        return workflow_controller::create_view(
            'mod_approval/workflow_type_form',
            [
                'title' => $title,
                'mform' => $content
            ]
        )->set_title($title);
    }

    /**
     * Create/Update workflow type data
     *
     * @param \stdClass $data
     */
    private function save(\stdClass $data): void {
        global $TEXTAREA_OPTIONS;

        if ($data->id) {
            $entity = new workflow_type_entity($data->id);
            $entity->name = $data->name;
            $entity->save();
            $workflow_type = workflow_type::load_by_entity($entity);
        } else {
            $workflow_type = workflow_type::create($data->name, '');
        }

        // Update description.
        $data = file_postupdate_standard_editor(
            $data,
            'description',
            $TEXTAREA_OPTIONS,
            $TEXTAREA_OPTIONS['context'],
            'mod_approval',
            'workflow_type',
            $workflow_type->id
        );
        $entity = new workflow_type_entity($workflow_type->id);
        $entity->description = $data->description;
        $entity->save();
    }
}
