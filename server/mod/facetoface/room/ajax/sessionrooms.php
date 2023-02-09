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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/dialogs/seminar_dialog_content.php');

use mod_facetoface\room_list;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;

$facetofaceid = required_param('facetofaceid', PARAM_INT); // Necessary when creating new sessions.
$sessionid = required_param('sessionid', PARAM_INT);       // Empty when adding new session.
$timestart = required_param('timestart', PARAM_INT);
$timefinish = required_param('timefinish', PARAM_INT);
$offset = optional_param('offset', 0, PARAM_INT);
$search = optional_param('search', 0, PARAM_INT);
$selected = optional_param('selected', '', PARAM_SEQUENCE);

if (empty($timestart) || empty($timefinish)) {
    print_error('notimeslotsspecified', 'facetoface');
}

$seminar = new seminar($facetofaceid);
if (!$seminar->exists()) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}

$seminarevent = new seminar_event($sessionid);
if (!$seminarevent->exists()) {
    // If it doesn't exist we'll need to set the facetofaceid for the event.
    $seminarevent->set_facetoface($seminar->get_id());
} else if ($seminarevent->get_facetoface() != $seminar->get_id()) {
    // If the event and seminar don't match up something is wrong.
    print_error('error:incorrectcoursemodulesession', 'facetoface');
}

$cm = $seminar->get_coursemodule();
$context = $seminar->get_contextmodule($cm->id);

ajax_require_login($seminar->get_course(), false, $cm);
require_sesskey();
require_capability('mod/facetoface:editevents', $context);

$params = [
    'facetofaceid' => $seminar->get_id(),
    'sessionid' => $seminarevent->get_id(),
    'timestart' => $timestart,
    'timefinish' => $timefinish,
    'selected' => $selected,
    'offset' => $offset,
];
$PAGE->set_context($context);
$PAGE->set_url('/mod/facetoface/room/ajax/sessionrooms.php', $params);

// Legacy Totara HTML ajax, this should be converted to json + AJAX_SCRIPT.
send_headers('text/html; charset=utf-8', false);

// Setup / loading data
$roomlist = room_list::get_available_rooms(0, 0 , $seminarevent);
$availablerooms = room_list::get_available_rooms($timestart, $timefinish, $seminarevent);
$seminarname = format_string($seminar->get_name(), true, ['context' => $context]);
$selectedids = explode(',', $selected);
$allrooms = [];
$selectedrooms = [];
$unavailablerooms = [];
$possible_virtual_meetings = [];
/** @var \mod_facetoface\room $room */
foreach ($roomlist as $room) {

    // Note: We'll turn the room class into a stdClass container here until customfields and dialogs play nicely with the room class.
    $roomdata = $room->to_record();
    // Note: Casting $room to string for display loads all its custom fields which can be slow if a lot of rooms
    // are available or a lot of custom fields are being used. We might want to consider displaying only room name
    // or specific pre-defined fields in the future to speed up dialog load time.
    $roomdata->fullname = (string)$room . " (" . get_string("capacity", "mod_facetoface") . ": {$roomdata->capacity})";
    if (!$availablerooms->contains($room->get_id()) && $seminarevent->get_cancelledstatus() == 0) {
        $unavailablerooms[$room->get_id()] = $room->get_id();
        $roomdata->fullname .= get_string('roomalreadybooked', 'mod_facetoface');
    } else {
        // Collect the ids which are still available.
        $possible_virtual_meetings[$room->get_id()] = $room->get_id();
    }
    if ($roomdata->custom && $seminarevent->get_cancelledstatus() == 0) {
        $roomdata->fullname .= ' (' . get_string('facetoface', 'mod_facetoface') . ': ' . $seminarname . ')';
    }

    if (in_array($room->get_id(), $selectedids)) {
        $selectedrooms[$room->get_id()] = $roomdata;
    }

    $allrooms[$room->get_id()] = $roomdata;
}
if ($possible_virtual_meetings) {
    $virtual_meetings = \mod_facetoface\room_virtualmeeting_list::from_roomids($possible_virtual_meetings);
    if (!is_null($virtual_meetings)) {
        foreach ($availablerooms as $room) {
            if ($virtual_meetings->contains($room->get_id()) &&
                $virtual_meetings->get($room->get_id())->get_userid() != $USER->id) {
                $unavailablerooms[$room->get_id()] = $room->get_id();
            }
        }
    }
}
// Display page.
$dialog = new \seminar_dialog_content();
$dialog->baseurl = '/mod/facetoface/room/ajax/sessionrooms.php';
$dialog->proxy_dom_data(['id', 'name', 'custom', 'capacity']);
$dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;
$dialog->manageadhoc = has_capability('mod/facetoface:manageadhocrooms', $context);
$dialog->items = $allrooms;
$dialog->disabled_items = $unavailablerooms;
$dialog->selected_id = 'selected-rooms';
$dialog->selected_items = $selectedrooms;
$dialog->selected_title = 'selected';
$dialog->lang_file = 'mod_facetoface';
$dialog->createid = 'show-editcustomroom' . $offset . '-dialog';
$dialog->customdata = $params;
$dialog->search_code = '/mod/facetoface/dialogs/search.php';
$dialog->searchtype = 'facetoface_room';
$dialog->string_nothingtodisplay = 'error:nopredefinedrooms';
// Additional url parameters needed for pagination in the search tab.
$dialog->urlparams = $params;

echo $dialog->generate_markup();