<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @package auth_approved
 */

namespace auth_approved\form;

use totara_form\form\element\hidden;
use totara_form\form\element\select;
use totara_form\form\element\static_html;
use totara_form\form\element\textarea;

defined('MOODLE_INTERNAL') || die();

/**
 * Confirmation of request approval.
 */
final class approve extends \totara_form\form {
    public function definition() {
        global $CFG, $DB;

        $message = \html_writer::tag('h2', get_string('approvesure', 'auth_approved'));
        $this->model->add(new static_html('sure', '', $message));
        $this->model->add(new hidden('requestid', PARAM_INT));
        $this->model->add(new hidden('reportid', PARAM_INT));

        $curretdata = $this->model->get_current_data(null);
        $details = \auth_approved\util::render_request_details_view($curretdata['requestid']);
        $this->model->add(new static_html('requestdetails', '', $details));

        if ($CFG->tenantsenabled) {
            $tenants = $DB->get_records_menu('tenant', [], 'name ASC', 'id,name');
            foreach ($tenants as $tenantid => $tenantname) {
                $tenantcontext = \context_tenant::instance($tenantid);
                if (!has_capability('totara/tenant:usercreate', $tenantcontext)) {
                    unset($tenants[$tenantid]);
                    continue;
                }
                $tenants[$tenantid] = format_string($tenantname);
            }
            if ($tenants) {
                $tenants = [0 => get_string('no')] + $tenants;
                $this->model->add(new select('tenantid', get_string('tenant', 'totara_tenant'), $tenants));
            }
        }

        $this->model->add(new textarea('custommessage', get_string('custommessage', 'auth_approved'), PARAM_CLEANHTML));

        $this->model->add_action_buttons(true, get_string('approve', 'auth_approved'));
    }
}
