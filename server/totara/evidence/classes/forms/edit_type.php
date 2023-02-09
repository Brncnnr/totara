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

namespace totara_evidence\forms;

use totara_evidence\entity\evidence_type as evidence_type_entity;
use totara_evidence\models\evidence_type as evidence_type_model;
use totara_form\form;
use totara_form\form\element\action_button;
use totara_form\form\element\checkbox;
use totara_form\form\element\editor;
use totara_form\form\element\hidden;
use totara_form\form\element\text;
use totara_form\form\group\buttons;
use totara_mvc\viewable;

class edit_type extends form implements viewable {

    protected function definition(): void {
        $name = $this->model->add(
            new text('name', get_string('type_name', 'totara_evidence'), PARAM_TEXT)
        );
        $name->set_attribute('maxlength', 1024);
        $name->set_attribute('required', true);

        $this->model->add(
            new text('idnumber', get_string('type_idnumber', 'totara_evidence'), PARAM_TEXT)
        )->set_attribute('maxlength', 100);

        $this->model->add(
            new editor('description', get_string('type_description', 'totara_evidence'))
        );

        $this->model->add(new hidden('id', PARAM_INT));

        $buttongroup = $this->model->add(new buttons('actionbuttonsgroup'), -1);
        $buttongroup->add(
            new action_button(
                'submit_continue',
                $this->model->get_current_data('id') ?
                    get_string('savechanges') : get_string('save_and_continue', 'totara_evidence'),
                action_button::TYPE_SUBMIT
            )
        );
        $buttongroup->add(new action_button('cancelbutton', get_string('cancel'), action_button::TYPE_CANCEL));
    }

    protected function validation(array $data, array $files): array {
        $errors = [];
        $data = (object) $data;

        if (empty(trim($data->name))) {
            $errors['name'] = get_string('error_message_empty_name', 'totara_evidence');
        }
        if ($data->idnumber !== '' && totara_idnumber_exists(evidence_type_entity::TABLE, $data->idnumber, $data->id)) {
            $errors['idnumber'] = get_string('idnumberexists', 'totara_core');
        }

        return $errors;
    }
}
