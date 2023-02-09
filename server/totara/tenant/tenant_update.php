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

use totara_tenant\local\util;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$id = required_param('id', PARAM_INT);

require_login(null, false);
require_capability('totara/tenant:config', context_system::instance());
if (empty($CFG->tenantsenabled)) {
    redirect(new moodle_url('/'));
}

admin_externalpage_setup('tenantsmanage', '', null, new moodle_url('/totara/tenant/tenant_update.php', ['id' => $id]));

$returnurl = new moodle_url('/totara/tenant/index.php');
$tenant = $DB->get_record('tenant', ['id' => $id], '*', MUST_EXIST);
$category = $DB->get_record('course_categories', ['id' => $tenant->categoryid], '*', MUST_EXIST);
$cohort = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);
$tenant->categoryname = $category->name;
$tenant->cohortname = $cohort->name;

$form = new totara_tenant\form\tenant_update($tenant);
if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    $tenant = util::update_tenant((array)$data);
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('tenantupdate', 'totara_tenant'));
echo $form->render();
echo $OUTPUT->footer();
