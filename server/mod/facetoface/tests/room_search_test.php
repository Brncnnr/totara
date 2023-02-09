<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

use \mod_facetoface\room_list;

/**
 * Test suite of searching the room with distinct entries, and pagination is correctly rendered
 */
class mod_facetoface_room_search_testcase extends advanced_testcase {

    /**
     * Creating a course, and a seminar activity for the course
     *
     * Returning an array of course and seminar
     * @return array
     * @throws coding_exception
     */
    private function create_course_with_seminar() {
        /** @var \mod_facetoface\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator("mod_facetoface");

        $course = $this->getDataGenerator()->create_course([], ['createsections' => true]);
        $facetoface = $generator->create_instance((object)['course' => $course->id]);

        return array($course, $facetoface);
    }

    /**
     * Generating a rooms and sessions date that associated with it With 50 of global rooms, there should have
     * 25 sessions dates that inserted into the database
     *
     * Returning a session (event) that created for facetoface
     * associating with the session dates
     *
     * @param stdclass $user
     * @param stdClass $facetoface
     * @param int $numberofrooms
     * @throws coding_exception
     * @return stdClass
     */
    private function create_session_with_rooms(stdClass $user, stdClass $facetoface, $numberofrooms=50) {
        global $DB;
        /** @var \mod_facetoface\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator("mod_facetoface");
        $time = time();
        $sessiontime = time() + 3600;

        $sessionid = $generator->add_session((object)['facetoface' => $facetoface->id]);

        for ($i = 0; $i < $numberofrooms; $i++) {
            $room = $generator->add_site_wide_room([
                'name' => "room_{$i}",
                'capacity' => rand(1, 10),
                'usercreated' => $user->id,
                'usermodified' => $user->id,
                'timecreated' => $time,
                'timemodified' => $time,
            ]);

            if ($i % 2 === 0) {
                $sessiondate =  (object)[
                    'sessionid' => $sessionid,
                    'timestart' => $sessiontime,
                    'timefinish' => $sessiontime + 7200,
                    'sessiontimezone' => 'Pacific/Auckland',
                ];

                $sessiondateid = $DB->insert_record("facetoface_sessions_dates", $sessiondate);
                $DB->insert_record("facetoface_room_dates", (object)[
                    'sessionsdateid' => $sessiondateid,
                    'roomid' => $room->id,
                ]);

                $sessiontime += 14400;
            }
        }
        $session = new stdClass;
        $session->id = $sessionid;
        return $session;
    }

    /**
     * Test suite of rendering the search result, whereas the test is checking for the pagination to assure that the
     * pagination is rendered correctly
     *
     * @throws coding_exception
     */
    public function test_search_room_with_distinct_record() {
        global $USER, $CFG;

        require_once($CFG->dirroot . '/mod/facetoface/dialogs/seminar_dialog_content.php');

        $this->setAdminUser();

        list($course, $facetoface) = $this->create_course_with_seminar();
        $session = $this->create_session_with_rooms($USER, $facetoface);

        $selected = 0;
        $offset = 0;
        $timestart = time();
        $timefinish = time();
        $seminarevent = new \mod_facetoface\seminar_event($session->id);

        $roomlist = room_list::get_available_rooms(0, 0 , $seminarevent);
        $availablerooms = room_list::get_available_rooms($timestart, $timefinish, $seminarevent);
        $selectedids = explode(',', $selected);
        $allrooms = [];
        $selectedrooms = [];
        $unavailablerooms = [];
        foreach ($roomlist as $room) {

            // Note: We'll turn the room class into a stdClass container here until customfields and dialogs play nicely with the room class.
            $roomdata = $room->to_record();

            $roomdata->fullname = (string)$room . " (" . get_string("capacity", "facetoface") . ": {$roomdata->capacity})";
            if (!$availablerooms->contains($room->get_id()) && $seminarevent->get_cancelledstatus() == 0) {
                $unavailablerooms[$room->get_id()] = $room->get_id();
                $roomdata->fullname .= get_string('roomalreadybooked', 'mod_facetoface');
            }
            if ($roomdata->custom && $seminarevent->get_cancelledstatus() == 0) {
                $roomdata->fullname .= ' (' . get_string('facetoface', 'mod_facetoface') . ': ' . format_string($facetoface->name) . ')';
            }

            if (in_array($room->get_id(), $selectedids)) {
                $selectedrooms[$room->get_id()] = $roomdata;
            }

            $allrooms[$room->get_id()] = $roomdata;
        }

        $params = [
            'facetofaceid' => $facetoface->id,
            'sessionid' => $session->id,
            'timestart' => $timestart,
            'timefinish' => $timefinish,
            'selected' => $selected,
            'offset' => $offset,
        ];

        $dialog = new \seminar_dialog_content();
        $dialog->baseurl = '/mod/facetoface/room/ajax/sessionrooms.php';
        $dialog->proxy_dom_data(['id', 'name', 'custom', 'capacity']);
        $dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;
        $dialog->items = $allrooms;
        $dialog->disabled_items = $unavailablerooms;
        $dialog->selected_items = $selectedrooms;
        $dialog->selected_title = 'itemstoadd';
        $dialog->lang_file = 'mod_facetoface';
        $dialog->createid = 'show-editcustomroom' . $offset . '-dialog';
        $dialog->customdata = $params;
        $dialog->search_code = '/mod/facetoface/dialogs/search.php';
        $dialog->searchtype = 'facetoface_room';
        $dialog->string_nothingtodisplay = 'error:nopredefinedrooms';
        // Additional url parameters needed for pagination in the search tab.
        $dialog->urlparams = $params;

        $_POST = [
            'query' => 'room',
            'page' => 0
        ];

        // As the searching is no loging including the duplicated entries within count, therefore, with 50
        // rooms records (barely the maximum per page) the test method should expecting no pagination at all
        $content = $dialog->generate_search();
        $paging_rendering_expected = '<div class="search-paging"><div class="paging"></div></div>';

        $this->assertStringContainsString($paging_rendering_expected, $content);
    }
}