<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\controllers;

use core\notification;
use moodle_url;
use totara_evidence\customfield_area;
use totara_evidence\forms;
use totara_evidence\models;
use totara_evidence\output\header;
use totara_form\file_area;
use totara_mvc\view;

class type_edit extends type {

    public function action() {
        $id = $this->get_optional_param('id', null, PARAM_INT);
        $type = models\evidence_type::load_by_id($id);
        if (!$type->can_modify()) {
            print_error(
                'error_notification_edit_type',
                'totara_evidence',
                new moodle_url('/totara/evidence/type/index.php'),
                $type->get_display_name()
            );
        }

        $this->get_page()->navbar->add(get_string('edit_x_type', 'totara_evidence', $type->get_display_name()));

        $form = new forms\edit_type([
            'id' => $type->id,
            'name' => $type->name,
            'idnumber' => $type->idnumber,
            'description' => $type->description,
            'location' => $type->location,
            'descriptionformat' => $type->descriptionformat,
            'descriptionfilearea' => new file_area(
                $this->context,
                'totara_evidence',
                models\evidence_type::DESCRIPTION_FILEAREA,
                $type->id
            ),
        ]);

        if ($form_data = $form->get_data()) {
            $type->update(
                $form_data->name,
                $form_data->idnumber,
                $form_data->description,
                $form_data->descriptionformat ?? null,
                $form_data->location ?? null
            );

            $form->update_file_area('description', $this->context, $type->get_id());

            notification::add(
                get_string('notification_type_updated', 'totara_evidence', $type->get_display_name()),
                notification::SUCCESS
            );
            redirect(customfield_area\evidence::get_url($type->get_id()));
        } else if ($form->is_cancelled()) {
            redirect(new moodle_url('/totara/evidence/type/index.php'));
        }

        $this->set_url(new moodle_url('/totara/evidence/type/edit.php', ['id' => $type->get_id()]));

        $view = new view('totara_evidence/page', [
            'header'  => header::create_for_type($type, true, 'general'),
            'content' => $form
        ]);
        $view->set_title(get_string('edit_x_type', 'totara_evidence', $type->get_display_name()));
        return $view;
    }

}
