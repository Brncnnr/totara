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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_user
 */

use core_user\access_controller;
use totara_core\hook\manager as hook_manager;
use core_user\hook\allow_view_profile_field;
use core_user\hook\allow_view_profile;
use totara_job\watcher\core_user_access_controller;

class core_user_access_controller_testcase extends advanced_testcase {

    public function setUp(): void {
        parent::setUp();

        // Reset the hook watchers so that we can have more accurate tests.
        hook_manager::phpunit_replace_watchers([
            [
                'hookname' => allow_view_profile_field::class,
                'callback' => [core_user_access_controller::class, 'allow_view_profile_field']
            ],
            [
                'hookname' => allow_view_profile::class,
                'callback' => [core_user_access_controller::class, 'allow_view_profile']
            ]
        ]);
        access_controller::clear_instance_cache();
    }

    public function test_for_creation() {

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        self::assertInstanceOf(access_controller::class, access_controller::for($user));
        self::assertInstanceOf(access_controller::class, access_controller::for($user, $course->id));
        self::assertInstanceOf(access_controller::class, access_controller::for($user, $course));

        try {
            access_controller::for($user->id);
            self::fail('Exception expected');
        } catch (coding_exception $exception) {
            self::assertStringContainsString('User access controllers can only be used for real users.', $exception->getMessage());
        }

        try {
            access_controller::for(null);
            self::fail('Exception expected');
        } catch (coding_exception $exception) {
            self::assertStringContainsString('User access controllers can only be used for real users.', $exception->getMessage());
        }

        try {
            access_controller::for((object)['id' => 0]);
            self::fail('Exception expected');
        } catch (coding_exception $exception) {
            self::assertStringContainsString('User access controllers can only be used for real users.', $exception->getMessage());
        }

        try {
            unset($user->id);
            access_controller::for($user);
            self::fail('Exception expected');
        } catch (coding_exception $exception) {
            self::assertStringContainsString('User access controllers can only be used for real users.', $exception->getMessage());
        }

    }

    public function test_for_user_id() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        self::assertInstanceOf(access_controller::class, access_controller::for_user_id($user->id));
        self::assertInstanceOf(access_controller::class, access_controller::for_user_id($user->id, $course->id));
        self::assertInstanceOf(access_controller::class, access_controller::for_user_id($user->id, $course));

        try {
            access_controller::for_user_id(0);
            self::fail('Exception expected');
        } catch (coding_exception $exception) {
            self::assertStringContainsString('Userid does not belong to a real user.', $exception->getMessage());
        }

        try {
            access_controller::for_user_id(-1);
            self::fail('Exception expected');
        } catch (coding_exception $exception) {
            self::assertStringContainsString('Userid does not belong to a real user.', $exception->getMessage());
        }

