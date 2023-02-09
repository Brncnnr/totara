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

class type_create extends type {

    protected $url = '/totara/evidence/type/create.php';

    public function action() {
        $form = new forms\edit_type([
            'descriptionfilearea' => new file_area(
                null,
                'totara_evidence',
                models\evidence_type::DESCRIPTION_FILEAREA
            ),
        ]);

        if ($form_data = $form->get_data()) {
            $type = models\evidence_type::create(
                $form_data->name,
                $form_data->idnumber,
                $form_data->description,
                $form_data->descriptionformat ?? null,
                models\evidence_type::LOCATION_EVIDENCE_BANK    // The location nowadays is only used to distinguish between system (RoL) and manual
            );

            $form->update_file_area('description', $this->context, $type->get_id());

            notification::add(
                get_string('notification_type_created', 'totara_evidence', $type->get_display_name()),
                notification::SUCCESS
            );
            redirect(customfield_area\evidence::get_url($type->get_id()));
        } else if ($form->is_cancelled()) {
            redirect(new moodle_url('/totara/evidence/type/index.php'));
        }

        $view = new view('totara_evidence/page', [
            'header'  => header::create_for_type(null, true, 'general'),
            'content' => $form
        ]);
        $view->set_title(get_string('add_an_evidence_type', 'totara_evidence'));
        return $view;
    }

}
