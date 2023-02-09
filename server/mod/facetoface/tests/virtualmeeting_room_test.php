<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_facetoface
 */

use core\entity\user;
use core\orm\query\builder;
use mod_facetoface\room;
use mod_facetoface\room_list;
use mod_facetoface\room_virtualmeeting;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\room_virtualmeeting_list;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_session;
use totara_core\entity\virtual_meeting as virtual_meeting_entity;
use totara_core\http\clients\simple_mock_client;
use totara_core\virtualmeeting\virtual_meeting as virtual_meeting_model;
use mod_facetoface\task\manage_virtualmeetings_adhoc_task as adhoc_task;

defined('MOODLE_INTERNAL') || die();

/**
 * @group virtualmeeting
 * @covers mod_facetoface\room_virtualmeeting
 * @covers mod_facetoface\room_dates_virtualmeeting
 */
class mod_facetoface_virtualmeeting_room_testcase extends advanced_testcase {

    public function test_room_virtualmeeting() {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user(['username' => 'alice']);
        $this->setUser($user1);
        /** @var \mod_facetoface\testing\generator */
        $seminar_generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $virtual_room = $seminar_generator->add_virtualmeeting_room(['name' => 'virtual']);

        // Test with a created virtualmeeting room
        $room_virtualmeeting = room_virtualmeeting::from_roomid($virtual_room->id);
        $this->assertNotEquals(0, $room_virtualmeeting->get_id());
        $this->assertEquals($virtual_room->id, $room_virtualmeeting->get_roomid());
        $this->assertEquals('poc_app', $room_virtualmeeting->get_plugin());
        $this->assertEquals($user1->id, $room_virtualmeeting->get_userid());
        unset($room_virtualmeeting);

        // Test with a new room (no virtualmeeting record yet)
        $custom_room = $seminar_generator->add_custom_room(['name' => 'physical']);
        $room_virtualmeeting = room_virtualmeeting::from_roomid($custom_room->id);
        $this->assertEquals(0, $room_virtualmeeting->get_id());
        $this->assertEquals(0, $room_virtualmeeting->get_roomid());
        $this->assertEquals('', $room_virtualmeeting->get_plugin());
        $this->assertEquals(0, $room_virtualmeeting->get_userid());

        // Set it up and save it
        $room_virtualmeeting->set_roomid($custom_room->id);
        $room_virtualmeeting->set_plugin('poc_app');
        $room_virtualmeeting->set_userid($user1->id);
        $room_virtualmeeting->save();
        $this->assertNotEquals(0, $room_virtualmeeting->get_id());
        $this->assertEquals($custom_room->id, $room_virtualmeeting->get_roomid());
        $this->assertEquals('poc_app', $room_virtualmeeting->get_plugin());
        $this->assertEquals($user1->id, $room_virtualmeeting->get_userid());
        unset($room_virtualmeeting);

        // Load it again
        $room_virtualmeeting = room_virtualmeeting::from_roomid($custom_room->id);
        $this->assertNotEquals(0, $room_virtualmeeting->get_id());
        $this->assertEquals($custom_room->id, $room_virtualmeeting->get_roomid());
        $this->assertEquals('poc_app', $room_virtualmeeting->get_plugin());
        $this->assertEquals($user1->id, $room_virtualmeeting->get_userid());
        unset($room_virtualmeeting);

        $room_virtualmeetings = $DB->get_records('facetoface_room_virtualmeeting');
        $this->assertCount(2, $room_virtualmeetings);

        // Delete by roomid
        room_virtualmeeting::delete_by_roomid($custom_room->id);
        $room_virtualmeetings = $DB->get_records('facetoface_room_virtualmeeting');
        $this->assertCount(1, $room_virtualmeetings);
        $room_virtualmeeting = room_virtualmeeting::from_roomid($custom_room->id);
        $this->assertEquals(0, $room_virtualmeeting->get_id());
        unset($room_virtualmeeting);

        // Delete method
        $room_virtualmeeting = room_virtualmeeting::from_roomid($virtual_room->id);
        $this->assertNotEquals(0, $room_virtualmeeting->get_id());
        $room_virtualmeeting->delete();
        $room_virtualmeetings = $DB->get_records('facetoface_room_virtualmeeting');
        $this->assertCount(0, $room_virtualmeetings);
    }

