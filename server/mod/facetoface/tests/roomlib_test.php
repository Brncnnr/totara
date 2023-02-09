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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_facetoface
 */

/*
 * Unit tests for mod/facetoface/room/lib.php functions.
 */

use mod_facetoface\room;
use mod_facetoface\room_helper;
use mod_facetoface\room_virtualmeeting;
use mod_facetoface\seminar_event;
use mod_facetoface\signup;
use mod_facetoface\signup\state\booked;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

class mod_facetoface_roomlib_testcase extends advanced_testcase {

    /** @var \mod_facetoface\testing\generator */
    protected $facetoface_generator;

    /** @var \totara_customfield\testing\generator */
    protected $customfield_generator;

    private $cfprefix = 'facetofaceroom', $cftableprefix = 'facetoface_room';

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

    public function test_facetoface_get_room() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

        $sitewideroom = $this->facetoface_generator->add_site_wide_room(array());
        customfield_load_data($sitewideroom, 'facetofaceroom', 'facetoface_room');

        $customroom = $this->facetoface_generator->add_custom_room(array());
        customfield_load_data($customroom, 'facetofaceroom', 'facetoface_room');

        $rooms = new \mod_facetoface\room_list(''); // Create a list containing all rooms.
        $this->assertCount(2, $rooms);

        $room = new \mod_facetoface\room($sitewideroom->id);
        $this->assertEquals($sitewideroom->id, $room->get_id());
        $this->assertEquals($sitewideroom->name, $room->get_name());
        $this->assertEquals((boolean) $sitewideroom->custom, $room->get_custom());
        $this->assertEquals((boolean) $sitewideroom->hidden, $room->get_hidden());
        $this->assertEquals($sitewideroom->capacity, $room->get_capacity());
        $this->assertEquals((boolean) $sitewideroom->allowconflicts, $room->get_allowconflicts());

        $room = new \mod_facetoface\room($customroom->id);
        $this->assertEquals($customroom->id, $room->get_id());
        $this->assertEquals($customroom->name, $room->get_name());
        $this->assertEquals((boolean) $customroom->custom, $room->get_custom());
        $this->assertEquals((boolean) $customroom->hidden, $room->get_hidden());
        $this->assertEquals($customroom->capacity, $room->get_capacity());
        $this->assertEquals((boolean) $customroom->allowconflicts, $room->get_allowconflicts());

        $room = new \mod_facetoface\room();
        $this->assertFalse($room->exists());

        $room = new \mod_facetoface\room(0);
        $this->assertFalse($room->exists());

