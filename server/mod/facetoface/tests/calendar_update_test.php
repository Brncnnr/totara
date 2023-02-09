<?php
/*
 * This file is part of Totara LMS
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

/**
 * Tests calendar::update_entries()
 *
 * See also add_session_to_calendar.php which tests that seminar events added to the calendar have the correct dates.
 */
class mod_facetoface_calendar_update_testcase extends advanced_testcase {

    protected $facetofacegenerator = null;
    protected $facetoface = null;
    protected $course = null;
    protected $users = null;
    protected $sessiondates = null;
    protected $facilitator = null;

    protected function tearDown(): void {
        $this->facetofacegenerator = null;
        $this->facetoface = null;
        $this->course = null;
        $this->users = null;
        $this->sessiondates = null;
        $this->facilitator = null;
        parent::tearDown();
    }

    public function setUp(): void {
        $this->facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $this->course = $this->getDataGenerator()->create_course();
        $this->users = array(
            $this->getDataGenerator()->create_and_enrol($this->course, 'student'),
            $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher'),
        );
        $this->facilitator = $this->facetofacegenerator->add_internal_facilitator();
    }

    /**
     * Generates a seminar, adds a seminarevent and three sessions with a facilitator, signs up the first user, and calls calendar::update_entries()
     *
     * @param array $params array of seminar properties for the generator
     */
    private function create_seminar(array $params) {
        // Create activity.
        $this->facetoface = $this->getDataGenerator()->create_module('facetoface', $params);
        // Generate 3 sessions.
        $now = time();
        $sessiondates = [];
        for ($i = 1; $i < 4; $i++) {
            $sessiondates[$i] = new stdClass();
            $sessiondates[$i]->timestart = $now + ($i * WEEKSECS);
            $sessiondates[$i]->timefinish = $sessiondates[$i]->timestart + (3 * HOURSECS);
            $sessiondates[$i]->sessiontimezone = '99';
            $sessiondates[$i]->assetids = array();
            $sessiondates[$i]->facilitatorids = array($this->facilitator->id);
        }
        // Generate seminarevent.
        $sid = $this->facetofacegenerator->add_session(array('facetoface' => $this->facetoface->id, 'sessiondates' => $sessiondates));
        $seminarevent = new \mod_facetoface\seminar_event($sid);

        // Add booking for student.
        $this->facetofacegenerator->create_signup($this->users[0], $seminarevent);

        // Update calendar events.
        \mod_facetoface\calendar::update_entries($seminarevent);
    }

    /**
     * Loads the various types of event records expected by the tests, and returns them in an array.
     *
     * @return array
     */
    private function load_event_records() {
        global $DB;

        $records = array();

        // Load site events
        $records['site'] = $DB->get_records('event', array(
            'modulename' => 'facetoface',
            'eventtype' => 'facetofacesession',
            'courseid' => SITEID,
            'userid' => 0
        ), 'timestart');

        // Load course events
        $records['course'] = $DB->get_records('event', array(
            'modulename' => 'facetoface',
            'eventtype' => 'facetofacesession',
            'courseid' => $this->course->id,
            'userid' => 0
        ), 'timestart');

        // Load user events
        $records['user'] = $DB->get_records('event', array(
            'modulename' => 'facetoface',
            'eventtype' => 'facetofacebooking',
            'courseid' => 0,
            'userid' => $this->users[0]->id
        ), 'timestart');

        // Load facilitator events
        $records['facilitator'] = $DB->get_records('event', array(
            'modulename' => '',
            'eventtype' => 'facetofacefacilitato',
            'courseid' => 0,
            'userid' => $this->facilitator->userid
        ), 'timestart');

        return $records;
    }

    public function test_facetoface_calendar_none_nouser() {
        $params = array('course' => $this->course->id, 'showoncalendar' => F2F_CAL_NONE, 'usercalentry' => 0);
        $this->create_seminar($params);

        $records = $this->load_event_records();

        $this->assertEquals(0, count($records['site']));
        $this->assertEquals(0, count($records['course']));
        $this->assertEquals(0, count($records['user']));
        $this->assertEquals(3, count($records['facilitator']));
    }

    public function test_facetoface_calendar_none_user() {
        $params = array('course' => $this->course->id, 'showoncalendar' => F2F_CAL_NONE, 'usercalentry' => 1);
        $this->create_seminar($params);

        $records = $this->load_event_records();

        $this->assertEquals(0, count($records['site']));
        $this->assertEquals(0, count($records['course']));
        $this->assertEquals(3, count($records['user']));
        $this->assertEquals(3, count($records['facilitator']));
    }

    public function test_facetoface_calendar_course_nouser() {
        $params = array('course' => $this->course->id, 'showoncalendar' => F2F_CAL_COURSE, 'usercalentry' => 0);
        $this->create_seminar($params);

        $records = $this->load_event_records();

        $this->assertEquals(0, count($records['site']));
        $this->assertEquals(3, count($records['course']));
        $this->assertEquals(0, count($records['user']));
        $this->assertEquals(3, count($records['facilitator']));
    }

    public function test_facetoface_calendar_course_user() {
        $params = array('course' => $this->course->id, 'showoncalendar' => F2F_CAL_COURSE, 'usercalentry' => 1);
        $this->create_seminar($params);

        $records = $this->load_event_records();

        $this->assertEquals(0, count($records['site']));
        $this->assertEquals(3, count($records['course']));
        $this->assertEquals(3, count($records['user']));
        $this->assertEquals(3, count($records['facilitator']));
    }

    public function test_facetoface_calendar_site_nouser() {
        $params = array('course' => $this->course->id, 'showoncalendar' => F2F_CAL_SITE, 'usercalentry' => 0);
        $this->create_seminar($params);

        $records = $this->load_event_records();

        $this->assertEquals(3, count($records['site']));
        $this->assertEquals(0, count($records['course']));
        $this->assertEquals(0, count($records['user']));
        $this->assertEquals(3, count($records['facilitator']));
    }

    public function test_facetoface_calendar_site_user() {
        $params = array('course' => $this->course->id, 'showoncalendar' => F2F_CAL_SITE, 'usercalentry' => 1);
        $this->create_seminar($params);

        $records = $this->load_event_records();

        $this->assertEquals(3, count($records['site']));
        $this->assertEquals(0, count($records['course']));
        $this->assertEquals(3, count($records['user']));
        $this->assertEquals(3, count($records['facilitator']));
    }

}