    public function test_room_dates_virtualmeeting() {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $seminar = new seminar();
        $seminar->set_course($course->id)->save();
        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($seminar->get_id())->save();

        // Add virtualmeeting #1
        [$roomdateid1, $roomdatevmid1, $virtualmeetingid1] = $this->add_session_with_virtualmeeting($seminarevent, new DateTime('tomorrow 6am'), new DateTime('tomorrow 9am'));

        // Add virtualmeeting #2
        [$roomdateid2, $roomdatevmid2, $virtualmeetingid2] = $this->add_session_with_virtualmeeting($seminarevent, new DateTime('tomorrow 12pm'), new DateTime('tomorrow 3pm'));

        // Add virtualmeeting #3
        [$roomdateid3, $roomdatevmid3, $virtualmeetingid3] = $this->add_session_with_virtualmeeting($seminarevent, new DateTime('tomorrow 6pm'), new DateTime('tomorrow 9pm'));

        $this->assertEquals(3, builder::table('facetoface_room_dates')->count());
        $this->assertEquals(3, builder::table(room_dates_virtualmeeting::DBTABLE)->count());

        // Delete by roomdateid1
        room_dates_virtualmeeting::delete_by_roomdateid($roomdateid1);
        $this->assertEqualsCanonicalizing([$roomdatevmid2, $roomdatevmid3], array_keys(builder::table(room_dates_virtualmeeting::DBTABLE)->fetch()));

        // Delete by virtualmeetingid1
        room_dates_virtualmeeting::delete_by_virtualmeetingid($virtualmeetingid1);
        $this->assertEquals(2, builder::table(room_dates_virtualmeeting::DBTABLE)->count());

        // Delete by virtualmeetingid2
        room_dates_virtualmeeting::delete_by_virtualmeetingid($virtualmeetingid2);
        $this->assertEquals($roomdatevmid3, builder::table(room_dates_virtualmeeting::DBTABLE)->one(true)->id);

        // Delete method
        $room_dates_virtualmeeting = new room_dates_virtualmeeting($roomdatevmid3);
        $room_dates_virtualmeeting->delete();
        $this->assertFalse(builder::table(room_dates_virtualmeeting::DBTABLE)->exists());
        $this->assertEquals(3, virtual_meeting_entity::repository()->count());
    }

    public function test_room_virtualmeeting_list() {
        $user1 = $this->getDataGenerator()->create_user(['username' => 'alice']);
        $this->setUser($user1);
        /** @var \mod_facetoface\testing\generator */
        $seminar_generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $virtual_room1 = $seminar_generator->add_virtualmeeting_room(['name' => 'virtual one']);
        $virtual_room2 = $seminar_generator->add_virtualmeeting_room(['name' => 'virtual two']);
        $virtual_room3 = $seminar_generator->add_virtualmeeting_room(['name' => 'virtual three']);
        $custom_room = $seminar_generator->add_custom_room(['name' => 'custom']);
        $sitewide_room = $seminar_generator->add_site_wide_room(['name' => 'sitewide']);

        $virtualmeeting_list = new room_virtualmeeting_list(''); // Get all records
        $this->assertCount(3, $virtualmeeting_list);
        $virtualmeeting_list = room_virtualmeeting_list::from_roomids([$sitewide_room->id, $virtual_room1->id, $virtual_room2->id, $custom_room->id]);
        $this->assertCount(2, $virtualmeeting_list);
        foreach($virtualmeeting_list as $virtualmeeting_room) {
            /** @var room_virtualmeeting $virtualmeeting_room */
            if ($virtual_room1->id == $virtualmeeting_room->get_roomid()) {
                $this->assertEquals($virtual_room1->id, $virtualmeeting_room->get_roomid());
                $this->assertEquals('poc_app', $virtualmeeting_room->get_plugin());
                $this->assertEquals($user1->id, $virtualmeeting_room->get_userid());
            } else {
                $this->assertNotEquals(0, $virtualmeeting_room->get_id());
                $this->assertEquals($virtual_room2->id, $virtualmeeting_room->get_roomid());
                $this->assertEquals('poc_app', $virtualmeeting_room->get_plugin());
                $this->assertEquals($user1->id, $virtualmeeting_room->get_userid());
            }
        }
    }

