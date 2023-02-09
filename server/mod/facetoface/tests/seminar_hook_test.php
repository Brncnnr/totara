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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_facetoface
 */

use mod_facetoface\asset;
use mod_facetoface\facilitator_helper;
use mod_facetoface\facilitator_user;
use mod_facetoface\hook\event_is_being_cancelled;
use mod_facetoface\hook\resources_are_being_updated;
use mod_facetoface\hook\sessions_are_being_updated;
use mod_facetoface\room;
use mod_facetoface\seminar_attachment_item;
use mod_facetoface\seminar_event;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\seminar_session;
use mod_facetoface\seminar_session_list;

class mod_facetoface_seminar_hook_testcase extends advanced_testcase {
    /** @var \mod_facetoface\testing\generator */
    private $f2fgen;

    /** @var stdClass */
    private $course;

    /** @var stdClass */
    private $f2f;

    /** @var \core_phpunit\hook_sink */
    private $sink;

    public function setUp(): void {
        $gen = $this->getDataGenerator();
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        $this->f2fgen = $f2fgen;
        /** @var \mod_facetoface\testing\generator $f2fgen */

        $this->course = $gen->create_course();
        $this->f2f = $f2fgen->create_instance(['course' => $this->course->id]);
    }

    public function tearDown(): void {
        $this->f2fgen = null;
        $this->course = null;
        $this->f2f = null;
    }

    /**
     * Start hook redirection.
     */
    private function redirect_hooks() {
        if ($this->sink) {
            throw new coding_exception('do not call redirect_hooks again');
        }
        $this->sink = $this->redirectHooks();
    }

    /**
     * Finish hook redirection and return hooks received.
     *
     * @return totara_core\hook\base[]
     */
    private function get_hooks(): array {
        if (!$this->sink) {
            throw new coding_exception('get_messages is called prior to redirect_messages');
        }
        $this->sink->close();
        $hooks = $this->sink->get_hooks();
        $this->sink = null;
        return $hooks;
    }

    /**
     * Finish hook redirection and return one hook received.
     *
     * @param string $class
     * @return totara_core\hook\base
     */
    private function get_one_hook(string $class): totara_core\hook\base {
        $hooks = $this->get_hooks();
        $hooks = array_filter($hooks, function ($hook) use ($class) {
            return $hook instanceof $class;
        });
        $this->assertCount(1, $hooks);
        return reset($hooks);
    }

    /**
     * Get the array of sorted item ids.
     *
     * @param Iterator $list an instance of asset_list, room_list or facilitator_list
     * @return integer[]
     */
    private static function get_sorted_ids(Iterator $list): array {
        $array = array_map(function ($item) {
            /** @var seminar_attachment_item $item */
            return $item->get_id();
        }, iterator_to_array($list, false));
        sort($array);
        return $array;
    }

    /**
     * Swap two variables.
     *
     * @param mixed $x
     * @param mixed $y
     */
    private static function swap(&$x, &$y): void {
        $t = $x;
        $x = $y;
        $y = $t;
    }

