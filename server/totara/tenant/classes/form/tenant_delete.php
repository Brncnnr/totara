<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
use \totara_form\form\element\action_button;

defined('MOODLE_INTERNAL') || die();

/**
 * Confirm tenant deletion action.
 */
final class tenant_delete extends \totara_form\form {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        global $DB;
        $tenant = $this->get_parameters()['tenant'];

        $id = new \totara_form\form\element\hidden('id', PARAM_INT);
        $this->model->add($id);

        $a = new \stdClass();
        $a->name = format_string($tenant->name);
        $message = get_string('tenantdeleteconfirm', 'totara_tenant', $a);
        $confirmation = new \totara_form\form\element\static_html('confirmation', '', $message);
        $this->model->add($confirmation);

        $name = new \totara_form\form\element\static_html('tenantidnumber', get_string('tenantidnumber', 'totara_tenant'), s($tenant->idnumber));
        $this->model->add($name);

        $count = $DB->count_records('user', ['tenantid' => $tenant->id, 'deleted' => 0]);
        $membercount = new \totara_form\form\element\static_html('membercount', get_string('membercount', 'totara_tenant'), $count);
        $this->model->add($membercount);

        $options = [
            util::DELETE_TENANT_USER_SUSPEND => get_string('tenantdeleteusersuspend', 'totara_tenant'),
            util::DELETE_TENANT_USER_DELETE => get_string('tenantdeleteuserdetele', 'totara_tenant'),
            util::DELETE_TENANT_USER_MIGRATE => get_string('tenantdeleteusermigrate', 'totara_tenant'),
        ];
        $useraction = new \totara_form\form\element\radios('useraction', get_string('tenantdeleteuseraction', 'totara_tenant'), $options);
        // Do not set field as required and rely on server-side validation instead for visual reasons.
        $this->model->add($useraction);

        $buttongroup = $this->model->add(new \totara_form\form\group\buttons('actionbuttonsgroup'), -1);
        $submitbutton = new action_button('submitbutton', get_string('tenantdelete', 'totara_tenant'),
            \totara_form\form\element\action_button::TYPE_SUBMIT);
        $submitbutton->set_primarybutton(true);
        $buttongroup->add($submitbutton);
        $buttongroup->add(new action_button('cancelbutton', get_string('cancel'), action_button::TYPE_CANCEL));
    }

    public function validation(array $data, array $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['useraction'])) {
            $errors['useraction'] = get_string('required');
        }

        return $errors;
    }
}
