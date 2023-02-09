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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use mod_facetoface\facilitator;
use mod_facetoface\facilitator_list;
use mod_facetoface\seminar_event;

class mod_facetoface_facilitator_testcase extends advanced_testcase {

    /** @var \mod_facetoface\testing\generator */
    protected $facetoface_generator;

    /** @var \totara_customfield\testing\generator */
    protected $customfield_generator;

    private $cfprefix = 'facetofacefacilitator', $cftableprefix = 'facetoface_facilitator';

    protected function tearDown(): void {
        $this->facetoface_generator = null;
        $this->customfield_generator = null;
        $this->cfprefix = null;
        parent::tearDown();
    }

    public function setUp(): void {
        parent::setUp();

        $this->facetoface_generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $this->customfield_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
    }

    public function test_facetoface_get_facilitator() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

        $sitewidefacilitator = $this->facetoface_generator->add_site_wide_facilitator(array());
        customfield_load_data($sitewidefacilitator, 'facetofacefacilitator', 'facetoface_facilitator');

        $customfacilitator = $this->facetoface_generator->add_custom_facilitator(array());
        customfield_load_data($customfacilitator, 'facetofacefacilitator', 'facetoface_facilitator');

        $this->assertCount(2, $DB->get_records('facetoface_facilitator', array()));

        $sitewidefacilitatorclass = new facilitator($sitewidefacilitator->id);
        $this->assertEquals($sitewidefacilitator->id, $sitewidefacilitatorclass->get_id());
        $this->assertEquals($sitewidefacilitator->name, $sitewidefacilitatorclass->get_name());

        $customfacilitatorclass = new facilitator($customfacilitator->id);
        $this->assertEquals($customfacilitator->id, $customfacilitatorclass->get_id());
        $this->assertEquals($customfacilitator->name, $customfacilitatorclass->get_name());

        $invalidfacilitator = new facilitator(0);
        $this->assertEmpty($invalidfacilitator->get_id());
        $this->assertEmpty($invalidfacilitator->get_name());

