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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test Totara-specific functionality in lib/accesslib.php
 */
class totara_core_accesslib_testcase extends advanced_testcase {
    public function test_capability_names() {
        global $DB;
        $capabilities = $DB->get_records('capabilities', array());
        foreach ($capabilities as $capability) {
            $name = get_capability_string($capability->name);
            $this->assertDebuggingNotCalled("Debugging not expected when getting name of capability {$capability->name}");
            $this->assertStringNotContainsString('???', $name, "Unexpected problem when getting name of capability {$capability->name}");
        }
    }

    public function test_role_unassign_all_bulk() {
        global $DB;


        $student = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
        $teacher = $DB->get_record('role', array('shortname' => 'editingteacher'), '*', MUST_EXIST);

        $course1 = $this->getDataGenerator()->create_course();
        $context1 = context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course();
        $context2 = context_course::instance($course2->id);
        $catcontext = context_coursecat::instance($course1->category);

        $user1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, $teacher->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id, $teacher->id);

        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id, $student->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id, $student->id);

        $user3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id, $student->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course2->id, $student->id);

        $user4 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user4->id, $course1->id, $student->id);
        role_assign($student->id, $user4->id, $context1->id, 'totara_core', 77);
        $this->getDataGenerator()->enrol_user($user4->id, $course2->id, $student->id);
        role_assign($student->id, $user4->id, $context2->id, 'totara_core', 66);

        $this->assertCount(10, $DB->get_records('role_assignments'));
        $this->assertCount(2, $DB->get_records('role_assignments', array('userid' => $user1->id)));
        $this->assertCount(2, $DB->get_records('role_assignments', array('userid' => $user2->id)));
        $this->assertCount(2, $DB->get_records('role_assignments', array('userid' => $user3->id)));
        $this->assertCount(4, $DB->get_records('role_assignments', array('userid' => $user4->id)));

        // Empty user list.
        role_unassign_all_bulk(array('contextid' => $context1->id, 'userids' => array()));
        $this->assertCount(10, $DB->get_records('role_assignments'));

        $this->assertDebuggingNotCalled();
        role_unassign_all_bulk(array('contextid' => $context1->id));
        $this->assertCount(10, $DB->get_records('role_assignments'));
        $this->assertDebuggingCalled('Missing userid parameter in role_unassign_all_bulk()');

        try {
            role_unassign_all_bulk(array());
            $this->fail('Exception expected when contextid parameter missing.');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Missing parameters in role_unsassign_all_bulk() call', $e->getMessage());
        }

        try {
            role_unassign_all_bulk(array('contextid' => $catcontext->id, 'xxx' => 1));
            $this->fail('Exception expected when unknown parameter present.');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Unknown role_unsassign_all_bulk() parameter key (key:xxx)', $e->getMessage());
        }

        role_unassign_all_bulk(array('contextid' => $context1->id, 'roleid' => $student->id, 'userids' => array($user4->id), 'component' => 'enrol_self'));
        $this->assertCount(10, $DB->get_records('role_assignments'));

        role_unassign_all_bulk(array('contextid' => $context1->id, 'roleid' => $student->id, 'userids' => array($user4->id), 'component' => 'totara_core'));
        $this->assertCount(9, $DB->get_records('role_assignments'));
        $this->assertCount(2, $DB->get_records('role_assignments', array('userid' => $user1->id)));
        $this->assertCount(2, $DB->get_records('role_assignments', array('userid' => $user2->id)));
        $this->assertCount(2, $DB->get_records('role_assignments', array('userid' => $user3->id)));
        $this->assertCount(1, $DB->get_records('role_assignments', array('userid' => $user4->id, 'contextid' => $context1->id, 'component' => '')));
        $this->assertCount(2, $DB->get_records('role_assignments', array('userid' => $user4->id, 'contextid' => $context2->id)));

        role_unassign_all_bulk(array('contextid' => $context2->id, 'roleid' => $student->id, 'userids' => array($user4->id), 'component' => 'totara_core'), false, true);
        $this->assertCount(7, $DB->get_records('role_assignments'));
        $this->assertCount(2, $DB->get_records('role_assignments', array('userid' => $user1->id)));
        $this->assertCount(2, $DB->get_records('role_assignments', array('userid' => $user2->id)));
        $this->assertCount(2, $DB->get_records('role_assignments', array('userid' => $user3->id)));
        $this->assertCount(1, $DB->get_records('role_assignments', array('userid' => $user4->id, 'contextid' => $context1->id, 'component' => '')));
        $this->assertCount(0, $DB->get_records('role_assignments', array('userid' => $user4->id, 'contextid' => $context2->id)));

        role_unassign_all_bulk(array('contextid' => $catcontext->id, 'userids' => array($user2->id, $user3->id)), true);
        $this->assertCount(3, $DB->get_records('role_assignments'));
        $this->assertCount(2, $DB->get_records('role_assignments', array('userid' => $user1->id)));
        $this->assertCount(0, $DB->get_records('role_assignments', array('userid' => $user2->id)));
        $this->assertCount(0, $DB->get_records('role_assignments', array('userid' => $user3->id)));
        $this->assertCount(1, $DB->get_records('role_assignments', array('userid' => $user4->id)));
    }

    /**
     * Test that core takes over migrated capabilities automatically with a debug message in case
     * we forget to do it in Totara pre-upgrade script.
     */
    public function test_capability_move() {
        global $DB;

        update_capabilities('moodle');
        $this->assertDebuggingNotCalled();

        $viewbadges = $DB->get_record('capabilities', array('name' => 'moodle/badges:viewbadges'), '*', MUST_EXIST);
        $this->assertSame('moodle', $viewbadges->component);

        $DB->set_field('capabilities', 'component', 'totara_core', array('id' => $viewbadges->id));

        update_capabilities('moodle');
        $this->assertDebuggingCalled('Capability \'moodle/badges:viewbadges\' already existed in different component, please fix the pre-upgrade code');

        $newviewbadges = $DB->get_record('capabilities', array('name' => 'moodle/badges:viewbadges'), '*', MUST_EXIST);
        $this->assertEquals($viewbadges, $newviewbadges);
    }

    public function test_has_capability_in_any_context() {
        global $DB, $USER;

        $course = $this->getDataGenerator()->create_course();
        $coursecontext = context_course::instance($course->id);
        $teacherrole = $DB->get_record('role', array('shortname'=>'editingteacher'), '*', MUST_EXIST);
        $teacher = $this->getDataGenerator()->create_user();
        role_assign($teacherrole->id, $teacher->id, $coursecontext);
        $admin = $DB->get_record('user', array('username'=>'admin'));

        $courses2 = $this->getDataGenerator()->create_course();
        $coursecontext2 = context_course::instance($course->id);
        role_assign($teacherrole->id, $teacher->id, $coursecontext2);

        // Any course level capabilities that editing teacher has will do.
        $this->assertTrue($DB->record_exists('capabilities', array('name'=>'moodle/backup:backupsection')));
        $this->assertTrue($DB->record_exists('capabilities', array('name'=>'moodle/backup:backupcourse')));
        // Same admin only capability.
        $this->assertTrue($DB->record_exists('capabilities', array('name'=>'moodle/site:approvecourse')));

        $this->setUser(0);
        $this->assertFalse(has_capability_in_any_context('moodle/backup:backupsection', null));
        $this->assertFalse(has_capability_in_any_context('moodle/backup:backupcourse', null));
        $this->assertFalse(has_capability_in_any_context('moodle/site:approvecourse', null));
        $this->assertFalse(has_capability_in_any_context('moodle/backup:backupsection', null, $USER));
        $this->assertFalse(has_capability_in_any_context('moodle/backup:backupcourse', null, $USER));
        $this->assertFalse(has_capability_in_any_context('moodle/site:approvecourse', null, $USER));
        $this->setUser($admin);
        $this->assertFalse(has_capability_in_any_context('moodle/backup:backupsection', null, 0));
        $this->assertFalse(has_capability_in_any_context('moodle/backup:backupcourse', null, 0));
        $this->assertFalse(has_capability_in_any_context('moodle/site:approvecourse', null, 0));

        $this->setUser($teacher);
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupsection', null));
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupcourse', null));
        $this->assertFalse(has_capability_in_any_context('moodle/site:approvecourse', null));
        $this->assertFalse(has_capability_in_any_context('moodle/backup:backupsection', [CONTEXT_COURSECAT]));
        $this->assertFalse(has_capability_in_any_context('moodle/backup:backupcourse', [CONTEXT_COURSECAT]));
        $this->assertFalse(has_capability_in_any_context('moodle/site:approvecourse', [CONTEXT_COURSECAT]));
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupsection', [CONTEXT_COURSECAT, CONTEXT_COURSE]));
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupcourse', [CONTEXT_COURSECAT, CONTEXT_COURSE]));
        $this->assertFalse(has_capability_in_any_context('moodle/site:approvecourse', [CONTEXT_COURSECAT, CONTEXT_COURSE]));
        $this->setUser(0);
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupsection', [CONTEXT_COURSECAT, CONTEXT_COURSE], $teacher));
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupcourse', [CONTEXT_COURSECAT, CONTEXT_COURSE], $teacher));
        $this->assertFalse(has_capability_in_any_context('moodle/site:approvecourse', [CONTEXT_COURSECAT, CONTEXT_COURSE], $teacher));

        $this->setUser($admin);
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupsection', null));
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupcourse', null));
        $this->assertTrue(has_capability_in_any_context('moodle/site:approvecourse', null));
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupsection', [CONTEXT_COURSECAT]));
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupcourse', [CONTEXT_COURSECAT]));
        $this->assertTrue(has_capability_in_any_context('moodle/site:approvecourse', [CONTEXT_COURSECAT]));
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupsection', [CONTEXT_COURSECAT, CONTEXT_COURSE]));
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupcourse', [CONTEXT_COURSECAT, CONTEXT_COURSE]));
        $this->assertTrue(has_capability_in_any_context('moodle/site:approvecourse', [CONTEXT_COURSECAT, CONTEXT_COURSE]));
        $this->setUser(0);
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupsection', [CONTEXT_COURSECAT, CONTEXT_COURSE], $admin));
        $this->assertTrue(has_capability_in_any_context('moodle/backup:backupcourse', [CONTEXT_COURSECAT, CONTEXT_COURSE], $admin));
        $this->assertTrue(has_capability_in_any_context('moodle/site:approvecourse', [CONTEXT_COURSECAT, CONTEXT_COURSE], $admin));
        $this->assertFalse(has_capability_in_any_context('moodle/backup:backupsection', [CONTEXT_COURSECAT, CONTEXT_COURSE], $admin, false));
        $this->assertFalse(has_capability_in_any_context('moodle/backup:backupcourse', [CONTEXT_COURSECAT, CONTEXT_COURSE], $admin, false));
        $this->assertFalse(has_capability_in_any_context('moodle/site:approvecourse', [CONTEXT_COURSECAT, CONTEXT_COURSE], $admin, false));
    }

    public function test_has_role_with_capability() {
        global $DB;

        $cat = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $cat->id]);
        $course_context = context_course::instance($course->id);
        $teacher_role = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
        $teacher = $this->getDataGenerator()->create_user();
        $admin = $this->getDataGenerator()->create_user();

        $this->setUser($teacher);

        $this->assertFalse(has_role_with_capability('moodle/course:managegroups'));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', null, $teacher->id));

        role_assign($teacher_role->id, $teacher->id, $course_context);

        $this->assertTrue(has_role_with_capability('moodle/course:managegroups'));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', null, $teacher->id));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE]));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT]));

        $this->setUser($admin);

        $this->assertFalse(has_role_with_capability('moodle/course:managegroups'));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT]));

        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [], $teacher->id));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE], $teacher->id));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT], $teacher->id));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT], $teacher->id));

        $this->setAdminUser();

        role_assign($teacher_role->id, $teacher->id, context_coursecat::instance($cat->id));
        role_unassign($teacher_role->id, $teacher->id, $course_context->id);

        $this->setUser($teacher);

        $this->assertTrue(has_role_with_capability('moodle/course:managegroups'));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', null, $teacher->id));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT]));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE]));

        $this->setUser($admin);

        $this->assertFalse(has_role_with_capability('moodle/course:managegroups'));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT]));

        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [], $teacher->id));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT], $teacher->id));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT], $teacher->id));
        // This is one of the limitation of this function: It does not check the parent contexts but this is for performance reasons
        // so it is always better to check all possible context levels
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE], $teacher->id));

        $this->setAdminUser();

        $this->assertTrue(has_role_with_capability('moodle/course:managegroups'));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT]));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT]));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE]));

        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', null, null, false));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT], null, false));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT], null, false));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE], null, false));

        $this->setUser(0);

        $this->assertFalse(has_role_with_capability('moodle/course:managegroups'));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE]));

        $this->setGuestUser();

        $this->assertFalse(has_role_with_capability('moodle/course:managegroups'));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE]));
    }

    public function test_has_role_with_capability_with_multi_tenancy() {
        global $DB;

        /** @var totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $this->getDataGenerator()->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        // Create users.
        $teacher1 = $this->getDataGenerator()->create_user(['tenantmember' => $tenant1->idnumber]);
        $teacher2 = $this->getDataGenerator()->create_user(['tenantmember' => $tenant2->idnumber]);

        $cat1 = $this->getDataGenerator()->create_category([
            'parent' => $tenant1->categoryid
        ]);
        $cat2 = $this->getDataGenerator()->create_category([
            'parent' => $tenant2->categoryid
        ]);
        $course1 = $this->getDataGenerator()->create_course(['category' => $cat1->id]);
        $course_context1 = context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course(['category' => $cat2->id]);
        $course_context2 = context_course::instance($course2->id);

        $teacher_role = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
        // System user
        $system_user = $this->getDataGenerator()->create_user();

        $this->setUser($teacher1);

        $this->assertFalse(has_role_with_capability('moodle/course:managegroups'));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', null, $teacher1->id));

        $this->setAdminUser();

        role_assign($teacher_role->id, $teacher1->id, $course_context1);

        $this->setUser($teacher1);

        $this->assertTrue(has_role_with_capability('moodle/course:managegroups'));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', null, $teacher1->id));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE]));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT]));

        $this->setUser($teacher2);

        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', null));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT]));

        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', null, $teacher1->id));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE], $teacher1->id));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT], $teacher1->id));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT], $teacher1->id));

        $this->setAdminUser();

        role_assign($teacher_role->id, $system_user->id, $course_context1);

        $this->setUser($system_user);

        $this->assertTrue(has_role_with_capability('moodle/course:managegroups'));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE]));
        $this->assertTrue(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT]));

        $this->setUser(0);

        $this->assertFalse(has_role_with_capability('moodle/course:managegroups'));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE]));

        $this->setGuestUser();

        $this->assertFalse(has_role_with_capability('moodle/course:managegroups'));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE, CONTEXT_COURSECAT]));
        $this->assertFalse(has_role_with_capability('moodle/course:managegroups', [CONTEXT_COURSE]));
    }
}
