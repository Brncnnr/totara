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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

use enrol_totara_facetoface\watcher\seminar_watcher;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\signup;
use mod_facetoface\room_helper;
use mod_facetoface\signup_status;

class mod_facetoface_renderer_testcase extends advanced_testcase {

    /** @var \core\testing\generator $data_generator */
    private $data_generator;

    /** @var \mod_facetoface\testing\generator */
    private $facetoface_generator;

    /** @var \totara_customfield\testing\generator */
    protected $customfield_generator;

    public function setUp(): void {
        parent::setUp();

        $this->data_generator = $this->getDataGenerator();
        $this->facetoface_generator = $this->data_generator->get_plugin_generator('mod_facetoface');
        $this->customfield_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        // Make sure we're not dealing with stale cache data on the enrol_totara_facetoface watcher.
        seminar_watcher::reset_enrol_plugin();
    }

    protected function tearDown(): void {
        $this->data_generator = null;
        $this->facetoface_generator = null;
        $this->customfield_generator = null;

        seminar_watcher::reset_enrol_plugin();
        parent::tearDown();
    }

    /**
     * Test the method get_signup_link in an ordinary situation.
     */
    public function test_get_signup_link_default() {
        [ $seminarid, $sessionid, $roomid ] = $this->create_seminar_session_and_room(null, false, 0, 0);
        $seminarevent = new seminar_event($sessionid);

        $renderer = $this->create_f2f_renderer();
        // hard-coded url instead of \mod_facetoface_renderer::DEFAULT_SIGNUP_LINK, to catch possible regression
        $expected = '/mod/facetoface/eventinfo.php';

        $link = $renderer->get_signup_link($seminarevent);
        $this->assertEquals($expected, $link);

        $expected = '/somewhere/else/signup.php';

        $this->resetDebugging();
        $renderer->set_signup_link($expected);
        $debugging = $this->getDebuggingMessages()[0]->message;
        $this->assertDebuggingCalled();

        $this->assertStringContainsString('deprecated', $debugging);
        $this->assertStringContainsString('mod_facetoface\\hook\\alternative_signup_link', $debugging);
        $link = $renderer->get_signup_link($seminarevent);
        $this->assertEquals($expected, $link);
    }

    /**
     * Test the method get_signup_link by activating enrol_totara_facetoface_plugin.
     * If the plugin is missing, the whole test will be skipped.
     */
    public function test_get_signup_link_with_enrol_plugin() {
        global $DB, $CFG;
        /** @var moodle_database $DB */

        $plugin = enrol_get_plugin('totara_facetoface');
        if ($plugin === null) {
            $this->markTestSkipped('enrol_totara_facetoface_plugin is not available');
        }

        $this->assertInstanceOf('enrol_totara_facetoface_plugin', $plugin);
        $enabled = enrol_get_plugins(true);
        $enabled['guest'] = true;
        $enabled['totara_facetoface'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));

        [ $seminarid, $session1id, $roomid ] = $this->create_seminar_session_and_room(null, false, 0, 0);
        $seminar = new seminar($seminarid);
        $seminarevent1 = new seminar_event($session1id);
        $course = $DB->get_record('course', array('id' => $seminar->get_course()));

        $session2id = $this->make_session((object)['id' => $seminarid], (object)['id' => $roomid], [ time() + YEARSECS ], false);
        $seminarevent2 = new seminar_event($session1id);

        $user = $this->getDataGenerator()->create_user();
        $context = context_course::instance($course->id);
        $renderer1 = $this->create_f2f_renderer();
        $renderer2 = $this->create_f2f_renderer();

        $alter = '/somewhere/else/signup.php';
        $renderer2->set_signup_link($alter);
        $this->assertDebuggingCalled();

        $this->setUser($user);

        $instid = $plugin->add_instance($course, ['name' => 'totara_facetoface', 'status' => ENROL_INSTANCE_ENABLED, 'customint6' => 1]);

