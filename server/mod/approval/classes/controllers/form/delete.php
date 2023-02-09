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

namespace mod_approval\controllers\form;

use core\notification;
use mod_approval\controllers\workflow_controller;
use mod_approval\model\form\form;
use single_button;

/**
 * Delete workflow form controller.
 */
class delete extends base {

    public const URL = '/mod/approval/form/delete.php';

    /**
     * @inheritDoc
     */
    public function action() {

        global $OUTPUT;

        $this->can_manage_workflows();

        $form = form::load_by_id($this->get_id_param());
        if ($form->get_workflows()->count() > 0) {
            redirect(
                $this->get_report_url(),
                get_string('error:form_inuse', 'mod_approval'),
                null,
                notification::ERROR
            );
        }

        if ($this->get_optional_param('confirm', false, PARAM_BOOL)) {
            require_sesskey();
            $form->delete(true);
            redirect(
                $this->get_report_url(),
                get_string('success:delete_form', 'mod_approval'),
                null,
                notification::SUCCESS
            );
        }

        $base_params = ['id' => $form->id, 'sesskey' => sesskey(), 'confirm' => 1];
        $rid = $this->get_optional_param('rid', null, PARAM_INT);
        if ($rid) {
            $base_params['rid'] = $rid;
        }
        $base_url = self::get_url($base_params);
        /** @var single_button $confirm_button */
        $confirm_button = new single_button($base_url, get_string('delete'), 'post', true);
        /** @var \core_renderer $OUTPUT */
        $confirm_modal = $OUTPUT->confirm(
            get_string('delete_form_warning', 'mod_approval', format_string($form->title)),
            $confirm_button,
            $this->get_report_url(),
            get_string('delete_form', 'mod_approval')
        );

        $params = ['id' => $form->id];
        if ($rid) {
            $params['rid'] = $rid;
        }
        $this->set_url(self::get_url($params));
        return workflow_controller::create_view(
            'mod_approval/form_delete_confirm',
            [
                'confirm_modal' => $confirm_modal,
            ]
        )->set_title(get_string('delete_form', 'mod_approval'));
    }
}