        try {
            $invalidfacilitator = new facilitator(-1);
            $this->fail("Incorrect facilitator id should throw error");
        } catch (exception $e) {
            //Do nothing
        }
    }

    /**
     * Basic tests for facilitator deletes.
     */
    public function test_facetoface_delete_facilitator() {
        global $DB, $TEXTAREA_OPTIONS;

        $fs = get_file_storage();
        $syscontext = $TEXTAREA_OPTIONS['context'];

        $sitewidefacilitator = $this->facetoface_generator->add_site_wide_facilitator(array());
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'mod_facetoface',
            'filearea' => 'facetofacefacilitator',
            'itemid' => $sitewidefacilitator->id,
            'filepath' => '/',
            'filename' => 'xx.jpg',
        );
        $sitefile = $fs->create_file_from_string($filerecord, 'xx');

        $customfacilitator = $this->facetoface_generator->add_custom_facilitator(array());
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'mod_facetoface',
            'filearea' => 'facetofacefacilitator',
            'itemid' => $customfacilitator->id,
            'filepath' => '/',
            'filename' => 'xx.jpg',
        );
        $customfile = $fs->create_file_from_string($filerecord, 'xx');

        $this->assertCount(2, $DB->get_records('facetoface_facilitator', array()));

        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondate1 = new stdClass();
        $sessiondate1->timestart = time() + (DAYSECS * 1);
        $sessiondate1->timefinish = $sessiondate1->timestart + (DAYSECS * 1);
        $sessiondate1->sessiontimezone = '99';
        $sessiondate1->facilitatorids = array($sitewidefacilitator->id);
        $sessionid1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array($sessiondate1)));
        $sessiondate1 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid1), '*', MUST_EXIST);
        $this->assertCount(1, $DB->get_records('facetoface_facilitator_dates', array('facilitatorid' => $sitewidefacilitator->id)));

        $sessiondate2 = new stdClass();
        $sessiondate2->timestart = time() + (DAYSECS * 2);
        $sessiondate2->timefinish = $sessiondate2->timestart + (DAYSECS * 2);
        $sessiondate2->sessiontimezone = '99';
        $sessiondate2->facilitatorids = array($customfacilitator->id, $sitewidefacilitator->id);
        $sessionid2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array($sessiondate2)));
        $sessiondate2 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid2), '*', MUST_EXIST);
        $this->assertCount(1, $DB->get_records('facetoface_facilitator_dates', array('facilitatorid' => $customfacilitator->id)));
        $this->assertCount(2, $DB->get_records('facetoface_facilitator_dates', array('facilitatorid' => $sitewidefacilitator->id)));

        $facilitator = new facilitator($sitewidefacilitator->id);
        $facilitator->delete();
        $this->assertFalse($DB->record_exists('facetoface_facilitator', array('id' => $sitewidefacilitator->id)));
        $this->assertTrue($DB->record_exists('facetoface_facilitator', array('id' => $customfacilitator->id)));
        $sessiondate1 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid1), '*', MUST_EXIST);
        $sessiondate2 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid2), '*', MUST_EXIST);
        $this->assertCount(0, $DB->get_records('facetoface_facilitator_dates', array('facilitatorid' => $sitewidefacilitator->id)));
        $this->assertCount(1, $DB->get_records('facetoface_facilitator_dates', array('facilitatorid' => $customfacilitator->id)));
        $this->assertFalse($fs->file_exists_by_hash($sitefile->get_pathnamehash()));
        $this->assertTrue($fs->file_exists_by_hash($customfile->get_pathnamehash()));

        // Second delete should do nothing.
        $facilitator->delete();
    }

    /**
     * Test facilitator availability functions.
     *
     * NOTE: this is a bit simplified because there is only one facilitator per date,
     *       the reason is this test is kept in sync with room tests.
     */
    public function test_facetoface_available_facilitators() {
        global $DB;

        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewidefacilitator1 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewidefacilitator2 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewidefacilitator3 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewidefacilitator4 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewidefacilitator5 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 5', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewidefacilitator6 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 6', 'allowconflicts' => 1, 'hidden' => 1));
        $customfacilitator1 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 1', 'allowconflicts' => 0));
        $customfacilitator2 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 2', 'allowconflicts' => 0));
        $customfacilitator3 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user2->id, 'name' => 'Custom facilitator 3', 'allowconflicts' => 0));
        $customfacilitator4 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 4', 'allowconflicts' => 1));
        $customfacilitator5 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 5', 'allowconflicts' => 1));
        $customfacilitator6 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user2->id, 'name' => 'Custom facilitator 6', 'allowconflicts' => 1));
        $allfacilitators = $DB->get_records('facetoface_facilitator', array());

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewidefacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewidefacilitator1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewidefacilitator2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customfacilitator1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customfacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customfacilitator4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewidefacilitator4->id);
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $seminarevent11 = new seminar_event($sessionid1_1);

        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => array()));
        $seminarevent12 = new seminar_event($sessionid1_2);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 30), $now + (DAYSECS * 31), $sitewidefacilitator1->id);
        $sessionid1_3 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $seminarevent13 = new seminar_event($sessionid1_3);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customfacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewidefacilitator4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));
        $seminarevent21 = new seminar_event($sessionid2_1);

        $this->setUser(null);
        $tempevent = new seminar_event();

        // Get all site facilitators that are not hidden.

        $facilitators = facilitator_list::get_available(0, 0, new seminar_event());
        $this->assertCount(4, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, new seminar_event()));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, new seminar_event()));
            }
        }

        // Get available site facilitators for given slot.
        $facilitators = facilitator_list::get_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), new seminar_event());
        $this->assertCount(4, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), new seminar_event()));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), new seminar_event()));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event());
        $this->assertCount(3, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event()));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event()));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), new seminar_event());
        $this->assertCount(2, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), new seminar_event()));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), new seminar_event()));
            }
        }

        // Specify only seminar id such as when adding new session.
        $tempevent->set_facetoface($facetoface1->id);
        $facilitators = facilitator_list::get_available(0, 0, $tempevent);
        $this->assertCount(7, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, $seminarevent));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, $seminarevent));
            }
        }

        $tempevent->set_facetoface($facetoface2->id);
        $facilitators = facilitator_list::get_available(0, 0, $tempevent);
        $this->assertCount(5, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface2->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, $seminarevent));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, $seminarevent));
            }
        }

        $tempevent->set_facetoface($facetoface1->id);
        $facilitators = facilitator_list::get_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $tempevent);
        $this->assertCount(7, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $tempevent);
        $this->assertCount(6, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $tempevent);
        $this->assertCount(5, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $tempevent);
        $this->assertCount(3, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent));
            }
        }

        $tempevent->set_facetoface($facetoface2->id);
        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $tempevent);
        $this->assertCount(2, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface2->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent));
            }
        }

        // Specify seminar id and session id such as when adding updating session.
        $facilitators = facilitator_list::get_available(0, 0, $seminarevent11);
        $this->assertCount(8, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator3->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, $seminarevent11));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, $seminarevent11));
            }
        }

        $facilitators = facilitator_list::get_available(0, 0, $seminarevent12);
        $this->assertCount(7, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, $seminarevent12));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, $seminarevent12));
            }
        }

        $facilitators = facilitator_list::get_available(0, 0, $seminarevent21);
        $this->assertCount(5, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, $seminarevent21));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, $seminarevent21));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent11);
        $this->assertCount(8, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator3->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent11));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent11));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent13);
        $this->assertCount(6, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent13));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent13));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent12);
        $this->assertCount(7, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent12));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent12));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11);
        $this->assertCount(8, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator3->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));

        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent12);
        $this->assertCount(6, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent12));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent12));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent11);
        $this->assertCount(8, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator3->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent11));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent11));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent12);
        $this->assertCount(5, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent12));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent12));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent11);
        $this->assertCount(7, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator3->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));

        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent11));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent11));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent12);
        $this->assertCount(3, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent12));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent12));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent21);
        $this->assertCount(2, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent21));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent21));
            }
        }

        // Now with user.

        $this->setUser($user1);

        $facilitators = facilitator_list::get_available(0, 0, new seminar_event());
        $this->assertCount(6, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, new seminar_event()));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, new seminar_event()));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), new seminar_event());
        $this->assertCount(6, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), new seminar_event()));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), new seminar_event()));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), new seminar_event());
        $this->assertCount(4, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), new seminar_event()));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), new seminar_event()));
            }
        }

        $facilitators = facilitator_list::get_available(0, 0, $seminarevent11);
        $this->assertCount(10, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator3->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, $seminarevent11));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, $seminarevent11));
            }
        }

        $facilitators = facilitator_list::get_available(0, 0, $seminarevent12);
        $this->assertCount(9, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, $seminarevent12));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, $seminarevent12));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent11);
        $this->assertCount(9, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator3->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent11));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent11));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent12);
        $this->assertCount(5, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent12));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent12));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent21);
        $this->assertCount(4, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent21));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent21));
            }
        }

        // Test slot must have size.
        $facilitators = facilitator_list::get_available(2, 1, new seminar_event());
        $this->assertDebuggingCalled();
        $this->assertCount(6, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
    }

    /**
     * Advanced facilitator availability test with multiple facilitators.
     */
    public function test_facetoface_available_facilitators_multiple() {
        global $DB;

        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewidefacilitator1 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewidefacilitator2 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewidefacilitator3 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewidefacilitator4 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewidefacilitator5 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 5', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewidefacilitator6 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 6', 'allowconflicts' => 1, 'hidden' => 1));
        $customfacilitator1 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 1', 'allowconflicts' => 0));
        $customfacilitator2 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 2', 'allowconflicts' => 0));
        $customfacilitator3 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user2->id, 'name' => 'Custom facilitator 3', 'allowconflicts' => 0));
        $customfacilitator4 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 4', 'allowconflicts' => 1));
        $customfacilitator5 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 5', 'allowconflicts' => 1));
        $customfacilitator6 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user2->id, 'name' => 'Custom facilitator 6', 'allowconflicts' => 1));
        $allfacilitators = $DB->get_records('facetoface_facilitator', array());

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewidefacilitator3->id, $sitewidefacilitator1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewidefacilitator1->id, $sitewidefacilitator2->id, $customfacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewidefacilitator2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customfacilitator1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customfacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customfacilitator4->id, $customfacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10));
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $seminarevent11 = new seminar_event($sessionid1_1);

        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => array()));
        $seminarevent12 = new seminar_event($sessionid1_2);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 30), $now + (DAYSECS * 31), $sitewidefacilitator1->id, $sitewidefacilitator6->id);
        $sessionid1_3 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $seminarevent13 = new seminar_event($sessionid1_3);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customfacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewidefacilitator4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));
        $seminarevent21 = new seminar_event($sessionid2_1);

        $tempevent = new seminar_event();
        $this->setUser(null);

        $facilitators = facilitator_list::get_available(0, 0, new seminar_event());
        $this->assertCount(4, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, new seminar_event()));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, new seminar_event()));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event());
        $this->assertCount(2, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event()));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event()));
            }
        }

        $tempevent->set_facetoface($facetoface1->id);
        $facilitators = facilitator_list::get_available(0, 0, $tempevent);
        $this->assertCount(7, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, $seminarevent));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, $seminarevent));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $tempevent);
        $this->assertCount(4, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent));
            }
        }

        $facilitators = facilitator_list::get_available(0, 0, $seminarevent11);
        $this->assertCount(8, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator3->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, $seminarevent11));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, $seminarevent11));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11);
        $this->assertCount(8, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator3->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));

        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11));
            }
        }

        $this->setUser($user1);

        $facilitators = facilitator_list::get_available(0, 0, new seminar_event());
        $this->assertCount(6, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, new seminar_event));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, new seminar_event()));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event());
        $this->assertCount(4, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event()));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event()));
            }
        }

        $tempevent->set_facetoface($facetoface1->id);
        $facilitators = facilitator_list::get_available(0, 0, $tempevent);
        $this->assertCount(9, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, $seminarevent));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, $seminarevent));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $tempevent);
        $this->assertCount(6, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent));
            }
        }

        $facilitators = facilitator_list::get_available(0, 0, $seminarevent11);
        $this->assertCount(10, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator3->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available(0, 0, $seminarevent11));
            } else {
                $this->assertFalse($facilitator->is_available(0, 0, $seminarevent11));
            }
        }

        $facilitators = facilitator_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11);
        $this->assertCount(10, $facilitators);
        $this->assertTrue($facilitators->contains($sitewidefacilitator1->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator2->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator3->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator4->id));
        $this->assertTrue($facilitators->contains($sitewidefacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator1->id));
        $this->assertTrue($facilitators->contains($customfacilitator2->id));
        $this->assertTrue($facilitators->contains($customfacilitator3->id));
        $this->assertTrue($facilitators->contains($customfacilitator5->id));
        $this->assertTrue($facilitators->contains($customfacilitator4->id));
        foreach ($allfacilitators as $facilitator) {
            $facilitator = new facilitator($facilitator->id);
            if ($facilitators->contains($facilitator->get_id())) {
                $this->assertTrue($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11));
            } else {
                $this->assertFalse($facilitator->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11));
            }
        }
    }

    public function test_facetoface_facilitator_has_conflicts() {
        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewidefacilitator1 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewidefacilitator2 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewidefacilitator3 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewidefacilitator4 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewidefacilitator5 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 5', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewidefacilitator6 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 6', 'allowconflicts' => 1, 'hidden' => 1));
        $customfacilitator1 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 1', 'allowconflicts' => 0));
        $customfacilitator2 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 2', 'allowconflicts' => 0));
        $customfacilitator3 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user2->id, 'name' => 'Custom facilitator 3', 'allowconflicts' => 0));
        $customfacilitator4 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 4', 'allowconflicts' => 1));
        $customfacilitator5 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 5', 'allowconflicts' => 1));
        $customfacilitator6 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user2->id, 'name' => 'Custom facilitator 6', 'allowconflicts' => 1));

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewidefacilitator1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewidefacilitator2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewidefacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewidefacilitator4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customfacilitator1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customfacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customfacilitator4->id);
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $seminarevent11 = new seminar_event($sessionid1_1);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 3), $sitewidefacilitator1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2.5), $now + (DAYSECS * 4.5), $sitewidefacilitator2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -3), $now + (DAYSECS * -1.5), $sitewidefacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 4), $now + (DAYSECS * 7), $customfacilitator4->id);
        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $seminarevent12 = new seminar_event($sessionid1_2);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewidefacilitator4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5.5), $now + (DAYSECS * 5.6), $customfacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 8), $now + (DAYSECS * 9), $customfacilitator4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));
        $seminarevent21 = new seminar_event($sessionid2_1);

        $this->assertTrue((new facilitator($sitewidefacilitator1->id))->has_conflicts());
        $this->assertTrue((new facilitator($sitewidefacilitator2->id))->has_conflicts());
        $this->assertTrue((new facilitator($sitewidefacilitator3->id))->has_conflicts());
        $this->assertTrue((new facilitator($sitewidefacilitator4->id))->has_conflicts());
        $this->assertFalse((new facilitator($sitewidefacilitator5->id))->has_conflicts());
        $this->assertFalse((new facilitator($sitewidefacilitator6->id))->has_conflicts());
        $this->assertFalse((new facilitator($customfacilitator1->id))->has_conflicts());
        $this->assertFalse((new facilitator($customfacilitator2->id))->has_conflicts());
        $this->assertTrue((new facilitator($customfacilitator3->id))->has_conflicts());
        $this->assertFalse((new facilitator($customfacilitator4->id))->has_conflicts());
        $this->assertFalse((new facilitator($customfacilitator5->id))->has_conflicts());
        $this->assertFalse((new facilitator($customfacilitator6->id))->has_conflicts());
    }

    public function test_session_cancellation() {
        global $DB;

        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewidefacilitator1 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewidefacilitator2 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewidefacilitator3 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewidefacilitator4 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewidefacilitator5 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 5', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewidefacilitator6 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 6', 'allowconflicts' => 1, 'hidden' => 1));
        $customfacilitator1 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 1', 'allowconflicts' => 0));
        $customfacilitator2 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 2', 'allowconflicts' => 0));
        $customfacilitator3 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user2->id, 'name' => 'Custom facilitator 3', 'allowconflicts' => 0));
        $customfacilitator4 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 4', 'allowconflicts' => 1));
        $customfacilitator5 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user1->id, 'name' => 'Custom facilitator 5', 'allowconflicts' => 1));
        $customfacilitator6 = $this->facetoface_generator->add_custom_facilitator(array('usercreated' => $user2->id, 'name' => 'Custom facilitator 6', 'allowconflicts' => 1));

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewidefacilitator1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewidefacilitator2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewidefacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewidefacilitator4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customfacilitator1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customfacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customfacilitator4->id);
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $seminarevent11 = new seminar_event($sessionid1_1);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 3), $sitewidefacilitator1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2.5), $now + (DAYSECS * 4.5), $sitewidefacilitator2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -3), $now + (DAYSECS * -1.5), $sitewidefacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 4), $now + (DAYSECS * 7), $customfacilitator4->id);
        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $seminarevent12 = new seminar_event($sessionid1_2);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewidefacilitator4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5.5), $now + (DAYSECS * 5.6), $customfacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 8), $now + (DAYSECS * 9), $customfacilitator4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));
        $seminarevent21 = new seminar_event($sessionid2_1);

        $seminarevent = new \mod_facetoface\seminar_event($sessionid2_1);
        $seminarevent->cancel();
        $dateids = $DB->get_fieldset_select('facetoface_sessions_dates', 'id', "sessionid = :sessionid", array('sessionid' => $sessionid2_1));
        foreach ($dateids as $did) {
            $this->assertTrue($DB->record_exists('facetoface_facilitator_dates', array('sessionsdateid' => $did)));
        }
    }

    /**
     * This method is used to set up the following facilitator_list unit tests.
     *
     * @return array [$facilitators, $seminarevent]
     */
    protected function set_up_facilitator_list_tests() {
        $now = time();

        $sitewidefacilitator1 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewidefacilitator2 = $this->facetoface_generator->add_internal_facilitator(array('name' => 'Site facilitator 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewidefacilitator3 = $this->facetoface_generator->add_internal_facilitator(array('name' => 'Site facilitator 3', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewidefacilitator4 = $this->facetoface_generator->add_site_wide_facilitator(array('name' => 'Site facilitator 4', 'allowconflicts' => 0, 'hidden' => 0));

        $facilitators = [$sitewidefacilitator1->id, $sitewidefacilitator2->id, $sitewidefacilitator3->id, $sitewidefacilitator4->id];

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        // Session 1 has an internal (3) and external (1)
        // Session 2 has both internal (2, 3)
        // Session 3 has external (1)
        // No sessions have (4)
        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $sitewidefacilitator3->id, $sitewidefacilitator1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewidefacilitator2->id, $sitewidefacilitator3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewidefacilitator1->id);
        $sessionid = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $seminarevent = new seminar_event($sessionid);

        return [$facilitators, $seminarevent];
    }

    /**
     * Test facilitator_list::from_seminarevent()
     */
    public function test_facetoface_facilitator_list_from_seminarevent() {
        list($facilitatorids, $seminarevent) = $this->set_up_facilitator_list_tests();

        // The fourth facilitator is not assigned, so unset.
        $unassigned = $facilitatorids[3];
        unset($facilitatorids[3]);

        $facilitator_list = facilitator_list::from_seminarevent($seminarevent->get_id());
        $this->assertCount(count($facilitatorids), $facilitator_list);

        // Check to see that the list has the expected facilitators.
        foreach ($facilitatorids as $id) {
            $this->assertTrue($facilitator_list->contains($id));
        }

        // Check to see that it doesn't have the unassigned facilitator (logically redundant).
        $this->assertFalse($facilitator_list->contains($unassigned));

        // Sanity check the first facilitator.
        $facilitator = $facilitator_list->get($facilitatorids[0]);
        $this->assertEquals('Site facilitator 1', $facilitator->get_name());

        // Now get internal facilitators only.
        // Remove the external facilitator from our expected ids.
        $external = $facilitatorids[0];
        unset($facilitatorids[0]);

        $internal_facilitator_list = facilitator_list::from_seminarevent($seminarevent->get_id(), true);
        $this->assertCount(count($facilitatorids), $internal_facilitator_list);

        // Check to see that the list has the expected facilitators.
        foreach ($facilitatorids as $id) {
            $this->assertTrue($internal_facilitator_list->contains($id));
        }

        // Check to see that it doesn't have the unassigned or external facilitators (logically redundant).
        $this->assertFalse($internal_facilitator_list->contains($unassigned));
        $this->assertFalse($internal_facilitator_list->contains($external));

        // Sanity check the first internal facilitator.
        $facilitator = $internal_facilitator_list->get($facilitatorids[1]);
        $this->assertEquals('Site facilitator 2', $facilitator->get_name());
    }

    /**
     * Test facilitator_list::from_session()
     */
    public function test_facetoface_facilitator_list_from_session() {
        list($facilitatorids, $seminarevent) = $this->set_up_facilitator_list_tests();

        // The first and fourth facilitators will not be assigned, so unset.
        $external = $facilitatorids[0];
        $unassigned = $facilitatorids[3];
        unset($facilitatorids[0]);
        unset($facilitatorids[3]);

        // Get the second session from the event.
        $seminar_sessions = $seminarevent->get_sessions();
        $seminar_sessions->next();
        $session = $seminar_sessions->current();

        $facilitator_list = facilitator_list::from_session($session->get_id());

        $this->assertCount(count($facilitatorids), $facilitator_list);

        // Check to see that the list has the expected facilitators.
        foreach ($facilitatorids as $id) {
            $this->assertTrue($facilitator_list->contains($id));
        }

        // Check to see that it doesn't have the external and unassigned facilitators (logically redundant).
        $this->assertFalse($facilitator_list->contains($external));
        $this->assertFalse($facilitator_list->contains($unassigned));

        // Sanity check the first facilitator.
        $facilitator = $facilitator_list->get($facilitatorids[1]);
        $this->assertEquals('Site facilitator 2', $facilitator->get_name());

        // Now get internal facilitators only from the next (aka first) session of the event. (Session list is in reverse chronological order.)
        $seminar_sessions->next();
        $session = $seminar_sessions->current();

        // Internal facilitator (2) is not assigned to this session.
        $unassigned_internal = $facilitatorids[1];
        unset($facilitatorids[1]);

        $internal_facilitator_list = facilitator_list::from_session($session->get_id(), true);
        $this->assertCount(count($facilitatorids), $internal_facilitator_list);

        // Check to see that the list has the expected facilitators.
        foreach ($facilitatorids as $id) {
            $this->assertTrue($internal_facilitator_list->contains($id));
        }

        // Check to see that it doesn't have the unassigned or external facilitators (logically redundant).
        $this->assertFalse($internal_facilitator_list->contains($external));
        $this->assertFalse($internal_facilitator_list->contains($unassigned));
        $this->assertFalse($internal_facilitator_list->contains($unassigned_internal));

        // Sanity check the first internal facilitator.
        $facilitator = $internal_facilitator_list->get($facilitatorids[2]);
        $this->assertEquals('Site facilitator 3', $facilitator->get_name());
    }

    /**
     * Test facilitator_list::to_ids()
     */
    public function test_facetoface_facilitator_list_to_ids() {
        list($facilitators, $seminarevent) = $this->set_up_facilitator_list_tests();

        $facilitator_list = facilitator_list::from_seminarevent($seminarevent->get_id());

        // Ids are expected to be in ascending order.
        $expected = [$facilitators[0] => $facilitators[0], $facilitators[1] => $facilitators[1], $facilitators[2] => $facilitators[2]];
        $this->assertEquals($expected, $facilitator_list->get_ids());
    }

    /**
     * Prepare a sessiondate object for the generator, from timestamps and 0 or more facilitator ids
     *
     * @param int $timestart
     * @param int $timeend
     * @param null|int $facilitatorid1
     * @param null|int $facilitatorid2
     * @param null|int $facilitatorid3
     * @return stdClass
     */
    protected function prepare_date($timestart, $timeend, $facilitatorid1 = null, $facilitatorid2 = null, $facilitatorid3 = null) {
        $facilitatorids = array();
        if ($facilitatorid1) {
            $facilitatorids[] = $facilitatorid1;
        }
        if ($facilitatorid2) {
            $facilitatorids[] = $facilitatorid2;
        }
        if ($facilitatorid3) {
            $facilitatorids[] = $facilitatorid3;
        }
        $sessiondate = new stdClass();
        $sessiondate->timestart = (string)$timestart;
        $sessiondate->timefinish = (string)$timeend;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->facilitatorids = $facilitatorids;
        return $sessiondate;
    }

}