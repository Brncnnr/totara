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
 * @category test
 */

use core\orm\query\builder;
use mod_facetoface\room_dates_virtualmeeting;
use mod_facetoface\room_helper;
use mod_facetoface\room_virtualmeeting;
use totara_core\virtualmeeting\virtual_meeting as virtualmeeting_model;
use mod_facetoface\seminar_event_helper;
use mod_facetoface\task\manage_virtualmeetings_adhoc_task as adhoc_task;
use virtualmeeting_poc_app\poc_factory;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/virtualmeeting_testcase.php');

/**
 * Test the manage_virtualmeetings_adhoc_task task.
 * @group virtualmeeting
 * @covers mod_facetoface\task\manage_virtualmeetings_adhoc_task
 */
class mod_facetoface_manage_virtualmeetings_adhoc_task_testcase extends mod_facetoface_virtualmeeting_testcase {
    /**
     * PhpUnit fixture method that runs before the test method executes.
     */
    public function setUp(): void {
        parent::setUp();
        set_config('facetoface_allow_legacy_notifications', 1);
    }

    public function test_create_from_seminar_event_id() {
        try {
            adhoc_task::create_from_seminar_event_id($this->event1->get_id(), 0);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('No user id set.', $ex->getMessage());
        }

        try {
            adhoc_task::create_from_seminar_event_id(0, $this->user1->id);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('No seminar event id set.', $ex->getMessage());
        }

        $task = adhoc_task::create_from_seminar_event_id($this->event1->get_id(), $this->user1->id);
        $this->assertEquals(['seminar_event_id' => $this->event1->get_id(), 'user_id' => $this->user1->id], (array)$task->get_custom_data());

        $this->setUser($this->user2->id);
        $task = adhoc_task::create_from_seminar_event_id($this->event1->get_id());
        $this->assertEquals(['seminar_event_id' => $this->event1->get_id(), 'user_id' => $this->user2->id], (array)$task->get_custom_data());
    }

    public function test_execute_no_changes() {
        // call execute() with no changes
        // ensure nothing happens
        $url1 = $this->virtualmeeting1->get_join_url();
        $url2 = $this->virtualmeeting2->get_join_url();

        $this->run_adhoc_task($this->event1, $this->user1->id);
        $this->nothing_happened($url1, $url2);

        $this->run_adhoc_task($this->event1, $this->user2->id);
        $this->nothing_happened($url1, $url2);
    }

    public function test_execute_changed_resources_but_unchanged_rooms() {
        // add assets/facilitators to session #1
        // call execute()
        // ensure nothing happens
        $this->setUser($this->user1);

        $url1 = $this->virtualmeeting1->get_join_url();
        $url2 = $this->virtualmeeting2->get_join_url();

        $asset = $this->semgen->add_custom_asset(['name' => 'an asset']);
        $facilitator = $this->semgen->add_custom_facilitator(['name' => 'a facilitator']);
        $rooms = [
            $this->session1->get_id() => [$this->virtual_room1->get_id()],
            $this->session2->get_id() => [$this->virtual_room2->get_id()],
        ];
        $assets = [
            $this->session1->get_id() => [$asset->id],
            $this->session2->get_id() => [],
        ];
        $facilitators = [
            $this->session1->get_id() => [],
            $this->session2->get_id() => [$facilitator->id],
        ];
        $this->event_save_changes($this->event1, $rooms, $assets, $facilitators);

        $this->run_adhoc_task($this->event1);
        $this->nothing_happened($url1, $url2);
    }

    public function test_execute_add_a_custom_room() {
        // add a custom room to session #1
        // call execute()
        // ensure nothing happens
        $this->setUser($this->user1);

        $url1 = $this->virtualmeeting1->get_join_url();
        $url2 = $this->virtualmeeting2->get_join_url();

        $rooms = [
            $this->session1->get_id() => [$this->virtual_room1->get_id(), $this->custom_room->get_id()],
            $this->session2->get_id() => [$this->virtual_room2->get_id()],
        ];
        $this->event_save_changes($this->event1, $rooms);

        $this->run_adhoc_task($this->event1);
        $this->nothing_happened($url1, $url2);
    }

