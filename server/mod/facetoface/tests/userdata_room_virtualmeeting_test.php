<?php
/*
* This file is part of Totara Learn
*
* Copyright (C) 2021 onwards Totara Learning Solutions LTD
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

use mod_facetoface\userdata\room_virtualmeeting;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/virtualmeeting_testcase.php');

class mod_facetoface_userdata_room_virtualmeeting_testcase extends mod_facetoface_virtualmeeting_testcase {
    /** @var target_user */
    private $targetuser1;

    /** @var target_user */
    private $targetuser2;

    public function setUp(): void {
        parent::setUp();
        $this->targetuser1 = new target_user($this->user1->to_record());
        $this->targetuser2 = new target_user($this->user2->to_record());
    }

    public function tearDown(): void {
        $this->targetuser1 = null;
        $this->targetuser2 = null;
        parent::tearDown();
    }

    /**
     * Test count.
     */
    public function test_count() {
        // System context
        $this->assertEquals(2, room_virtualmeeting::execute_count($this->targetuser1, context_system::instance()));
        $this->assertEquals(0, room_virtualmeeting::execute_count($this->targetuser2, context_system::instance()));

        // Module context
        $coursemodule = $this->event1->get_seminar()->get_coursemodule();
        $modulecontext = context_module::instance($coursemodule->id);
        $this->assertEquals(2, room_virtualmeeting::execute_count($this->targetuser1, $modulecontext));
        $this->assertEquals(0, room_virtualmeeting::execute_count($this->targetuser2, $modulecontext));

        // Course context
        $coursemodule = $this->event1->get_seminar()->get_coursemodule();
        $coursecontext = context_course::instance($coursemodule->course);
        $this->assertEquals(2, room_virtualmeeting::execute_count($this->targetuser1, $coursecontext));
        $this->assertEquals(0, room_virtualmeeting::execute_count($this->targetuser2, $coursecontext));

        // Course category context
        $coursemodule = $this->event1->get_seminar()->get_coursemodule();
        $course = get_course($coursemodule->course);
        $coursecatcontext = context_coursecat::instance($course->category);
        $this->assertEquals(2, room_virtualmeeting::execute_count($this->targetuser1, $coursecatcontext));
        $this->assertEquals(0, room_virtualmeeting::execute_count($this->targetuser2, $coursecatcontext));
    }

    /**
     * Test export.
     */
    public function test_export() {
        // System content.
        $export = room_virtualmeeting::execute_export($this->targetuser1, context_system::instance());
        $data = $export->data;
        $this->assertCount(2, $data);

        $record = array_shift($data);
        $this->assertEquals($this->targetuser1->id, $record->userid);
        $this->assertEquals('vroom1', $record->name);
        $this->assertEquals('poc_app', $record->plugin);
        $this->assertNotEmpty($record->description);

        // Module context
        $coursemodule = $this->event1->get_seminar()->get_coursemodule();
        $modulecontext = context_module::instance($coursemodule->id);
        $export = room_virtualmeeting::execute_export($this->targetuser1, $modulecontext);
        $data = $export->data;
        $this->assertCount(2, $data);

        $record = array_shift($data);
        $this->assertEquals($this->targetuser1->id, $record->userid);
        $this->assertEquals('vroom1', $record->name);
        $this->assertEquals('poc_app', $record->plugin);
        $this->assertNotEmpty($record->description);

        // Course context
        $coursemodule = $this->event1->get_seminar()->get_coursemodule();
        $coursecontext = context_course::instance($coursemodule->course);
        $export = room_virtualmeeting::execute_export($this->targetuser1, $coursecontext);
        $data = $export->data;
        $this->assertCount(2, $data);

        $record = array_shift($data);
        $this->assertEquals($this->targetuser1->id, $record->userid);
        $this->assertEquals('vroom1', $record->name);
        $this->assertEquals('poc_app', $record->plugin);
        $this->assertNotEmpty($record->description);

        // Course category context
        $coursemodule = $this->event1->get_seminar()->get_coursemodule();
        $course = get_course($coursemodule->course);
        $coursecatcontext = context_coursecat::instance($course->category);
        $export = room_virtualmeeting::execute_export($this->targetuser1, $coursecatcontext);
        $data = $export->data;
        $this->assertCount(2, $data);

        $record = array_shift($data);
        $this->assertEquals($this->targetuser1->id, $record->userid);
        $this->assertEquals('vroom1', $record->name);
        $this->assertEquals('poc_app', $record->plugin);
        $this->assertNotEmpty($record->description);

        $export = room_virtualmeeting::execute_export($this->targetuser2, context_system::instance());
        $data = $export->data;
        $this->assertEmpty($data);
    }

    public function test_purge_context_system() {
        global $DB;

        $status = room_virtualmeeting::execute_purge($this->targetuser2, context_system::instance());

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertEquals(2, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user1->id]));
        $this->assertEquals(0, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user2->id]));

        $status = room_virtualmeeting::execute_purge($this->targetuser1, context_system::instance());

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertEquals(0, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user1->id]));
        $this->assertEquals(0, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user2->id]));
    }

    public function test_purge_context_module() {
        global $DB;

        $coursemodule = $this->event1->get_seminar()->get_coursemodule();
        $modulecontext = context_module::instance($coursemodule->id);

        $status = room_virtualmeeting::execute_purge($this->targetuser2, $modulecontext);

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertEquals(2, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user1->id]));
        $this->assertEquals(0, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user2->id]));

        $status = room_virtualmeeting::execute_purge($this->targetuser1, $modulecontext);

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertEquals(0, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user1->id]));
        $this->assertEquals(0, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user2->id]));
    }

    public function test_purge_context_course() {
        global $DB;

        $coursemodule = $this->event1->get_seminar()->get_coursemodule();
        $coursecontext = context_course::instance($coursemodule->course);

        $status = room_virtualmeeting::execute_purge($this->targetuser2, $coursecontext);

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertEquals(2, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user1->id]));
        $this->assertEquals(0, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user2->id]));

        $status = room_virtualmeeting::execute_purge($this->targetuser1, $coursecontext);

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertEquals(0, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user1->id]));
        $this->assertEquals(0, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user2->id]));
    }

    public function test_purge_context_course_category() {
        global $DB;

        $coursemodule = $this->event1->get_seminar()->get_coursemodule();
        $course = get_course($coursemodule->course);
        $coursecatcontext = context_coursecat::instance($course->category);

        $status = room_virtualmeeting::execute_purge($this->targetuser2, $coursecatcontext);

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertEquals(2, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user1->id]));
        $this->assertEquals(0, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user2->id]));

        $status = room_virtualmeeting::execute_purge($this->targetuser1, $coursecatcontext);

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertEquals(0, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user1->id]));
        $this->assertEquals(0, $DB->count_records('facetoface_room_virtualmeeting', ['userid' => $this->user2->id]));
    }
}
