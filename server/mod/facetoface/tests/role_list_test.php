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
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package mod_facetoface
 */

use mod_facetoface\role_list;
use mod_facetoface\seminar_event;

class mod_facetoface_role_list_testcase extends advanced_testcase {
    public function test_constructor() {
        global $DB;

        /** @var \mod_facetoface\testing\generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $course1 = $this->getDataGenerator()->create_course();
        $facetoface1 = $facetofacegenerator->create_instance(['name' => 'facetoface1', 'course' => $course1->id]);
        $sessionid1 = $facetofacegenerator->add_session(['facetoface' => $facetoface1->id]);

        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $student3 = $this->getDataGenerator()->create_user();

        $teacher1 = $this->getDataGenerator()->create_user();
        $teacher2 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student3->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacher2->id, $course1->id, 'editingteacher');

        $editingteacher = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $sessrole1 = new stdClass();
        $sessrole1->roleid = $editingteacher->id;
        $sessrole1->sessionid = $sessionid1;
        $sessrole1->userid = $teacher1->id;
        $DB->insert_record('facetoface_session_roles', $sessrole1);

        $list = new role_list(['sessionid' => $sessionid1]);

        $array = iterator_to_array($list);
        $this->assertCount(1, $array);
        $role = reset($array);
        $this->assertInstanceOf(\mod_facetoface\role::class, $role);
        $this->assertEquals($teacher1->id, $role->get_userid());
    }

    public function test_get_distinct_users_from_seminarevent() {
        global $DB;

        /** @var \mod_facetoface\testing\generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $course1 = $this->getDataGenerator()->create_course();
        $facetoface1 = $facetofacegenerator->create_instance(['name' => 'facetoface1', 'course' => $course1->id]);
        $sessionid1 = $facetofacegenerator->add_session(['facetoface' => $facetoface1->id]);

        $facetoface2 = $facetofacegenerator->create_instance(['name' => 'facetoface1', 'course' => $course1->id]);
        $sessionid2 = $facetofacegenerator->add_session(['facetoface' => $facetoface2->id]);

        $student1 = $this->getDataGenerator()->create_user();
        $student2 = $this->getDataGenerator()->create_user();
        $student3 = $this->getDataGenerator()->create_user();

        $teacher1 = $this->getDataGenerator()->create_user();
        $teacher2 = $this->getDataGenerator()->create_user();
        $teacher3 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student3->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacher2->id, $course1->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($teacher1->id, $course1->id, 'teacher');
        $this->getDataGenerator()->enrol_user($teacher3->id, $course1->id, 'teacher');

        $editingteacher = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);

        $sessrole1 = new stdClass();
        $sessrole1->roleid = $editingteacher->id;
        $sessrole1->sessionid = $sessionid1;
        $sessrole1->userid = $teacher1->id;
        $DB->insert_record('facetoface_session_roles', $sessrole1);

        $sessrole2 = new stdClass();
        $sessrole2->roleid = $teacherrole->id;
        $sessrole2->sessionid = $sessionid1;
        $sessrole2->userid = $teacher1->id;
        $DB->insert_record('facetoface_session_roles', $sessrole2);

        $sessrole3 = new stdClass();
        $sessrole3->roleid = $editingteacher->id;
        $sessrole3->sessionid = $sessionid1;
        $sessrole3->userid = $teacher2->id;
        $DB->insert_record('facetoface_session_roles', $sessrole3);

        $sessrole4 = new stdClass();
        $sessrole4->roleid = $teacherrole->id;
        $sessrole4->sessionid = $sessionid2;
        $sessrole4->userid = $teacher3->id;
        $DB->insert_record('facetoface_session_roles', $sessrole4);

        $event1 = seminar_event::seek($sessionid1);

        $list = role_list::get_distinct_users_from_seminarevent($event1);

        $array = iterator_to_array($list);
        $this->assertCount(2, $array);
        $role = reset($array);
        $this->assertInstanceOf(\mod_facetoface\role::class, $role);
        $this->assertEquals($teacher1->id, $role->get_userid());

        $role = next($array);
        $this->assertInstanceOf(\mod_facetoface\role::class, $role);
        $this->assertEquals($teacher2->id, $role->get_userid());
    }
}
