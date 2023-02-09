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

use core\plugininfo\virtualmeeting;
use mod_facetoface\room;
use mod_facetoface\room_virtualmeeting;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\seminar;
use totara_core\virtualmeeting\plugin\feature;

$facetofaceid = required_param('facetofaceid', PARAM_INT);
$itemseq = required_param('itemids', PARAM_SEQUENCE);
$itemids = explode(',', $itemseq);

if (empty($itemids) || empty($itemids[0])) {
    exit();
}

$seminar = new seminar($facetofaceid);
$cm = $seminar->get_coursemodule();
$context = $seminar->get_contextmodule($cm->id);

ajax_require_login($seminar->get_course(), false, $cm);
require_sesskey();
require_capability('mod/facetoface:editevents', $context);

$PAGE->set_context($context);
$PAGE->set_url('/mod/facetoface/room/ajax/room_item.php', ['facetofaceid' => $facetofaceid, 'itemids' => $itemseq]);

$rooms = array();
foreach($itemids as $itemid) {
    $room = new room($itemid);
    $virtual_meeting = room_virtualmeeting::get_virtual_meeting($room);
    $res = (object)[
        'id' => $room->get_id(),
        'name' => $room->get_name(),
        'name_only' => $room->get_name(),
        'hidden' => $room->get_hidden(),
        'custom' => $room->get_custom(),
        'capacity' => $room->get_capacity(),
        'can_manage' => $virtual_meeting->can_manage(),
        'virtualmeeting' => false,
        'virtualroom' => !empty($room->get_url()),
        'virtualroom_status' => [],
        'lossyupdate' => null,
    ];
    if ($virtual_meeting->exists()) {
        $res->virtualmeeting = true;
        $res->virtualroom = true;
        $virtual_meeting_data = [];
        $room_dates_virtual_meetings = room_dates_virtualmeeting::load_all_by_room($room);
        foreach ($room_dates_virtual_meetings as $i => $room_dates_virtualmeeting) {
            /** @var room_dates_virtualmeeting $room_dates_virtualmeeting */
            $virtual_meeting_data[] = (object)[
                'sessionsdateid' => $room_dates_virtualmeeting->get_sessionsdateid(),
                'status' => $room_dates_virtualmeeting->get_status()
            ];
        }
        $res->virtualroom_status = $virtual_meeting_data;
        $plugin = virtualmeeting::get_all_plugins()[$virtual_meeting->get_plugin()] ?? null;
        if ($plugin !== null) {
            $res->lossyupdate = $plugin->get_feature(feature::LOSSY_UPDATE);
        }
    }
    $rooms[] = $res;
}

// Render rooms list.
echo json_encode(array_values($rooms));
