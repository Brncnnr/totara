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

use \totara_tenant\local\util;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);

require_login(null, false);
require_capability('totara/tenant:config', context_system::instance());
if (empty($CFG->tenantsenabled)) {
    redirect(new moodle_url('/'));
}

admin_externalpage_setup('tenantsmanage', '', null, new moodle_url('/totara/tenant/tenant_delete.php', ['id' => $id]), ['pagelayout' => 'noblocks']);

$returnurl = new moodle_url('/totara/tenant/index.php');
$tenant = \core\record\tenant::fetch($id);

$confirmform = new \totara_tenant\form\tenant_delete(['id' => $tenant->id], ['tenant' => $tenant]);

if ($confirmform->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $confirmform->get_data()) {
    $success = util::delete_tenant($data->id, $data->useraction);
    if ($success) {
        redirect($returnurl);
    }
    $message = get_string('error');
    redirect($returnurl, $message, null, \core\output\notification::NOTIFY_ERROR);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('tenantdelete', 'totara_tenant'));

// Hack alert: create fake confirmation similar to \core_renderer::confirm()
$output = $OUTPUT->box_start('generalbox modal modal-dialog modal-in-page show', 'notice');
$output .= $OUTPUT->box_start('modal-content', 'modal-content');
$output .= $OUTPUT->box_start('modal-header', 'modal-header');
$output .= html_writer::tag('h4', get_string('confirm'));
$output .= $OUTPUT->box_end();
$output .= $OUTPUT->box_start('modal-body', 'modal-body');
$output .= $confirmform->render();
$output .= $OUTPUT->box_end();
$output .= $OUTPUT->box_end();
$output .= $OUTPUT->box_end();
echo $output;

echo $OUTPUT->footer();
