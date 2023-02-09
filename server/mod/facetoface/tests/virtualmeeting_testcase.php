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
use mod_facetoface\room;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\room_virtualmeeting;
use mod_facetoface\seminar;
use totara_core\virtualmeeting\virtual_meeting as virtualmeeting_model;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\seminar_session;
use mod_facetoface\task\manage_virtualmeetings_adhoc_task as adhoc_task;
use totara_core\entity\virtual_meeting as virtualmeeting_entity;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

/**
 * Base test case for seminar virtual meeting.
 */
abstract class mod_facetoface_virtualmeeting_testcase extends advanced_testcase {
    /** @var mod_facetoface_generator */
    protected $semgen;
    /** @var user */
    protected $user1;
    /** @var user */
    protected $user2;
    /** @var stdClass */
    protected $course;
    /** @var seminar_event */
    protected $event1;
    /** @var seminar_session */
    protected $session1;
    /** @var seminar_session */
    protected $session2;
    /** @var room */
    protected $sitewide_room;
    /** @var room */
    protected $custom_room;
    /** @var room */
    protected $virtual_room1;
    /** @var room */
    protected $virtual_room2;
    /** @var virtualmeeting_model */
    protected $virtualmeeting1;
    /** @var virtualmeeting_model */
    protected $virtualmeeting2;

    public function setUp(): void {
        $this->user1 = $this->create_user(['username' => 'bob']);
        $this->user2 = $this->create_user(['username' => 'ann']);
        $this->course = $this->getDataGenerator()->create_course();

        $this->setUser($this->user1);
        /** @var mod_facetoface_generator */
        $this->semgen = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $seminar = $this->add_seminar([
            'name' => 'Test seminar',
            'course' => $this->course->id
        ]);
        $this->sitewide_room = $this->add_site_wide_room(['name' => 'just a room']);
        $this->custom_room = $this->add_custom_room(['name' => 'room with url', 'url' => 'https://example.com']);
        $this->virtual_room1 = $this->add_virtualmeeting_room(['name' => 'vroom1'], ['status' => room_virtualmeeting::STATUS_CONFIRMED]);
        $this->virtual_room2 = $this->add_virtualmeeting_room(['name' => 'vroom2'], ['status' => room_virtualmeeting::STATUS_CONFIRMED]);
        $session1start = time() + 3600;
        $session1finish = time() + 5400;
        $session2start = time() + 7200;
        $session2finish = time() + 9000;
        $this->event1 = $this->add_seminar_event([
            'facetoface' => $seminar->get_id(),
            'sessiondates' => [
                [
                    'timestart' => $session1start,
                    'timefinish' => $session1finish,
                    'sessiontimezone' => 'Pacific/Auckland',
                    'roomids' => [$this->virtual_room1->get_id()]
                ],
                [
                    'timestart' => $session2start,
                    'timefinish' => $session2finish,
                    'sessiontimezone' => 'Pacific/Auckland',
                    'roomids' => [$this->virtual_room2->get_id()]
                ],
            ],
        ]);
        $this->session1 = $this->event1->get_sessions()->get_first();
        $this->session2 = $this->event1->get_sessions()->get_last();
        $this->assertEquals(1, $this->count_session_room_dates($this->session1));
        $this->assertEquals(1, $this->count_session_room_dates($this->session2));

        // Create virtualmeeting instances up front.
        $this->virtualmeeting1 = $this->add_virtual_meeting($this->session1, $this->virtual_room1, 'Test seminar');
        $this->virtualmeeting2 = $this->add_virtual_meeting($this->session2, $this->virtual_room2, 'Test seminar');

        // Log out.
        $this->setUser();

        $unavailable_meetings = builder::table(room_dates_virtualmeeting::DBTABLE)
            ->where(function (builder $inner) {
                $inner->where_null('status')->or_where('status', '!=', room_dates_virtualmeeting::STATUS_AVAILABLE);
            })
            ->count();
        $this->assertEquals(0, $unavailable_meetings);

        // Delete outstanding ad-hoc tasks to avoid any side effects.
        builder::table('task_adhoc')->delete();
    }

    public function tearDown(): void {
        $this->semgen = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->course = null;
        $this->event1 = null;
        $this->session1 = null;
        $this->session2 = null;
        $this->sitewide_room = null;
        $this->custom_room = null;
        $this->virtual_room1 = null;
        $this->virtual_room2 = null;
        $this->virtualmeeting1 = null;
        $this->virtualmeeting2 = null;
        parent::tearDown();
    }

