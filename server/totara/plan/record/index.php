<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara
 * @subpackage plan
 */

use totara_core\advanced_feature;
use totara_core\totara\menu\myteam;
use totara_plan\totara\menu\recordoflearning;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/totara/plan/lib.php');

require_login();

if (advanced_feature::is_disabled('recordoflearning')) {
    print_error('error:recordoflearningdisabled', 'totara_plan');
}

$userid = optional_param('userid', $USER->id, PARAM_INT);
$rolstatus = optional_param('status', 'all', PARAM_ALPHA);

$params = array('userid' => $userid, 'status' => $rolstatus);

if ($visible = dp_get_rol_tabs_visible($userid)) {
    $showtab = $visible[0];
    if ($showtab !== 'evidence') {
        redirect(new moodle_url("/totara/plan/record/{$showtab}.php", $params));
    } else {
        redirect(new moodle_url('/totara/plan/record/evidence/index.php', $params));
    }
}

// No tabs are visible so show a message
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/totara/plan/record/index.php', ['userid' => $userid]));
$PAGE->set_totara_menu_selected(recordoflearning::class);

if ($USER->id == $userid) {
    $heading = get_string('recordoflearning', 'totara_core');
    $role = 'learner';
} else {
    $heading = get_string('recordoflearningforname', 'totara_core', fullname($DB->get_record('user', ['id' => $userid]), true));
    $role = 'manager';

    if (advanced_feature::is_enabled('myteam')) {
        $PAGE->set_totara_menu_selected(myteam::class);
        $PAGE->navbar->add(get_string('team', 'totara_core'), new moodle_url('/my/teammembers.php'));
    }
}

$PAGE->set_title($heading);
$PAGE->navbar->add($heading);
dp_display_plans_menu($userid, 0, $role, 'index', 'none', false);

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);
echo html_writer::tag('i', get_string('norecords', 'totara_plan'));
echo $OUTPUT->footer();
