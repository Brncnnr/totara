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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 * @category test
 */

use core\entity\user;
use core\orm\query\builder;
use mod_facetoface\event\session_updated;
use mod_facetoface\room;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\room_helper;
use mod_facetoface\room_list;
use mod_facetoface\room_virtualmeeting;
use mod_facetoface\seminar;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\seminar_session;
use mod_facetoface\task\manage_virtualmeetings_adhoc_task;
use mod_facetoface\watcher\virtualmeeting_watcher;
use totara_core\http\clients\simple_mock_client;
use totara_core\virtualmeeting\virtual_meeting as virtual_meeting_model;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

/**
 * @group virtualmeeting
 * @covers mod_facetoface\watcher\virtualmeeting_watcher
 */
class mod_facetoface_virtual_meeting_watcher_testcase extends advanced_testcase {
    public function setUp(): void {
        parent::setUp();
    }

    /**
     * @return seminar
     */
    private function create_seminar(): seminar {
        $course = $this->getDataGenerator()->create_course();
        /** @var mod_facetoface\testing\generator */
        $f2g = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        return new seminar($f2g->create_instance(['course' => $course->id])->id);
    }

    /**
     * @return array of [seminar, future_event, ongoing1_event, ongoing2_event, past_event]
     * - seminar $seminar
     * - seminar_event $future_event with one future session
     * - seminar_event $ongoing1_event with one ongoing session
     * - seminar_event $ongoing2_event with one future session and one past session
     * - seminar_event $past_event with one past session
     */
    private function create_seminar_events(): array {
        $now = time();
        $seminar = $this->create_seminar();
        $future = new seminar_event();
        $future->set_facetoface($seminar->get_id())->set_details('future')->save();
        $session = new seminar_session();
        $session->set_sessionid($future->get_id())->set_timestart($now + DAYSECS)->set_timefinish($now + DAYSECS * 2)->set_sessiontimezone('99')->save();
        $ongoing1 = new seminar_event();
        $ongoing1->set_facetoface($seminar->get_id())->set_details('ongoing1')->save();
        $session = new seminar_session();
        $session->set_sessionid($ongoing1->get_id())->set_timestart($now - DAYSECS)->set_timefinish($now + DAYSECS)->set_sessiontimezone('99')->save();
        $ongoing2 = new seminar_event();
        $ongoing2->set_facetoface($seminar->get_id())->set_details('ongoing2')->save();
        $session = new seminar_session();
        $session->set_sessionid($ongoing2->get_id())->set_timestart($now + DAYSECS * 2)->set_timefinish($now + DAYSECS * 3)->set_sessiontimezone('99')->save();
        $session = new seminar_session();
        $session->set_sessionid($ongoing2->get_id())->set_timestart($now - DAYSECS * 2)->set_timefinish($now - DAYSECS)->set_sessiontimezone('99')->save();
        $past = new seminar_event();
        $past->set_facetoface($seminar->get_id())->set_details('past')->save();
        $session = new seminar_session();
        $session->set_sessionid($past->get_id())->set_timestart($now - DAYSECS * 3)->set_timefinish($now - DAYSECS * 2)->set_sessiontimezone('99')->save();
        return [$seminar, $future, $ongoing1, $ongoing2, $past];
    }

    /**
     * @return integer
     */
    private function count_confirmed_room_virtualmeeting(): int {
        return builder::table(room_virtualmeeting::DBTABLE)
            ->where('status', room_virtualmeeting::STATUS_CONFIRMED)
            ->count();
    }

    /**
     * @param integer|null $sessionid
     * @param integer $status
     * @return integer
     */
    private function count_room_dates_virtualmeeting(?int $sessionid, int $status): int {
        $this->assertNotSame(0, $sessionid);
        $builder = builder::table(room_dates_virtualmeeting::DBTABLE)->where('status', $status);
        if ($sessionid !== null) {
            $builder->where('sessionsdateid', $sessionid);
        }
        return $builder->count();
    }

    /**
     * @param integer|null $sessionid
     * @param integer[] $statuses
     * @return integer
     */
    private function count_room_dates_virtualmeeting_not(?int $sessionid, array $statuses): int {
        $this->assertNotSame(0, $sessionid);
        $builder = builder::table(room_dates_virtualmeeting::DBTABLE)
            ->where(function (builder $inner) use ($statuses) {
                return $inner->where_null('status')->or_where_not_in('status', $statuses);
            });
        if ($sessionid !== null) {
            $builder->where('sessionsdateid', $sessionid);
        }
        return $builder->count();
    }

    /**
     * @param integer $sessionid
     * @param integer $status
     * @return integer
     */
    private function count_session_room_dates_virtualmeeting(int $sessionid, int $status): int {
        return builder::table(room_dates_virtualmeeting::DBTABLE, 'frdvm')
            ->join(['facetoface_room_dates', 'frd'], 'frdvm.roomdateid', '=', 'frd.id')
            ->join([seminar_session::DBTABLE, 'sd'], 'frd.sessionsdateid', '=', 'sd.id')
            ->where('sd.id', $sessionid)
            ->where('frdvm.status', $status)
            ->count();
    }