        $room = new \mod_facetoface\room(-1);
        $this->assertFalse($room->exists());
    }

    public function test_facetoface_get_used_rooms() {
        $now = time();

        $sitewideroom1 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site x 1'));
        $sitewideroom2 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site a 2'));
        $sitewideroom3 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site b 3'));
        $customroom1 = $this->facetoface_generator->add_custom_room(array('name' => 'Custom 1'));
        $customroom2 = $this->facetoface_generator->add_custom_room(array('name' => 'Custom 2'));
        $customroom3 = $this->facetoface_generator->add_custom_room(array('name' => 'Custom 3'));

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom1->id);
        $sessionid1_1 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom3->id);
        $sessionid1_2 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom2->id);
        $sessionid2_1 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));

        $rooms = \mod_facetoface\room_list::get_seminar_rooms($facetoface1->id);
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertEquals(4, $rooms->count(), 'unexpected amount of rooms used in seminar');
    }

    public function test_facetoface_get_session_rooms() {
        $now = time();

        $sitewideroom1 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site x 1'));
        customfield_load_data($sitewideroom1, 'facetofaceroom', 'facetoface_room');
        $sitewideroom2 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site a 2'));
        customfield_load_data($sitewideroom2, 'facetofaceroom', 'facetoface_room');
        $sitewideroom3 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site b 3'));
        customfield_load_data($sitewideroom3, 'facetofaceroom', 'facetoface_room');
        $customroom1 = $this->facetoface_generator->add_custom_room(array('name' => 'Custom 1'));
        customfield_load_data($customroom1, 'facetofaceroom', 'facetoface_room');
        $customroom2 = $this->facetoface_generator->add_custom_room(array('name' => 'Custom 2'));
        customfield_load_data($customroom2, 'facetofaceroom', 'facetoface_room');
        $customroom3 = $this->facetoface_generator->add_custom_room(array('name' => 'Custom 3'));
        customfield_load_data($customroom3, 'facetofaceroom', 'facetoface_room');

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom1->id);
        $sessionid1_1 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom3->id);
        $sessionid1_2 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom2->id);
        $sessionid2_1 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));

        $rooms = \mod_facetoface\room_list::get_event_rooms($sessionid1_1);
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
    }

    /**
     * Basic tests for room deletes.
     */
    public function test_facetoface_delete_room() {
        global $DB;

        $fs = get_file_storage();
        $syscontext = context_system::instance();

        $sitewideroom = $this->facetoface_generator->add_site_wide_room(array());
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'mod_facetoface',
            'filearea' => 'room',
            'itemid' => $sitewideroom->id,
            'filepath' => '/',
            'filename' => 'xx.jpg',
        );
        $sitefile = $fs->create_file_from_string($filerecord, 'xx');

        $customroom = $this->facetoface_generator->add_custom_room(array());
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'mod_facetoface',
            'filearea' => 'room',
            'itemid' => $customroom->id,
            'filepath' => '/',
            'filename' => 'xx.jpg',
        );
        $customfile = $fs->create_file_from_string($filerecord, 'xx');

        $this->assertCount(2, $DB->get_records('facetoface_room', array()));

        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondate1 = new stdClass();
        $sessiondate1->timestart = time() + (DAYSECS * 1);
        $sessiondate1->timefinish = $sessiondate1->timestart + (DAYSECS * 1);
        $sessiondate1->sessiontimezone = '99';
        $sessiondate1->roomids = [$sitewideroom->id];
        $sessionid1 = $this->facetoface_generator->add_session(array(
            'facetoface' => $facetoface->id,
            'sessiondates' => array($sessiondate1)
        ));
        $rooms = \mod_facetoface\room_list::get_event_rooms($sessionid1);// from_session($sessionid1);
        $this->assertSame((int) $sitewideroom->id, $rooms->get($sitewideroom->id)->get_id());

        $sessiondate2 = new stdClass();
        $sessiondate2->timestart = time() + (DAYSECS * 2);
        $sessiondate2->timefinish = $sessiondate2->timestart + (DAYSECS * 2);
        $sessiondate2->sessiontimezone = '99';
        $sessiondate2->roomids = [$customroom->id];
        $sessionid2 = $this->facetoface_generator->add_session(array(
            'facetoface' => $facetoface->id,
            'sessiondates' => array($sessiondate2)
        ));
        $rooms = \mod_facetoface\room_list::get_event_rooms($sessionid2);// from_session($sessionid2);
        $this->assertSame((int) $customroom->id, $rooms->get($customroom->id)->get_id());

        $room = new room($sitewideroom->id);
        $room->delete();
        $this->assertFalse($DB->record_exists('facetoface_room', array('id' => $sitewideroom->id)));
        $this->assertFalse($DB->record_exists('facetoface_room_dates', array('roomid' => $sitewideroom->id)));
        $this->assertTrue($DB->record_exists('facetoface_room', array('id' => $customroom->id)));
        $this->assertFalse($fs->file_exists_by_hash($sitefile->get_pathnamehash()));

        $rooms = \mod_facetoface\room_list::get_event_rooms($sessionid2);// from_session($sessionid2);
        $this->assertSame((int) $customroom->id, $rooms->get($customroom->id)->get_id());
        $this->assertTrue($fs->file_exists_by_hash($customfile->get_pathnamehash()));
    }

    /**
     * This is the most basic test to make sure that customfields are deleted
     * when a room is deleted via room_delete().
     */
    public function test_facetoface_delete_room_customfield_text() {
        global $DB;

        $sitewideroom = $this->facetoface_generator->add_site_wide_room(array());
        // Create a room customfield, text type.
        $roomcftextids = $this->customfield_generator->create_text($this->cftableprefix, array('fullname' => 'roomcftext'));
        // Add some text to it.
        $this->customfield_generator->set_text($sitewideroom, $roomcftextids['roomcftext'], 'Some test text', $this->cfprefix,
            $this->cftableprefix);
        $cfdata = customfield_get_data($sitewideroom, $this->cftableprefix, $this->cfprefix);
        $this->assertEquals('Some test text', $cfdata['roomcftext']);
        $this->assertEquals(1, $DB->count_records('facetoface_room_info_data', array('facetofaceroomid' => $sitewideroom->id)));

        $room = new room($sitewideroom->id);
        $room->delete();

        // We'll make sure the site-wide room was definitely deleted.
        $this->assertEquals(0, $DB->count_records('facetoface_room', array('id' => $sitewideroom->id)));

        //Get the customfield data again after deletion.
        $cfdata = customfield_get_data($sitewideroom, $this->cftableprefix, $this->cfprefix);
        $this->assertEmpty($cfdata);
        $this->assertEquals(0, $DB->count_records('facetoface_room_info_data', array('facetofaceroomid' => $sitewideroom->id)));
    }

    /**
     * Tests that room_delete also gets rid of files records when
     * deleting custom fields.
     */
    public function test_facetoface_delete_room_customfield_file() {
        global $DB;

        // Create both a site-wide and custom room.
        $sitewideroom = $this->facetoface_generator->add_site_wide_room(array());
        $customroom = $this->facetoface_generator->add_custom_room(array());

        // The file handing used by functions during this test requires $USER to be set.
        $this->setAdminUser();

        // Create a file custom field.
        $roomcffileids = $this->customfield_generator->create_file($this->cftableprefix, array('roomcffile' => array()));
        $roomcffileid = $roomcffileids['roomcffile'];

        // Create several files.
        $itemid1 = 1;
        $filename = 'testfile1.txt';
        $filecontent = 'Test file content';
        $testfile1 = $this->customfield_generator->create_test_file_from_content($filename, $filecontent, $itemid1);

        $itemid2 = 2;
        $filename = 'testfile1.txt';
        $filecontent = 'Test file content';
        $testfile1copy = $this->customfield_generator->create_test_file_from_content($filename, $filecontent, $itemid2);
        $filename = 'testfile2.txt';
        $filecontent = 'Other test file content';
        $testfile2 = $this->customfield_generator->create_test_file_from_content($filename, $filecontent, $itemid2);

        // Add $testfile1 only to the $sitewideroom.
        $this->customfield_generator->set_file($sitewideroom, $roomcffileid, $itemid1, $this->cfprefix, $this->cftableprefix);
        // Add both $testfile1 and $testfile2 to the $customroom.
        $this->customfield_generator->set_file($customroom, $roomcffileid, $itemid2, $this->cfprefix, $this->cftableprefix);
        //$this->customfield_generator->set_file($customroom, $roomcffileid, $testfile2, $this->cfprefix, $this->cftableprefix);

        $infodata_sitewide_cffile = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $sitewideroom->id, 'fieldid' => $roomcffileid));
        $this->assertNotEmpty($infodata_sitewide_cffile);
        // Sitewide should now have testfile1 but not testfile2.
        $this->assertEquals(1, $DB->count_records('files',
            array(
                'filearea' => 'facetofaceroom_filemgr',
                'filename' => 'testfile1.txt',
                'itemid' => $infodata_sitewide_cffile->id
            )));
        $this->assertEquals(0, $DB->count_records('files',
            array(
                'filearea' => 'facetofaceroom_filemgr',
                'filename' => 'testfile2.txt',
                'itemid' => $infodata_sitewide_cffile->id
            )));

        $infodata_custom_cffile = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $customroom->id, 'fieldid' => $roomcffileid));
        $this->assertNotEmpty($infodata_custom_cffile);
        // Sitewide should now have both testfile1 and testfile2.
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile1.txt', 'itemid' => $infodata_custom_cffile->id)));
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile2.txt', 'itemid' => $infodata_custom_cffile->id)));

        $room = new room($sitewideroom->id);
        $room->delete();

        // We'll make sure the site-wide room was definitely deleted and the custom room wasn't.
        $this->assertEquals(0, $DB->count_records('facetoface_room', array('id' => $sitewideroom->id)));
        $this->assertEquals(1, $DB->count_records('facetoface_room', array('id' => $customroom->id)));

        // We don't want to overwrite the original $infodata_sitewide_cffile object because we want to use
        // it's id value for the next check.
        $infodata_sitewide_cffile_again = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $sitewideroom->id, 'fieldid' => $roomcffileid));
        $this->assertEmpty($infodata_sitewide_cffile_again);
        // There should be no files left with the id from the info_data record.
        $this->assertEquals(0, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'itemid' => $infodata_sitewide_cffile->id)));

        // Nothing should have changed for the custom room values.
        $infodata_custom_cffile = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $customroom->id, 'fieldid' => $roomcffileid));
        $this->assertNotEmpty($infodata_custom_cffile);
        // Sitewide should now have both testfile1 and testfile2.
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile1.txt', 'itemid' => $infodata_custom_cffile->id)));
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile2.txt', 'itemid' => $infodata_custom_cffile->id)));

        // Now we get rid of the custom room to make sure nothing about it being custom prevents deletion of custom files.
        $room = new room($customroom->id);
        $room->delete();
        $infodata_custom_cffile_again = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $customroom->id, 'fieldid' => $roomcffileid));
        $this->assertEmpty($infodata_custom_cffile_again);
        // There should be no files left with the id from the info_data record.
        $this->assertEquals(0, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'itemid' => $infodata_custom_cffile->id)));
    }

    /**
     * Tests the that the deletion of rooms will also delete custom field data when there
     * are several types in use.
     */
    public function test_facetoface_delete_room_customfield_mixed() {
        global $DB;

        // Create both a site-wide and custom room.
        $sitewideroom = $this->facetoface_generator->add_site_wide_room(array());
        $customroom = $this->facetoface_generator->add_custom_room(array());

        // The file handing used by functions during this test requires $USER to be set.
        $this->setAdminUser();

        // Create various custom fields, including datetime, file and text types.
        $roomcffileids = $this->customfield_generator->create_file($this->cftableprefix, array('roomcffile' => array()));
        $roomcffileid = $roomcffileids['roomcffile'];

        // Create a text custom field.
        $roomcftextids = $this->customfield_generator->create_text($this->cftableprefix, array('fullname' => 'roomcftext'));
        $roomcftextid = $roomcftextids['roomcftext'];

        $roomcfdateids = $this->customfield_generator->create_datetime($this->cftableprefix, array('roomcfdate' => array()));
        $roomcfdateid = $roomcfdateids['roomcfdate'];

        // Add data to the rooms for each custom field type.

        // Create several files.
        $itemid1 = 1;
        $filename = 'testfile1.txt';
        $filecontent = 'Test file content';
        $testfile1 = $this->customfield_generator->create_test_file_from_content($filename, $filecontent, $itemid1);

        $itemid2 = 2;
        $filename = 'testfile1.txt';
        $filecontent = 'Test file content';
        $testfile1copy = $this->customfield_generator->create_test_file_from_content($filename, $filecontent, $itemid2);
        $filename = 'testfile2.txt';
        $filecontent = 'Other test file content';
        $testfile2 = $this->customfield_generator->create_test_file_from_content($filename, $filecontent, $itemid2);

        // Add $testfile1 only to the $sitewideroom.
        $this->customfield_generator->set_file($sitewideroom, $roomcffileid, $itemid1, $this->cfprefix, $this->cftableprefix);
        // Add both $testfile1 and $testfile2 to the $customroom.
        $this->customfield_generator->set_file($customroom, $roomcffileid, $itemid2, $this->cfprefix, $this->cftableprefix);
        //$this->customfield_generator->set_file($customroom, $roomcffileid, $testfile2, $this->cfprefix, $this->cftableprefix);

        $this->customfield_generator->set_text($sitewideroom, $roomcftextid, 'Here is some text', $this->cfprefix,
            $this->cftableprefix);
        $this->customfield_generator->set_text($customroom, $roomcftextid, 'Some other text', $this->cfprefix,
            $this->cftableprefix);

        $sitewidedate = 1000000;
        $customdate = 200000000;
        $this->customfield_generator->set_datetime($sitewideroom, $roomcfdateid, $sitewidedate, $this->cfprefix,
            $this->cftableprefix);
        $this->customfield_generator->set_datetime($customroom, $roomcfdateid, $customdate, $this->cfprefix, $this->cftableprefix);

        // Check all the data is as expecting before deleting any room.
        $infodata_sitewide_cffile = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $sitewideroom->id, 'fieldid' => $roomcffileid));
        $this->assertNotEmpty($infodata_sitewide_cffile);
        // Sitewide should now have testfile1 but not testfile2.
        $this->assertEquals(1, $DB->count_records('files',
            array(
                'filearea' => 'facetofaceroom_filemgr',
                'filename' => 'testfile1.txt',
                'itemid' => $infodata_sitewide_cffile->id
            )));
        $this->assertEquals(0, $DB->count_records('files',
            array(
                'filearea' => 'facetofaceroom_filemgr',
                'filename' => 'testfile2.txt',
                'itemid' => $infodata_sitewide_cffile->id
            )));

        $infodata_custom_cffile = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $customroom->id, 'fieldid' => $roomcffileid));
        $this->assertNotEmpty($infodata_custom_cffile);
        // Sitewide should now have both testfile1 and testfile2.
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile1.txt', 'itemid' => $infodata_custom_cffile->id)));
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile2.txt', 'itemid' => $infodata_custom_cffile->id)));

        // We can't currently test files with whats returned from customfield_get_data, but we can text the others.
        $cfdata = customfield_get_data($sitewideroom, $this->cftableprefix, $this->cfprefix);

        $this->assertEquals('Here is some text', $cfdata['roomcftext']);
        $this->assertEquals(3, $DB->count_records('facetoface_room_info_data', array('facetofaceroomid' => $sitewideroom->id)));
        $this->assertEquals(userdate($sitewidedate, get_string('strftimedaydatetime', 'langconfig')), $cfdata['roomcfdate']);

        $cfdata = customfield_get_data($customroom, $this->cftableprefix, $this->cfprefix);

        $this->assertEquals('Some other text', $cfdata['roomcftext']);
        $this->assertEquals(3, $DB->count_records('facetoface_room_info_data', array('facetofaceroomid' => $customroom->id)));
        $this->assertEquals(userdate($customdate, get_string('strftimedaydatetime', 'langconfig')), $cfdata['roomcfdate']);

        // Now we'll delete the custom room.
        $room = new room($customroom->id);
        $room->delete();

        // We'll make sure the custom room was definitely deleted and the site-wide room wasn't.
        $this->assertEquals(1, $DB->count_records('facetoface_room', array('id' => $sitewideroom->id)));
        $this->assertEquals(0, $DB->count_records('facetoface_room', array('id' => $customroom->id)));

        // Let's check the files first.

        $infodata_sitewide_cffile = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $sitewideroom->id, 'fieldid' => $roomcffileid));
        $this->assertNotEmpty($infodata_sitewide_cffile);
        $this->assertEquals(1, $DB->count_records('files',
            array(
                'filearea' => 'facetofaceroom_filemgr',
                'filename' => 'testfile1.txt',
                'itemid' => $infodata_sitewide_cffile->id
            )));

        // Nothing should have changed for the custom room values.
        $infodata_custom_cffile_again = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $customroom->id, 'fieldid' => $roomcffileid));
        $this->assertEmpty($infodata_custom_cffile_again);
        // There should be no files left with the id from the info_data record.
        $this->assertEquals(0, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'itemid' => $infodata_custom_cffile->id)));

        // Now the rest of the fields.
        $cfdata = customfield_get_data($sitewideroom, $this->cftableprefix, $this->cfprefix);

        $this->assertEquals('Here is some text', $cfdata['roomcftext']);
        $this->assertEquals(3, $DB->count_records('facetoface_room_info_data', array('facetofaceroomid' => $sitewideroom->id)));
        $this->assertEquals(userdate($sitewidedate, get_string('strftimedaydatetime', 'langconfig')), $cfdata['roomcfdate']);

        $cfdata = customfield_get_data($customroom, $this->cftableprefix, $this->cfprefix);

        $this->assertEmpty($cfdata);
        $this->assertEquals(0, $DB->count_records('facetoface_room_info_data', array('facetofaceroomid' => $customroom->id)));
    }

    /**
     * Test room availability functions.
     */
    public function test_facetoface_available_rooms() {
        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewideroom1 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideroom2 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideroom3 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewideroom4 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideroom5 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 5', 'allowconflicts' => 1, 'hidden' => 0));
        $customroom1 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user1->id,
            'name' => 'Custom room 1',
            'allowconflicts' => 0
        ));
        $customroom2 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user1->id,
            'name' => 'Custom room 2',
            'allowconflicts' => 0
        ));
        $customroom3 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user2->id,
            'name' => 'Custom room 3',
            'allowconflicts' => 0
        ));
        $customroom4 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user1->id,
            'name' => 'Custom room 4',
            'allowconflicts' => 1
        ));
        $customroom5 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user1->id,
            'name' => 'Custom room 5',
            'allowconflicts' => 1
        ));
        $allrooms = new \mod_facetoface\room_list(''); // Create a list containing all rooms.

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewideroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customroom4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom4->id);
        $sessionid1_1 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $event11 = new \mod_facetoface\seminar_event($sessionid1_1);

        $sessionid1_2 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => array()));
        $event12 = new \mod_facetoface\seminar_event($sessionid1_2);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 30), $now + (DAYSECS * 31), $sitewideroom1->id);
        $sessionid1_3 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $event13 = new \mod_facetoface\seminar_event($sessionid1_3);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom4->id);
        $sessionid2_1 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));
        $event21 = new \mod_facetoface\seminar_event($sessionid2_1);

        $this->setUser(null);

        // Set up some empty events for the tests.
        $event00 = new \mod_facetoface\seminar_event();
        $event10 = new \mod_facetoface\seminar_event();
        $event10->set_facetoface($facetoface1->id);
        $event20 = new \mod_facetoface\seminar_event();
        $event20->set_facetoface($facetoface2->id);

        // Get all site rooms that are not hidden.
        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event00);
        $this->assertEquals(4, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event00));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event00));
            }
        }

        // Get available site rooms for given slot.
        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00);
        $this->assertEquals(4, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event00);
        $this->assertEquals(3, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event00));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event00));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event00);
        $this->assertEquals(2, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event00));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event00));
            }
        }

        // Specify only seminar id such as when adding new session.
        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event10);
        $this->assertEquals(7, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event10));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event10));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event20);
        $this->assertEquals(5, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event20));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event20));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event10);
        $this->assertEquals(7, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event10));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event10));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event10);
        $this->assertEquals(6, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event10));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event10));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event10);
        $this->assertEquals(5, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event10));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event10));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event10);
        $this->assertEquals(3, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event10));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event10));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event20);
        $this->assertEquals(2, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event20));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event20));
            }
        }

        // Specify seminar id and session id such as when adding updating session.
        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event11);
        $this->assertEquals(8, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event11));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event12);
        $this->assertEquals(7, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event12));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event21);
        $this->assertEquals(5, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event21));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event21));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event11);
        $this->assertEquals(8, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event11));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event13);
        $this->assertEquals(6, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event13));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event13));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event12);
        $this->assertEquals(7, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event12));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event11);
        $this->assertEquals(8, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event11));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event12);
        $this->assertEquals(6, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event12));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event11);
        $this->assertEquals(8, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event11));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event12);
        $this->assertEquals(5, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event12));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11);
        $this->assertEquals(7, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event12);
        $this->assertEquals(3, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event12));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event21);
        $this->assertEquals(2, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event21));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event21));
            }
        }

        // Now with user.
        $this->setUser($user1);

        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event00);
        $this->assertEquals(6, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00);
        $this->assertEquals(6, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event00);
        $this->assertEquals(4, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event00));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event00));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event11);
        $this->assertEquals(10, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event11));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event12);
        $this->assertEquals(9, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event12));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11);
        $this->assertEquals(9, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event12);
        $this->assertEquals(5, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event12));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event21);
        $this->assertEquals(4, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            /** @var room $room */
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event21));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event21));
            }
        }

        // The fields can no longer be specified, make sure it contains all the important ones.
        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11);
        $this->assertEquals(9, $rooms->count());
        foreach ($rooms as $room) {
            $this->assertInstanceOf('\mod_facetoface\room', $room);
            $this->assertObjectHasAttribute('id', $room);
            $this->assertObjectHasAttribute('name', $room);
            $this->assertObjectHasAttribute('hidden', $room);
            $this->assertObjectHasAttribute('custom', $room);
            $this->assertObjectHasAttribute('capacity', $room);
            $this->assertObjectHasAttribute('allowconflicts', $room);
        }

        // Test slot must have size.
        $rooms = \mod_facetoface\room_list::get_available_rooms(2, 1, $event00);
        $this->assertDebuggingCalled();
        $this->assertEquals(6, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom5->id));
    }

    public function test_facetoface_room_has_conflicts() {
        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewideroom1 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideroom2 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideroom3 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewideroom4 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideroom5 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 5', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideroom6 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 6', 'allowconflicts' => 1, 'hidden' => 1));
        $customroom1 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user1->id,
            'name' => 'Custom room 1',
            'allowconflicts' => 0
        ));
        $customroom2 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user1->id,
            'name' => 'Custom room 2',
            'allowconflicts' => 0
        ));
        $customroom3 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user2->id,
            'name' => 'Custom room 3',
            'allowconflicts' => 0
        ));
        $customroom4 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user1->id,
            'name' => 'Custom room 4',
            'allowconflicts' => 1
        ));
        $customroom5 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user1->id,
            'name' => 'Custom room 5',
            'allowconflicts' => 1
        ));
        $customroom6 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user2->id,
            'name' => 'Custom room 6',
            'allowconflicts' => 1
        ));

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewideroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customroom4->id);
        $sessionid1_1 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 3), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2.5), $now + (DAYSECS * 4.5), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -3), $now + (DAYSECS * -1.5), $sitewideroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 4), $now + (DAYSECS * 7), $customroom4->id);
        $sessionid1_2 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5.5), $now + (DAYSECS * 5.6), $customroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 8), $now + (DAYSECS * 9), $customroom4->id);
        $sessionid2_1 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));

        $room = new \mod_facetoface\room();

        $room->from_record($sitewideroom1);
        $this->assertTrue($room->has_conflicts());
        $room->from_record($sitewideroom2);
        $this->assertTrue($room->has_conflicts());
        $room->from_record($sitewideroom3);
        $this->assertTrue($room->has_conflicts());
        $room->from_record($sitewideroom4);
        $this->assertTrue($room->has_conflicts());
        $room->from_record($sitewideroom5);
        $this->assertFalse($room->has_conflicts());
        $room->from_record($sitewideroom6);
        $this->assertFalse($room->has_conflicts());

        $room->from_record($customroom1);
        $this->assertFalse($room->has_conflicts());
        $room->from_record($customroom2);
        $this->assertFalse($room->has_conflicts());
        $room->from_record($customroom3);
        $this->assertTrue($room->has_conflicts());
        $room->from_record($customroom4);
        $this->assertFalse($room->has_conflicts());
        $room->from_record($customroom5);
        $this->assertFalse($room->has_conflicts());
        $room->from_record($customroom6);
        $this->assertFalse($room->has_conflicts());
    }

    public function test_session_cancellation() {
        global $DB;

        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewideroom1 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideroom2 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideroom3 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewideroom4 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideroom5 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 5', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideroom6 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 6', 'allowconflicts' => 1, 'hidden' => 1));
        $customroom1 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user1->id,
            'name' => 'Custom room 1',
            'allowconflicts' => 0
        ));
        $customroom2 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user1->id,
            'name' => 'Custom room 2',
            'allowconflicts' => 0
        ));
        $customroom3 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user2->id,
            'name' => 'Custom room 3',
            'allowconflicts' => 0
        ));
        $customroom4 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user1->id,
            'name' => 'Custom room 4',
            'allowconflicts' => 1
        ));
        $customroom5 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user1->id,
            'name' => 'Custom room 5',
            'allowconflicts' => 1
        ));
        $customroom6 = $this->facetoface_generator->add_custom_room(array(
            'usercreated' => $user2->id,
            'name' => 'Custom room 6',
            'allowconflicts' => 1
        ));

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewideroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customroom4->id);
        $sessionid1_1 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 3), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2.5), $now + (DAYSECS * 4.5), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -3), $now + (DAYSECS * -1.5), $sitewideroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 4), $now + (DAYSECS * 7), $customroom4->id);
        $sessionid1_2 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5.5), $now + (DAYSECS * 5.6), $customroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 8), $now + (DAYSECS * 9), $customroom4->id);
        $sessionid2_1 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));

        $dateids = $DB->get_fieldset_select('facetoface_sessions_dates', 'id', "sessionid = :sessionid",
            array('sessionid' => $sessionid2_1));
        foreach ($dateids as $did) {
            $this->assertTrue($DB->record_exists('facetoface_room_dates', array('sessionsdateid' => $did)));
        }
        $seminarevent = new \mod_facetoface\seminar_event($sessionid2_1);
        $seminarevent->cancel();
        $dateids = $DB->get_fieldset_select('facetoface_sessions_dates', 'id', "sessionid = :sessionid",
            array('sessionid' => $sessionid2_1));
        foreach ($dateids as $did) {
            $this->assertTrue($DB->record_exists('facetoface_room_dates', array('sessionsdateid' => $did)));
        }
    }

    /**
     * test: false && (false || false)
     */
    public function test_show_joinnow_button_1() {
        $now = time();

        $testroom = $this->facetoface_generator->add_site_wide_room(array());
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $testroom->id);
        $seminareventid =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => $sessiondates));
        $seminarevent = new \mod_facetoface\seminar_event($seminareventid);
        // Test
        foreach ($seminarevent->get_sessions() as $session) {
            // time is not right, no attendee booked, no trainer
            $this->assertFalse(\mod_facetoface\room_helper::show_joinnow($seminarevent, $session, null, $now));
        }
    }

    /**
     * test: true && (false || false)
     */
    public function test_show_joinnow_button_2() {
        $now = time();
        $testroom = $this->facetoface_generator->add_site_wide_room(array());
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        // Time start: current time + 10 min, finish time: current time + 2 hours
        $sessiondates[] = $this->prepare_date($now + (MINSECS * 10), $now + (HOURSECS * 2), $testroom->id);
        $seminareventid =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => $sessiondates));
        $seminarevent = new \mod_facetoface\seminar_event($seminareventid);
        // Test
        foreach ($seminarevent->get_sessions() as $session) {
            // time is right, no attendee booked, no trainer
            $this->assertFalse(\mod_facetoface\room_helper::show_joinnow($seminarevent, $session, null, $now));
        }
    }

    /**
     * test: true && (true || false)
     */
    public function test_show_joinnow_button_3() {
        $now = time();
        $testroom = $this->facetoface_generator->add_site_wide_room(array());
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        // Time start: current time + 10 min, finish time: current time + 2 hours
        $sessiondates[] = $this->prepare_date($now + (MINSECS * 10), $now + (HOURSECS * 2), $testroom->id);
        $seminareventid =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => $sessiondates));
        $seminarevent = new \mod_facetoface\seminar_event($seminareventid);

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $seminarevent->get_seminar()->get_course(), 'student');
        $signup = signup::create($user->id, $seminarevent);
        $signup->save();
        $signup->switch_state(booked::class);
        // Login as a student
        $this->setUser($user);
        // Test
        foreach ($seminarevent->get_sessions() as $session) {
            // time is right, attendee booked, no trainer
            $this->assertTrue(\mod_facetoface\room_helper::show_joinnow($seminarevent, $session, null, $now));
            $this->assertTrue(\mod_facetoface\room_helper::show_joinnow($seminarevent, $session, $signup, $now));
        }
    }

    /**
     * test: true && (false || true)
     */
    public function test_show_joinnow_button_4() {
        global $DB;

        $now = time();
        $testroom = $this->facetoface_generator->add_site_wide_room(array());
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        // Time start: current time + 10 min, finish time: current time + 2 hours
        $sessiondates[] = $this->prepare_date($now + (MINSECS * 10), $now + (HOURSECS * 2), $testroom->id);
        $seminareventid =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => $sessiondates));
        $seminarevent = new \mod_facetoface\seminar_event($seminareventid);

        // Create teacher.
        set_config('facetoface_session_roles', '4');
        $trainer = $this->getDataGenerator()->create_user();
        $trainerrole = $DB->get_record('role', ['shortname' => 'teacher']);

        $DB->set_field('facetoface', 'approvalrole', $trainerrole->id, ['id' => $facetoface->id]);
        $DB->insert_record('facetoface_session_roles',
            (object)['sessionid' => $seminarevent->get_id(), 'roleid' => $trainerrole->id, 'userid' => $trainer->id]);

        $context = context_course::instance($seminarevent->get_seminar()->get_course());
        $this->getDataGenerator()->role_assign($trainerrole->id, $trainer->id, $context->id);
        $this->getDataGenerator()->enrol_user($trainer->id, $seminarevent->get_seminar()->get_course(), 'editingteacher');
        // Login as a trainer
        $this->setUser($trainer);
        // Test
        foreach ($seminarevent->get_sessions() as $session) {
            // time is right, no attendee booked, yes trainer
            $this->assertTrue(\mod_facetoface\room_helper::show_joinnow($seminarevent, $session, null, $now));
        }
    }

    /**
     * test: false && (true || false)
     */
    public function test_show_joinnow_button_5() {
        $now = time();
        $testroom = $this->facetoface_generator->add_site_wide_room(array());
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $testroom->id);
        $seminareventid =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => $sessiondates));

        $seminarevent = new \mod_facetoface\seminar_event($seminareventid);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $seminarevent->get_seminar()->get_course(), 'student');
        $signup = signup::create($user->id, $seminarevent);
        $signup->save();
        $signup->switch_state(booked::class);
        // Login as a student
        $this->setUser($user);
        // Test
        foreach ($seminarevent->get_sessions() as $session) {
            // time is not right, attendee booked, no trainer
            $this->assertFalse(\mod_facetoface\room_helper::show_joinnow($seminarevent, $session, null, $now));
            $this->assertFalse(\mod_facetoface\room_helper::show_joinnow($seminarevent, $session, $signup, $now));
        }
    }

    /**
     * test: false && (false || true)
     */
    public function test_show_joinnow_button_6() {
        global $DB;

        $now = time();
        $testroom = $this->facetoface_generator->add_site_wide_room(array());
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $testroom->id);
        $seminareventid =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => $sessiondates));
        $seminarevent = new \mod_facetoface\seminar_event($seminareventid);

        // Create teacher.
        set_config('facetoface_session_roles', '4');
        $trainer = $this->getDataGenerator()->create_user();
        $trainerrole = $DB->get_record('role', ['shortname' => 'teacher']);

        $DB->set_field('facetoface', 'approvalrole', $trainerrole->id, ['id' => $facetoface->id]);
        $DB->insert_record('facetoface_session_roles',
            (object)['sessionid' => $seminarevent->get_id(), 'roleid' => $trainerrole->id, 'userid' => $trainer->id]);

        $context = context_course::instance($seminarevent->get_seminar()->get_course());
        $this->getDataGenerator()->role_assign($trainerrole->id, $trainer->id, $context->id);
        $this->getDataGenerator()->enrol_user($trainer->id, $seminarevent->get_seminar()->get_course(), 'editingteacher');
        // Login as a trainer
        $this->setUser($trainer);
        // Test
        foreach ($seminarevent->get_sessions() as $session) {
            // time is not right, no attendee booked, yes trainer
            $this->assertFalse(\mod_facetoface\room_helper::show_joinnow($seminarevent, $session, null, $now));
        }
    }

    /**
     * Test the visibility of the 'join now' button on a cancelled event.
     */
    public function test_show_joinnow_button_7_far_future(): void {
        $ts = DAYSECS * 1;
        $tf = DAYSECS * 2;
        [$trainer, $learner, $seminarevent, $session, $now] = $this->set_show_joinnow_button_7_data($ts, $tf);

        $this->assert_show_joinnow_button_7($trainer, $learner, $seminarevent, $session, $now, [false, false, false, false]);
    }

    /**
     * Test the visibility of the 'join now' button on a cancelled event.
     */
    public function test_show_joinnow_button_7_near_future(): void {
        [$trainer, $learner, $seminarevent, $session, $now] = $this->set_show_joinnow_button_7_data();

        $this->assert_show_joinnow_button_7($trainer, $learner, $seminarevent, $session, $now, [true, true, false, false]);
    }

    /**
     * Test the visibility of the 'join now' button on a cancelled event.
     */
    public function test_show_joinnow_button_7_ongoing(): void {
        [$trainer, $learner, $seminarevent, $session, $now] = $this->set_show_joinnow_button_7_data();

        // Set ongoing session
        /** var seminar_session $session */
        $session->set_timestart($now + (HOURSECS * -1));
        $session->set_timefinish($now + (HOURSECS * 1));
        $session->save();

        $this->assert_show_joinnow_button_7($trainer, $learner, $seminarevent, $session, $now, [true, true, false, false]);
    }

    /**
     * Test the visibility of the 'join now' button on a cancelled event.
     */
    public function test_show_joinnow_button_7_past(): void {
        [$trainer, $learner, $seminarevent, $session, $now] = $this->set_show_joinnow_button_7_data();

        // Set past session
        /** var seminar_session $session */
        $session->set_timestart($now + (DAYSECS * -2));
        $session->set_timefinish($now + (DAYSECS * -1));
        $session->save();

        $this->assert_show_joinnow_button_7($trainer, $learner, $seminarevent, $session, $now, [false, false, false, false]);
    }

    private function set_show_joinnow_button_7_data(int $timestart = 0, int $timefinish = 0) {
        $DB = \core\orm\query\sql\sql::get_db();

        $now = time();
        $testroom = $this->facetoface_generator->add_site_wide_room([]);
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->facetoface_generator->create_instance(['course' => $course->id]);

        $timestart = $timestart ? ($now + $timestart) : ($now + (MINSECS * 10));
        $timefinish = $timefinish ? ($now + $timefinish) : ($now + (HOURSECS * 2));

        $sessiondates = [];
        $sessiondates[] = $this->prepare_date($timestart, $timefinish, $testroom->id);
        $seminareventid =
            $this->facetoface_generator->add_session(['facetoface' => $facetoface->id, 'sessiondates' => $sessiondates]);
        $seminarevent = new seminar_event($seminareventid);
        /** @var mod_facetoface\seminar_session */
        $session = $seminarevent->get_sessions()->current();

        $learner = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($learner->id, $course->id, 'student');
        $signup = signup::create($learner->id, $seminarevent);
        $signup->save();
        $signup->switch_state(booked::class);

        // Create teacher.
        set_config('facetoface_session_roles', '4');
        $trainer = $this->getDataGenerator()->create_user();
        $trainerrole = $DB->get_record('role', ['shortname' => 'teacher']);

        $DB->set_field('facetoface', 'approvalrole', $trainerrole->id, ['id' => $facetoface->id]);
        $DB->insert_record('facetoface_session_roles',
            (object)['sessionid' => $seminarevent->get_id(), 'roleid' => $trainerrole->id, 'userid' => $trainer->id]);

        $context = context_course::instance($course->id);
        $this->getDataGenerator()->role_assign($trainerrole->id, $trainer->id, $context->id);
        $this->getDataGenerator()->enrol_user($trainer->id, $course->id, 'editingteacher');

        return [$trainer, $learner, $seminarevent, $session, $now];
    }

    private function assert_show_joinnow_button_7($trainer, $learner, $seminarevent, $session, $now, $expression) {
        $this->setUser($trainer);
        $this->assertEquals($expression[0], room_helper::show_joinnow($seminarevent, $session, null, $now));
        $this->setUser($learner);
        $this->assertEquals($expression[1], room_helper::show_joinnow($seminarevent, $session, null, $now));
        // Cancel the event.
        // NOTE: we can't cancel the past event through the UI
        // room_helper::show_joinnow will return correct value if it is a past event or cancelled event.
        if (!$seminarevent->is_over()) {
            $seminarevent->set_cancelledstatus(1)->save();
        }
        $this->setUser($trainer);
        $this->assertEquals($expression[2], room_helper::show_joinnow($seminarevent, $session, null, $now));
        $this->setUser($learner);
        $this->assertEquals($expression[3], room_helper::show_joinnow($seminarevent, $session, null, $now));
    }

    /**
     * Test room availability for a new event with the same dates as the cancelled event
     */
    public function test_session_cancellation_2() {

        $now = time();

        $room1 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 1', 'allowconflicts' => 0, 'hidden' => 0));
        $room2 =
            $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 2', 'allowconflicts' => 0, 'hidden' => 0));

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $timestart1 = $now + (DAYSECS * 1);
        $timefinish1 = $now + (DAYSECS * 2);

        $timestart2 = $now + (DAYSECS * 2);
        $timefinish2 = $now + (DAYSECS * 3);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($timestart1, $timefinish1, $room1->id);
        $sessiondates[] = $this->prepare_date($timestart2, $timefinish2, $room2->id);
        $sessionid_1 =
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $seminarevent1 = new \mod_facetoface\seminar_event($sessionid_1);
        $seminarevent1->cancel();

        $sessionid_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id));
        $seminarevent2 = new \mod_facetoface\seminar_event($sessionid_2);

        $room1 = new \mod_facetoface\room($room1->id);
        $room2 = new \mod_facetoface\room($room2->id);

        $this->assertTrue($room1->is_available($timestart1, $timefinish1, $seminarevent2));
        $this->assertTrue($room2->is_available($timestart2, $timefinish2, $seminarevent2));
    }

    /**
     * Create room_virtualmeeting record
     */
    public static function create_room_virtualmeeting(int $roomid, int $userid): room_virtualmeeting {

        $virtual_meeting = new room_virtualmeeting();
        $virtual_meeting->set_plugin('msteams')->set_roomid($roomid)->set_userid($userid);
        $virtual_meeting->save();

        return $virtual_meeting;
    }

    public function data_save_virtual_room() {
        return [
            'empty' => ['', ''], // empty should acts as none
            'none' => [room_virtualmeeting::VIRTUAL_MEETING_NONE, ''],
            'custom' => [room_virtualmeeting::VIRTUAL_MEETING_INTERNAL, 'https://example.com'],
        ];
    }

    /**
     * @var string $plugin
     * @var string $url
     * @dataProvider data_save_virtual_room
     * @covers mod_facetoface\room_helper::save
     */
    public function test_save_virtual_room(string $plugin, string $url) {
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        foreach ([false, true] as $notcustom) {
            $room = room_helper::save((object)[
                'id' => 0,
                'name' => sprintf('Virtual room %s #%d', $plugin, (int)$notcustom),
                'roomcapacity' => 11,
                'allowconflicts' => 0,
                'plugin' => $plugin,
                'url' => 'https://example.com',
                'notcustom' => $notcustom,
                'description_editor' => ['text' => '', 'itemid' => 0, 'format' => FORMAT_HTML],
            ]);
            $this->assertTrue($room->exists());
            $this->assertNotEquals($notcustom, $room->get_custom());
            $this->assertEquals($url, $room->get_url());
        }
    }

    /**
     * @group virtualmeeting
     * @covers mod_facetoface\room_helper::save
     */
    public function test_save_virtualmeeting() {
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $room1 = room_helper::save((object)[
            'id' => 0,
            'name' => 'Virtual meeting #0',
            'roomcapacity' => 11,
            'allowconflicts' => 0,
            'plugin' => 'poc_app',
            'url' => '',
            'notcustom' => false,
            'description_editor' => ['text' => '', 'itemid' => 0, 'format' => FORMAT_HTML],
        ]);
        $this->assertTrue($room1->exists());
        $this->assertTrue($room1->get_custom());

        try {
            $room1 = room_helper::save((object)[
                'id' => 0,
                'name' => 'Virtual meeting #1',
                'roomcapacity' => 11,
                'allowconflicts' => 0,
                'plugin' => 'poc_app',
                'url' => '',
                'notcustom' => true,
                'description_editor' => ['text' => '', 'itemid' => 0, 'format' => FORMAT_HTML],
            ]);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('you cannot create a site-wide virtual meeting!', $ex->getMessage());
        }
    }

    /**
     * Test room_helper::save() api to test converting room from internal to none
     * @group virtualmeeting
     */
    public function test_update_internal_room_to_none() {

        $data = new \stdClass();
        $data->id = 0;
        $data->name = 'Internal room';
        $data->roomcapacity = floor(rand(5, 50));
        $data->allowconflicts = 0;
        $data->plugin = \mod_facetoface\room_virtualmeeting::VIRTUAL_MEETING_INTERNAL;
        $data->url = 'https://example.com/totara/room/id/14151267';
        $data->notcustom = false;
        $data->description_editor = ['text' => '', 'itemid' => 0, 'format' => FORMAT_HTML];

        $record = \mod_facetoface\room_helper::save($data);
        $room = new \mod_facetoface\room($record->get_id());

        $this->assertEquals($data->name, $room->get_name());
        $this->assertEquals($data->url, $room->get_url());
        $this->assertNotEmpty($room->get_url());

        // Let's update the room
        $data = new \stdClass();
        $data->id = $room->get_id();
        $data->name = 'None room';
        $data->roomcapacity = floor(rand(5, 50));
        $data->allowconflicts = 0;
        $data->plugin = \mod_facetoface\room_virtualmeeting::VIRTUAL_MEETING_NONE;
        $data->url = 'https://example.com/totara/room/id/14151267'; // Ooops we forgot to remove the url!?
        $data->notcustom = false;
        $data->description_editor = ['text' => '', 'itemid' => 0, 'format' => FORMAT_HTML];

        $record = \mod_facetoface\room_helper::save($data);
        $room = new \mod_facetoface\room($record->get_id());

        $this->assertEquals($data->name, $room->get_name());
        $this->assertEmpty($room->get_url());
    }

    protected function prepare_date($timestart, $timeend, $roomid) {
        $sessiondate = new stdClass();
        $sessiondate->timestart = (string)$timestart;
        $sessiondate->timefinish = (string)$timeend;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->roomids = [$roomid];
        return $sessiondate;
    }
}
