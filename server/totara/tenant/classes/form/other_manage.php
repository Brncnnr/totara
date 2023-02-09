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

defined('MOODLE_INTERNAL') || die();

/**
 * manage non-member tenant participation and migration to member.
 */
final class other_manage extends \totara_form\form {
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        global $DB;
        $id = new \totara_form\form\element\hidden('id', PARAM_INT);
        $this->model->add($id);

        $alltenants = $DB->get_records_menu('tenant', [], "name ASC", 'id, name');
        $alltenants = array_map('format_string', $alltenants);

        $options = [0 => get_string('no')] + $alltenants;
        $tenant = new \totara_form\form\element\select('tenantid', get_string('tenantmember', 'totara_tenant'), $options);
        $this->model->add($tenant);

        $options = $alltenants;
        $tenantids = new \totara_form\form\element\checkboxes('tenantids', get_string('participant', 'totara_tenant'), $options);
        $this->model->add($tenantids);
        $this->model->add_clientaction(new \totara_form\form\clientaction\hidden_if($tenantids))->not_equals($tenant, 0);

        $warningstr = get_string('migrationtomemberwarning', 'totara_tenant');
        $warning = new \totara_form\form\element\static_html('warning', '', $warningstr);
        $this->model->add($warning);
        $this->model->add_clientaction(new \totara_form\form\clientaction\hidden_if($warning))->is_equal($tenant, 0);

        $this->model->add_action_buttons();
    }
}