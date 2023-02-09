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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\controllers\form;

use core\notification;
use mod_approval\entity\form\form as form_entity;
use mod_approval\model\form\form;
use mod_approval\form\form_manage as form_form;
use mod_approval\controllers\workflow_controller;
use totara_mvc\view;

/**
 * Add/Update form
 */
class manage extends base {

    public const URL =  '/mod/approval/form/manage.php';

    /**
     * @inheritDoc
     */
    public function action() {
        $this->can_manage_workflows();
        $id = $this->get_optional_param('id', 0, PARAM_INT);
        $rid = $this->get_optional_param('rid', 0, PARAM_INT);
        if ($id == 0) {
            $form = new form_entity();
        } else {
            $form = form::load_by_id($id);
        }
        $mform = new form_form(null, ['form' => $form, 'rid' => $rid, 'post']);
        if ($mform->is_cancelled()) {
            redirect($this->get_report_url());
        }
        if ($data = $mform->get_data()) {
            $this->save($data);
            redirect(
                $this->get_report_url(),
                get_string('success:update_form', 'mod_approval'),
                null,
                notification::SUCCESS
            );
        }
        $header = ($id) ? 'update' : 'add';
        return $this->view($mform->render(), [], get_string($header.'_form_header', 'mod_approval'));
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
            'mod_approval/form_form',
            [
                'title' => $title,
                'mform' => $content
            ]
        )->set_title($title);
    }

    /**
     * Create/Update form entity
     *
     * @param \stdClass $data
     */
    private function save(\stdClass $data): void {
        if ($data->id) {
            $entity = new form_entity($data->id);
            $entity->title = $data->title;
            $entity->save();
            $form = form::load_by_entity($entity);
        } else {
            $form = form::create($data->plugin_name, $data->title);
        }
    }
}
