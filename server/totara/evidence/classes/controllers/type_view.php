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

use html_writer;
use moodle_url;
use totara_evidence\customfield_area\evidence;
use totara_evidence\entity\evidence_type_field;
use totara_evidence\models\evidence_type;
use totara_evidence\models\helpers\multilang_helper;
use totara_evidence\output\header;
use totara_evidence\output\table;
use totara_mvc\view;

class type_view extends type {

    public function action() {
        $id = $this->get_required_param('id', PARAM_INT);
        $type = evidence_type::load_by_id($id);

        $this->set_url(new moodle_url('/totara/evidence/type/view.php', ['id' => $type->get_id()]));

        if ($type->can_modify()) {
            $edit_button = [
                'url'   => evidence::get_url($type->get_id()),
                'label' => get_string('edit_this_type', 'totara_evidence')
            ];
        }

        $title = $type->get_display_name();
        $this->get_page()->navbar->add($title);

        $table = (new table($type->fields->all()))
            ->set_columns(
                [
                    'value' => function (evidence_type_field $field): string {
                        return html_writer::link(
                            new moodle_url('/totara/evidence/type/view_field.php', ['id' => $field->id]),
                            multilang_helper::parse_field_name_string($field->fullname)
                        );
                    },
                    'label' => get_string('custom_field_name', 'totara_evidence')
                ],
                [
                    'value' => function (evidence_type_field $field): string {
                        return get_string("customfieldtype{$field->datatype}", 'totara_customfield');
                    },
                    'label' => get_string('type', 'totara_evidence')
                ]
            )
            ->set_no_data_message(get_string('no_custom_fields_defined', 'totara_evidence'))
            ->set_id('table-evidence-type-fields');

        return (new view('totara_evidence/page', [
            'header'  => header::create($title, [
                'url'   => new moodle_url('/totara/evidence/type/index.php'),
                'label' => get_string('navigation_back_to_manage_types', 'totara_evidence')
            ], $edit_button ?? null),
            'content' => new view('totara_evidence/view_type', [
                'idnumber'    => $type->get_display_idnumber(),
                'description' => $type->get_display_description(),
                'fields'      => $table
            ])
        ]))->set_title($title);
    }

}
