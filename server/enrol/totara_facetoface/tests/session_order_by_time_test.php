<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralms.com>
 * @package enrol_totara_facetoface
 */

defined('MOODLE_INTERNAL') || die();

use mod_facetoface\{seminar, seminar_event};

class enrol_totara_facetoface_session_order_by_time_testcase extends advanced_testcase {

    public function test_session_order_by_time() {

        $this->setAdminUser();
        $this->enable_plugin();

        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id));

        $totara_facetoface = enrol_get_plugin('totara_facetoface');
        $fields = array('name' => 'facetoface_enrolment', 'status' => 0, 'roleid' => 0, 'customint6' => 1);
        $totara_facetoface->add_instance($course, $fields);

        // Session 1
        $session = new stdClass();
        $session->facetoface = $facetoface->id;
        $time = time();
        $sessiondate = new stdClass();
        $sessiondate->timestart = $time + DAYSECS;
        $sessiondate->timefinish = $time + (DAYSECS * 3);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $session->sessiondates = array($sessiondate);
        $sid = $facetofacegenerator->add_session($session);

        // Session 2
        $session = new stdClass();
        $session->facetoface = $facetoface->id;
        $session->sessiondates = array();
        $sid = $facetofacegenerator->add_session($session);

        // Session 3
        $session = new stdClass();
        $session->facetoface = $facetoface->id;
        $time = time();
        $sessiondate = new stdClass();
        $sessiondate->timestart = $time + WEEKSECS;
        $sessiondate->timefinish = $time + (WEEKSECS * 2);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $session->sessiondates = array($sessiondate);
        $sid = $facetofacegenerator->add_session($session);

        // Session 4
        $session = new stdClass();
        $session->facetoface = $facetoface->id;
        $time = time();
        $sessiondate = new stdClass();
        $sessiondate->timestart = $time + MINSECS;
        $sessiondate->timefinish = $time + (DAYSECS * 2);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $session->sessiondates = array($sessiondate);
        $sid = $facetofacegenerator->add_session($session);

        $seminarevents = (new seminar($facetoface->id))->get_events();
        $enrolablesessions = $totara_facetoface->get_enrolable_sessions($course->id, null, $facetoface->id);

        /** @var seminar_event $seminarevent */
        foreach ($seminarevents as $seminarevent) {
            $seminareventid = $seminarevent->get_id();
            $sessions = $seminarevent->get_sessions();
            $this->assertEquals(array_values($sessions->to_records()), $enrolablesessions[$seminareventid]->sessiondates);
        }
    }

    private function enable_plugin() {
        $enabled = enrol_get_plugins(true);
        $enabled['totara_facetoface'] = true;
        $enabled = array_keys($enabled);
        set_config('enrol_plugins_enabled', implode(',', $enabled));
    }
}
