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
use mod_approval\event\form_version_updated;
use mod_approval\form_schema\form_schema;
use mod_approval\model\form\approvalform_base;
use mod_approval\model\form\form;
use mod_approval\form\form_refresh;
use mod_approval\controllers\workflow_controller;
use mod_approval\model\form\form_version;
use totara_mvc\view;

/**
 * Refresh form schema from approvalform plugin
 */
class refresh extends base {

    public const URL =  '/mod/approval/form/refresh.php';

    /**
     * @inheritDoc
     */
    public function action() {
        $this->can_manage_workflows();

        $id = $this->get_required_param('id', PARAM_INT);
        $rid = $this->get_optional_param('rid', 0, PARAM_INT);
        $form = form::load_by_id($id);
        $form_version = $form->get_active_version();
        $plugin = approvalform_base::from_plugin_name($form->plugin_name);

        $plugin_form_version = $plugin->get_form_version();
        if ($plugin_form_version <= $form_version->version) {
            redirect(
                $this->get_report_url(),
                get_string('info:up_to_date_with_plugin', 'mod_approval'),
                null,
                notification::INFO
            );
        }

        $mform = new form_refresh(null, ['form' => $form, 'form_version' => $form_version->version, 'plugin_version' => $plugin_form_version, 'rid' => $rid, 'post']);
        if ($mform->is_cancelled()) {
            redirect($this->get_report_url());
        }
        if ($data = $mform->get_data()) {
            $this->reload_schema($form_version, $plugin);
            redirect(
                $this->get_report_url(),
                get_string('success:update_form', 'mod_approval'),
                null,
                notification::SUCCESS
            );
        }

        $inuse_warning = false;
        $added_keys = [];
        $missing_keys = [];
        if ($form->get_workflows()->count() > 0) {
            foreach ($form->get_workflows() as $workflow) {
                if ($workflow->is_any_active() || $workflow->is_any_archived()) {
                    $inuse_warning = true;
                    break;
                }
            }
            if ($inuse_warning) {
                $old_schema = form_schema::from_json($form_version->json_schema);
                $old_keys = $old_schema->get_fields();
                $new_keys = $plugin->get_form_schema()->get_fields();
                $added_keys = array_diff_key($new_keys, $old_keys);
                $missing_keys = array_diff_key($old_keys, $new_keys);
                if (empty($added_keys) && empty($missing_keys)) {
                    $inuse_warning = false;
                }
            }
        }

        $content = \html_writer::tag('p', get_string('refresh_form_schema_confirm', 'mod_approval', $form->title));
        $content .= $mform->render();

        return $this->view($content, [], get_string('refresh_form_header', 'mod_approval'), $inuse_warning);
    }

    /**
     * Return totara_mvc/view
     *
     * @param string $content
     * @param array $params - moodle_url params
     * @param string $title
     * @param bool $inuse Whether the form is in use
     * @return view
     */
    private function view(string $content = '', array $params = [], string $title = '', bool $inuse = false): view {
        $this->set_url(self::get_url($params));
        return workflow_controller::create_view(
            'mod_approval/form_refresh',
            [
                'title' => $title,
                'mform' => $content,
                'inuse' => $inuse,
            ]
        )->set_title($title);
    }

    /**
     * Reload form_version schema from approvalform plugin
     *
     * @param form_version $form_version
     * @param approvalform_base $plugin
     */
    private function reload_schema(form_version $form_version, approvalform_base $plugin): void {
        $new_schema = $plugin->get_form_schema();
        $new_version = $plugin->get_form_version();
        $form_version = $form_version->set_schema($new_schema, $new_version);
        form_version_updated::execute($form_version);
    }
}
