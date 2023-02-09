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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

use \core\notification;
use mod_facetoface\facilitator;
use mod_facetoface\facilitator_user;
use mod_facetoface\facilitator_type;
use mod_facetoface\form\facilitator_edit;
use mod_facetoface\output\seminar_dialog_selected;

$id = optional_param('id', 0, PARAM_INT);
$backurl = optional_param('b', '', PARAM_LOCALURL);

$params = ['id' => $id];
$baseurl = new moodle_url('/mod/facetoface/facilitator/edit.php', $params);
// Check permissions.
if (is_siteadmin()) {
    admin_externalpage_setup('modfacetofacefacilitators', '', null, $baseurl);
} else {
    $context = context_system::instance();
    $PAGE->set_pagelayout('standard');
    $PAGE->set_context($context);
    $PAGE->set_url($baseurl);
    require_login(0, false);
    require_capability('mod/facetoface:managesitewidefacilitators', $context);
}

$facilitator = new facilitator($id);
if (!empty($backurl)) {
    $returnurl = new moodle_url($backurl);
} else {
    $returnurl = new moodle_url('/mod/facetoface/facilitator/manage.php');
}

if ($facilitator->get_custom()) {
    redirect($returnurl, get_string('error:incorrectfacilitatorid', 'mod_facetoface'), null, notification::ERROR);
}

local_js(array(
    TOTARA_JS_DIALOG,
    TOTARA_JS_TREEVIEW
));
$PAGE->requires->strings_for_js(array('choosefacilitator', 'error:facilitatornotselected', 'selected'), 'mod_facetoface');
$jsmodule = array(
    'name' => 'totara_seminar_facilitator',
    'fullpath' => '/mod/facetoface/js/facilitator_user.js',
    'requires' => ['json', 'totara_core']);
// Return markup for 'Currently selected' info in a dialog.
$selected = $PAGE->get_renderer('mod_facetoface')->render(new seminar_dialog_selected([]));

// Markup for notification warning user it will updating all upcoming events.
$renderable = (new \core\output\notification(get_string('facilitatoruserchanged', 'mod_facetoface'), notification::WARNING))->set_show_closebutton(true);
$warningblock = \html_writer::span($PAGE->get_renderer('core')->render($renderable), '', ['id' => uniqid()]);
$errorblock = \html_writer::span(get_string('error:facilitatornotselected', 'mod_facetoface'), 'error', ['id' => 'id_error_userid']);
$errorblock .= \html_writer::empty_tag('br', ['class' => 'error', 'id' => 'id_error_break_userid']);

$facilitatortype = ['internal' => facilitator_type::INTERNAL, 'external' => facilitator_type::EXTERNAL];
$args = [
    'dialog_display_facilitator' => $selected,
    'userid' => $facilitator->get_userid(),
    'warningblock' => $warningblock,
    'errorblock' => $errorblock,
    'facilitatortype' => $facilitatortype,
];
$PAGE->requires->js_init_call('M.totara_seminar_facilitator.init', [$args], false, $jsmodule);

$facilitator = new facilitator_user($facilitator);
$customdata = ['facilitator' => $facilitator, 'adhoc' => false, 'backurl' => $returnurl];
$mform = new facilitator_edit(null, $customdata, 'post', '', ['class' => 'manage_facilitator dialog-nobind'], true, null, 'mform_modal');

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $mform->get_data()) {
    $facilitator = $mform->save($data);
    $message = $id ? get_string('facilitatorupdatesuccess', 'mod_facetoface') : get_string('facilitatorcreatesuccess', 'mod_facetoface');
    redirect($returnurl, $message, null, notification::SUCCESS);
}

$pageheading = $id ? get_string('editfacilitator', 'mod_facetoface') : get_string('addfacilitator', 'mod_facetoface');
$PAGE->set_title($pageheading);
echo $OUTPUT->header();
echo $OUTPUT->heading($pageheading);

if ((bool)$facilitator->get_userid() && !facilitator_user::is_userid_active($facilitator->get_userid())) {
    notification::warning(get_string('facilitatoruserwarning', 'mod_facetoface', $facilitator->get_fullname()));
}

$mform->display();
echo $OUTPUT->footer();