    /**
     * Test to make sure user cannot update an another user's virtual meeting room
     */
    public function test_can_manage(){

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user(['deleted' => 1]);
        $this->setUser($user2);
        /** @var \mod_facetoface\testing\generator */
        $seminar_generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $customroom = $seminar_generator->add_virtualmeeting_room(['name' => 'virtual', 'url' => 'link', 'usercreated' => $user2->id], ['userid' => $user2->id]);
        $customroom2 = $seminar_generator->add_custom_room(['name' => 'casual', 'usercreated' => $user2->id]);
        $room = new \mod_facetoface\room($customroom->id);
        $room2 = new \mod_facetoface\room($customroom2->id);

        // Anyone can create virtualmeeting
        $this->setAdminUser();
        $can_manage = (new room_virtualmeeting())->can_manage($user1->id);
        $this->assertTrue($can_manage);
        // Unless they are deleted
        $can_manage = (new room_virtualmeeting())->can_manage($user3->id);
        $this->assertFalse($can_manage);
        // Or they do not exist
        $can_manage = (new room_virtualmeeting())->can_manage(-42);
        $this->assertFalse($can_manage);

        // Non-creator cannot update virtualmeeting
        $this->setUser($user1);
        $can_manage = room_virtualmeeting::get_virtual_meeting($room)->can_manage();
        $this->assertFalse($can_manage);

        // And can update casual room
        $this->setUser($user2);
        $can_manage = room_virtualmeeting::from_roomid($customroom2->id)->can_manage($user1->id);
        $this->assertTrue($can_manage);

        // Creator can update virtualmeeting
        $can_manage = room_virtualmeeting::get_virtual_meeting($room)->can_manage();
        $this->assertTrue($can_manage);

        // And can update casual room
        $this->setUser($user1);
        $can_manage = room_virtualmeeting::from_roomid($customroom2->id)->can_manage($user2->id);
        $this->assertTrue($can_manage);

        // Deleted user cannot update virtualmeeting
        $this->setAdminUser();
        $can_manage = room_virtualmeeting::get_virtual_meeting($room)->can_manage($user3->id);
        $this->assertFalse($can_manage);
        $can_manage = room_virtualmeeting::from_roomid($customroom2->id)->can_manage($user3->id);
        $this->assertFalse($can_manage);

        // Non-existent user cannot update virtualmeeting
        $can_manage = room_virtualmeeting::get_virtual_meeting($room)->can_manage(0);
        $this->assertFalse($can_manage);
        $can_manage = room_virtualmeeting::from_roomid($customroom2->id)->can_manage(-42);
        $this->assertFalse($can_manage);
    }

    public function test_is_virtual_meeting() {
        $this->assertFalse(room_virtualmeeting::is_virtual_meeting('@none'), '@none');
        $this->assertFalse(room_virtualmeeting::is_virtual_meeting('@internal'), '@internal');
        $this->assertTrue(room_virtualmeeting::is_virtual_meeting(''), '(empty)');
        $this->assertTrue(room_virtualmeeting::is_virtual_meeting('poc_app'), 'poc_app');
        $this->assertTrue(room_virtualmeeting::is_virtual_meeting('he_who_must_not_be_named'), 'he_who_must_not_be_named');
    }