    /**
     * @param integer $sessionid
     * @param integer $roomid
     * @return integer|null
     */
    private function status_room_dates_virtualmeeting(int $roomid): ?int {
        return builder::table(room_dates_virtualmeeting::DBTABLE)
            ->where('roomid', $roomid)
            ->select('status')
            ->one(true)
            ->status;
    }

    /**
     * @param string $name
     * @return room
     */
    private function create_custom_room(string $name): room {
        /** @var mod_facetoface\testing\generator */
        $f2g = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        return (new room())->from_record($f2g->add_custom_room(['name' => $name]));
    }

    /**
     * @param string $plugin
     * @param integer $userid
     * @return room
     */
    private function create_virtual_room(string $plugin = 'poc_app', int $userid = 0): room {
        global $USER;
        if (!in_array($plugin, ['poc_app', 'poc_user'])) {
            throw new coding_exception('invalid plugin name');
        }
        $userid = $userid ?: $USER->id;
        $this->assertNotEquals(0, $userid, 'setUser() must be called first');
        /** @var mod_facetoface\testing\generator */
        $f2g = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        return (new room())->from_record($f2g->add_virtualmeeting_room([], ['plugin' => $plugin, 'userid' => $userid]));
    }

    /**
     * @param integer|null $status
     */
    private function override_status_room_virtualmeeting(?int $status): void {
        builder::table(room_virtualmeeting::DBTABLE)
            ->update(['status' => $status]);
    }

    /**
     * @param integer|null $status
     */
    private function override_status_room_dates_virtualmeeting(?int $status): void {
        builder::table(room_dates_virtualmeeting::DBTABLE)
            ->update(['status' => $status]);
    }

    public function data_sessions_updated_time(): array {
        return [
            'future to future' => [DAYSECS, DAYSECS * 2],
            'future to ongoing' => [DAYSECS, -HOURSECS],
            'future to past' => [DAYSECS, -DAYSECS],
            'ongoing to future' => [-HOURSECS * 2, DAYSECS * 2],
            'ongoing to ongoing' => [-HOURSECS * 2, -HOURSECS],
            'ongoing to past' => [-HOURSECS * 2, -DAYSECS],
            'past to future' => [-DAYSECS * 2, DAYSECS * 2],
            'past to ongoing' => [-DAYSECS * 2, -HOURSECS],
            'past to past' => [-DAYSECS * 2, -DAYSECS],
        ];
    }