    public function test_execute_remove_a_custom_room() {
        // remove a custom room from session1
        // call execute()
        // ensure nothing happens
        $this->setUser($this->user1);

        $url1 = $this->virtualmeeting1->get_join_url();
        $url2 = $this->virtualmeeting2->get_join_url();

        // Add a custom room to session #1 without disturbing the watcher.
        room_helper::sync($this->session1->get_id(), [$this->virtual_room1->get_id(), $this->custom_room->get_id()]);
        $this->run_adhoc_task($this->event1);
        $this->nothing_happened($url1, $url2);
        $this->assertEquals(2, $this->count_session_room_dates($this->session1));
        $this->assertEquals(1, $this->count_session_room_dates($this->session2));

        // Then simulate save changes.
        $rooms = [
            $this->session1->get_id() => [$this->virtual_room1->get_id()],
            $this->session2->get_id() => [$this->virtual_room2->get_id()],
        ];
        $this->event_save_changes($this->event1, $rooms);

        $this->run_adhoc_task($this->event1);
        $this->nothing_happened($url1, $url2);
    }

    public function test_execute_add_virtual_room_to_session() {
        // add vroom2 to session #1
        // call execute()
        // ensure virtualmeeting record is created for vroom2 on session #1
        $this->setUser($this->user1);

        $url1 = $this->virtualmeeting1->get_join_url();
        $url2 = $this->virtualmeeting2->get_join_url();

        $rooms = [
            $this->session1->get_id() => [$this->virtual_room1->get_id(), $this->virtual_room2->get_id()],
            $this->session2->get_id() => [$this->virtual_room2->get_id()],
        ];
        $this->event_save_changes($this->event1, $rooms);
        $this->run_adhoc_task($this->event1);

        $virtualmeetings = $this->count_virtualmeeting();
        $this->assertEquals(3, $virtualmeetings);
        $room_virtualmeetings = $this->count_room_virtualmeeting();
        $this->assertEquals(2, $room_virtualmeetings);
        $room_date_virtualmeetings = $this->count_room_dates_virtualmeeting();
        $this->assertEquals(3, $room_date_virtualmeetings);
        $room_dates = $this->count_session_room_dates($this->session1);
        $this->assertEquals(2, $room_dates);
        $room_dates = $this->count_session_room_dates($this->session2);
        $this->assertEquals(1, $room_dates);

        // Ensure the existing virtualmeetings are preserved.
        $vm1 = virtualmeeting_model::load_by_id($this->virtualmeeting1->get_id());
        $vm2 = virtualmeeting_model::load_by_id($this->virtualmeeting2->get_id());
        $this->assertEquals($url1, $vm1->get_join_url());
        $this->assertEquals($url2, $vm2->get_join_url());
    }

    public function test_execute_update_time_of_session() {
        // change date/time of session #1
        // call execute()
        // ensure virtualmeeting record is updated for vroom1 on session #1
        $this->setUser($this->user1);

        $url1 = $this->virtualmeeting1->get_join_url();
        $url2 = $this->virtualmeeting2->get_join_url();

        builder::table(room_virtualmeeting::DBTABLE)->update(['status' => room_virtualmeeting::STATUS_CONFIRMED]);
        builder::table(room_dates_virtualmeeting::DBTABLE)->update(['status' => room_dates_virtualmeeting::STATUS_AVAILABLE]);

        $date1 = $this->session1->to_record();
        $date1->roomids = [$this->virtual_room1->get_id()];
        $date1->timestart -= 600;
        $date1->timefinish += 600;
        $date2 = $this->session2->to_record();
        $date2->roomids = [$this->virtual_room2->get_id()];
        seminar_event_helper::merge_sessions($this->event1, [$date1, $date2]);

        $this->run_adhoc_task($this->event1);

        $virtualmeetings = $this->count_virtualmeeting();
        $this->assertEquals(2, $virtualmeetings);
        $room_virtualmeetings = $this->count_room_virtualmeeting();
        $this->assertEquals(2, $room_virtualmeetings);
        $room_date_virtualmeetings = $this->count_room_dates_virtualmeeting();
        $this->assertEquals(2, $room_date_virtualmeetings);
        $vm1 = virtualmeeting_model::load_by_id($this->virtualmeeting1->get_id());
        $vm2 = virtualmeeting_model::load_by_id($this->virtualmeeting2->get_id());
        $this->assert_aged_url($url1, $vm1->get_join_url(), 1, -600, 600);
        $this->assertEquals($url2, $vm2->get_join_url());
    }

