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

use mod_facetoface\room_list;

defined('MOODLE_INTERNAL') || die();

use \mod_facetoface\{seminar, seminar_event, room_helper};

class mod_facetoface_custom_room_search_testcase extends advanced_testcase {

    /**
     * Creating a seminar
     * @param stdClass $course
     * @return stdClass
     */
    private function create_seminar(stdClass $course): stdClass {
        global $DB;
        $time = time();
        $data = [
            'course' => $course->id,
            'name' => 'Seminar 1',
            'timecreated' => $time,
            'timemodified' => $time,
        ];

        $object = (object)$data;
        $id = $DB->insert_record("facetoface", $object);
        $object->id = $id;

        return $object;
    }

    /**
     * Create an event and assign it to a seminar,
     * if the room i specified, assign the room to this event
     *
     * @param stdClass $facetoface
     * @param stdClass $user        The user who is responsible for the action
     *
     * @return stdClass             Event record
     */
    private function create_facetoface_event(stdClass $facetoface, stdClass $user): stdClass {
        global $DB;

        $data = array(
            'facetoface' => $facetoface->id,
            'capacity' => 20,
            'allowoeverbook' => 1,
            'waitlisteveryone' => 0,
            'usermodified' => $user->id,
        );

        $obj = (object) $data;
        $id = $DB->insert_record("facetoface_sessions", $obj);
        $obj->id = $id;
        return $obj;
    }

    /**
     * Creating event session date for seminar
     *
     * @param stdClass $event
     * @param stdClass $room
     */
    private function create_event_session(stdClass $event, stdClass $room): void {
        global $DB, $CFG;

        $time = time();
        $data = array(
            'sessionid' => $event->id,
            'sessiontimezone' => isset($CFG->timezone) ?  $CFG->timezone : 99,
            'timestart' => $time,
            'timefinish' => $time + 3600
        );

        $fsdid = $DB->insert_record("facetoface_sessions_dates", (object)$data);
        room_helper::sync($fsdid, [$room->id]);
    }


    /**
     * Creating a seminar custom room
     *
     * @param stdClass $user
     * @return stdClass
     */
    private function create_custom_room(stdClass $user): stdClass {
        global $DB;

        $time = time();
        $data = [
            'name' => 'Seminar Room',
            'capacity' => 50,
            'custom' => 1,
            'hidden' => 0,
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => $time,
            'timemodified' => $time
        ];

        $object = (object)$data;
        $id = $DB->insert_record("facetoface_room", $object);
        $object->id = $id;

        return $object;
    }

    /**
     * Test suite of whether the search dialog class is able
     * to find the custom room or not
     */
    public function test_custom_room_is_appearing_in_search_result(): void {
        global $USER;

        $this->setAdminUser();

        $time = time();

        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->create_seminar($course);
        $event = $this->create_facetoface_event($facetoface, $USER);
        $room = $this->create_custom_room($USER);
        $this->create_event_session($event, $room);

        $post = [
            'search' => 1,
            'query' => 'seminar room'
        ];

        $messages = array(
            'The rendered search does not',
            'contain the custom room name:',
            'Seminar Room'
        );
        $markup = $this->set_data($event, $time, $time + 3600, $post);
        $this->assertStringContainsString("Seminar Room", $markup, implode(" ", $messages));
    }

    /**
     * Test suite instruction:
     *
     * Create a course,
     * Create a seminar,
     * Create an event for the seminar
     * Create a session date for event
     * Create a room and assign this room to the event, that has a session,
     * Create another seminar and try to search for the room within this seminar.
     *
     * As the result, the room that is being used elsewhere would not be found in other seminar,
     * but it would be found in the same seminar where it is being assigned.
     */
    public function test_used_custom_room_is_not_appearing_in_search_result(): void {
        global $USER;

        $this->setAdminUser();

        $time = time();

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->create_seminar($course);
        $room = $this->create_custom_room($USER);
        $event1 = $this->create_facetoface_event($facetoface1, $USER);
        $this->create_event_session($event1, $room);

        $facetoface2 = $this->create_seminar($course);
        $event2 = $this->create_facetoface_event($facetoface2, $USER);

        $post = [
            'search' => 1,
            'query' => 'Seminar Room',
        ];

        $markup = $this->set_data($event2, $time, $time + 3600, $post);
        $this->assertStringContainsString('No results found for "Seminar Room"', $markup);
    }

    /**
     * Test suite of assuring that the custom room that has been used in one facetoface must not
     * appear in a different seminar.
     */
    public function test_custom_room_is_not_appearing_in_different_seminar(): void {
        global $USER;

        $this->setAdminUser();

        $time = time() + (42 * 3600);

        $gen = $this->getDataGenerator();

        $course1 = $gen->create_course([], ['createsections' => 1]);
        $f2f1 = $this->create_seminar($course1);
        $room1 = $this->create_custom_room($USER);
        $event1 = $this->create_facetoface_event($f2f1, $USER);
        $this->create_event_session($event1, $room1);

        $course2 = $gen->create_course([], ['createsections' => 1]);
        $f2f2 = $this->create_seminar($course2);
        $event2 = $this->create_facetoface_event($f2f2, $USER);

        $post = [
            'search' => 1,
            'query' => 'Seminar Room',
        ];

        $markup = $this->set_data($event2, $time, $time + 3600, $post);
        $this->assertStringContainsString('No results found for "Seminar Room"', $markup);
    }

    private function set_data($event, $timestart, $timefinish, $post) {
        global $CFG;

        require_once($CFG->dirroot . '/mod/facetoface/dialogs/seminar_dialog_content.php');

        $offset = 0;
        $selected = 0;

        $seminarevent = new seminar_event($event->id);
        $seminar = new seminar($seminarevent->get_facetoface());

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
                $roomdata->fullname .= ' (' . get_string('facetoface', 'mod_facetoface') . ': ' . format_string($seminar->get_name()) . ')';
            }

            if (in_array($room->get_id(), $selectedids)) {
                $selectedrooms[$room->get_id()] = $roomdata;
            }

            $allrooms[$room->get_id()] = $roomdata;
        }

        $params = [
            'facetofaceid' => $seminar->get_id(),
            'sessionid' => $event->id,
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

        $_POST = $post;

        // As the searching is no loging including the duplicated entries within count, therefore, with 50
        // rooms records (barely the maximum per page) the test method should expecting no pagination at all
        $content = $dialog->generate_search();
        return $content;
    }
}
