<?php
/*
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
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

$sid = required_param('s', PARAM_INT);
$confirm = optional_param('confirm', false, PARAM_BOOL);
$backtoeventinfo = optional_param('backtoeventinfo', 0, PARAM_BOOL);
$managerid = optional_param('managerid', null, PARAM_INT);

$seminarevent = new \mod_facetoface\seminar_event($sid);
$seminar = $seminarevent->get_seminar();
$course = $DB->get_record('course', array('id' => $seminar->get_course()), '*', MUST_EXIST);
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

$url = new moodle_url('/mod/facetoface/reservations/delete.php', ['s' => $sid, 'confirm' => $confirm, 'managerid' => $managerid, 'sesskey' => sesskey()]);
if ($backtoeventinfo) {
    $url->param('backtoeventinfo', 1);
}
$PAGE->set_url($url);

require_login($course, false, $cm);
require_capability('mod/facetoface:managereservations', $context);

if ($confirm) {
    // Delete reservations to free up space in session.
    if (confirm_sesskey()) {
        try {
            \mod_facetoface\reservations::remove($seminarevent, $managerid, null, ($USER->id != $managerid));

            if ($backtoeventinfo) {
                $url = new moodle_url('/mod/facetoface/eventinfo.php', array('s' => $seminarevent->get_id()));
            } else {
                $url = new moodle_url('/mod/facetoface/view.php', ['f' => $seminarevent->get_facetoface()]);
            }
            \core\notification::success(get_string('managerreservationdeleted', 'mod_facetoface'));
            redirect($url);
        } catch (moodle_exception $e) {
            $url = new moodle_url('/mod/facetoface/reservations/manage.php', ['s' => $sid]);
            \core\notification::error(get_string('managerreservationdeletionfailed', 'mod_facetoface'));
            redirect($url);
        }
    }
}

$output = $OUTPUT;

$title = get_string('deletereservation', 'mod_facetoface');
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $output->header();
echo $output->heading($title, 2);

$confirmurl = new moodle_url('/mod/facetoface/reservations/delete.php', [
    's' => $sid, 'confirm' => true, 'managerid' => $managerid, 'sesskey' => sesskey(),
    'backtoeventinfo' => $backtoeventinfo
]);
$cancelurl  = new moodle_url('/mod/facetoface/reservations/manage.php', ['s' => $sid, 'backtoeventinfo' => $backtoeventinfo]);

$manager = \core_user::get_user($managerid);
$managername = fullname($manager);

echo $output->confirm(get_string('deletereservationconfirm', 'mod_facetoface', $managername), $confirmurl, $cancelurl);
echo $output->footer();