        try {
            $id = self::get_unused_userid();
            access_controller::for_user_id($id);
            self::fail('Exception expected');
        } catch (dml_missing_record_exception $exception) {
            self::assertStringContainsString('Can not find data record in database', $exception->getMessage());
        }
    }

    public function test_for_current_user() {

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        try {
            access_controller::for_current_user();
            self::fail('Exception expected');
        } catch (coding_exception $exception) {
            self::assertStringContainsString('There is no current user', $exception->getMessage());
        }

        try {
            access_controller::for_current_user($course);
            self::fail('Exception expected');
        } catch (coding_exception $exception) {
            self::assertStringContainsString('There is no current user', $exception->getMessage());
        }

        $this->setUser($user);

        self::assertInstanceOf(access_controller::class, access_controller::for_current_user());
        self::assertInstanceOf(access_controller::class, access_controller::for_current_user($course->id));
        self::assertInstanceOf(access_controller::class, access_controller::for_current_user($course));

    }

    public static function test_clear_instance_cache() {
        // We can't actually populate the cache as its unusable in unit tests.
        // Just make sure the function succeeds.
        \core_user\access_controller::clear_instance_cache();
    }

    public function test_access_controller_initialisation() {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $context = context_user::instance($user->id); // Just make sure its pre-cached.

        // Test there is no database interaction when we have what we need.
        $reads_before = $DB->perf_get_reads();
        $controller = access_controller::for($user);
        $reads_after = $DB->perf_get_reads();
        self::assertSame($reads_before, $reads_after);
        self::assertSame(false, self::get_controller_property($controller, 'userdeleted'));
        self::assertSame((int)$user->maildisplay, self::get_controller_property($controller, 'usermaildisplay'));
        self::assertSame(false, self::get_controller_property($controller, 'iscurrentuser'));
        self::assertSame($context, self::get_controller_property($controller, 'context_user'));
        self::assertSame(null, self::get_controller_property($controller, 'courseid'));
        self::assertSame(null, self::get_controller_property($controller, 'cachedcourse'));

        // Test we load data from the database when required
        $controller = access_controller::for((object)['id' => $user->id], $course);
        $reads_after = $DB->perf_get_reads();
        self::assertSame($reads_before + 1, $reads_after);
        self::assertSame(false, self::get_controller_property($controller, 'userdeleted'));
        self::assertSame((int)$user->maildisplay, self::get_controller_property($controller, 'usermaildisplay'));
        self::assertSame(false, self::get_controller_property($controller, 'iscurrentuser'));
        self::assertSame($context, self::get_controller_property($controller, 'context_user'));
        self::assertSame($course->id, self::get_controller_property($controller, 'courseid'));
        self::assertSame($course, self::get_controller_property($controller, 'cachedcourse'));

        // Test for the current user
        $this->setUser($user);
        $controller = access_controller::for($user, $course);
        self::assertSame(false, self::get_controller_property($controller, 'userdeleted'));
        self::assertSame((int)$user->maildisplay, self::get_controller_property($controller, 'usermaildisplay'));
        self::assertSame(true, self::get_controller_property($controller, 'iscurrentuser'));
        self::assertSame($context, self::get_controller_property($controller, 'context_user'));
        self::assertSame($course->id, self::get_controller_property($controller, 'courseid'));
        self::assertSame($course, self::get_controller_property($controller, 'cachedcourse'));

        // Test for deleted user.
        $user = $this->getDataGenerator()->create_user();
        delete_user($user);
        $controller = access_controller::for_user_id($user->id, $course);
        self::assertSame(true, self::get_controller_property($controller, 'userdeleted'));
        self::assertSame((int)$user->maildisplay, self::get_controller_property($controller, 'usermaildisplay'));
        self::assertSame(false, self::get_controller_property($controller, 'iscurrentuser'));
        self::assertSame(false, self::get_controller_property($controller, 'context_user'));
        self::assertSame($course->id, self::get_controller_property($controller, 'courseid'));
        self::assertSame($course, self::get_controller_property($controller, 'cachedcourse'));

        // Test for fake user.
        try {
            access_controller::for((object)['id' => 0]);
            self::fail('Exception expected');
        } catch (coding_exception $exception) {
            self::assertStringContainsString('User access controllers can only be used for real users.', $exception->getMessage());
        }

        // Test for invalid user.
        try {
            access_controller::for((object)['id' => -10]);
            self::fail('Exception expected');
        } catch (coding_exception $exception) {
            self::assertStringContainsString('User access controllers can only be used for real users.', $exception->getMessage());
        }

        // Test for fake user direct to constructor.
        try {
            $ref = new ReflectionClass(access_controller::class);
            $controller = $ref->newInstanceWithoutConstructor();
            $constructor = new ReflectionMethod($controller, '__construct');
            $constructor->setAccessible(true);
            $constructor->invoke($controller, (object)['id' => 0]);
            self::fail('Exception expected');
        } catch (coding_exception $exception) {
            self::assertStringContainsString('User access controllers can only be used for real users.', $exception->getMessage());
        }
    }

    public function test_can_view_profile_not_logged_in() {
        global $CFG;

        self::assertNotEmpty($CFG->forceloginforprofiles);

        $user = $this->getDataGenerator()->create_user();

        self::assertFalse(access_controller::for($user)->can_view_profile());
        $CFG->forceloginforprofiles = false;
        self::assertTrue(access_controller::for($user)->can_view_profile());
    }

    public function test_can_view_profile_as_guest_user() {
        global $CFG;

        self::assertNotEmpty($CFG->forceloginforprofiles);

        $user = $this->getDataGenerator()->create_user();

        $this->setGuestUser();
        self::assertFalse(access_controller::for($user)->can_view_profile());
        $CFG->forceloginforprofiles = false;
        self::assertTrue(access_controller::for($user)->can_view_profile());
        // Guest user viewing the guest user.
        self::assertTrue(access_controller::for(guest_user())->can_view_profile());
    }

    public function test_can_view_profile_of_guest_user() {
        global $CFG;

        self::assertNotEmpty($CFG->forceloginforprofiles);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        self::assertFalse(access_controller::for(guest_user())->can_view_profile());
        $CFG->forceloginforprofiles = false;
        self::assertTrue(access_controller::for(guest_user())->can_view_profile());
    }

    public function test_can_view_profile_as_admin_user() {
        global $CFG;

        self::assertNotEmpty($CFG->forceloginforprofiles);

        $user = $this->getDataGenerator()->create_user();
        $this->setAdminUser();

        self::assertTrue(access_controller::for($user)->can_view_profile());
    }

    public function test_can_view_profile_of_admin_user() {
        global $CFG;

        self::assertNotEmpty($CFG->forceloginforprofiles);

        $this->setUser($this->getDataGenerator()->create_user());

        self::assertFalse(access_controller::for(get_admin())->can_view_profile());
        $CFG->forceloginforprofiles = false;
        self::assertTrue(access_controller::for(get_admin())->can_view_profile());
    }

    public function test_can_view_profile_current_user() {
        global $CFG;

        self::assertNotEmpty($CFG->forceloginforprofiles);

        $this->setUser($this->getDataGenerator()->create_user());
        $user = $this->getDataGenerator()->create_user();

        self::assertFalse(access_controller::for($user)->can_view_profile());
        $this->setUser($user);
        self::assertTrue(access_controller::for($user)->can_view_profile());
    }

    public function test_can_view_profile_of_deleted_user() {
        global $CFG;

        self::assertNotEmpty($CFG->forceloginforprofiles);

        $user = $this->getDataGenerator()->create_user();
        delete_user($user);

        $this->setAdminUser();

        self::assertFalse(access_controller::for_user_id($user->id)->can_view_profile());
        $CFG->forceloginforprofiles = false;
        self::assertFalse(access_controller::for_user_id($user->id)->can_view_profile());
    }

    public function test_can_view_profile_course_contact_role() {
        global $CFG, $DB;

        self::assertNotEmpty($CFG->forceloginforprofiles);

        $editingtrainer = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        self::assertContains($editingtrainer, explode(',', $CFG->coursecontact));

        $this->setUser($this->getDataGenerator()->create_user());
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        self::assertFalse(access_controller::for($user)->can_view_profile());
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $editingtrainer);
        self::assertTrue(has_coursecontact_role($user->id));
        self::assertTrue(access_controller::for($user)->can_view_profile());
    }

    public function test_can_view_profile_view_details_capability_at_system() {
        global $CFG, $DB;

        self::assertNotEmpty($CFG->forceloginforprofiles);
        $CFG->coursecontact = '';
        $role_editingtrainer = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        self::assertNotContainsEquals($role_editingtrainer, explode(',', $CFG->coursecontact));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $viewrole = $this->getDataGenerator()->create_role();
        $viewallrole = $this->getDataGenerator()->create_role();
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $viewrole, context_system::instance());
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $viewallrole, context_system::instance());
        $this->getDataGenerator()->role_assign($viewrole, $user2->id);
        $this->getDataGenerator()->role_assign($viewallrole, $user3->id);

        $this->setUser($user1);
        self::assertFalse(access_controller::for($user2)->can_view_profile());
        self::assertFalse(access_controller::for($user3)->can_view_profile());

        $this->setUser($user2);
        self::assertTrue(access_controller::for($user1)->can_view_profile());
        self::assertTrue(access_controller::for($user3)->can_view_profile());

        $this->setUser($user3);
        self::assertTrue(access_controller::for($user1)->can_view_profile());
        self::assertTrue(access_controller::for($user2)->can_view_profile());
    }

    public function test_can_view_profile_view_details_capability_at_user() {
        global $CFG, $DB;

        self::assertNotEmpty($CFG->forceloginforprofiles);
        $CFG->coursecontact = '';
        $role_editingtrainer = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        self::assertNotContainsEquals($role_editingtrainer, explode(',', $CFG->coursecontact));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $viewrole = $this->getDataGenerator()->create_role();
        $viewallrole = $this->getDataGenerator()->create_role();
        $context = \context_user::instance($user1->id);
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $viewrole, context_system::instance());
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $viewallrole, context_system::instance());
        $this->getDataGenerator()->role_assign($viewrole, $user2->id, $context);
        $this->getDataGenerator()->role_assign($viewallrole, $user3->id, $context);

        $this->setUser($user1);
        self::assertFalse(access_controller::for($user2)->can_view_profile());
        self::assertFalse(access_controller::for($user3)->can_view_profile());

        $this->setUser($user2);
        self::assertTrue(access_controller::for($user1)->can_view_profile());
        self::assertFalse(access_controller::for($user3)->can_view_profile());

        $this->setUser($user3);
        self::assertTrue(access_controller::for($user1)->can_view_profile());
        self::assertFalse(access_controller::for($user2)->can_view_profile());
    }

    public function test_can_view_profile_view_details_capability_at_course() {
        global $CFG, $DB;

        self::assertNotEmpty($CFG->forceloginforprofiles);
        $CFG->coursecontact = '';
        $role_editingtrainer = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        self::assertNotContainsEquals($role_editingtrainer, explode(',', $CFG->coursecontact));

        $course = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $user1context = context_user::instance($user1->id);
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $viewrole = $this->getDataGenerator()->create_role();
        $viewallrole = $this->getDataGenerator()->create_role();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $context = \context_course::instance($course->id);
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $viewrole, $context);
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $viewallrole, $user1context);
        $this->getDataGenerator()->role_assign($viewrole, $user2->id, $context);
        $this->getDataGenerator()->role_assign($viewallrole, $user3->id, $user1context);

        // Remove the viewdetails cap from the learner, so that our custom roles have to grant it.
        $role_student = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        assign_capability('moodle/user:viewdetails', CAP_PREVENT, $role_student, $context);
        assign_capability('moodle/user:viewalldetails', CAP_PREVENT, $role_student, $context);

        $this->setUser($user1);
        self::assertTrue(access_controller::for($user1)->can_view_profile());
        self::assertFalse(access_controller::for($user2)->can_view_profile());
        self::assertFalse(access_controller::for($user3)->can_view_profile());

        $this->setUser($user2);
        self::assertTrue(access_controller::for($user1)->can_view_profile());
        self::assertTrue(access_controller::for($user2)->can_view_profile());
        self::assertTrue(access_controller::for($user3)->can_view_profile());

        $this->setUser($user3);
        self::assertTrue(access_controller::for($user1)->can_view_profile());
        self::assertFalse(access_controller::for($user2)->can_view_profile());
        self::assertTrue(access_controller::for($user3)->can_view_profile());
    }

    public function test_can_view_profile_view_details_capability_at_course_separate_groups() {
        global $CFG, $DB;

        self::assertNotEmpty($CFG->forceloginforprofiles);
        $CFG->coursecontact = '';
        $role_editingtrainer = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        self::assertNotContainsEquals($role_editingtrainer, explode(',', $CFG->coursecontact));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['groupmode' => SEPARATEGROUPS]);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $group_a = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group_b = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group_a, 'userid' => $user1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group_b, 'userid' => $user2->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group_b, 'userid' => $user3->id]);

        $this->setUser($user1);
        self::assertFalse(access_controller::for($user2)->can_view_profile());
        self::assertFalse(access_controller::for($user3)->can_view_profile());
        self::assertFalse(access_controller::for($user4)->can_view_profile());

        $this->setUser($user2);
        self::assertTrue(groups_user_groups_visible($course, $user3->id));
        self::assertFalse(access_controller::for($user1)->can_view_profile());
        self::assertTrue(access_controller::for($user3)->can_view_profile());
        self::assertFalse(access_controller::for($user4)->can_view_profile());

        $this->setUser($user3);
        self::assertFalse(access_controller::for($user1)->can_view_profile());
        self::assertTrue(access_controller::for($user2)->can_view_profile());
        self::assertFalse(access_controller::for($user4)->can_view_profile());

        $this->setUser($user4);
        self::assertFalse(access_controller::for($user1)->can_view_profile());
        self::assertFalse(access_controller::for($user2)->can_view_profile());
        self::assertFalse(access_controller::for($user3)->can_view_profile());
    }

    public function test_can_view_profile_view_details_capability_at_course_visible_groups() {
        global $CFG, $DB;

        self::assertNotEmpty($CFG->forceloginforprofiles);
        $CFG->coursecontact = '';
        $role_editingtrainer = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        self::assertNotContainsEquals($role_editingtrainer, explode(',', $CFG->coursecontact));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['groupmode' => VISIBLEGROUPS]);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $group_a = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group_b = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group_a, 'userid' => $user1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group_b, 'userid' => $user2->id]);

        $this->setUser($user1);
        self::assertTrue(access_controller::for($user2)->can_view_profile());
        self::assertTrue(access_controller::for($user3)->can_view_profile());

        $this->setUser($user2);
        self::assertTrue(access_controller::for($user1)->can_view_profile());
        self::assertTrue(access_controller::for($user3)->can_view_profile());

        $this->setUser($user3);
        self::assertTrue(access_controller::for($user1)->can_view_profile());
        self::assertTrue(access_controller::for($user2)->can_view_profile());
    }

    public function test_can_view_profile_real_roles() {
        global $CFG, $DB;

        self::assertNotEmpty($CFG->forceloginforprofiles);

        $role_editingtrainer = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        $role_sitemanager = $DB->get_field('role', 'id', ['shortname' => 'manager'], MUST_EXIST);
        $role_staffmanager = $DB->get_field('role', 'id', ['shortname' => 'staffmanager'], MUST_EXIST);
        self::assertContains($role_editingtrainer, explode(',', $CFG->coursecontact));

        $user = $this->getDataGenerator()->create_user();
        $learner = $this->getDataGenerator()->create_user();
        $trainer = $this->getDataGenerator()->create_user();
        $staffmanager = $this->getDataGenerator()->create_user();
        $sitemanager = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $context_user = \context_user::instance($user->id);

        $this->getDataGenerator()->enrol_user($learner->id, $course->id);
        $this->getDataGenerator()->enrol_user($trainer->id, $course->id, $role_editingtrainer);
        $this->getDataGenerator()->role_assign($role_sitemanager, $sitemanager->id);
        $this->getDataGenerator()->role_assign($role_staffmanager, $staffmanager->id, $context_user->id);

        $this->setUser($user);
        self::assertTrue(access_controller::for($user)->can_view_profile());
        self::assertFalse(access_controller::for($learner)->can_view_profile());
        self::assertTrue(access_controller::for($trainer)->can_view_profile());
        self::assertFalse(access_controller::for($staffmanager)->can_view_profile());
        self::assertFalse(access_controller::for($sitemanager)->can_view_profile());

        $this->setUser($learner);
        self::assertFalse(access_controller::for($user)->can_view_profile());
        self::assertTrue(access_controller::for($learner)->can_view_profile());
        self::assertTrue(access_controller::for($trainer)->can_view_profile());
        self::assertFalse(access_controller::for($staffmanager)->can_view_profile());
        self::assertFalse(access_controller::for($sitemanager)->can_view_profile());

        $this->setUser($trainer);
        self::assertFalse(access_controller::for($user)->can_view_profile());
        self::assertTrue(access_controller::for($learner)->can_view_profile());
        self::assertTrue(access_controller::for($trainer)->can_view_profile());
        self::assertFalse(access_controller::for($staffmanager)->can_view_profile());
        self::assertFalse(access_controller::for($sitemanager)->can_view_profile());

        $this->setUser($staffmanager);
        self::assertTrue(access_controller::for($user)->can_view_profile());
        self::assertFalse(access_controller::for($learner)->can_view_profile());
        self::assertTrue(access_controller::for($trainer)->can_view_profile());
        self::assertTrue(access_controller::for($staffmanager)->can_view_profile());
        self::assertFalse(access_controller::for($sitemanager)->can_view_profile());

        $this->setUser($sitemanager);
        self::assertTrue(access_controller::for($user)->can_view_profile());
        self::assertTrue(access_controller::for($learner)->can_view_profile());
        self::assertTrue(access_controller::for($trainer)->can_view_profile());
        self::assertTrue(access_controller::for($staffmanager)->can_view_profile());
        self::assertTrue(access_controller::for($sitemanager)->can_view_profile());
    }

    public function test_can_view_profile_job_relationship() {
        global $CFG, $DB;

        self::assertNotEmpty($CFG->forceloginforprofiles);

        $manager = $this->getDataGenerator()->create_user();
        $appraiser = $this->getDataGenerator()->create_user();
        /** @var \totara_job\testing\generator $jobgen */
        $jobgen = $this->getDataGenerator()->get_plugin_generator('totara_job');
        [$user, $job] = $jobgen->create_user_and_job([], $manager->id);
        $DB->update_record('job_assignment', (object)['id' => $job->id, 'appraiserid' => $appraiser->id]);

        $this->setUser($user);
        self::assertTrue(access_controller::for($manager)->can_view_profile());
        self::assertTrue(access_controller::for($appraiser)->can_view_profile());

        $this->setUser($manager);
        self::assertTrue(access_controller::for($user)->can_view_profile());
        self::assertTrue(access_controller::for($appraiser)->can_view_profile());

        $this->setUser($appraiser);
        self::assertTrue(access_controller::for($user)->can_view_profile());
        self::assertTrue(access_controller::for($manager)->can_view_profile());
    }

    public function test_can_view_profile_view_all_details_capability_at_course_separate_groups() {
        global $CFG, $DB;

        self::assertNotEmpty($CFG->forceloginforprofiles);
        $CFG->coursecontact = '';
        $role_editingtrainer = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        self::assertNotContainsEquals($role_editingtrainer, explode(',', $CFG->coursecontact));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['groupmode' => SEPARATEGROUPS]);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $group_a = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group_b = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group_a, 'userid' => $user1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group_b, 'userid' => $user2->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group_b, 'userid' => $user3->id]);

        $method = new ReflectionMethod(access_controller::class, 'has_view_all_details_capability');
        $method->setAccessible(true);

        $this->setUser($user1);
        self::assertFalse($method->invoke(access_controller::for($user2)));
        self::assertFalse($method->invoke(access_controller::for($user3)));
        self::assertFalse($method->invoke(access_controller::for($user4)));

        $this->setUser($user2);
        self::assertFalse($method->invoke(access_controller::for($user1)));
        self::assertFalse($method->invoke(access_controller::for($user3)));
        self::assertFalse($method->invoke(access_controller::for($user4)));

        $this->setUser($user3);
        self::assertFalse($method->invoke(access_controller::for($user1)));
        self::assertFalse($method->invoke(access_controller::for($user2)));
        self::assertFalse($method->invoke(access_controller::for($user4)));

        $this->setUser($user4);
        self::assertFalse($method->invoke(access_controller::for($user1)));
        self::assertFalse($method->invoke(access_controller::for($user2)));
        self::assertFalse($method->invoke(access_controller::for($user3)));

        $role = $this->getDataGenerator()->create_role();
        $context = context_course::instance($course->id);
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role, $context);
        $this->getDataGenerator()->role_assign($role, $user1->id, $context);
        $this->getDataGenerator()->role_assign($role, $user2->id, $context);
        $this->getDataGenerator()->role_assign($role, $user3->id, $context);
        $this->getDataGenerator()->role_assign($role, $user4->id, $context);

        $this->setUser($user1);
        self::assertFalse(access_controller::for($user2)->can_view_profile());
        self::assertFalse(access_controller::for($user3)->can_view_profile());
        self::assertFalse(access_controller::for($user4)->can_view_profile());

        $this->setUser($user2);
        self::assertFalse(access_controller::for($user1)->can_view_profile());
        self::assertTrue(access_controller::for($user3)->can_view_profile());
        self::assertFalse(access_controller::for($user4)->can_view_profile());

        $this->setUser($user3);
        self::assertFalse(access_controller::for($user1)->can_view_profile());
        self::assertTrue(access_controller::for($user2)->can_view_profile());
        self::assertFalse(access_controller::for($user4)->can_view_profile());

        $this->setUser($user4);
        self::assertFalse(access_controller::for($user1)->can_view_profile());
        self::assertFalse(access_controller::for($user2)->can_view_profile());
        self::assertFalse(access_controller::for($user3)->can_view_profile());
    }

    public function test_get_profile_url() {
        global $CFG;

        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id, 'student', 'manual', 0, 0, ENROL_USER_SUSPENDED);

        $this->setUser($admin);

        $url = access_controller::for($admin, null)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$admin->id", $url->out(false));

        $url = access_controller::for($admin, $course1)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$admin->id", $url->out(false));

        $url = access_controller::for($user1, null)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$user1->id", $url->out(false));

        $url = access_controller::for($user1, $course1)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$user1->id&course=$course1->id", $url->out(false));

        $url = access_controller::for($user1, $course2)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$user1->id", $url->out(false));

        $url = access_controller::for($user2, null)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$user2->id", $url->out(false));

        $url = access_controller::for($user2, $course1)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$user2->id", $url->out(false));

        $url = access_controller::for($user2, $course2)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$user2->id&course=$course2->id", $url->out(false));

        $this->setUser($user1);

        $url = access_controller::for($admin, null)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($admin, $course1)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($user1, null)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$user1->id", $url->out(false));

        $url = access_controller::for($user1, $course1)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$user1->id&course=$course1->id", $url->out(false));

        $url = access_controller::for($user1, $course2)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$user1->id", $url->out(false));

        $url = access_controller::for($user2, null)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($user2, $course1)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($user2, $course2)->get_profile_url();
        $this->assertNull($url);

        $this->setUser($user2);

        $url = access_controller::for($admin, null)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($admin, $course1)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($user1, null)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($user1, $course1)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($user1, $course2)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($user2, null)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$user2->id", $url->out(false));

        $url = access_controller::for($user2, $course1)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$user2->id", $url->out(false));

        $url = access_controller::for($user2, $course2)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$user2->id", $url->out(false));

        $this->setUser($user3);

        $url = access_controller::for($admin, null)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($admin, $course1)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($user1, null)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($user1, $course1)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($user1, $course2)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($user2, null)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($user2, $course1)->get_profile_url();
        $this->assertNull($url);

        $url = access_controller::for($user2, $course2)->get_profile_url();
        $this->assertNull($url);
        $this->assertNull($url);

        $url = access_controller::for($user3, null)->get_profile_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertSame("$CFG->wwwroot/user/profile.php?id=$user3->id", $url->out(false));
    }

    public function test_has_view_details_capability_deleted_user() {
        $user = $this->getDataGenerator()->create_user();
        delete_user($user);
        $this->setAdminUser();

        $method = new ReflectionMethod(access_controller::class, 'has_view_details_capability');
        $method->setAccessible(true);

        self::assertFalse($method->invoke(access_controller::for_user_id($user->id)));
    }

    public function test_admin_at_site() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $controller = access_controller::for($user);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertTrue($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'auth', 'confirmed', 'suspended', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'skype', 'phone1', 'phone2', 'institution', 'department', 'address', 'city', 'country', 'lang',
            'theme', 'timezone', 'firstaccess', 'lastaccess', 'url', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'imagealt', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'lastip',
        ];
        $notallowed = [
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);

        // Quick test while we're here of an unknown field.
        try {
            $controller->can_view_field('blah');
            $this->fail('Exception expected');
        } catch (coding_exception $exception) {
            self::assertStringContainsString('Unknown user field', $exception->getMessage());
        }
    }

    public function test_admin_at_site_with_deleted_user() {
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        delete_user($user);

        $controller = access_controller::for_user_id($user->id);

        self::assertFalse($controller->can_view_profile());
        self::assertFalse($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertFalse($controller->can_view_customfields());
        self::assertFalse($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id',
        ];
        $notallowed = [
            'auth', 'confirmed', 'suspended', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'skype', 'phone1', 'phone2', 'institution', 'department', 'address', 'city', 'country', 'lang',
            'theme', 'timezone', 'firstaccess', 'lastaccess', 'url', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'imagealt', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_admin_at_course_enrolled() {
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $controller = access_controller::for($user, $course);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertTrue($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'auth', 'confirmed', 'suspended', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'skype', 'phone1', 'phone2', 'institution', 'department', 'address', 'city', 'country', 'lang',
            'theme', 'timezone', 'firstaccess', 'lastaccess', 'url', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'imagealt', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'lastip',
        ];
        $notallowed = [
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_admin_at_course_not_enrolled() {
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $controller = access_controller::for($user, $course);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertTrue($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'auth', 'confirmed', 'suspended', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'skype', 'phone1', 'phone2', 'institution', 'department', 'address', 'city', 'country', 'lang',
            'theme', 'timezone', 'firstaccess', 'lastaccess', 'url', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'imagealt', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'lastip',
        ];
        $notallowed = [
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_guest_at_site() {
        global $CFG;

        $this->setGuestUser();
        $user = $this->getDataGenerator()->create_user();
        $controller = access_controller::for($user);

        self::assertFalse($controller->can_view_profile());
        self::assertFalse($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertFalse($controller->can_view_customfields());
        self::assertFalse($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id',
        ];
        $notallowed = [
            'auth', 'confirmed', 'suspended', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'skype', 'phone1', 'phone2', 'institution', 'department', 'address', 'city', 'country', 'lang',
            'theme', 'timezone', 'firstaccess', 'lastaccess', 'url', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'imagealt', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);

        $CFG->forceloginforprofiles = false;

        $allowed = [
            'id', 'imagealt', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'suspended',
            'skype', 'city', 'country', 'firstaccess', 'url'
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'phone1', 'phone2', 'institution', 'department', 'address', 'lang',
            'theme', 'timezone', 'lastip', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields(access_controller::for($user), $allowed, $notallowed);
    }

    public function test_guest_at_course_not_enrolled() {
        global $CFG;

        $this->setGuestUser();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $controller = access_controller::for($user, $course);

        self::assertFalse($controller->can_view_profile());
        self::assertFalse($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertFalse($controller->can_view_customfields());
        self::assertFalse($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id',
        ];
        $notallowed = [
            'auth', 'confirmed', 'suspended', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'skype', 'phone1', 'phone2', 'institution', 'department', 'address', 'city', 'country', 'lang',
            'theme', 'timezone', 'firstaccess', 'lastaccess', 'url', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'imagealt', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);

        $CFG->forceloginforprofiles = false;

        $allowed = [
            'id', 'imagealt', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'suspended',
            'skype', 'city', 'country', 'firstaccess', 'url'
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'phone1', 'phone2', 'institution', 'department', 'address', 'lang',
            'theme', 'timezone', 'lastip', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields(access_controller::for($user), $allowed, $notallowed);
    }

    public function test_nouser_at_site() {
        global $CFG;

        $user = $this->getDataGenerator()->create_user();
        $controller = access_controller::for($user);

        self::assertFalse($controller->can_view_profile());
        self::assertFalse($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertFalse($controller->can_view_customfields());
        self::assertFalse($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id',
        ];
        $notallowed = [
            'auth', 'confirmed', 'suspended', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'skype', 'phone1', 'phone2', 'institution', 'department', 'address', 'city', 'country', 'lang',
            'theme', 'timezone', 'firstaccess', 'lastaccess', 'url', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'imagealt', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);

        $CFG->forceloginforprofiles = false;

        $allowed = [
            'id', 'imagealt', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'suspended',
            'skype', 'city', 'country', 'firstaccess', 'url'
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'phone1', 'phone2', 'institution', 'department', 'address', 'lang',
            'theme', 'timezone', 'lastip', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields(access_controller::for($user), $allowed, $notallowed);
    }

    public function test_nouser_at_course_not_enrolled() {
        global $CFG;

        $this->setGuestUser();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $controller = access_controller::for($user, $course);

        self::assertFalse($controller->can_view_profile());
        self::assertFalse($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertFalse($controller->can_view_customfields());
        self::assertFalse($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id',
        ];
        $notallowed = [
            'auth', 'confirmed', 'suspended', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'skype', 'phone1', 'phone2', 'institution', 'department', 'address', 'city', 'country', 'lang',
            'theme', 'timezone', 'firstaccess', 'lastaccess', 'url', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'imagealt', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);

        $CFG->forceloginforprofiles = false;

        $allowed = [
            'id', 'imagealt', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'suspended',
            'skype', 'city', 'country', 'firstaccess', 'url'
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'phone1', 'phone2', 'institution', 'department', 'address', 'lang',
            'theme', 'timezone', 'lastip', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields(access_controller::for($user), $allowed, $notallowed);
    }

    public function test_job_manager_can_see_report_at_site() {
        global $CFG;

        $manager = $this->getDataGenerator()->create_user();
        /** @var \totara_job\testing\generator $jobgen */
        $jobgen = $this->getDataGenerator()->get_plugin_generator('totara_job');
        [$user, $job] = $jobgen->create_user_and_job([], $manager->id);

        $this->setUser($manager);
        $controller = access_controller::for($user);

        $CFG->profilesforenrolledusersonly = false;

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'auth', 'confirmed', 'suspended', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'skype', 'phone1', 'phone2', 'institution', 'department', 'address', 'city', 'country', 'lang',
            'theme', 'timezone', 'firstaccess', 'lastaccess', 'url', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'imagealt', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall',
        ];
        $notallowed = [
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_job_manager_cannot_see_report_at_course_not_enrolled() {
        $course = $this->getDataGenerator()->create_course();
        $manager = $this->getDataGenerator()->create_user();
        /** @var \totara_job\testing\generator $jobgen */
        $jobgen = $this->getDataGenerator()->get_plugin_generator('totara_job');
        [$user, $job] = $jobgen->create_user_and_job([], $manager->id);

        $this->setUser($manager);
        $controller = access_controller::for($user, $course);

        self::assertFalse($controller->can_view_profile());
    }

    public function test_job_manager_can_see_report_at_course_enrolled_not_shared() {
        $course = $this->getDataGenerator()->create_course();
        $manager = $this->getDataGenerator()->create_user();
        /** @var \totara_job\testing\generator $jobgen */
        $jobgen = $this->getDataGenerator()->get_plugin_generator('totara_job');
        [$user, $job] = $jobgen->create_user_and_job([], $manager->id);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->setUser($manager);
        $controller = access_controller::for($user, $course);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'auth', 'confirmed', 'suspended', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'skype', 'phone1', 'phone2', 'institution', 'department', 'address', 'city', 'country', 'lang',
            'theme', 'timezone', 'firstaccess', 'lastaccess', 'url', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'imagealt', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall',
        ];
        $notallowed = [
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_job_manager_can_see_report_at_course_enrolled_shared() {
        $course = $this->getDataGenerator()->create_course();
        $manager = $this->getDataGenerator()->create_user();
        /** @var \totara_job\testing\generator $jobgen */
        $jobgen = $this->getDataGenerator()->get_plugin_generator('totara_job');
        [$user, $job] = $jobgen->create_user_and_job([], $manager->id);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->getDataGenerator()->enrol_user($manager->id, $course->id);

        $this->setUser($manager);
        $controller = access_controller::for($user, $course);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'auth', 'confirmed', 'suspended', 'username', 'idnumber', 'firstname', 'lastname', 'email',
            'skype', 'phone1', 'phone2', 'institution', 'department', 'address', 'city', 'country', 'lang',
            'theme', 'timezone', 'firstaccess', 'lastaccess', 'url', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'imagealt', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall',
        ];
        $notallowed = [
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_job_report_can_see_manager_at_site() {
        $manager = $this->getDataGenerator()->create_user();
        /** @var \totara_job\testing\generator $jobgen */
        $jobgen = $this->getDataGenerator()->get_plugin_generator('totara_job');
        [$user, $job] = $jobgen->create_user_and_job([], $manager->id);

        $this->setUser($user);
        $controller = access_controller::for($manager);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'description', 'descriptionformat'
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'firstname', 'lastname',
            'phone1', 'phone2', 'institution', 'department', 'address', 'lang',
            'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_job_report_cannot_see_manager_at_course_not_enrolled() {
        $course = $this->getDataGenerator()->create_course();
        $manager = $this->getDataGenerator()->create_user();
        /** @var \totara_job\testing\generator $jobgen */
        $jobgen = $this->getDataGenerator()->get_plugin_generator('totara_job');
        [$user, $job] = $jobgen->create_user_and_job([], $manager->id);

        $this->setUser($user);
        $controller = access_controller::for($manager, $course);

        self::assertFalse($controller->can_view_profile());
    }

    public function test_job_report_can_see_manager_at_course_enrolled_not_shared() {
        $course = $this->getDataGenerator()->create_course();
        $manager = $this->getDataGenerator()->create_user();
        /** @var \totara_job\testing\generator $jobgen */
        $jobgen = $this->getDataGenerator()->get_plugin_generator('totara_job');
        [$user, $job] = $jobgen->create_user_and_job([], $manager->id);

        $this->getDataGenerator()->enrol_user($manager->id, $course->id);

        $this->setUser($user);
        $controller = access_controller::for($manager, $course);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'description', 'descriptionformat'
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'firstname', 'lastname',
            'phone1', 'phone2', 'institution', 'department', 'address', 'lang',
            'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_job_report_can_see_manager_at_course_enrolled_shared() {
        $course = $this->getDataGenerator()->create_course();
        $manager = $this->getDataGenerator()->create_user();
        /** @var \totara_job\testing\generator $jobgen */
        $jobgen = $this->getDataGenerator()->get_plugin_generator('totara_job');
        [$user, $job] = $jobgen->create_user_and_job([], $manager->id);

        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->getDataGenerator()->enrol_user($manager->id, $course->id);

        $this->setUser($user);
        $controller = access_controller::for($manager, $course);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'description', 'descriptionformat'
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'firstname', 'lastname',
            'phone1', 'phone2', 'institution', 'department', 'address', 'lang',
            'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_job_appraiser_can_see_appraisee_at_site() {
        global $DB;
        $manager = $this->getDataGenerator()->create_user();
        $appraiser = $this->getDataGenerator()->create_user();
        /** @var \totara_job\testing\generator $jobgen */
        $jobgen = $this->getDataGenerator()->get_plugin_generator('totara_job');
        [$user, $job] = $jobgen->create_user_and_job([], $manager->id);
        $DB->update_record('job_assignment', (object)['id' => $job->id, 'appraiserid' => $appraiser->id]);

        $this->setUser($appraiser);
        $controller = access_controller::for($user);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'email'
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'firstname', 'lastname',
            'phone1', 'phone2', 'institution', 'department', 'address', 'lang',
            'theme', 'timezone', 'description', 'descriptionformat', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_job_appraisee_can_see_appraiser_at_site() {
        global $DB;

        $appraiser = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        /** @var \totara_job\testing\generator $jobgen */
        $jobgen = $this->getDataGenerator()->get_plugin_generator('totara_job');
        [$user, $job] = $jobgen->create_user_and_job([], $manager->id);
        $DB->update_record('job_assignment', (object)['id' => $job->id, 'appraiserid' => $appraiser->id]);

        $this->setUser($user);
        $controller = access_controller::for($appraiser);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess',
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'firstname', 'lastname', 'description', 'descriptionformat',
            'phone1', 'phone2', 'institution', 'department', 'address', 'lang',
            'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_site_manager_can_see_user_at_site() {
        global $CFG, $DB;

        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($DB->get_field('role', 'id', ['shortname' => 'manager']), $manager->id);
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($manager);
        $controller = access_controller::for($user);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertTrue($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'auth', 'confirmed', 'username',
            'idnumber', 'firstname', 'lastname', 'phone1', 'phone2', 'institution', 'department', 'address', 'lang',
            'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename', 'lastip',
        ];
        $notallowed = [
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync',
            'description', 'descriptionformat',
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);

        $CFG->profilesforenrolledusersonly = false;
        // CFG has changed, force the static cache to clear, this isn't normal so it doesn't happen automatically.
        \core_user\access_controller::clear_instance_cache();

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'auth', 'confirmed', 'username',
            'idnumber', 'firstname', 'lastname', 'phone1', 'phone2', 'institution', 'department', 'address', 'lang',
            'theme', 'timezone', 'mailformat', 'description', 'descriptionformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename', 'lastip',
        ];
        $notallowed = [
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync',
        ];
        self::assert_expected_fields(access_controller::for($user), $allowed, $notallowed);
    }

    public function test_site_manager_can_see_user_in_course() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($DB->get_field('role', 'id', ['shortname' => 'manager']), $manager->id, context_course::instance($course->id)->id);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->setUser($manager);
        $controller = access_controller::for($user);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'firstname', 'lastname',
            'phone1', 'phone2', 'description', 'descriptionformat', 'address'
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'institution', 'department', 'lang', 'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_editing_trainer_can_see_user_in_course_at_site() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($DB->get_field('role', 'id', ['shortname' => 'editingteacher']), $manager->id, context_course::instance($course->id)->id);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->setUser($manager);
        $controller = access_controller::for($user);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'firstname', 'lastname',
            'phone1', 'phone2', 'description', 'descriptionformat', 'address'
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'institution', 'department', 'lang', 'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_editing_trainer_can_see_user_in_course_at_course() {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $manager = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->role_assign($DB->get_field('role', 'id', ['shortname' => 'editingteacher']), $manager->id, context_course::instance($course->id)->id);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->setUser($manager);
        $controller = access_controller::for($user);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'firstname', 'lastname',
            'phone1', 'phone2', 'description', 'descriptionformat', 'address'
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'institution', 'department', 'lang', 'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_two_learners_sharing_a_course_at_site() {
        $course = $this->getDataGenerator()->create_course();
        $learner1 = $this->getDataGenerator()->create_user();
        $learner2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($learner1->id, $course->id);
        $this->getDataGenerator()->enrol_user($learner2->id, $course->id);

        $this->setUser($learner1);
        $controller = access_controller::for($learner2);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'description', 'descriptionformat'
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'firstname', 'lastname', 'phone1', 'phone2', 'institution', 'department', 'address', 'lang',
            'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_two_learners_sharing_a_course_at_course() {
        $course = $this->getDataGenerator()->create_course();
        $learner1 = $this->getDataGenerator()->create_user();
        $learner2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($learner1->id, $course->id);
        $this->getDataGenerator()->enrol_user($learner2->id, $course->id);

        $this->setUser($learner1);
        $controller = access_controller::for($learner2, $course);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'description', 'descriptionformat'
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'firstname', 'lastname',
            'phone1', 'phone2', 'institution', 'department', 'address', 'lang',
            'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename',
            'alternatename', 'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_current_user_at_site() {
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);
        $controller = access_controller::for($user);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertTrue($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertTrue($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'auth', 'confirmed', 'username', 'idnumber', 'firstname', 'lastname',
            'phone1', 'phone2', 'institution', 'department', 'address', 'lang', 'theme', 'timezone', 'mailformat', 'description', 'descriptionformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename',
        ];
        $notallowed = [
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_current_user_at_course() {
        $user = $this->getDataGenerator()->create_user();

        $this->setUser($user);
        $controller = access_controller::for($user);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertTrue($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertTrue($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'auth', 'confirmed', 'username', 'idnumber', 'firstname', 'lastname',
            'phone1', 'phone2', 'institution', 'department', 'address', 'lang', 'theme', 'timezone', 'mailformat', 'description', 'descriptionformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename',
        ];
        $notallowed = [
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_hidden_fields_with_capabilities_at_system() {
        global $CFG;
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user(['maildisplay' => 1]);

        $context = \context_user::instance($user2->id);
        $role = $this->getDataGenerator()->create_role();
        assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $role, $context);
        $this->getDataGenerator()->role_assign($role, $user1->id, $context->id);

        $CFG->profilesforenrolledusersonly = false;

        $this->setUser($user1);
        $controller = access_controller::for($user2);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'description', 'descriptionformat',
            'auth', 'confirmed', 'username', 'idnumber', 'institution', 'department', 'lang', 'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename',
        ];
        $notallowed = [
            'firstname', 'lastname', 'phone1', 'phone2', 'address',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);

        $CFG->hiddenuserfields = join(',', [
            'mycourses', 'description', 'descriptionformat', 'country', 'city', 'url', 'skype', 'suspended',
            'firstaccess', 'lastaccess'
        ]);
        // CFG has changed, force the static cache to clear, this isn't normal so it doesn't happen automatically.
        \core_user\access_controller::clear_instance_cache();

        $controller = access_controller::for($user2);

        self::assertTrue($controller->can_view_profile());
        self::assertFalse($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'auth', 'confirmed', 'username', 'idnumber', 'institution', 'department', 'lang', 'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename',
        ];
        $notallowed = [
            'firstname', 'lastname', 'phone1', 'phone2', 'address', 'suspended', 'lastaccess', 'url', 'skype',
            'city', 'country', 'firstaccess', 'description', 'descriptionformat',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);

        assign_capability('moodle/user:viewhiddendetails', CAP_ALLOW, $role, $context);

        $controller = access_controller::for($user2);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'description', 'descriptionformat',
            'auth', 'confirmed', 'username', 'idnumber', 'institution', 'department', 'lang', 'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename', 'phone1', 'phone2', 'address'
        ];
        $notallowed = [
            'firstname', 'lastname',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_hidden_fields_with_capabilities_at_course() {
        global $CFG;
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user(['maildisplay' => 2]);
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);

        $context = \context_course::instance($course->id);
        $role = $this->getDataGenerator()->create_role();
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $role, $context);
        $this->getDataGenerator()->role_assign($role, $user1->id, $context->id);

        $this->setUser($user1);
        $controller = access_controller::for($user2);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'description', 'descriptionformat',
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'institution', 'department', 'lang', 'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename',
            'firstname', 'lastname', 'phone1', 'phone2', 'address',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);

        $CFG->hiddenuserfields = join(',', [
            'mycourses', 'description', 'descriptionformat', 'country', 'city', 'url', 'skype', 'suspended',
            'firstaccess', 'lastaccess'
        ]);
        // CFG has changed, force the static cache to clear, this isn't normal so it doesn't happen automatically.
        \core_user\access_controller::clear_instance_cache();

        $controller = access_controller::for($user2);

        self::assertTrue($controller->can_view_profile());
        self::assertFalse($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'institution', 'department', 'lang', 'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename',
            'firstname', 'lastname', 'phone1', 'phone2', 'address', 'suspended', 'lastaccess', 'url', 'skype',
            'city', 'country', 'firstaccess', 'description', 'descriptionformat',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);

        assign_capability('moodle/course:viewhiddenuserfields', CAP_ALLOW, $role, $context);

        $controller = access_controller::for($user2);

        self::assertTrue($controller->can_view_profile());
        self::assertTrue($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertTrue($controller->can_view_customfields());
        self::assertTrue($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt', 'email',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'description', 'descriptionformat',
            'phone1', 'phone2', 'address',
        ];
        $notallowed = [
            'auth', 'confirmed', 'username', 'idnumber', 'institution', 'department', 'lang', 'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename',
            'firstname', 'lastname',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_hidden_fields_with_capabilities_at_course_separate_groups() {
        global $CFG;
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user(['maildisplay' => 2]);
        $course = $this->getDataGenerator()->create_course(['groupmode' => SEPARATEGROUPS]);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group1->id, 'userid' => $user1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group2->id, 'userid' => $user2->id]);


        $context = \context_course::instance($course->id);
        $role = $this->getDataGenerator()->create_role();
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $role, $context);
        assign_capability('moodle/course:viewhiddenuserfields', CAP_ALLOW, $role, $context);
        $this->getDataGenerator()->role_assign($role, $user1->id, $context->id);

        $CFG->hiddenuserfields = join(',', [
            'mycourses', 'description', 'descriptionformat', 'country', 'city', 'url', 'skype', 'suspended',
            'firstaccess', 'lastaccess'
        ]);

        $this->setUser($user1);
        $controller = access_controller::for($user2, $course);

        self::assertFalse($controller->can_view_profile());
        self::assertFalse($controller->can_view_enrolledcourses());
        self::assertFalse($controller->can_view_preferences());
        self::assertFalse($controller->can_view_customfields());
        self::assertFalse($controller->can_view_interests());
        self::assertFalse($controller->can_manage_files());

        $allowed = [
            'id', 'email'
        ];
        $notallowed = [
            'suspended', 'fullname', 'interests', 'profileimagealt', 'profileimageurl', 'profileimageurlsmall', 'imagealt',
            'lastaccess', 'url', 'skype', 'city', 'country', 'firstaccess', 'description', 'descriptionformat',
            'auth', 'confirmed', 'username', 'idnumber', 'institution', 'department', 'lang', 'theme', 'timezone', 'mailformat',
            'timecreated', 'timemodified', 'lastnamephonetic', 'firstnamephonetic', 'middlename', 'alternatename',
            'firstname', 'lastname', 'phone1', 'phone2', 'address',
            'policyagreed', 'deleted', 'password', 'secret', 'emailstop', 'calendartype', 'lastlogin', 'currentlogin',
            'lastip', 'picture', 'maildigest', 'maildisplay', 'autosubscribe', 'trackforums', 'trustbitmask', 'totarasync'
        ];
        self::assert_expected_fields($controller, $allowed, $notallowed);
    }

    public function test_capability_controlled_fields_with_separate_groups() {
        global $CFG, $DB;

        self::assertNotEmpty($CFG->forceloginforprofiles);
        $CFG->coursecontact = '';
        $role_editingtrainer = $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        self::assertNotContainsEquals($role_editingtrainer, explode(',', $CFG->coursecontact));

        $user1 = $this->getDataGenerator()->create_user(['maildisplay' => 0]);
        $user2 = $this->getDataGenerator()->create_user(['maildisplay' => 0]);
        $user3 = $this->getDataGenerator()->create_user(['maildisplay' => 0]);
        $course = $this->getDataGenerator()->create_course(['groupmode' => SEPARATEGROUPS]);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id);
        $group_a = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group_b = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group_a, 'userid' => $user1->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group_a, 'userid' => $user2->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group_b, 'userid' => $user3->id]);

        $this->setUser($user1);
        self::assertFalse(access_controller::for($user2)->can_view_field('email'));
        self::assertFalse(access_controller::for($user2)->can_view_field('firstname'));
        self::assertFalse(access_controller::for($user3)->can_view_field('email'));
        self::assertFalse(access_controller::for($user3)->can_view_field('firstname'));

        $role = $this->getDataGenerator()->create_role();
        $context = context_course::instance($course->id);
        assign_capability('moodle/site:viewfullnames', CAP_ALLOW, $role, $context);
        assign_capability('moodle/course:useremail', CAP_ALLOW, $role, $context);
        $this->getDataGenerator()->role_assign($role, $user1->id, $context);

        self::assertTrue(access_controller::for($user2)->can_view_field('email'));
        self::assertTrue(access_controller::for($user2)->can_view_field('firstname'));
        self::assertFalse(access_controller::for($user3)->can_view_field('email'));
        self::assertFalse(access_controller::for($user3)->can_view_field('firstname'));
    }

    public function test_can_loginas() {
        global $DB;

        // Create some users
        $learner1 = $this->getDataGenerator()->create_user();
        $learner2 = $this->getDataGenerator()->create_user();
        $trainer = $this->getDataGenerator()->create_user();
        $sitemanager = $this->getDataGenerator()->create_user();
        $siteadmin1 = $this->getDataGenerator()->create_user();
        $siteadmin2 = $this->getDataGenerator()->create_user();
        $deleted = $this->getDataGenerator()->create_user(array('deleted' => 1));
        $guest = $DB->get_record('user', array('username' => 'guest'));

        // Assign sitewide roles
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $this->getDataGenerator()->role_assign($managerrole->id, $sitemanager->id);
        set_config('siteadmins', $siteadmin1->id . ',' . $siteadmin2->id);

        // Create two courses
        $stdcourse = $this->getDataGenerator()->create_course();
        $groupedcourse = $this->getDataGenerator()->create_course(array('groupmode' => SEPARATEGROUPS));

        // Enrol learners and trainer
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

        $this->getDataGenerator()->enrol_user($learner1->id, $stdcourse->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($trainer->id, $stdcourse->id, $teacherrole->id);

        $this->getDataGenerator()->enrol_user($learner1->id, $groupedcourse->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($learner2->id, $groupedcourse->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($trainer->id, $groupedcourse->id, $teacherrole->id);

        // For grouped course, set up groups
        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $groupedcourse->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $groupedcourse->id));

        // Assign trainer and learner1 to group1, learner2 to group2
        groups_add_member($group1->id, $learner1->id);
        groups_add_member($group1->id, $trainer->id);
        groups_add_member($group2->id, $learner2->id);

        // Contexts
        $systemcontext = context_system::instance();
        $stdcoursecontext = context_course::instance($stdcourse->id);
        $groupedcoursecontext = context_course::instance($groupedcourse->id);

        // Add loginas capability to editingteacher
        role_change_permission($teacherrole->id, $systemcontext, 'moodle/user:loginas', CAP_ALLOW);

        // Tests as Admin
        $this->setUser($siteadmin1);

        // Siteadmin should be able to log in as (almost) anyone in system context
        self::assertTrue(access_controller::for($learner1)->can_loginas());
        self::assertTrue(access_controller::for($learner2)->can_loginas());
        self::assertTrue(access_controller::for($trainer)->can_loginas());
        self::assertTrue(access_controller::for($sitemanager)->can_loginas());
        self::assertFalse(access_controller::for($guest)->can_loginas());

        // Target user must not be same user
        self::assertFalse(access_controller::for($siteadmin1)->can_loginas());

        // Target user must not be another siteadmin
        self::assertFalse(access_controller::for($siteadmin2)->can_loginas());

        // Target user must not be deleted
        self::assertFalse(access_controller::for($deleted)->can_loginas());

        // Tests as Site Manager
        $this->setUser($sitemanager);

        // Site Manager should also be able to log in as (almost) anyone in system context
        self::assertTrue(access_controller::for($learner1)->can_loginas());
        self::assertTrue(access_controller::for($learner2)->can_loginas());
        self::assertTrue(access_controller::for($trainer)->can_loginas());
        self::assertFalse(access_controller::for($guest)->can_loginas());

        // Site Manager should also be able to log in as enrolees in course context
        self::assertTrue(access_controller::for($learner1, $stdcourse)->can_loginas());
        self::assertTrue(access_controller::for($trainer, $stdcourse)->can_loginas());
        self::assertTrue(access_controller::for($learner1, $groupedcourse)->can_loginas());
        self::assertTrue(access_controller::for($learner2, $groupedcourse)->can_loginas());
        self::assertTrue(access_controller::for($trainer, $groupedcourse)->can_loginas());

        // Target user must be enrolled in the course, though
        self::assertFalse(access_controller::for($learner2, $stdcourse)->can_loginas());

        // Tests as Learner
        $this->setUser($learner1);

        // Learner1 should not be able to log in as anybody in system or course contexts
        self::assertFalse(access_controller::for($learner2)->can_loginas());
        self::assertFalse(access_controller::for($trainer, $stdcourse)->can_loginas());
        self::assertFalse(access_controller::for($guest)->can_loginas());

        // Tests as Trainer
        $this->setUser($trainer);

        // Trainer should only be able to login as an enrolee in course context
        self::assertFalse(access_controller::for($learner2)->can_loginas());
        self::assertTrue(access_controller::for($learner1, $stdcourse)->can_loginas());
        self::assertFalse(access_controller::for($learner2, $stdcourse)->can_loginas());

        // In a separated group course, trainer should only be able to login as an enrolee in the same group
        // Note that an editing trainer could do this, but not a trainer
        self::assertTrue(access_controller::for($learner1, $groupedcourse)->can_loginas());
        self::assertFalse(access_controller::for($learner2, $groupedcourse)->can_loginas());

        // Fake being "loggedinas" already
        $GLOBALS['USER']->realuser = $siteadmin1->id;
        self::assertFalse(access_controller::for($learner1, $groupedcourse)->can_loginas());

        // Clear that global
        $GLOBALS['USER']->realuser = null;
    }

    private static function assert_expected_fields(access_controller $controller, $allowed, $notallowed) {
        $wrong = [];
        foreach ($allowed as $field) {
            if (!$controller->can_view_field($field)) {
                $wrong[] = $field;
            }
        }
        self::assertEmpty($wrong, "The following fields were not accessible but should have been: \n\t" . join("\n\t", $wrong));

        $wrong = [];
        foreach ($notallowed as $field) {
            if ($controller->can_view_field($field)) {
                $wrong[] = $field;
            }
        }
        self::assertEmpty($wrong, "The following fields were accessible but should not have been: \n\t" . join("\n\t", $wrong));
    }

    private static function get_controller_property($controller, $property) {
        $ref = new ReflectionProperty($controller, $property);
        $ref->setAccessible(true);
        return $ref->getValue($controller);
    }

    private static function get_unused_userid() {
        global $DB;
        do {
            $id = rand(3, 3000);
        } while ($DB->record_exists('user', ['id' => $id]));
        return $id;
    }

    public function test_instance_cache_max_size() {
        $users = [];
        for ($i = 0; $i < 15; $i++) {
            $users[] = $user = $this->getDataGenerator()->create_user();
            access_controller::for($user);
        }


        $property = new ReflectionProperty(access_controller::class, 'instancecache');
        $property->setAccessible(true);

        $value = $property->getValue(null);
        self::assertCount(10, $value);
        foreach ($value as $key => $access_controller) {
            /** @var access_controller $access_controller */
            $property = new ReflectionProperty(access_controller::class, 'userid');
            $property->setAccessible(true);
            self::assertEquals($key, $property->getValue($access_controller));
        }
    }

}