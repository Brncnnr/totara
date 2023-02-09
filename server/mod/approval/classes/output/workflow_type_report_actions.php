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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\output;

use moodle_url;
use reportbuilder;
use core\output\template;
use mod_approval\controllers\workflow\types\delete;
use mod_approval\controllers\workflow\types\toggle;
use mod_approval\controllers\workflow\types\manage;

/**
 * This output component is used for rendering a box of action icons within a column
 * of a report build. It allows us to use the AMD modules for the interactions at FRONT-END
 */
final class workflow_type_report_actions extends template {

    /**
     *
     * @return workflow_type_report_actions
     */
    public static function create(int $id, reportbuilder $report, \stdClass $extrafields): workflow_type_report_actions {
        global $OUTPUT;

        $hasusage = !($extrafields->inuse == 0);
        $is_active = ($extrafields->active == 0);

        /**
         * Legacy flex icon helper API in use, please use the flex icon template instead.
         * if we use
         * {{#flex_icon}}trash,{"classes":"ft-state-disabled"}{{/flex_icon}}
         */
        $flex_icon_delete_disabled = $OUTPUT->flex_icon('trash', ['classes' => 'ft-state-disabled']);
        $data = [
            'deleteurl' => null,
            'updateurl' => null,
            'toggleurl' => null,
            'delete_url_title' => null,
            'update_url_title' => null,
            'hasusage' => $hasusage,
            'is_active' => $is_active,
            'confirm_message' => get_string('delete_workflow_type_warning', 'mod_approval', format_string($extrafields->name)),
            'flex_icon_delete_disabled' => $flex_icon_delete_disabled,
            'sesskey' => sesskey()
        ];

        $widget = new workflow_type_report_actions($data);

        // Crafting the update url, if user has the capability
        $updateurl = new moodle_url(manage::URL, ['id' => $id]);
        if (!$report->embedded) {
            $updateurl->param('rid', $report->get_id());
        }
        $widget->set_update_url($updateurl);

        $toggleurl = new moodle_url(toggle::URL, ['id' => $id]);
        if (!$report->embedded) {
            $toggleurl->param('rid', $report->get_id());
        }
        $widget->set_toggle_url($toggleurl);

        if ($extrafields->inuse == 0) {
            // Crafting the delete icon if the user has any capability
            $delete_url = new moodle_url(delete::URL, ['id' => $id]);
            if (!$report->embedded) {
                $delete_url->param('rid', $report->get_id());
            }
            $widget->set_delete_url($delete_url);
        }

        return $widget;
    }

    /**
     * @param moodle_url|null $delete_url
     * @return void
     */
    public function set_delete_url(?moodle_url $delete_url): void {
        if (null === $delete_url) {
            $this->data['deleteurl'] = null;
            return;
        }
        $this->data['deleteurl'] = $delete_url->out(false);
    }

    /**
     * @param moodle_url|null $update_url
     * @return void
     */
    public function set_update_url(?moodle_url $update_url): void {
        if (null === $update_url) {
            $this->data['updateurl'] = null;
            return;
        }
        $this->data['updateurl'] = $update_url->out(false);
    }

    /**
     * @param moodle_url|null $toggle_url
     * @return void
     */
    public function set_toggle_url(?moodle_url $toggle_url): void {
        if (null === $toggle_url) {
            $this->data['toggleurl'] = null;
            return;
        }
        $this->data['toggleurl'] = $toggle_url->out(false);
    }

    /**
     * @param string $title
     * @return void
     */
    public function set_delete_url_title(string $title): void {
        $this->data['delete_url_title'] = $title;
    }

    /**
     * @param string $title
     * @return void
     */
    public function set_update_url_title(string $title): void {
        $this->data['update_url_title'] = $title;
    }
}