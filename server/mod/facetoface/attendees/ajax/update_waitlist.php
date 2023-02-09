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
 * @author Andrew Davidson <andrew.davidson@synergy-learning.com>
 * @package mod_facetoface
 */
/**
 * This class is an ajax back-end for updating attendance
 */
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot.'/mod/facetoface/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$sessionid = required_param('sessionid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$data = required_param('datasubmission', PARAM_SEQUENCE);

$data = explode(',', $data);

$seminarevent = new \mod_facetoface\seminar_event($sessionid);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

// Check essential permissions.
require_course_login($seminar->get_course(), true, $cm);
require_capability('mod/facetoface:takeattendance', $context);
require_sesskey();

$result = array('result' => 'failure', 'content' => '');
$helper = new \mod_facetoface\attendees_helper($seminarevent);

switch($action) {
    case 'confirmattendees':
        $result = \mod_facetoface\signup_helper::confirm_waitlist($seminarevent, $data);
        break;
    case 'cancelattendees':
        \mod_facetoface\signup_helper::cancel_waitlist($seminarevent, $data);
        $result['result'] = 'success';
        break;
    case 'playlottery':
        $result = \mod_facetoface\signup_helper::confirm_waitlist_randomly($seminarevent, $data);
        break;
    case 'checkcapacity':
        $signupcount = $helper->count_attendees();

        if (($signupcount + count($data)) > $seminarevent->get_capacity()) {
            $result['result'] = 'overcapacity';
        } else {
            $result['result'] = 'undercapacity';
        }
        echo json_encode($result);
        die();
        break;
    case 'hascapacity':
        if ($seminarevent->get_free_capacity() > 0) {
            $result['result'] = 'hascapacity';
        } else {
            $result['result'] = 'nocapacity';
        }
        echo json_encode($result);
        die();
        break;
}

$attendees = $helper->get_attendees_with_codes(
    [
        \mod_facetoface\signup\state\booked::get_code(),
        \mod_facetoface\signup\state\user_cancelled::get_code()
    ]
);

$result['attendees'] = array_keys($attendees);
echo json_encode($result);