    /**
     * test room_dates_virtualmeeting::load_all_by_room()
     */
    public function test_load_all_by_room() {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $seminar = new seminar();
        $seminar->set_course($course->id)->save();
        $seminarevent = new seminar_event();
        $seminarevent->set_facetoface($seminar->get_id())->save();

        [$roomdateid1, $roomdatevmid1, $virtualmeetingid1] = $this->add_session_with_virtualmeeting($seminarevent, new DateTime('tomorrow 6am'), new DateTime('tomorrow 9am'));
        [$roomdateid2, $roomdatevmid2, $virtualmeetingid2] = $this->add_session_with_virtualmeeting($seminarevent, new DateTime('tomorrow 12pm'), new DateTime('tomorrow 3pm'));
        [$roomdateid3, $roomdatevmid3, $virtualmeetingid3] = $this->add_session_with_virtualmeeting($seminarevent, new DateTime('tomorrow 6pm'), new DateTime('tomorrow 9pm'));

        $this->assertEquals(3, builder::table('facetoface_room_dates')->count());
        $this->assertEquals(3, builder::table(room_dates_virtualmeeting::DBTABLE)->count());

        $task = adhoc_task::create_from_seminar_event_id($seminarevent->get_id());
        $task->execute();

        $this->assertEquals(3, builder::table(room_dates_virtualmeeting::DBTABLE)->count());

        $roomdatevmids = [$roomdatevmid1, $roomdatevmid2, $roomdatevmid3];
        $virtualmeetingids = [$virtualmeetingid1, $virtualmeetingid2, $virtualmeetingid3];
        $rooms = room_list::get_event_rooms($seminarevent->get_id());
        foreach ($rooms as $room) {
            $room_dates_virtual_meetings = room_dates_virtualmeeting::load_all_by_room($room);
            foreach ($room_dates_virtual_meetings as $i => $room_dates_virtualmeeting) {
                $this->assertTrue(in_array($i, $roomdatevmids));
                /** @var room_dates_virtualmeeting $room_dates_virtualmeeting */
                $this->assertTrue($room_dates_virtualmeeting->exists());
                $this->assertTrue(in_array($room_dates_virtualmeeting->get_id(), $roomdatevmids));
                $this->assertTrue(in_array($room_dates_virtualmeeting->get_virtualmeetingid(), $virtualmeetingids));
                $this->assertNotEmpty($room_dates_virtualmeeting->get_sessionsdateid());
                // No way to get a proper status, maybe someday in the future.
                $this->assertNull($room_dates_virtualmeeting->get_status());
            }
        }

        $seminar_generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $room4 = (new room())->from_record($seminar_generator->add_custom_room(['name' => 'custom']));
        $seminarsession = new seminar_session();
        $timestart = new DateTime('tomorrow 10am');
        $timefinish = new DateTime('tomorrow 11am');
        $seminarsession->set_sessionid($seminarevent->get_id())->set_timestart($timestart->getTimestamp())->set_timefinish($timefinish->getTimestamp())->set_sessiontimezone('99')->save();
        $roomdateid = builder::table('facetoface_room_dates')->insert(['sessionsdateid' => $seminarsession->get_id(), 'roomid' => $room4->get_id()]);
        $room_dates_virtual_meetings = room_dates_virtualmeeting::load_all_by_room($room4);
        $this->assertEmpty($room_dates_virtual_meetings);

        $room5 = (new room())->from_record($seminar_generator->add_site_wide_room(['name' => 'sitewide']));
        $seminarsession = new seminar_session();
        $timestart = new DateTime('tomorrow 4pm');
        $timefinish = new DateTime('tomorrow 5pm');
        $seminarsession->set_sessionid($seminarevent->get_id())->set_timestart($timestart->getTimestamp())->set_timefinish($timefinish->getTimestamp())->set_sessiontimezone('99')->save();
        $roomdateid = builder::table('facetoface_room_dates')->insert(['sessionsdateid' => $seminarsession->get_id(), 'roomid' => $room5->get_id()]);
        $room_dates_virtual_meetings = room_dates_virtualmeeting::load_all_by_room($room5);
        $this->assertEmpty($room_dates_virtual_meetings);
    }

    /**
     * @param seminar_event $seminarevent
     * @param DateTime $timestart
     * @param DateTime $timefinish
     * @return array of [roomdate_id, roomdate_virtualmeeting_id, virtualmeeting_id]
     */
    private function add_session_with_virtualmeeting(seminar_event $seminarevent, DateTime $timestart, DateTime $timefinish): array {
        /** @var mod_facetoface_generator */
        $seminar_generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        // Set it up and save it
        $seminarsession = new seminar_session();
        $seminarsession->set_sessionid($seminarevent->get_id())->set_timestart($timestart->getTimestamp())->set_timefinish($timefinish->getTimestamp())->set_sessiontimezone('99')->save();
        $room = (new room())->from_record($seminar_generator->add_virtualmeeting_room([], ['plugin' => 'poc_app']));
        $virtualmeeting = virtual_meeting_model::create('poc_app', user::logged_in(), 'Test virtual meeting', $timestart, $timefinish, new simple_mock_client());
        $room_dates_virtualmeeting = new room_dates_virtualmeeting();
        $room_dates_virtualmeeting->set_roomid($room->get_id());
        $room_dates_virtualmeeting->set_sessionsdateid($seminarsession->get_id());
        $room_dates_virtualmeeting->set_virtualmeetingid($virtualmeeting->get_id());
        $room_dates_virtualmeeting->save();
        $this->assertNotEquals(0, $room_dates_virtualmeeting->get_id());

        // Load it again
        $rd_id = $room_dates_virtualmeeting->get_id();
        $room_dates_virtualmeeting = new room_dates_virtualmeeting($rd_id);
        $this->assertEquals($rd_id, $room_dates_virtualmeeting->get_id());
        $this->assertEquals($room->get_id(), $room_dates_virtualmeeting->get_roomid());
        $this->assertEquals($seminarsession->get_id(), $room_dates_virtualmeeting->get_sessionsdateid());
        $this->assertEquals($virtualmeeting->get_id(), $room_dates_virtualmeeting->get_virtualmeetingid());

        $roomdateid = builder::table('facetoface_room_dates')->insert(['sessionsdateid' => $seminarsession->get_id(), 'roomid' => $room->get_id()]);
        return [$roomdateid, $room_dates_virtualmeeting->get_id(), $virtualmeeting->get_id()];
    }
}
