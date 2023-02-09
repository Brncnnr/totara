<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

namespace totara_tenant\form;

use totara_tenant\local\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Update existing tenant tenant
 */
final class tenant_update extends \totara_form\form {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        $id = new \totara_form\form\element\hidden('id', PARAM_INT);
        $this->model->add($id);

        $name = new \totara_form\form\element\text('name', get_string('name'), PARAM_TEXT);
        $name->set_attributes(array('required'=> 1, 'maxlength' => 1333, 'size' => 100));
        $this->model->add($name);

        $idnumber = new \totara_form\form\element\text('idnumber', get_string('tenantidnumber', 'totara_tenant'), PARAM_RAW);
        $idnumber->set_attributes(array('required'=> 1, 'maxlength' => 255));
        $idnumber->add_help_button('tenantidnumber', 'totara_tenant');
        $this->model->add($idnumber);

        $description = new \totara_form\form\element\editor('description', get_string('description'));
        $description->set_attributes(array('rows'=> 4));
        $this->model->add($description);

        $suspended = new \totara_form\form\element\checkbox('suspended', get_string('suspended', 'totara_tenant'));
        $this->model->add($suspended);

        $categoryname = new \totara_form\form\element\text('categoryname', get_string('categoryname', 'totara_tenant'), PARAM_TEXT);
        $categoryname->set_attributes(array('required'=> 1, 'maxlength' => 255, 'size' => 100));
        $this->model->add($categoryname);

        $cohortname = new \totara_form\form\element\text('cohortname', get_string('cohortname', 'totara_tenant'), PARAM_TEXT);
        $cohortname->set_attributes(array('required'=> 1, 'maxlength' => 255, 'size' => 100));
        $this->model->add($cohortname);

        $this->model->add_action_buttons(true, get_string('tenantupdate', 'totara_tenant'));
    }

    /**
     * Validation - makes sure the idnumber and name are unique.
     *
     * @param array $data
     * @param array $files
     * @return array list of errors
     */
    public function validation(array $data, array $files) {
        $errors = parent::validation($data, $files);

        $namevalid = util::is_valid_name($data['name'], $data['id']);
        if ($namevalid !== true) {
            $errors['name'] = $namevalid;
        }
        $idnumbervalid = util::is_valid_idnumber($data['idnumber'], $data['id']);
        if ($idnumbervalid !== true) {
            $errors['idnumber'] = $idnumbervalid;
        }
        if (trim($data['categoryname']) === '') {
            $errors['categoryname'] = get_string('required');
        }
        if (trim($data['cohortname']) === '') {
            $errors['cohortname'] = get_string('required');
        }
        return $errors;
    }
}
