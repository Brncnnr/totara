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

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/dialogs/seminar_dialog_content.php');

use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\facilitator_list;
use mod_facetoface\customfield_area\facetofacefacilitator as facilitatorcustomfield;

$facetofaceid = required_param('facetofaceid', PARAM_INT); // Necessary when creating new sessions.
$sessionid = required_param('sessionid', PARAM_INT);       // Empty when adding new session.
$timestart = required_param('timestart', PARAM_INT);
$timefinish = required_param('timefinish', PARAM_INT);
$offset = optional_param('offset', 0, PARAM_INT);
$search = optional_param('search', 0, PARAM_INT);
$selected = required_param('selected', PARAM_SEQUENCE);

if (empty($timestart) || empty($timefinish)) {
    print_error('notimeslotsspecified', 'mod_facetoface');
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
    print_error('error:incorrectcoursemodulesession', 'mod_facetoface');
}

$cm = $seminar->get_coursemodule();
$context = $seminar->get_contextmodule($cm->id);

ajax_require_login($seminar->get_course( ), false, $cm);
require_sesskey();
require_capability('mod/facetoface:editevents', $context);

$params = [
    'facetofaceid' => $seminar->get_id(),
    'sessionid' => $seminarevent->get_id(),
    'timestart' => $timestart,
    'timefinish' => $timefinish,
    'selected' => $selected,
    'offset' => $offset
];
$PAGE->set_context($context);
$PAGE->set_url('/mod/facetoface/facilitator/ajax/sessionfacilitators.php', $params);

// Legacy Totara HTML ajax, this should be converted to json + AJAX_SCRIPT.
send_headers('text/html; charset=utf-8', false);

// Setup / loading data
$allfacilitators = [];
$selectedfacilitators = [];
$unavailablefacilitators = [];
$selectedids = explode(',', $selected);
$prefix = facilitatorcustomfield::get_area_name();
$tblprefix = facilitatorcustomfield::get_prefix();
$facilitatorslist = facilitator_list::get_available(0, 0, $seminarevent);
$availablefacilitators = facilitator_list::get_available($timestart, $timefinish, $seminarevent);
foreach ($facilitatorslist as $facilitatorid => $facilitator) {
    /** @var \mod_facetoface\facilitator_user $facilitator */

    $dialogdata = (object)[
        'id' => $facilitatorid,
        'fullname' => $facilitator->get_display_name(),
        'custom' => $facilitator->get_custom(),
    ];

    if (!$availablefacilitators->contains($facilitatorid) && $seminarevent->get_cancelledstatus() == 0) {
        $unavailablefacilitators[$facilitatorid] = $facilitatorid;
        $dialogdata->fullname .= get_string('facilitatoralreadybooked', 'mod_facetoface');
    }

    if ($dialogdata->custom && $seminarevent->get_cancelledstatus() == 0) {
        $dialogdata->fullname .= ' (' . get_string('facetoface', 'mod_facetoface') . ': ' . format_string($seminar->get_name()) . ')';
    }

    if (in_array($facilitatorid, $selectedids)) {
        $selectedfacilitators[$facilitatorid] = $dialogdata;
    }

    $allfacilitators[$facilitatorid] = $dialogdata;
}
// Display page.
$dialog = new \seminar_dialog_content();
$dialog->baseurl = '/mod/facetoface/facilitator/ajax/sessionfacilitators.php';
$dialog->proxy_dom_data(array('id', 'custom', 'fullname'));
$dialog->type = \totara_dialog_content::TYPE_CHOICE_MULTI;
$dialog->manageadhoc = has_capability('mod/facetoface:manageadhocfacilitators', $context);
$dialog->items = $allfacilitators;
$dialog->disabled_items = $unavailablefacilitators;
$dialog->selected_id = 'selected-facilitators';
$dialog->selected_items = $selectedfacilitators;
$dialog->selected_title = 'selected';
$dialog->lang_file = 'mod_facetoface';
$dialog->createid = 'show-editcustomfacilitator' . $offset . '-dialog';
$dialog->customdata = $params;
$dialog->search_code = '/mod/facetoface/dialogs/search.php';
$dialog->searchtype = 'facetoface_facilitator';
$dialog->string_nothingtodisplay = 'error:nopredefinedfacilitators';
// Additional url parameters needed for pagination in the search tab.
$dialog->urlparams = $params;

echo $dialog->generate_markup();