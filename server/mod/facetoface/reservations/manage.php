<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author  Maria Torres <maria.torres@totaralearning.com>
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

$sid = required_param('s', PARAM_INT);
$backtoeventinfo = optional_param('backtoeventinfo', 0, PARAM_BOOL);

$seminarevent = new \mod_facetoface\seminar_event($sid);
$seminar = $seminarevent->get_seminar();
$course = $DB->get_record('course', array('id' => $seminar->get_course()), '*', MUST_EXIST);
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/facetoface/reservations/manage.php', ['s' => $seminarevent->get_id()]);
if ($backtoeventinfo) {
    $url->param('backtoeventinfo', 1);
}
$PAGE->set_url($url);

require_login($course, false, $cm);
require_capability('mod/facetoface:managereservations', $context);

$reservations = \mod_facetoface\reservations::get($seminarevent);
$allsessionsurl = new moodle_url('/mod/facetoface/view.php', array('id' => $cm->id));
if ($backtoeventinfo) {
    $gobackurl = new moodle_url('/mod/facetoface/eventinfo.php', array('s' => $seminarevent->get_id()));
} else {
    $gobackurl = null;
}

/** @var mod_facetoface_renderer $output */
$output = $PAGE->get_renderer('mod_facetoface');
$output->setcontext($context);

$title = get_string('managereservations', 'mod_facetoface');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $output->header();
echo $output->heading($title, 2);
echo $output->heading(format_string($seminar->get_name()), 3);
echo $output->print_reservation_management_table($reservations, true, $backtoeventinfo);
echo $output->render_action_bar_on_reservation_page($gobackurl, $allsessionsurl);
echo $output->footer();