    /**
     * @param array|stdClass $record
     * @param array $options
     * @return user
     */
    protected function create_user($record = null, array $options = null): user {
        return new user($this->getDataGenerator()->create_user($record, $options));
    }

    /**
     * @param array|stdClass $record
     * @return room
     */
    protected function add_site_wide_room($record): room {
        return (new room())->from_record($this->semgen->add_site_wide_room($record));
    }

    /**
     * @param array|stdClass $record
     * @return room
     */
    protected function add_custom_room($record): room {
        return (new room())->from_record($this->semgen->add_custom_room($record));
    }

    /**
     * @param array|stdClass $record
     * @param array $options
     * @return room
     */
    protected function add_virtualmeeting_room($record, $options = array()): room {
        return (new room())->from_record($this->semgen->add_virtualmeeting_room($record, $options));
    }

    /**
     * @param array|stdClass $record
     * @param array $options
     * @return seminar
     */
    protected function add_seminar($record = null, array $options = null): seminar {
        $record = $this->semgen->create_instance($record, $options);
        unset($record->cmid);
        return (new seminar())->map_instance($record);
    }

    /**
     * @param array|stdClass $record
     * @param array $options
     * @return seminar_event
     */
    protected function add_seminar_event($record, $options = array()): seminar_event {
        return new seminar_event($this->semgen->add_session($record, $options));
    }

    /**
     * @param seminar_session $session
     * @param room $room
     * @param string $name
     * @param string $plugin
     * @param user $user
     * @return virtualmeeting_model
     */
    protected function add_virtual_meeting(seminar_session $session, room $room, string $name, string $plugin = 'poc_app', user $user = null): virtualmeeting_model {
        $vm = virtualmeeting_model::create(
            $plugin,
            $user ?? user::logged_in(),
            $name,
            DateTime::createFromFormat('U', $session->get_timestart()),
            DateTime::createFromFormat('U', $session->get_timefinish())
        );
        room_dates_virtualmeeting::load_by_session_room($session, $room)
            ->set_virtualmeetingid($vm->get_id())
            ->set_status(room_dates_virtualmeeting::STATUS_AVAILABLE)
            ->save();
        return $vm;
    }

    /**
     * Count virtualmeeting.
     *
     * @return integer
     */
    protected function count_virtualmeeting(): int {
        return virtualmeeting_entity::repository()->count();
    }

    /**
     * Count room_virtualmeeting.
     *
     * @return integer
     */
    protected function count_room_virtualmeeting(): int {
        return builder::table(room_virtualmeeting::DBTABLE)->count();
    }

    /**
     * Count room_dates_virtualmeeting.
     *
     * @return integer
     */
    protected function count_room_dates_virtualmeeting(): int {
        return builder::table(room_dates_virtualmeeting::DBTABLE)->count();
    }

    /**
     * Count facetoface_room_dates belonging to the session.
     *
     * @param seminar_session $session
     * @return integer
     */
    protected function count_session_room_dates(seminar_session $session): int {
        return builder::table('facetoface_room_dates')->where('sessionsdateid', $session->get_id())->count();
    }

    /**
     * Ensure that nothing has happened.
     *
     * @param string $url1
     * @param string $url2
     */
    protected function nothing_happened(string $url1, string $url2): void {
        // There should be two each of virtualmeetings, room_virtualmeetings, and room_dates_virtualmeetings.
        $virtualmeetings = $this->count_virtualmeeting();
        $this->assertEquals(2, $virtualmeetings, 'virtualmeeting');
        $room_virtualmeetings = $this->count_room_virtualmeeting();
        $this->assertEquals(2, $room_virtualmeetings, 'room_virtualmeeting');
        $room_date_virtualmeetings = $this->count_room_dates_virtualmeeting();
        $this->assertEquals(2, $room_date_virtualmeetings, 'room_dates_virtualmeeting');
        $vm1 = virtualmeeting_model::load_by_id($this->virtualmeeting1->get_id());
        $vm2 = virtualmeeting_model::load_by_id($this->virtualmeeting2->get_id());
        $this->assertEquals($url1, $vm1->get_join_url(), 'vm1.url');
        $this->assertEquals($url2, $vm2->get_join_url(), 'vm2.url');
    }

