<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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

use moodle_url;
use totara_evidence\controllers\helpers\customfield_form_helper;
use totara_evidence\entity\evidence_type_field;
use totara_evidence\models\evidence_type;
use totara_evidence\models\helpers\multilang_helper;
use totara_evidence\output\header;
use totara_mvc\view;

class type_view_field extends type {

    public function action() {
        $field = new evidence_type_field($this->get_required_param('id', PARAM_INT));
        $type  = evidence_type::load_by_id($field->typeid);

        $this->set_url(new moodle_url('/totara/evidence/type/view_field.php', ['id' => $field->id]));

        $field_type = get_string("customfieldtype{$field->datatype}", 'totara_customfield');
        $field_name = multilang_helper::parse_field_name_string($field->fullname);

        $title = get_string('title_type_field_name', 'totara_evidence', [
            'type' => $field_type,
            'name' => $field_name
        ]);

        $this->get_page()->navbar
            ->add($type->get_display_name(), new moodle_url('/totara/evidence/type/view.php', ['id' => $type->get_id()]))
            ->add($title);

        $back_url = new moodle_url('/totara/evidence/type/view.php', ['id' => $type->get_id()]);

        $form = customfield_form_helper::get_view_field_form($field);
        if ($form->is_cancelled()) {
            redirect($back_url);
        }

        return (new view('totara_evidence/page', [
            'header' => header::create($title, [
                'url'   => $back_url,
                'label' => get_string('navigation_back_to_x', 'totara_evidence', $type->get_display_name())
            ]),
            'content' => $form
        ]))->set_title($title);
    }

}