    /**
     * @param integer $timefrom
     * @param integer $timeto
     * @dataProvider data_sessions_updated_time
     */
    public function test_sessions_updated_time(int $timefrom, int $timeto): void {
        $this->setAdminUser();

        $now = time();
        // assumes these are virtual meeting space
        $room1 = $this->create_virtual_room();
        $room2 = $this->create_virtual_room();
        $room1->set_name('room 1')->save();
        $room2->set_name('room 2')->save();
        $seminar = $this->create_seminar();
        /** @var mod_facetoface\testing\generator */
        $f2g = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        // add session + assign room1 & room2
        $event = new seminar_event($f2g->add_session([
            'facetoface' => $seminar->get_id(),
            'sessiondates' => [
                (object)[
                    'timestart' => $now + $timefrom,
                    'timefinish' => $now + $timefrom + HOURSECS * 3,
                    'sessiontimezone' => 'Pacific/Auckland',
                    'roomids' => [$room1->get_id(), $room2->get_id()],
                    'assetids' => [],
                    'facilitatorids' => [],
                ]
            ]
        ]));
        $sessionid = $event->get_sessions()->current()->get_id();
        // assertion
        $this->assertEquals(2, $this->count_confirmed_room_virtualmeeting());
        $this->assertEquals(2, $this->count_room_dates_virtualmeeting($sessionid, room_dates_virtualmeeting::STATUS_PENDING_UPDATE));
        $this->assertEquals(0, $this->count_room_dates_virtualmeeting_not($sessionid, [room_dates_virtualmeeting::STATUS_PENDING_UPDATE]));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $this->status_room_dates_virtualmeeting($room1->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $this->status_room_dates_virtualmeeting($room2->get_id()));

        // edit session time + unassign room2
        $this->override_status_room_dates_virtualmeeting(room_dates_virtualmeeting::STATUS_AVAILABLE);
        seminar_event_helper::merge_sessions($event, [
            (object)[
                'id' => $sessionid,
                'timestart' => $now + $timeto,
                'timefinish' => $now + $timeto + HOURSECS * 3,
                'sessiontimezone' => 'Pacific/Auckland',
                'roomids' => [$room1->get_id()],
                'assetids' => [],
                'facilitatorids' => [],
            ]
        ]);
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting($sessionid, room_dates_virtualmeeting::STATUS_PENDING_UPDATE));
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting($sessionid, room_dates_virtualmeeting::STATUS_PENDING_DELETION));
        $this->assertEquals(0, $this->count_room_dates_virtualmeeting_not($sessionid, [room_dates_virtualmeeting::STATUS_PENDING_UPDATE, room_dates_virtualmeeting::STATUS_PENDING_DELETION]));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $this->status_room_dates_virtualmeeting($room1->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_DELETION, $this->status_room_dates_virtualmeeting($room2->get_id()));

        // delete session
        $this->override_status_room_dates_virtualmeeting(room_dates_virtualmeeting::STATUS_AVAILABLE);
        seminar_event_helper::merge_sessions($event, []);
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting(null, room_dates_virtualmeeting::STATUS_PENDING_DELETION));
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting_not(null, [room_dates_virtualmeeting::STATUS_PENDING_DELETION]));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_DELETION, $this->status_room_dates_virtualmeeting($room1->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_AVAILABLE, $this->status_room_dates_virtualmeeting($room2->get_id()));
    }

    public function data_sessions_updated_status(): array {
        // current status     expected status  expected vmid
        // --------------     ---------------  -------------
        // UNAVAILABLE        -                -
        // AVAILABLE          PENDING_UPDATE   -
        // PENDING_UPDATE     PENDING_UPDATE   -
        // PENDING_DELETION   PENDING_UPDATE   null
        // FAILURE_CREATION   PENDING_UPDATE   -
        // FAILURE_UPDATE     PENDING_UPDATE   -
        // FAILURE_DELETION   PENDING_UPDATE   null
        return [
            'Unavailable w/o room' => [room_dates_virtualmeeting::STATUS_UNAVAILABLE, false, room_dates_virtualmeeting::STATUS_UNAVAILABLE, false],
            'Unavailable w/ room' => [room_dates_virtualmeeting::STATUS_UNAVAILABLE, true, room_dates_virtualmeeting::STATUS_UNAVAILABLE, true],
            'Available' => [room_dates_virtualmeeting::STATUS_AVAILABLE, true, room_dates_virtualmeeting::STATUS_PENDING_UPDATE, true],
            'Pending creation' => [room_dates_virtualmeeting::STATUS_PENDING_UPDATE, false, room_dates_virtualmeeting::STATUS_PENDING_UPDATE, false],
            'Pending update' => [room_dates_virtualmeeting::STATUS_PENDING_UPDATE, true, room_dates_virtualmeeting::STATUS_PENDING_UPDATE, true],
            'Pending deletion' => [room_dates_virtualmeeting::STATUS_PENDING_DELETION, true, room_dates_virtualmeeting::STATUS_PENDING_UPDATE, false],
            'Failure creation' => [room_dates_virtualmeeting::STATUS_FAILURE_CREATION, false, room_dates_virtualmeeting::STATUS_PENDING_UPDATE, false],
            'Failure update' => [room_dates_virtualmeeting::STATUS_FAILURE_UPDATE, true, room_dates_virtualmeeting::STATUS_PENDING_UPDATE, true],
            'Failure deletion' => [room_dates_virtualmeeting::STATUS_FAILURE_DELETION, true, room_dates_virtualmeeting::STATUS_PENDING_UPDATE, false],
        ];
    }

    /**
     * @param integer $status
     * @param boolean $create
     * @param integer $newstatus
     * @param boolean $has_vm
     * @dataProvider data_sessions_updated_status
     */
    public function test_sessions_updated_status(int $status, bool $create, int $newstatus, bool $has_vm) {
        $this->setAdminUser();

        $now = time();
        $room = $this->create_virtual_room();
        $seminar = $this->create_seminar();
        /** @var mod_facetoface\testing\generator */
        $f2g = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        // add session + assign room1 & room2
        $event = new seminar_event($f2g->add_session([
            'facetoface' => $seminar->get_id(),
            'sessiondates' => [$now + DAYSECS],
        ]));
        $session = $event->get_sessions(true)->get_first();
        $this->attach_room($session, $room, $status);

        if ($create) {
            $vm = virtual_meeting_model::create('poc_app', user::logged_in(), 'room', new DateTime('@'.$session->get_timestart()), new DateTime('@'.$session->get_timefinish()), new simple_mock_client());
            room_dates_virtualmeeting::load_by_session_room($session, $room)->set_virtualmeetingid($vm->get_id())->save();
        }

        // assertion
        $this->assertEquals(1, $this->count_confirmed_room_virtualmeeting());
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting($session->get_id(), $status));
        $this->assertEquals(0, $this->count_room_dates_virtualmeeting_not($session->get_id(), [$status]));
        $this->assertSame($status, $this->status_room_dates_virtualmeeting($room->get_id()));

        // edit session time + unassign room2
        seminar_event_helper::merge_sessions($event, [
            (object)[
                'id' => $session->get_id(),
                'timestart' => $session->get_timestart() + DAYSECS,
                'timefinish' => $session->get_timefinish() + DAYSECS,
                'sessiontimezone' => $session->get_sessiontimezone(),
                'roomids' => [$room->get_id()],
                'assetids' => [],
                'facilitatorids' => [],
            ]
        ]);
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting($session->get_id(), $newstatus));
        $this->assertEquals(0, $this->count_room_dates_virtualmeeting_not($session->get_id(), [$newstatus]));
        $this->assertSame($newstatus, $this->status_room_dates_virtualmeeting($room->get_id()));
        $this->assertEquals($has_vm, null !== room_dates_virtualmeeting::load_by_session_room($session, $room)->get_virtualmeeting());
    }

    public function data_event_cancelled(): array {
        return [
            'cancel' => [
                false,
                [
                    'future' => ['room future'],
                    'ongoing1' => [],
                    'ongoing2' => [],
                    'past' => [],
                ]
            ],
            'delete' => [
                true,
                [
                    'future' => ['room future'],
                    'ongoing1' => ['room ongoing1'],
                    'ongoing2' => ['room ongoing2 future', 'room ongoing2 past'],
                    'past' => ['room past'],
                ]
            ],
        ];
    }

    /**
     * @param seminar_session $session
     * @param room $room
     * @param integer $status
     */
    private function attach_room(seminar_session $session, room $room, int $status = room_dates_virtualmeeting::STATUS_AVAILABLE): void {
        room_helper::sync($session->get_id(), [$room->get_id()]);
        $roomdate_vm = new room_dates_virtualmeeting();
        $roomdate_vm->set_status($status);
        $roomdate_vm->set_sessionsdateid($session->get_id());
        $roomdate_vm->set_roomid($room->get_id());
        $roomdate_vm->set_virtualmeetingid(null);
        $roomdate_vm->save();
    }

    /**
     * @param boolean $delete
     * @param array $expections
     * @dataProvider data_event_cancelled
     */
    public function test_event_cancelled(bool $delete, array $expections): void {
        $this->setAdminUser();

        // assumes these are virtual meeting space
        $room_fu = $this->create_virtual_room();
        $room_o1 = $this->create_virtual_room();
        $room_o2f = $this->create_virtual_room();
        $room_o2p = $this->create_virtual_room();
        $room_pa = $this->create_virtual_room();
        $room_fu->set_name('room future')->save();
        $room_o1->set_name('room ongoing1')->save();
        $room_o2f->set_name('room ongoing2 future')->save();
        $room_o2p->set_name('room ongoing2 past')->save();
        $room_pa->set_name('room past')->save();

        [$seminar, $future, $ongoing1, $ongoing2, $past] = $this->create_seminar_events();
        /** @var seminar $seminar */
        /** @var seminar_event $future */
        /** @var seminar_event $ongoing1 */
        /** @var seminar_event $ongoing2 */
        /** @var seminar_event $past */

        $this->attach_room($future->get_sessions()->current(), $room_fu);
        $this->attach_room($ongoing1->get_sessions()->current(), $room_o1);
        $this->attach_room($ongoing2->get_sessions()->get_last(), $room_o2f);
        $this->attach_room($ongoing2->get_sessions()->get_first(), $room_o2p);
        $this->attach_room($past->get_sessions()->current(), $room_pa);

        $data = [];
        foreach ([$future, $ongoing1, $ongoing2, $past] as $event) {
            // pre-fetch session ids before they are deleted
            $sessionids = array_keys($event->get_sessions()->to_array());
            $what = $event->get_details();
            if ($delete) {
                $event->delete();
            } else {
                $event->cancel();
            }
            $data[$what] = builder::table(room_dates_virtualmeeting::DBTABLE, 'frdvm')
                ->join([room::DBTABLE, 'fr'], 'frdvm.roomid', '=', 'fr.id')
                ->where('frdvm.status', room_dates_virtualmeeting::STATUS_PENDING_DELETION)
                ->where_in('frdvm.sessionsdateid', $sessionids)
                ->select('fr.name')
                ->order_by('fr.name')
                ->map_to(function ($record){
                    return $record->name;
                })
                ->fetch(true);
        };

        $this->assertEquals($expections['future'], $data['future']);
        $this->assertEquals($expections['ongoing1'], $data['ongoing1']);
        $this->assertEquals($expections['ongoing2'], $data['ongoing2']);
        $this->assertEquals($expections['past'], $data['past']);
    }

    public function test_resources_updated_in_session(): void {
        $this->setAdminUser();

        // assumes these are virtual meeting space
        $room_fua = $this->create_virtual_room();
        $room_fub = $this->create_virtual_room();
        $room_o1a = $this->create_virtual_room();
        $room_o1b = $this->create_virtual_room();
        $room_o2fa = $this->create_virtual_room();
        $room_o2fb = $this->create_virtual_room();
        $room_o2pa = $this->create_virtual_room();
        $room_o2pb = $this->create_virtual_room();
        $room_paa = $this->create_virtual_room();
        $room_pab = $this->create_virtual_room();
        $room_fua->set_name('room future A')->save();
        $room_fub->set_name('room future B')->save();
        $room_o1a->set_name('room ongoing1 A')->save();
        $room_o1b->set_name('room ongoing1 B')->save();
        $room_o2fa->set_name('room ongoing2 future A')->save();
        $room_o2fb->set_name('room ongoing2 future B')->save();
        $room_o2pa->set_name('room ongoing2 past A')->save();
        $room_o2pb->set_name('room ongoing2 past B')->save();
        $room_paa->set_name('room past A')->save();
        $room_pab->set_name('room past B')->save();

        [$seminar, $future, $ongoing1, $ongoing2, $past] = $this->create_seminar_events();
        /** @var seminar $seminar */
        /** @var seminar_event $future */
        /** @var seminar_event $ongoing1 */
        /** @var seminar_event $ongoing2 */
        /** @var seminar_event $past */

        $sdid_fu = $future->get_sessions()->current()->get_id();
        $sdid_og1 = $ongoing1->get_sessions()->current()->get_id();
        $sdid_og2f = $ongoing2->get_sessions()->get_last()->get_id();
        $sdid_og2p = $ongoing2->get_sessions()->get_first()->get_id();
        $sdid_pa = $past->get_sessions()->current()->get_id();

        /**
         * Assign rooms
         */

        // add rooms to future event
        $this->add_remove_rooms($future, [$sdid_fu => [$room_fua->get_id(), $room_fub->get_id()]]);
        $this->assertEquals(2, $this->count_confirmed_room_virtualmeeting());
        $this->assertEquals(2, $this->count_room_dates_virtualmeeting($sdid_fu, room_dates_virtualmeeting::STATUS_PENDING_UPDATE));
        $this->assertEquals(0, $this->count_room_dates_virtualmeeting_not($sdid_fu, [room_dates_virtualmeeting::STATUS_PENDING_UPDATE]));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $this->status_room_dates_virtualmeeting($room_fua->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $this->status_room_dates_virtualmeeting($room_fub->get_id()));

        // add rooms to ongoing event 1
        $this->add_remove_rooms($ongoing1, [$sdid_og1 => [$room_o1a->get_id(), $room_o1b->get_id()]]);
        $this->assertEquals(2, $this->count_confirmed_room_virtualmeeting());
        $this->assertEquals(2, $this->count_room_dates_virtualmeeting($sdid_og1, room_dates_virtualmeeting::STATUS_PENDING_UPDATE));
        $this->assertEquals(0, $this->count_room_dates_virtualmeeting_not($sdid_og1, [room_dates_virtualmeeting::STATUS_PENDING_UPDATE]));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $this->status_room_dates_virtualmeeting($room_o1a->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $this->status_room_dates_virtualmeeting($room_o1b->get_id()));

        // add rooms to ongoing event 2
        $this->add_remove_rooms($ongoing2, [$sdid_og2f => [$room_o2fa->get_id(), $room_o2fb->get_id()], $sdid_og2p => [$room_o2pa->get_id(), $room_o2pb->get_id()]]);
        $this->assertEquals(4, $this->count_confirmed_room_virtualmeeting());
        $this->assertEquals(2, $this->count_room_dates_virtualmeeting($sdid_og2f, room_dates_virtualmeeting::STATUS_PENDING_UPDATE));
        $this->assertEquals(0, $this->count_room_dates_virtualmeeting_not($sdid_og2f, [room_dates_virtualmeeting::STATUS_PENDING_UPDATE]));
        $this->assertEquals(2, $this->count_room_dates_virtualmeeting($sdid_og2p, room_dates_virtualmeeting::STATUS_PENDING_UPDATE));
        $this->assertEquals(0, $this->count_room_dates_virtualmeeting_not($sdid_og2p, [room_dates_virtualmeeting::STATUS_PENDING_UPDATE]));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $this->status_room_dates_virtualmeeting($room_o2fa->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $this->status_room_dates_virtualmeeting($room_o2fb->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $this->status_room_dates_virtualmeeting($room_o2pa->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $this->status_room_dates_virtualmeeting($room_o2pb->get_id()));

        // add rooms to past event
        $this->add_remove_rooms($past, [$sdid_pa => [$room_paa->get_id(), $room_pab->get_id()]]);
        $this->assertEquals(2, $this->count_confirmed_room_virtualmeeting());
        $this->assertEquals(2, $this->count_room_dates_virtualmeeting($sdid_pa, room_dates_virtualmeeting::STATUS_PENDING_UPDATE));
        $this->assertEquals(0, $this->count_room_dates_virtualmeeting_not($sdid_pa, [room_dates_virtualmeeting::STATUS_PENDING_UPDATE]));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $this->status_room_dates_virtualmeeting($room_paa->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_UPDATE, $this->status_room_dates_virtualmeeting($room_pab->get_id()));

        /**
         * Unassign rooms
         */

        // delete a room from future event
        $this->add_remove_rooms($future, [$sdid_fu => [$room_fua->get_id()]]);
        $this->assertEquals(1, $this->count_confirmed_room_virtualmeeting());
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting($sdid_fu, room_dates_virtualmeeting::STATUS_PENDING_DELETION));
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting_not($sdid_fu, [room_dates_virtualmeeting::STATUS_PENDING_DELETION]));
        $this->assertSame(-42, $this->status_room_dates_virtualmeeting($room_fua->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_DELETION, $this->status_room_dates_virtualmeeting($room_fub->get_id()));

        // delete a room from ongoing event 1
        $this->add_remove_rooms($ongoing1, [$sdid_og1 => [$room_o1a->get_id()]]);
        $this->assertEquals(1, $this->count_confirmed_room_virtualmeeting());
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting($sdid_og1, room_dates_virtualmeeting::STATUS_PENDING_DELETION));
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting_not($sdid_og1, [room_dates_virtualmeeting::STATUS_PENDING_DELETION]));
        $this->assertSame(-42, $this->status_room_dates_virtualmeeting($room_o1a->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_DELETION, $this->status_room_dates_virtualmeeting($room_o1b->get_id()));

        // delete a room from ongoing event 2
        $this->add_remove_rooms($ongoing2, [$sdid_og2f => [$room_o2fa->get_id()], $sdid_og2p => [$room_o2pa->get_id()]]);
        $this->assertEquals(2, $this->count_confirmed_room_virtualmeeting());
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting($sdid_og2f, room_dates_virtualmeeting::STATUS_PENDING_DELETION));
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting_not($sdid_og2f, [room_dates_virtualmeeting::STATUS_PENDING_DELETION]));
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting($sdid_og2p, room_dates_virtualmeeting::STATUS_PENDING_DELETION));
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting_not($sdid_og2p, [room_dates_virtualmeeting::STATUS_PENDING_DELETION]));
        $this->assertSame(-42, $this->status_room_dates_virtualmeeting($room_o2fa->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_DELETION, $this->status_room_dates_virtualmeeting($room_o2fb->get_id()));
        $this->assertSame(-42, $this->status_room_dates_virtualmeeting($room_o2pa->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_DELETION, $this->status_room_dates_virtualmeeting($room_o2pb->get_id()));

        // delete a room from past event
        $this->add_remove_rooms($past, [$sdid_pa => [$room_paa->get_id()]]);
        $this->assertEquals(1, $this->count_confirmed_room_virtualmeeting());
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting($sdid_pa, room_dates_virtualmeeting::STATUS_PENDING_DELETION));
        $this->assertEquals(1, $this->count_room_dates_virtualmeeting_not($sdid_pa, [room_dates_virtualmeeting::STATUS_PENDING_DELETION]));
        $this->assertSame(-42, $this->status_room_dates_virtualmeeting($room_paa->get_id()));
        $this->assertSame(room_dates_virtualmeeting::STATUS_PENDING_DELETION, $this->status_room_dates_virtualmeeting($room_pab->get_id()));
    }

    public function test_resources_updated_when_plugin_is_selected(): void {
        $this->setAdminUser();

        $room_fua = $this->create_custom_room('room future A');
        $room_fub = $this->create_custom_room('room future B');
        $room_o1a = $this->create_custom_room('room ongoing1 A');
        $room_o1b = $this->create_custom_room('room ongoing1 B');
        $room_o2fa = $this->create_custom_room('room ongoing2 future A');
        $room_o2fb = $this->create_custom_room('room ongoing2 future B');
        $room_o2pa = $this->create_custom_room('room ongoing2 past A');
        $room_o2pb = $this->create_custom_room('room ongoing2 past B');
        $room_paa = $this->create_custom_room('room past A');
        $room_pab = $this->create_custom_room('room past B');

        [$seminar, $future, $ongoing1, $ongoing2, $past] = $this->create_seminar_events();
        /** @var seminar $seminar */
        /** @var seminar_event $future */
        /** @var seminar_event $ongoing1 */
        /** @var seminar_event $ongoing2 */
        /** @var seminar_event $past */

        $sdid_fu = $future->get_sessions()->current()->get_id();
        $sdid_og1 = $ongoing1->get_sessions()->current()->get_id();
        $sdid_og2f = $ongoing2->get_sessions()->get_last()->get_id();
        $sdid_og2p = $ongoing2->get_sessions()->get_first()->get_id();
        $sdid_pa = $past->get_sessions()->current()->get_id();
        $this->add_remove_rooms($future, [$sdid_fu => [$room_fua->get_id(), $room_fub->get_id()]]);
        $this->add_remove_rooms($ongoing1, [$sdid_og1 => [$room_o1a->get_id(), $room_o1b->get_id()]]);
        $this->add_remove_rooms($ongoing2, [$sdid_og2f => [$room_o2fa->get_id(), $room_o2fb->get_id()], $sdid_og2p => [$room_o2pa->get_id(), $room_o2pb->get_id()]]);
        $this->add_remove_rooms($past, [$sdid_pa => [$room_paa->get_id(), $room_pab->get_id()]]);

        $builder = builder::table('task_adhoc')->where('classname', '\\' . manage_virtualmeetings_adhoc_task::class);

        $this->assertEquals(0, $builder->count());

        $this->override_status_room_virtualmeeting(-42);
        $this->switch_to_virtual_meeting($future, [$room_fua]);
        $this->assertEquals(1, $builder->count());
        $this->assertEquals(1, $this->count_confirmed_room_virtualmeeting());

        $this->override_status_room_virtualmeeting(-42);
        $this->switch_to_virtual_meeting($ongoing1, [$room_o1a]);
        $this->assertEquals(2, $builder->count());
        $this->assertEquals(1, $this->count_confirmed_room_virtualmeeting());

        $this->override_status_room_virtualmeeting(-42);
        $this->switch_to_virtual_meeting($ongoing2, [$room_o2fa, $room_o2pa]);
        $this->assertEquals(3, $builder->count());
        $this->assertEquals(2, $this->count_confirmed_room_virtualmeeting());

        $this->override_status_room_virtualmeeting(-42);
        $this->switch_to_virtual_meeting($past, [$room_paa]);
        $this->assertEquals(4, $builder->count());
        $this->assertEquals(1, $this->count_confirmed_room_virtualmeeting());
    }

    public function test_seminar_event_updated(): void {
        $creator = $this->getDataGenerator()->create_user();
        $this->setUser($creator);

        $room1 = $this->create_virtual_room();
        $room2 = $this->create_virtual_room();
        $room1->set_name('room 1')->save();
        $room2->set_name('room 2')->save();
        $seminar = $this->create_seminar();
        $event1 = new seminar_event();
        $event1->set_facetoface($seminar->get_id())->save();
        $event2 = new seminar_event();
        $event2->set_facetoface($seminar->get_id())->save();
        $now = time();
        $statuses1 = [
            room_dates_virtualmeeting::STATUS_UNAVAILABLE => room_dates_virtualmeeting::STATUS_UNAVAILABLE,
            room_dates_virtualmeeting::STATUS_AVAILABLE => room_dates_virtualmeeting::STATUS_AVAILABLE,
            room_dates_virtualmeeting::STATUS_PENDING_UPDATE => room_dates_virtualmeeting::STATUS_PENDING_UPDATE,
            room_dates_virtualmeeting::STATUS_PENDING_DELETION => room_dates_virtualmeeting::STATUS_PENDING_DELETION,
            room_dates_virtualmeeting::STATUS_FAILURE_CREATION => room_dates_virtualmeeting::STATUS_PENDING_UPDATE,
            room_dates_virtualmeeting::STATUS_FAILURE_UPDATE => room_dates_virtualmeeting::STATUS_PENDING_UPDATE,
            room_dates_virtualmeeting::STATUS_FAILURE_DELETION => room_dates_virtualmeeting::STATUS_PENDING_DELETION,
        ];
        $statuses2 = [
            room_dates_virtualmeeting::STATUS_FAILURE_CREATION,
            room_dates_virtualmeeting::STATUS_FAILURE_UPDATE,
            room_dates_virtualmeeting::STATUS_FAILURE_DELETION,
        ];
        /** @var room_dates_virtualmeeting[] */
        $roomdatevms1 = [];
        /** @var room_dates_virtualmeeting[] */
        $roomdatevms2 = [];
        $i = DAYSECS;
        foreach ($statuses1 as $status => $x) {
            $session = new seminar_session();
            $session->set_sessionid($event1->get_id())->set_timestart($now + $i)->set_timefinish($now + $i + HOURSECS)->set_sessiontimezone('99')->save();
            room_helper::sync($session->get_id(), [$room1->get_id()]);
            if (in_array($status, [room_dates_virtualmeeting::STATUS_AVAILABLE, room_dates_virtualmeeting::STATUS_PENDING_DELETION, room_dates_virtualmeeting::STATUS_FAILURE_UPDATE, room_dates_virtualmeeting::STATUS_FAILURE_DELETION])) {
                $vmid = virtual_meeting_model::create('poc_app', $creator->id, 'test room ' . ($i / DAYSECS), DateTime::createFromFormat('U', $now + $i), DateTime::createFromFormat('U', $now + $i + HOURSECS), new simple_mock_client())->id;
            } else {
                $vmid = null;
            }
            $roomdate_vm = (new room_dates_virtualmeeting())
                ->set_sessionsdateid($session->get_id())
                ->set_roomid($room1->get_id())
                ->set_virtualmeetingid($vmid)
                ->set_status($status);
            $roomdate_vm->save();
            $roomdatevms1[$status] = $roomdate_vm;
            $i += DAYSECS;
        }
        foreach ($statuses2 as $status) {
            $session = new seminar_session();
            $session->set_sessionid($event2->get_id())->set_timestart($now + $i)->set_timefinish($now + $i + HOURSECS)->set_sessiontimezone('99')->save();
            room_helper::sync($session->get_id(), [$room2->get_id()]);
            if (in_array($status, [room_dates_virtualmeeting::STATUS_FAILURE_UPDATE, room_dates_virtualmeeting::STATUS_FAILURE_DELETION])) {
                $vmid = virtual_meeting_model::create('poc_app', $creator->id, 'test room ' . ($i / DAYSECS), DateTime::createFromFormat('U', $now + $i), DateTime::createFromFormat('U', $now + $i + HOURSECS), new simple_mock_client())->id;
            } else {
                $vmid = null;
            }
            $roomdate_vm = (new room_dates_virtualmeeting())
                ->set_sessionsdateid($session->get_id())
                ->set_roomid($room2->get_id())
                ->set_virtualmeetingid($vmid)
                ->set_status($status);
            $roomdate_vm->save();
            $roomdatevms2[$status] = $roomdate_vm;
            $i += DAYSECS;
        }
        $builder = builder::table('task_adhoc')->where('classname', '\\' . manage_virtualmeetings_adhoc_task::class);
        $context = context_module::instance($seminar->get_coursemodule()->id);

        $event = session_updated::create_from_session((object)['id' => 0], $context);
        virtualmeeting_watcher::seminar_event_updated($event);
        $this->assertEquals(0, $builder->count());

        $this->setUser();
        $event = session_updated::create_from_session((object)['id' => $event1->get_id()], $context);
        virtualmeeting_watcher::seminar_event_updated($event);
        $this->assertEquals(0, $builder->count());

        $this->setAdminUser();
        $event = session_updated::create_from_session((object)['id' => $event1->get_id()], $context);
        virtualmeeting_watcher::seminar_event_updated($event);
        $this->assertEquals(0, $builder->count());

        $this->setUser($creator->id);
        $event = session_updated::create_from_session((object)['id' => $event1->get_id()], $context);
        virtualmeeting_watcher::seminar_event_updated($event);
        $this->assertEquals(1, $builder->count());

        foreach ($statuses1 as $status => $newstatus) {
            $this->assertEquals($newstatus, $roomdatevms1[$status]->load()->get_status());
        }
        foreach ($statuses2 as $status) {
            $this->assertEquals($status, $roomdatevms2[$status]->load()->get_status());
        }
    }

    /**
     * @param seminar_event $event
     * @param integer[] $sessionrooms
     */
    private function add_remove_rooms(seminar_event $event, array $sessionrooms): void {
        $dates = [];
        foreach ($event->get_sessions(true) as $session) {
            /** @var seminar_session $session */
            $date = $session->to_record();
            $date->roomids = $sessionrooms[$date->id] ?? [];
            $dates[] = $date;
        }
        builder::table(room_virtualmeeting::DBTABLE)->update(['status' => -42]);
        $this->override_status_room_dates_virtualmeeting(-42);
        seminar_event_helper::merge_sessions($event, $dates);
    }

    /**
     * @param seminar_event $event
     * @param room[] $rooms
     */
    private function switch_to_virtual_meeting(seminar_event $event, array $rooms): void {
        foreach ($rooms as $room) {
            room_helper::save((object)[
                'id' => $room->get_id(),
                'name' => $room->get_name(),
                'roomcapacity' => $room->get_capacity(),
                'allowconflicts' => $room->get_allowconflicts(),
                'plugin' => 'poc_app',
                'url' => 'https://example.com',
                'notcustom' => 0,
                'description_editor' => ['text' => '', 'itemid' => 0, 'format' => FORMAT_HTML],
            ]);
        }
        $dates = $event->get_sessions(true)->to_records(false);
        foreach ($dates as &$date) {
            $date->roomids = array_map(
                function (room $room) {
                    return $room->get_id();
                },
                room_list::from_session($date->id)->to_array(false)
            );
            // what about assetids and facilitatorids?
        }
        seminar_event_helper::merge_sessions($event, $dates);
    }

    /**
     * @codeCoverageIgnore
     */
    private function dump_room_virtual_meeting(): void {
        $records = builder::table(room_virtualmeeting::DBTABLE, 'frvm')
            ->left_join([room::DBTABLE, 'fr'], 'frvm.roomid', 'fr.id')
            ->left_join(['user', 'u'], 'frvm.userid', 'u.id')
            ->select(['frvm.id', 'frvm.status', 'frvm.plugin', 'fr.name', 'u.username'])
            ->map_to(function ($record) {
                return (object)[
                    'id' => $record->id,
                    'status' => $record->status,
                    'plugin' => $record->plugin,
                    'room' => $record->name,
                    'username' => $record->username,
                ];
            })
            ->fetch();
        var_export($records);
    }

    /**
     * @param integer|null $sessionid
     * @codeCoverageIgnore
     */
    private function dump_room_dates_virtualmeeting(?int $sessionid): void {
        $builder = builder::table(room_dates_virtualmeeting::DBTABLE, 'frdvm')
            ->join([seminar_session::DBTABLE, 'fsd'], 'frdvm.sessionsdateid', 'fsd.id')
            ->join([seminar_event::DBTABLE, 'fs'], 'fsd.sessionid', 'fs.id')
            ->join([room::DBTABLE, 'fr'], 'frdvm.roomid', 'fr.id')
            ->select(['frdvm.id', 'frdvm.status', 'fsd.timestart', 'fsd.timefinish', 'fr.name', 'fs.details'])
            ->map_to(function ($record) {
                return (object)[
                    'id' => $record->id,
                    'status' => $record->status,
                    'event' => $record->details,
                    'room' => $record->name,
                    'start' => userdate($record->timestart, '%d %B %Y %I:%M:%S %p'),
                    'finish' => userdate($record->timefinish, '%d %B %Y %I:%M:%S %p'),
                ];
            });
        if ($sessionid !== null) {
            $builder->where('sessionsdateid', $sessionid);
        }
        var_export($builder->fetch());
    }
}