    /**
     * Assert that:
     * - $actual's age is $age_diff older than $expected's
     * - $actual's timestart is $timestart_diff later than $expected's
     * - $actual's timefinish is $timefinish_diff later than $expected's
     *
     * @param string $expected
     * @param string $actual
     * @param integer $age_diff
     * @param integer $timestart_diff
     * @param integer $timefinish_diff
     * @param string $message
     */
    protected function assert_aged_url(string $expected, string $actual, int $age_diff, int $timestart_diff, int $timefinish_diff, string $message = ''): void {
        $this->assertEquals(1, preg_match('/age=(\d+)/', $expected, $matches1), 'url does not contain age');
        $this->assertEquals(1, preg_match('/timestart=(\d+)/', $expected, $matches2), 'url does not contain timestart');
        $this->assertEquals(1, preg_match('/timefinish=(\d+)/', $expected, $matches3), 'url does not contain timefinish');
        $age1 = (int)$matches1[1];
        $age2 = $age1 + $age_diff;
        $timestart1 = (int)$matches2[1];
        $timestart2 = $timestart1 + $timestart_diff;
        $timefinish1 = (int)$matches3[1];
        $timefinish2 = $timefinish1 + $timefinish_diff;
        $expected = str_replace("age={$age1}", "age={$age2}", $expected);
        $expected = str_replace("timestart={$timestart1}", "timestart={$timestart2}", $expected);
        $expected = str_replace("timefinish={$timefinish1}", "timefinish={$timefinish2}", $expected);
        $this->assertEquals($expected, $actual, $message);
    }

    /**
     * Save changes.
     *
     * @param seminar_event $event
     * @param array $rooms
     * @param array $assets
     * @param array $facilitators
     */
    protected function event_save_changes(seminar_event $event, array $rooms = [], array $assets = [], array $facilitators = []): void {
        $dates = [];
        foreach ($event->get_sessions(true) as $session) {
            /** @var seminar_session $session */
            $date = $session->to_record();
            $date->roomids = $rooms[$date->id] ?? [];
            $date->assetids = $assets[$date->id] ?? [];
            $date->facilitatorids = $facilitators[$date->id] ?? [];
            $dates[] = $date;
        }
        builder::table(room_virtualmeeting::DBTABLE)->update(['status' => room_virtualmeeting::STATUS_CONFIRMED]);
        builder::table(room_dates_virtualmeeting::DBTABLE)->update(['status' => room_dates_virtualmeeting::STATUS_AVAILABLE]);
        $this->merge_sessions($event, $dates);
    }

    /**
     * Add/remove seminar sessions to/from the seminar event.
     *
     * @param seminar_event $event
     * @param array $dates
     */
    protected function merge_sessions(seminar_event $event, array $dates): void {
        global $USER;
        $this->assertTrue(!empty($USER->id), 'setUser() must be called first');
        seminar_event_helper::merge_sessions($event, $dates);
    }

    /**
     * Queue and execute ad-hoc task for all virtual meetings on the event.
     *
     * @param seminar_event $event
     * @param integer $user_id
     */
    protected function run_adhoc_task(seminar_event $event, int $user_id = 0): void {
        global $USER;
        $this->assertTrue(!empty($USER->id) || $user_id, 'setUser() must be called first');
        $task = adhoc_task::create_from_seminar_event_id($event->get_id(), $user_id);
        $task->execute();
    }

    /**
     * Dump all room_virtualmeeting records for humans.
     */
    protected function dump_room_virtual_meeting(): void {
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
        var_dump($records);
    }

    /**
     * Dump all room_dates_virtualmeeting records for humans.
     *
     * @param integer|null $sessionid
     * @return void
     */
    protected function dump_room_dates_virtualmeeting(?int $sessionid): void {
        $builder = builder::table(room_dates_virtualmeeting::DBTABLE, 'frdvm')
            ->join([seminar_session::DBTABLE, 'fsd'], 'frdvm.sessionsdateid', 'fsd.id')
            ->left_join([seminar_event::DBTABLE, 'fs'], 'fsd.sessionid', 'fs.id')
            ->left_join([room::DBTABLE, 'fr'], 'frdvm.roomid', 'fr.id')
            ->select(['frdvm.id', 'frdvm.status', 'fsd.timestart', 'fsd.timefinish', 'fr.name', 'fs.details'])
            ->map_to(function ($record) {
                return (object)[
                    'id' => $record->id,
                    'status' => $record->status,
                    'event' => isset($record->details) ? $record->details : '(none)',
                    'room' => isset($record->name) ? $record->name : '(none)',
                    'start' => isset($record->timestart) ? userdate($record->timestart, '%d %B %Y %I:%M:%S %p') : '(none)',
                    'finish' => isset($record->timefinish) ? userdate($record->timefinish, '%d %B %Y %I:%M:%S %p') : '(none)',
                ];
            });
        if ($sessionid !== null) {
            $builder->where('frdvm.sessionsdateid', $sessionid);
        }
        var_dump($builder->fetch());
    }
}