    public function test_sessions_are_being_updated() {
        $this->redirect_hooks();
        $eventid = $this->f2fgen->add_session([
            'facetoface' => $this->f2f->id,
            'sessiondates' => [],
        ]);
        $this->assertEmpty($this->get_hooks());

        $seminarevent = new seminar_event($eventid);
        $time = time();

        // Insert two sessions.
        $dates = [
            (object)[
                'sessiontimezone' => '99',
                'timestart' => $time - 400,
                'timefinish' => $time - 300,
                'roomids' => [],
                'assetids' => [],
                'facilitatorids' => [],
            ],
            (object)[
                'sessiontimezone' => '99',
                'timestart' => $time + 500,
                'timefinish' => $time + 600,
                'roomids' => [],
                'assetids' => [],
                'facilitatorids' => [],
            ],
        ];
        $this->redirect_hooks();
        seminar_event_helper::merge_sessions($seminarevent, $dates);
        $hook = $this->get_one_hook(sessions_are_being_updated::class);
        /** @var sessions_are_being_updated $hook */
        $this->assertCount(2, $hook->sessionstobeinserted);
        $this->assertEmpty($hook->sessionstobeupdated);
        $this->assertEmpty($hook->sessionstobedeleted);
        $this->assertEquals($time - 400, $hook->sessionstobeinserted[0]->get_session()->get_timestart());
        $this->assertEquals($time + 500, $hook->sessionstobeinserted[1]->get_session()->get_timestart());

        // Delete the past session and insert an ongoing session.
        $dates = $seminarevent->get_sessions(true)->sort('timestart')->to_records(false);
        array_splice($dates, 0, 1, [
            (object)[
                'sessiontimezone' => '99',
                'timestart' => $time - 100,
                'timefinish' => $time + 100,
                'roomids' => [],
                'assetids' => [],
                'facilitatorids' => [],
            ]
        ]);
        $this->redirect_hooks();
        seminar_event_helper::merge_sessions($seminarevent, $dates);
        $hook = $this->get_one_hook(sessions_are_being_updated::class);
        /** @var sessions_are_being_updated $hook */
        $this->assertCount(1, $hook->sessionstobeinserted);
        $this->assertEmpty($hook->sessionstobeupdated);
        $this->assertCount(1, $hook->sessionstobedeleted);
        $this->assertEquals($time - 100, $hook->sessionstobeinserted[0]->get_session()->get_timestart());
        $this->assertEquals($time - 400, $hook->sessionstobedeleted[0]->get_session()->get_timestart());

        // Update the future session time, delete the ongoing session and insert a past session.
        $dates = $seminarevent->get_sessions(true)->sort('timestart')->to_records(false);
        $dates[1]->timestart = $time + 800;
        $dates[1]->timefinish = $time + 900;
        array_splice($dates, 0, 1, [
            (object)[
                'sessiontimezone' => '99',
                'timestart' => $time - 200,
                'timefinish' => $time - 100,
                'roomids' => [],
                'assetids' => [],
                'facilitatorids' => [],
            ]
        ]);
        $this->redirect_hooks();
        seminar_event_helper::merge_sessions($seminarevent, $dates);
        $hook = $this->get_one_hook(sessions_are_being_updated::class);
        /** @var sessions_are_being_updated $hook */
        $this->assertCount(1, $hook->sessionstobeinserted);
        $this->assertCount(1, $hook->sessionstobeupdated);
        $this->assertCount(1, $hook->sessionstobedeleted);
        $this->assertEquals($time - 200, $hook->sessionstobeinserted[0]->get_session()->get_timestart());
        $this->assertEquals($time + 800, $hook->sessionstobeupdated[0]->get_session()->get_timestart());
        $this->assertEquals($time - 100, $hook->sessionstobedeleted[0]->get_session()->get_timestart());

        // Exchange the time of two sessions.
        $dates = $seminarevent->get_sessions(true)->sort('timestart')->to_records(false);
        self::swap($dates[0]->timestart, $dates[1]->timestart);
        self::swap($dates[0]->timefinish, $dates[1]->timefinish);
        $this->redirect_hooks();
        seminar_event_helper::merge_sessions($seminarevent, $dates);
        $hook = $this->get_one_hook(sessions_are_being_updated::class);
        /** @var sessions_are_being_updated $hook */
        $this->assertEmpty($hook->sessionstobeinserted);
        $this->assertCount(2, $hook->sessionstobeupdated);
        $this->assertEmpty($hook->sessionstobedeleted);
        $this->assertEquals($time + 800, $hook->sessionstobeupdated[0]->get_session()->get_timestart());
        $this->assertEquals($time - 200, $hook->sessionstobeupdated[1]->get_session()->get_timestart());
    }

    public function test_resources_are_being_updated() {
        $gen = $this->getDataGenerator();
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $ass1 = new asset($this->f2fgen->add_custom_asset([])->id);
        $ass2 = new asset($this->f2fgen->add_site_wide_asset([])->id);
        $roo1 = new room($this->f2fgen->add_custom_room([])->id);
        $roo2 = new room($this->f2fgen->add_site_wide_room([])->id);
        $fac1 = new facilitator_user($this->f2fgen->add_internal_facilitator([], $user1));
        $fac2 = new facilitator_user($this->f2fgen->add_custom_facilitator([]));
        $fac3 = new facilitator_user($this->f2fgen->add_site_wide_facilitator([]));

        $time = time();
        $eventid = $this->f2fgen->add_session([
            'facetoface' => $this->f2f->id,
            'sessiondates' => [
                (object)['timestart' => $time + 200, 'timefinish' => $time + 300]
            ],
        ]);
        $seminarevent = new seminar_event($eventid);

        // Add assets, rooms and facilitators.
        $dates = $seminarevent->get_sessions(true)->sort('timestart')->to_records(false);
        $dates[0]->assetids = [$ass1->get_id(), $ass2->get_id()];
        $dates[0]->roomids = [$roo1->get_id(), $roo2->get_id()];
        $dates[0]->facilitatorids = [$fac1->get_id(), $fac2->get_id(), $fac3->get_id()];
        $this->redirect_hooks();
        seminar_event_helper::merge_sessions($seminarevent, $dates);
        $hook = $this->get_one_hook(resources_are_being_updated::class);
        /** @var resources_are_being_updated $hook */
        $assetids = self::get_sorted_ids($hook->sessions[0]->get_asset_list());
        $roomids = self::get_sorted_ids($hook->sessions[0]->get_room_list());
        $facilitatorids = self::get_sorted_ids($hook->sessions[0]->get_facilitator_list(false));
        $this->assertEquals([$ass1->get_id(), $ass2->get_id()], $assetids);
        $this->assertEquals([$roo1->get_id(), $roo2->get_id()], $roomids);
        $this->assertEquals([$fac1->get_id(), $fac2->get_id(), $fac3->get_id()], $facilitatorids);

        // Delete all assets, rooms and facilitators.
        $dates = $seminarevent->get_sessions(true)->sort('timestart')->to_records(false);
        $dates[0]->assetids = [];
        $dates[0]->roomids = [];
        $dates[0]->facilitatorids = [];
        $this->redirect_hooks();
        seminar_event_helper::merge_sessions($seminarevent, $dates);
        $hook = $this->get_one_hook(resources_are_being_updated::class);
        /** @var resources_are_being_updated $hook */
        $this->assertTrue($hook->sessions[0]->get_asset_list()->is_empty());
        $this->assertTrue($hook->sessions[0]->get_room_list()->is_empty());
        $this->assertTrue($hook->sessions[0]->get_facilitator_list(false)->is_empty());
    }