    public function test_execute_update_foreign_room() {
        // add a session with a new virtual room as user2
        // call execute()
        // ensure vroom1 and vroom2 are not affected
        $this->setUser($this->user2);

        $url1 = $this->virtualmeeting1->get_join_url();
        $url2 = $this->virtualmeeting2->get_join_url();

        $virtual_room3 = $this->add_virtualmeeting_room(['name' => 'vroom3']);

        $date1 = $this->session1->to_record();
        $date1->roomids = [$this->virtual_room1->get_id()];
        $date2 = $this->session2->to_record();
        $date2->roomids = [$this->virtual_room2->get_id()];
        $date3 = (object)[
            'timestart' => time() + 10800,
            'timefinish' => time() + 12600,
            'sessiontimezone' => 'Pacific/Auckland',
            'roomids' => [$virtual_room3->get_id()],
        ];
        seminar_event_helper::merge_sessions($this->event1, [$date1, $date2, $date3]);

        $this->run_adhoc_task($this->event1);

        $virtualmeetings = $this->count_virtualmeeting();
        $this->assertEquals(3, $virtualmeetings);
        $room_virtualmeetings = $this->count_room_virtualmeeting();
        $this->assertEquals(3, $room_virtualmeetings);
        $room_date_virtualmeetings = $this->count_room_dates_virtualmeeting();
        $this->assertEquals(3, $room_date_virtualmeetings);
        $vm1 = virtualmeeting_model::load_by_id($this->virtualmeeting1->get_id());
        $vm2 = virtualmeeting_model::load_by_id($this->virtualmeeting2->get_id());
        $this->assertEquals($url1, $vm1->get_join_url());
        $this->assertEquals($url2, $vm2->get_join_url());
    }

    public function test_execute_deleted_room() {
        // delete vroom1 from session #1
        // call execute()
        // ensure virtualmeeting record is deleted for vroom1 on session #1
        global $DB;

        // TODO: Test against TL-29046
        $this->markTestSkipped();
        $DB->delete_records('facetoface_room_dates', ['roomid' => $this->virtual_room1->id]);
    }

    public function test_execute_deleted_session() {
        // delete session #1
        // call execute()
        // ensure virtualmeeting record is deleted for vroom1 on session #1

        // TODO: Test against TL-29046
        $this->markTestSkipped();
    }

    public function test_execute_deleted_event() {
        // delete session #1
        // call execute()
        // ensure virtualmeeting record is deleted for vroom1 on session #1 AND session #2

        // TODO: Test against TL-29046
        $this->markTestSkipped();
    }

    public function test_execute_failure_creation_by_service() {
        $this->setUser($this->user1);
        $sink = $this->redirectMessages();

        $url1 = $this->virtualmeeting1->get_join_url();
        $url2 = $this->virtualmeeting2->get_join_url();

        $virtual_room3 = $this->add_virtualmeeting_room(['name' => 'vroom3']);
        $seminar = $this->add_seminar(['name' => 'me failure', 'course' => $this->course->id]);
        $newevent = $this->add_seminar_event([
            'facetoface' => $seminar->get_id(),
            'sessiondates' => [
                [
                    'timestart' => time() + 10800,
                    'timefinish' => time() + 12600,
                    'sessiontimezone' => 'Pacific/Auckland',
                    'roomids' => [$virtual_room3->get_id()],
                ],
            ],
        ]);

        $this->run_adhoc_task($newevent);
        // Kick out notification.
        $this->execute_adhoc_tasks();

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('Virtual meeting creation failure', $messages[0]->subject);
        $this->assertEquals($this->user1->id, $messages[0]->useridto);
        $this->assertStringContainsString('Seminar: me failure', $messages[0]->smallmessage);

        // Ensure the existing virtualmeetings are preserved.
        $vm1 = virtualmeeting_model::load_by_id($this->virtualmeeting1->get_id());
        $vm2 = virtualmeeting_model::load_by_id($this->virtualmeeting2->get_id());
        $this->assertEquals($url1, $vm1->get_join_url());
        $this->assertEquals($url2, $vm2->get_join_url());
    }

