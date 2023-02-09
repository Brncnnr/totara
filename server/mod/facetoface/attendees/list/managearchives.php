<?php
/**
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

use core\output\notification;
use mod_facetoface\output\manage_archives;
use mod_facetoface\output\session_time;
use mod_facetoface\seminar_event;
use mod_facetoface\signup\state\state;
use mod_facetoface\signup_helper;

require_once(__DIR__ . '/../../../../config.php');

define('MAX_USERS_PER_PAGE', 1000);

$s = required_param('s', PARAM_INT); // Facetoface session ID.
$baseurl = new moodle_url('/mod/facetoface/attendees/list/managearchives.php', array('s' => $s));
$backurl = new moodle_url('/mod/facetoface/attendees/view.php', ['s' => $s]);

$seminarevent = new seminar_event($s);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context =  context_module::instance($cm->id);

require_login($seminar->get_course(), false, $cm);
require_capability('mod/facetoface:managearchivedattendees', $context);

$pagetitle = get_string('managearchivedattendees', 'mod_facetoface');
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($seminar->get_name() . ': ' . $pagetitle);

if ($formdata = data_submitted()) {
    require_sesskey();
    $restored = 0;
    if (!empty($formdata->signupids)) {
        $restored = signup_helper::unarchive_signups($s, $formdata->signupids);
    }
    if ($restored) {
        \core\notification::success(get_string('archive_success', 'mod_facetoface', $restored));
        redirect($backurl);
    } else {
        redirect($baseurl);
    }
}

$records = signup_helper::get_archived_signups($s);

/** @var core_renderer $OUTPUT */

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

if (!empty($records)) {
    $warning = new notification(get_string('archive_warning', 'mod_facetoface'), notification::NOTIFY_WARNING);
    $table = new html_table();
    $table->attributes['class'] = 'mod_facetoface__archive generaltable';
    $table->head = [
        html_writer::empty_tag('input', ['type' => 'checkbox', 'class' => 'mod_facetoface__archive__select-all', 'aria-label' => get_string('takeattendance_tickall', 'mod_facetoface')]),
        get_string('attendancename', 'mod_facetoface'),
        get_string('attendancetimeofsignup', 'mod_facetoface'),
        get_string('attendancestatus', 'mod_facetoface')
    ];
    $table->data = array_map(function ($record) use ($seminar) {
        $user = core_user::get_user($record->userid);
        $fullname = fullname($user);
        $url = user_get_profile_url($user->id, $seminar->get_course());
        if ($url) {
            $fullnamelink = html_writer::link($url, $fullname);
        } else {
            $fullnamelink = $fullname;
        }
        return [
            html_writer::empty_tag('input', ['type' => 'checkbox', 'class' => 'mod_facetoface__archive__select-one', 'name' => 'signupids[]', 'value' => $record->id, 'aria-label' => get_string('takeattendance_tick', 'mod_facetoface', $fullname)]),
            $fullnamelink,
            session_time::format_datetime($record->timecreated, 'html'),
            state::from_code($record->statuscode)::get_string(),
        ];
    }, $records);
    $table->size = ['1px'];
    $template = manage_archives::create(
        $warning,
        $table,
        $s,
        $baseurl,
        $backurl
    );
} else {
    $template = manage_archives::create_empty($backurl);
}

echo $OUTPUT->render($template);
echo $OUTPUT->footer();
