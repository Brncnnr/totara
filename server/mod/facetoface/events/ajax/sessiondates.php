<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$facetofaceid = required_param('facetofaceid', PARAM_INT); // Necessary when creating new sessions.
$start = required_param('start', PARAM_INT);
$finish = required_param('finish', PARAM_INT);
$sessiondateid = optional_param('sessiondateid', 0, PARAM_INT);       // Empty when adding new session.
$timezone = optional_param('timezone', '99', PARAM_TIMEZONE);
$roomids = optional_param('roomids', null, PARAM_SEQUENCE);
$assetids = optional_param('assetids', null, PARAM_SEQUENCE);
$facilitatorids = optional_param('facilitatorids', null, PARAM_SEQUENCE);

$seminar = new mod_facetoface\seminar($facetofaceid);
if (!$seminar->exists()) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}

$cm = $seminar->get_coursemodule();
$context = $seminar->get_contextmodule($cm->id);

$params = compact('facetofaceid', 'start', 'finish', 'timezone', 'roomids', 'assetids', 'facilitatorids', 'sessiondateid');
$currenturl = new moodle_url('/mod/facetoface/events/ajax/sessiondates.php', $params);

$params['sessionid'] = 0;
if ($sessiondateid) {
    $sessionid = $DB->get_field('facetoface_sessions_dates', 'sessionid', array('id' => $sessiondateid));
    $seminarevent = \mod_facetoface\seminar_event::seek($sessionid);
    if (!$seminarevent->exists()) {
        print_error('error:incorrectcoursemodulesession', 'facetoface');
    }
    if ($seminarevent->get_facetoface() != $facetofaceid) {
        print_error('error:incorrectcoursemodulesession', 'facetoface');
    }
    $currenturl->param('sessiondateid', $sessiondateid);
    $params['sessionid'] = $sessionid;
}

ajax_require_login($seminar->get_course(), false, $cm);
require_sesskey();
require_capability('mod/facetoface:editevents', $context);

$jsmodule = array(
    'name' => 'totara_f2f_dateintervalkeeper',
    'fullpath' => '/mod/facetoface/js/dateintervalkeeper.js'
);

$PAGE->requires->js_init_call('M.totara_f2f_dateintervalkeeper.init', array(), false, $jsmodule);

$form = new \mod_facetoface\form\event_date($currenturl, $params, 'post', '', array('class' => 'dialog-nobind'), true, null, md5($start.$finish));
if ($data = $form->get_data()) {
    // Provide timestamp, timezone values, and rendered dates text.
    $data->html = \mod_facetoface\event_dates::render(
            $data->timestart,
            $data->timefinish,
            $data->sessiontimezone,
            $displaytimezones = get_config(null, 'facetoface_displaysessiontimezones')
    );
    echo json_encode($data);
    exit();
}

$form->display();
echo $PAGE->requires->get_end_code(false);