    public function data_user_field(): array {
        return [['suspended'], ['deleted']];
    }

    public function test_execute_failure_update_by_service() {
        $this->setUser($this->user1);
        $sink = $this->redirectMessages();

        $url1 = $this->virtualmeeting1->get_join_url();
        $url2 = $this->virtualmeeting2->get_join_url();

        builder::table(room_dates_virtualmeeting::DBTABLE)
            ->where('virtualmeetingid', $this->virtualmeeting1->id)
            ->update(['status' => room_dates_virtualmeeting::STATUS_PENDING_UPDATE]);
        builder::table('facetoface')
            ->where('id', $this->event1->get_facetoface())
            ->update(['name' => 'Test failing seminar']);

        $this->run_adhoc_task($this->event1);
        // Kick out notification.
        $this->execute_adhoc_tasks();

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('Virtual meeting creation failure', $messages[0]->subject);
        $this->assertEquals($this->user1->id, $messages[0]->useridto);
        $this->assertStringContainsString('Seminar: Test failing seminar', $messages[0]->smallmessage);

        // Ensure the existing virtualmeetings are preserved.
        $vm1 = virtualmeeting_model::load_by_id($this->virtualmeeting1->get_id());
        $vm2 = virtualmeeting_model::load_by_id($this->virtualmeeting2->get_id());
        $this->assertEquals($url1, $vm1->get_join_url());
        $this->assertEquals($url2, $vm2->get_join_url());
    }

    public function test_execute_failure_update_by_bogus_plugin() {
        $this->setUser($this->user1);
        $sink = $this->redirectMessages();

        $url1 = $this->virtualmeeting1->get_join_url();
        $url2 = $this->virtualmeeting2->get_join_url();

        builder::table(room_dates_virtualmeeting::DBTABLE)
            ->where('virtualmeetingid', $this->virtualmeeting1->id)
            ->update(['status' => room_dates_virtualmeeting::STATUS_PENDING_UPDATE]);
        builder::table('facetoface')
            ->where('id', $this->event1->get_facetoface())
            ->update(['name' => 'Test failing seminar']);
        builder::table('virtualmeeting')->where('id', $this->virtualmeeting1->id)->update(['plugin' => 'poc_user']);

        $this->run_adhoc_task($this->event1);
        // Kick out notification.
        $this->execute_adhoc_tasks();

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('Virtual meeting creation failure', $messages[0]->subject);
        $this->assertEquals($this->user1->id, $messages[0]->useridto);
        $this->assertStringContainsString('Seminar: Test failing seminar', $messages[0]->smallmessage);

        // Ensure the existing virtualmeetings are preserved.
        $vm1 = virtualmeeting_model::load_by_id($this->virtualmeeting1->get_id());
        $vm2 = virtualmeeting_model::load_by_id($this->virtualmeeting2->get_id());
        $this->assertEquals($url1, $vm1->get_join_url());
        $this->assertEquals($url2, $vm2->get_join_url());
    }

    /**
     * @covers mod_facetoface\task\manage_virtualmeetings_adhoc_task
     * @covers facetoface_notification::send_notification_virtual_meeting_creation_failure
     */
    public function test_execute_unavailable_plugin() {
        // call execute()
        // ensure adhoc task fails with 'plugin is not configured'
        $this->setUser($this->user1);
        $sink = $this->redirectMessages();

        poc_factory::toggle('poc_app', false);
        builder::table(room_dates_virtualmeeting::DBTABLE)
            ->where('virtualmeetingid', $this->virtualmeeting1->id)
            ->update(['status' => room_dates_virtualmeeting::STATUS_PENDING_UPDATE]);

        $this->run_adhoc_task($this->event1);
        // Kick out notification.
        $this->executeAdhocTasks();

        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('Virtual meeting creation failure', $messages[0]->subject);
        $this->assertEquals($this->user1->id, $messages[0]->useridto);
        $this->assertStringContainsString('Seminar: Test seminar', $messages[0]->smallmessage);
    }
}