        // hard-coded url to catch possible regression
        $expected = '/enrol/totara_facetoface/signup.php';

        $link = $renderer1->get_signup_link($seminarevent1);
        $this->assertEquals($expected, $link);
        $link = $renderer1->get_signup_link($seminarevent2);
        $this->assertEquals($expected, $link);

        $link = $renderer2->get_signup_link($seminarevent1);
        $this->assertEquals($alter, $link);
        $link = $renderer2->get_signup_link($seminarevent2);
        $this->assertEquals($alter, $link);

        $inst = $DB->get_record('enrol', array('id' => $instid), '*', MUST_EXIST);

        // once enrolled the signup link must be default
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);

        $plugin->enrol_user($inst, $user->id, $studentrole->id);

        // hard-coded url instead of \mod_facetoface_renderer::DEFAULT_SIGNUP_LINK, to catch possible regression
        $expected = '/mod/facetoface/eventinfo.php';

        $link = $renderer1->get_signup_link($seminarevent1);
        $this->assertEquals($expected, $link);
        $link = $renderer1->get_signup_link($seminarevent2);
        $this->assertEquals($expected, $link);

        $link = $renderer2->get_signup_link($seminarevent1);
        $this->assertEquals($alter, $link);
        $link = $renderer2->get_signup_link($seminarevent2);
        $this->assertEquals($alter, $link);

        // seminar_watcher hook should honour restricted access
        // see TL-16472 for more info
        $CFG->enableavailability = true;
        $cm = $seminar->get_coursemodule();
        $DB->set_field('course_modules', 'availability', '{"op":"&","c":[{"type":"date","d":"<","t":1}],"showc":[true]}', ['id' => $cm->id]);
        rebuild_course_cache($course->id, true);

        $modinfo = get_fast_modinfo($cm->course);
        $cm = $modinfo->get_cm($cm->id);
        $this->assertFalse($cm->available);

        $link = $renderer1->get_signup_link($seminarevent1);
        $this->assertSame('', $link);
    }

    /**
     * Instantiate the mod_facetoface_renderer, set system context and initialise page.
     *
     * @return \mod_facetoface_renderer
     */
    private function create_f2f_renderer(): mod_facetoface_renderer {
        global $PAGE, $CFG;
        /** @var \moodle_page $PAGE */

        require_once($CFG->dirroot.'/mod/facetoface/renderer.php');

        // only admin can see the attendance taking column
        $this->setAdminUser();
        $sysctx = context_system::instance();

        $renderer = new \mod_facetoface_renderer($PAGE, null);
        $renderer->setcontext($sysctx);
        $PAGE->set_context($sysctx);
        // We need to set the url as this is queried during the run of render_session_list_table.
        $PAGE->set_url('/mod/facetoface/view.php');

        return $renderer;
    }

    /**
     * @see \mod_facetoface_lib_testcase::prepare_date
     *
     * @param int|string $timestart
     * @param int|string $timeend
     * @param int|string $roomid
     * @return \stdClass
     */
    protected function prepare_date($timestart, $timeend, $roomid) {
        $sessiondate = new stdClass();
        $sessiondate->timestart = is_numeric($timestart) ? (string)(int)$timestart : strtotime($timestart);
        $sessiondate->timefinish = is_numeric($timeend) ? (string)(int)$timeend : strtotime($timeend);
        $sessiondate->sessiontimezone = '99';
        $sessiondate->roomids = [(int)$roomid];
        return $sessiondate;
    }

    /**
     * Add a session to a seminar event.
     * @see \mod_facetoface_lib_testcase::make_session
     *
     * @param \stdClass $f2f
     * @param \stdClass $room
     * @param array $dates
     * @param boolean $cancelrightnow
     * @param integer $capacity
     * @return integer
     */
    private function make_session($f2f, $room, array $dates, $cancelrightnow = false, $capacity = 10) : int {
        $dates = array_map(
            function ($e) use ($room) {
                return $this->prepare_date($e, $e + HOURSECS * 3, $room->id);
            },
            $dates
        );
        $id = $this->facetoface_generator->add_session(['facetoface' => $f2f->id, 'sessiondates' => $dates, 'capacity' => $capacity]);
        if ($id && $cancelrightnow) {
            $evt = new seminar_event($id);
            $evt->cancel();
        }
        return $id;
    }

    /**
     * Create a seminar, a session and a room.
     *
     * @param integer|null $startdate
     * @param boolean $cancelled
     * @param integer $sessionattendance
     * @param integer $attendancetime \mod_facetoface\seminar::ATTENDANCE_TIME_xxx
     * @return integer[] [ seminar_id, session_id, room_id ]
     */
    private function create_seminar_session_and_room($startdate, bool $cancelled, int $sessionattendance, int $attendancetime) : array {
        $course = $this->getDataGenerator()->create_course();
        $room = $this->facetoface_generator->add_site_wide_room([ 'name' => 'Chamber', 'allowconflicts' => 1 ]);
        $f2f = $this->facetoface_generator->create_instance(
            [
                'course' => $course->id,
                'sessionattendance' => $sessionattendance,
                'attendancetime' => $attendancetime
            ]
        );

        $dates = $startdate !== null ? [ time() + $startdate ] : [];

        $seminarid = $f2f->id;
        $sessionid = $this->make_session($f2f, $room, $dates, $cancelled, 2);
        $roomid = $room->id;
        return [ $seminarid, $sessionid, $roomid ];
    }

    /**
     * Create a DOMDocument without warnings and errors.
     *
     * @param string $html
     * @return DOMDocument
     */
    private static function new_domdocument(string $html) : DOMDocument {
        $doc = new DOMDocument();
        $doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR); // requires PHP 7.2+, 7.1.4+, 7.0.18+
        return $doc;
    }

    /**
     * Ensure that render_session_list_table() renders the list using the given $sessions parameter.
     */
    public function test_render_session_list_table_with_crafted_records() {
        $course = $this->getDataGenerator()->create_course();
        $f2f = $this->facetoface_generator->create_instance(['course' => $course->id]);
        $room1 = $this->facetoface_generator->add_custom_room([ 'name' => 'Chamber', 'allowconflicts' => 1 ]);
        $room2 = $this->facetoface_generator->add_custom_room([ 'name' => 'Arena', 'allowconflicts' => 1 ]);

        // Create a bunch of sessions
        $date1 = $this->prepare_date('1 Dec last year', '1 Jan next year', $room1->id);
        $date2 = $this->prepare_date('1 Feb next year', '1 Feb next year +1 hour', $room1->id);
        $date3 = $this->prepare_date('1 Mar next year', '1 Mar next year +1 hour', $room1->id);
        $date4 = $this->prepare_date('1 Apr next year', '1 Apr next year +1 hour', $room1->id);
        $date5 = $this->prepare_date('1 Aug last year', '1 Aug last year +1 hour', $room1->id);
        $date6 = $this->prepare_date('1 Sep last year', '1 Sep last year +1 hour', $room1->id);
        $session1id = $this->facetoface_generator->add_session(['facetoface' => $f2f->id, 'sessiondates' => [$date1, $date2, $date3], 'capacity' => 10 ]);
        $session2id = $this->facetoface_generator->add_session(['facetoface' => $f2f->id, 'sessiondates' => [], 'capacity' => 10 ]);
        $session3id = $this->facetoface_generator->add_session(['facetoface' => $f2f->id, 'sessiondates' => [$date4], 'capacity' => 10 ]);
        $session4id = $this->facetoface_generator->add_session(['facetoface' => $f2f->id, 'sessiondates' => [$date5], 'capacity' => 10 ]);
        $session5id = $this->facetoface_generator->add_session(['facetoface' => $f2f->id, 'sessiondates' => [$date6], 'capacity' => 10 ]);

        // Make the session list in the order of [ future, far_past, ongoing, waitlisted, past ]
        $sessions = [];
        $sessions[$session3id] = seminar_event_helper::get_sessiondata(new seminar_event($session3id), null);
        $sessions[$session4id] = seminar_event_helper::get_sessiondata(new seminar_event($session4id), null);
        $sessions[$session1id] = seminar_event_helper::get_sessiondata(new seminar_event($session1id), null, true);
        $sessions[$session2id] = seminar_event_helper::get_sessiondata(new seminar_event($session2id), null);
        $sessions[$session5id] = seminar_event_helper::get_sessiondata(new seminar_event($session5id), null);

        // .. and shuffle session dates of the ongoing event as [ $date2, $date3, $date1 ]
        $firstid = array_key_first($sessions[$session1id]->sessiondates);
        $firstrecord = array_shift($sessions[$session1id]->sessiondates);
        $sessions[$session1id]->sessiondates[$firstid] = $firstrecord;

        // .. and change the room of one event to Arena
        $data = new \stdClass();
        $data->roomid = $room2->id;
        $data->sessiondateid = $firstid;
        room_helper::sync($data->sessiondateid, [$data->roomid]);

        $renderer = $this->create_f2f_renderer();
        $option = new mod_facetoface\dashboard\render_session_option();
        $config = new mod_facetoface\dashboard\render_session_list_config(new seminar(), context_system::instance(), $option);
        $config->viewattendees = false;
        $config->editevents = true;
        $config->displaytimezones = false;
        $config->reserveinfo = array();
        $config->minimal = true;
        $config->returntoallsessions = false;
        $config->sessionattendance = seminar::SESSION_ATTENDANCE_DISABLED;
        $config->eventattendance = seminar::EVENT_ATTENDANCE_DEFAULT;
        $config->viewsignupperiod = false;
        $config->viewactions = false;
        $html = $renderer->render_session_list_table($sessions, $config);

        // .. multiple rooms in the session
        $this->assertStringContainsString('Chamber', $html);
        $this->assertStringContainsString('Arena', $html);

        $doc = self::new_domdocument($html);
        $tables = $doc->getElementsByTagName('table');
        $this->assertCount(1, $tables);
        $rows = $tables[0]->getElementsByTagName('tbody')[0]->getElementsByTagName('tr');
        $this->assertCount(7, $rows);

        /** @var DOMNode[] $rows */
        // .. and the list is sorted by custom order
        $this->assertStringContainsString('April', $rows[0]->textContent);
        $this->assertStringContainsString('August', $rows[1]->textContent);
        $this->assertStringContainsString('February', $rows[2]->textContent);
        $this->assertStringContainsString('March', $rows[3]->textContent);
        $this->assertStringContainsString('December', $rows[4]->textContent);
        $this->assertStringContainsString('Wait-listed', $rows[5]->textContent);
        $this->assertStringContainsString('September', $rows[6]->textContent);
    }

    /**
     * Ensure debugging() is called at least once with the expected message.
     *
     * @param string $expected_message
     */
    public function assert_debugging_called(string $expected_message) {
        $debugging = $this->getDebuggingMessages();
        $this->resetDebugging();
        $this->assertNotCount(0, $debugging, 'debugging() was not called.');
        $filtered = array_filter($debugging, function ($debug) use ($expected_message) {
            return strpos($debug->message, $expected_message) !== false;
        });
        $this->assertNotCount(0, $filtered, print_r($debugging, true));
    }

    /**
     * Ensure deprecated renderer functions are still working.
     */
    public function test_deprecated_functions() {
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user(2, $course->id);
        $f2f = $this->facetoface_generator->create_instance(['course' => $course->id, 'sessionattendance' => seminar::SESSION_ATTENDANCE_UNRESTRICTED, 'attendancetime' => seminar::EVENT_ATTENDANCE_UNRESTRICTED]);
        $room1 = $this->facetoface_generator->add_custom_room(['name' => 'room 1', 'allowconflicts' => 1]);
        $room2 = $this->facetoface_generator->add_custom_room(['name' => 'room 2', 'allowconflicts' => 1]);
        $cfids = [];
        $cfids = array_merge($cfids, $this->customfield_generator->create_text('facetoface_session', ['event_text']));
        $cfids = array_merge($cfids, $this->customfield_generator->create_datetime('facetoface_session', ['event_date' => []]));
        $cfids = array_merge($cfids, $this->customfield_generator->create_multiselect('facetoface_session', ['event_multi' => ['opt1', 'opt2']]));
        $cfids = array_merge($cfids, $this->customfield_generator->create_location('facetoface_session', ['event_location' => []]));
        $sessionid = $this->facetoface_generator->add_session([
            'facetoface' => $f2f->id,
            'sessiondates' => [
                $this->prepare_date(time() + 3333, time() + 5555, $room1->id),
                $this->prepare_date(time() + 7777, time() + 9999, $room2->id),
            ],
            'capacity' => 10,
            'allowoverbook' => 1,
            'registrationtimestart' => time() + 1111,
            'registrationtimefinish' => time() + 6666,
        ]);
        $seminar = new seminar($f2f->id);
        $seminarevent = new seminar_event($sessionid);
        $signup = signup::create(2, $seminarevent)->save();
        signup_status::create($signup, new \mod_facetoface\signup\state\booked($signup))->save();
        $sessiondata = seminar_event_helper::get_sessiondata($seminarevent, null);
        $cd = $sessiondata->cntdates;
        $date1 = reset($sessiondata->sessiondates);
        $date2 = next($sessiondata->sessiondates);
        $this->customfield_generator->set_text($sessiondata, $cfids['event_text'], 'value1', 'facetofacesession', 'facetoface_session');
        $this->customfield_generator->set_datetime($sessiondata, $cfids['event_date'], 2121212121, 'facetofacesession', 'facetoface_session');
        $this->customfield_generator->set_multiselect($sessiondata, $cfids['event_multi'], ['opt2'], 'facetofacesession', 'facetoface_session');
        $this->customfield_generator->set_location_address($sessiondata, $cfids['event_location'], '6925 Hollywood Boulevard', 'facetofacesession', 'facetoface_session');
        $customfields = customfield_get_fields_definition('facetoface_session', array('hidden' => 0));
        $renderer = $this->create_f2f_renderer();

        $this->assertNotEmpty($renderer->print_session_list_table([$sessiondata], true, true, true, array(), 'foo', false, true, seminar::SESSION_ATTENDANCE_UNRESTRICTED, seminar::EVENT_ATTENDANCE_UNRESTRICTED, true, true));
        $this->assert_debugging_called('mod_facetoface_renderer::print_session_list_table() is deprecated');

        $this->assertNotEmpty($renderer->print_session_list($seminar, $room1->id));
        $this->assert_debugging_called('mod_facetoface_renderer::print_session_list() is deprecated');

        $this->assertNotEmpty($renderer->render_session_list($seminar, new \mod_facetoface\dashboard\filter_list(), new \mod_facetoface\dashboard\render_session_option()));
        $this->assert_debugging_called('mod_facetoface_renderer::render_session_list() is deprecated');

        $call = function ($method, $args) use (&$renderer) {
            $rm = new ReflectionMethod($renderer, $method);
            $rm->setAccessible(true);
            return $rm->invokeArgs($renderer, $args);
        };

        $this->assertCount(count($customfields), $call('session_customfield_table_cells', [$sessiondata, $customfields]));
        $this->assert_debugging_called('mod_facetoface_renderer::session_customfield_table_cells() is deprecated');

        $this->assertInstanceOf(html_table_cell::class, $call('session_capacity_table_cell', [$seminarevent, true, 1, $cd]));
        $this->assert_debugging_called('mod_facetoface_renderer::session_capacity_table_cell() is deprecated');

        $this->assertInstanceOf(\mod_facetoface\output\attendance_tracking_table_cell::class, $call('attendance_tracking_table_cell', [$sessiondata, $date1, seminar::SESSION_ATTENDANCE_UNRESTRICTED]));
        $this->assert_debugging_called('mod_facetoface_renderer::attendance_tracking_table_cell() is deprecated');

        $this->assertInstanceOf(html_table_cell::class, $call('session_status_table_cell', [$sessiondata, $date1, 0]));
        $this->assert_debugging_called('mod_facetoface_renderer::session_status_table_cell() is deprecated');

        $this->assertInstanceOf(html_table_cell::class, $call('event_status_table_cell', [$sessiondata, 1, $cd, null]));
        $this->assert_debugging_called('mod_facetoface_renderer::event_status_table_cell() is deprecated');

        $this->assertNotEmpty($renderer->event_status_attendance_taking_html($sessiondata, null));
        $this->assert_debugging_called('mod_facetoface_renderer::event_status_attendance_taking_html() is deprecated');

        $this->assertInstanceOf(html_table_cell::class, $call('session_resgistrationperiod_table_cell', [$seminarevent, $cd, null]));
        $this->assert_debugging_called('mod_facetoface_renderer::session_resgistrationperiod_table_cell() is deprecated');

        $this->assertInstanceOf(html_table_cell::class, $call('session_options_table_cell', [$seminarevent, true, true, '<a>reserve</a>', '<a>sign-up</a>', $cd]));
        $this->assert_debugging_called('mod_facetoface_renderer::session_options_table_cell() is deprecated');

        $this->assertNotEmpty($call('get_regdates_tooltip_info', [$seminarevent, true]));
        $this->assert_debugging_called('mod_facetoface_renderer::get_regdates_tooltip_info() is deprecated');

        $this->assertNotEmpty($call('session_options_reserve_link', [$seminarevent, 1, ['allocate' => 1, 'maxallocate' => 2, 'reserve' => 3, 'maxreserve' => 4, 'reserveother' => 1, 'reservepastdeadline' => 0]]));
        $this->assert_debugging_called('mod_facetoface_renderer::session_options_reserve_link() is deprecated');

        $this->assertNotEmpty($call('session_options_signup_link', [$seminarevent, 0, true]));
        $this->assert_debugging_called('mod_facetoface_renderer::session_options_signup_link() is deprecated');

        ob_start();
        $renderer->print_action_bar($seminar);
        $this->assertNotEmpty(ob_get_contents());
        $this->assert_debugging_called('The method mod_facetoface_renderer::print_action_bar() has been deprecated');
        ob_end_clean();

        ob_start();
        $roomid = 0;
        $eventtime = \mod_facetoface\event_time::ALL;
        $renderer->print_filter_bar($seminar, $roomid, $eventtime);
        $this->assertNotEmpty(ob_get_contents());
        $this->assert_debugging_called('The method mod_facetoface_renderer::print_filter_bar() has been deprecated');
        ob_end_clean();

        $roomid = 0;
        $this->assertCount(3, $call('get_filter_by_room', [$seminar, &$roomid]));
        $this->assert_debugging_called('The method mod_facetoface_renderer::get_filter_by_room() has been deprecated');

        $eventtime = \mod_facetoface\event_time::ALL;
        $this->assertNotCount(0, $call('get_filter_by_event_time', [&$seminar, &$eventtime]));
        $this->assert_debugging_called('The method mod_facetoface_renderer::get_filter_by_event_time() has been deprecated');

        ob_start();
        $this->assertSame(0, $renderer->filter_by_room($seminar, 0));
        $this->assertNotEmpty(ob_get_contents());
        $this->assert_debugging_called('The method mod_facetoface_renderer::filter_by_room() has been deprecated');
        ob_end_clean();

        $renderer->set_signup_link('lorem ipsum');
        $this->assert_debugging_called('mod_facetoface_renderer::set_signup_link() is deprecated');
        $this->assertSame('lorem ipsum', $renderer->get_signup_link($seminarevent));
    }
}
