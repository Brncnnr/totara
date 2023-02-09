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
use core\orm\query\builder;
use core\entity\user as user_entity;
use mod_approval\exception\access_denied_exception;
use mod_approval\form\assignment_overrides_upload_confirm;
use mod_approval\model\assignment\helper\csv_upload as csv_import_helper;
use mod_approval\model\workflow\workflow;
use mod_approval\controllers\workflow\edit as workflow_edit;

/**
 * Class assignment overrides confirm csv uploaded data
 */
class overrides_confirm extends base {

    /**
     * @inheritDoc
     */
    public function action() {

        $workflow_id = $this->get_required_param('workflow_id', PARAM_INT);
        $process_id = $this->get_required_param('process_id', PARAM_INT);

        $workflow = workflow::load_by_id((int)$workflow_id);
        if (!$workflow->get_interactor(user_entity::logged_in()->id)->can_upload_approver_overrides()) {
            throw access_denied_exception::workflow('Cannot upload assignment overrides for the given workflow');
        }

        $title = get_string('upload_assignment_overrides', 'mod_approval', (object)['name' => $workflow->name]);
        $params = ['workflow_id' => $workflow_id, 'process_id' => $process_id];
        $base_url = self::get_url($params);

        $csv = csv_import_helper::instance($workflow_id, $process_id, $base_url);
        $data = $csv->get_all_user_data();
        if (empty($data['raw_data'])) {
            $csv->clean();
            $url = overrides::get_url(['workflow_id' => $workflow_id]);
            $this->set_url($base_url);
            return self::create_view(
                'mod_approval/assignment_override_form_confirm',
                [
                    'title' => $title,
                    'mform' => '',
                    'table' => get_string('upload_assignment_overrides_no_data', 'mod_approval', (object)['url' => $url->out(true)]),
                ]
            )->set_title(get_string('upload_assignment_overrides', 'mod_approval'));
        }

        $mform = new assignment_overrides_upload_confirm(null, $params);
        if ($mform->is_cancelled()) {
            $csv->clean();
            redirect(overrides::get_url(['workflow_id' => $workflow_id]));
        }
        if ($mform->is_submitted()) {
            $transaction = builder::get_db()->start_delegated_transaction();
            try {
                $csv->process_data();
                $transaction->allow_commit();
            } catch (Exception $exception) {
                $transaction->rollback();
            }
            redirect(workflow_edit::get_url(['workflow_id' => $workflow_id]));
        }

        $this->set_url($base_url);
        return self::create_view(
            'mod_approval/assignment_override_form_confirm',
            [
                'title' => $title,
                'mform' => $mform->render(),
                'table' => $csv->render_data($base_url)
            ]
        )->set_title($title);
    }

    /**
     * @inheritDoc
     */
    public static function get_base_url(): string {
        return '/mod/approval/assignment/overrides_confirm.php';
    }

    /**
     * @inheritDoc
     */
    public function setup_context(): context {
        $workflow_id = $this->get_required_param('workflow_id', PARAM_INT);
        return workflow::load_by_id($workflow_id)->get_context();
    }
}