    public function test_event_is_being_cancelled() {
        $time = time();
        $pastevent = new seminar_event($this->f2fgen->add_session([
            'facetoface' => $this->f2f->id,
            'sessiondates' => [
                (object)['timestart' => $time - 600, 'timefinish' => $time - 500]
            ],
        ]));
        $this->redirect_hooks();
        $eventid = $pastevent->get_id();
        $pastevent->delete();
        $hook = $this->get_one_hook(event_is_being_cancelled::class);
        /** @var event_is_being_cancelled $hook */
        $this->assertEquals($eventid, $hook->seminarevent->get_id());
        $this->assertTrue($hook->deleted);

        $currentevent = new seminar_event($this->f2fgen->add_session([
            'facetoface' => $this->f2f->id,
            'sessiondates' => [
                (object)['timestart' => $time - 100, 'timefinish' => $time + 100]
            ],
        ]));
        $this->redirect_hooks();
        $eventid = $currentevent->get_id();
        $currentevent->delete();
        $hook = $this->get_one_hook(event_is_being_cancelled::class);
        /** @var event_is_being_cancelled $hook */
        $this->assertEquals($eventid, $hook->seminarevent->get_id());
        $this->assertTrue($hook->deleted);

        $futureevent1 = new seminar_event($this->f2fgen->add_session([
            'facetoface' => $this->f2f->id,
            'sessiondates' => [
                (object)['timestart' => $time + 200, 'timefinish' => $time + 300]
            ],
        ]));
        $this->redirect_hooks();
        $eventid = $futureevent1->get_id();
        $this->assertTrue($futureevent1->cancel());
        $hook = $this->get_one_hook(event_is_being_cancelled::class);
        /** @var event_is_being_cancelled $hook */
        $this->assertEquals($eventid, $hook->seminarevent->get_id());
        $this->assertFalse($hook->deleted);
        $this->redirect_hooks();
        $futureevent1->delete();
        $this->assertEmpty($this->get_hooks());

        $futureevent2 = new seminar_event($this->f2fgen->add_session([
            'facetoface' => $this->f2f->id,
            'sessiondates' => [
                (object)['timestart' => $time + 400, 'timefinish' => $time + 500]
            ],
        ]));
        $this->redirect_hooks();
        $eventid = $futureevent2->get_id();
        $futureevent2->delete();
        $hook = $this->get_one_hook(event_is_being_cancelled::class);
        /** @var event_is_being_cancelled $hook */
        $this->assertEquals($eventid, $hook->seminarevent->get_id());
        $this->assertTrue($hook->deleted);

        $waitlistedevent1 = new seminar_event($this->f2fgen->add_session([
            'facetoface' => $this->f2f->id,
            'sessiondates' => [],
        ]));
        $this->redirect_hooks();
        $eventid = $waitlistedevent1->get_id();
        $this->assertTrue($waitlistedevent1->cancel());
        $hook = $this->get_one_hook(event_is_being_cancelled::class);
        /** @var event_is_being_cancelled $hook */
        $this->assertEquals($eventid, $hook->seminarevent->get_id());
        $this->assertFalse($hook->deleted);
        $this->redirect_hooks();
        $waitlistedevent1->delete();
        $this->assertEmpty($this->get_hooks());

        $waitlistedevent2 = new seminar_event($this->f2fgen->add_session([
            'facetoface' => $this->f2f->id,
            'sessiondates' => [],
        ]));
        $this->redirect_hooks();
        $eventid = $waitlistedevent2->get_id();
        $waitlistedevent2->delete();
        $hook = $this->get_one_hook(event_is_being_cancelled::class);
        /** @var event_is_being_cancelled $hook */
        $this->assertEquals($eventid, $hook->seminarevent->get_id());
        $this->assertTrue($hook->deleted);
    }
}
