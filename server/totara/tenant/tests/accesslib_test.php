<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests covering multitenancy related changes in lib/accesslib.php
 */
class totara_tenant_accesslib_testcase extends advanced_testcase {
    public function test_tenant_context() {
        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();
        $context = context_tenant::instance($tenant->id);
        $this->assertSame(CONTEXT_TENANT, $context->contextlevel);
        $this->assertSame($tenant->id, $context->instanceid);
        $this->assertSame($tenant->id, $context->tenantid);
        $this->assertSame('/' . SYSCONTEXTID . '/' . $context->id, $context->path);
        $this->assertSame('2', $context->depth);
        $tenantcontext = context::instance_by_id($context->id);
        $this->assertSame($context, $tenantcontext);
    }

    public function test_user_context() {
        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $this->assertNull($user1->tenantid);
        $usercontext1 = context_user::instance($user1->id);
        $this->assertNull($usercontext1->tenantid);

        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);
        $this->assertSame($tenant->id, $user2->tenantid);
        $usercontext2 = context_user::instance($user2->id);
        $this->assertSame($tenant->id, $usercontext2->tenantid);
    }

    public function test_course_category_context() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/coursecatlib.php');

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();

        $categorycontext = context_coursecat::instance($tenant->categoryid);
        $this->assertSame(CONTEXT_COURSECAT, $categorycontext->contextlevel);
        $this->assertSame($tenant->categoryid, $categorycontext->instanceid);
        $this->assertSame($tenant->id, $categorycontext->tenantid);
        $this->assertSame('/' . SYSCONTEXTID . '/' . $categorycontext->id, $categorycontext->path);
        $this->assertSame('2', $categorycontext->depth);

        // Sub categories must inherit tenantid.

        $data = new stdClass();
        $data->name = 'aaa';
        $data->description = 'aaa';
        $data->idnumber = '';
        $data->parent = $tenant->categoryid;
        $subcategory = coursecat::create($data);
        $subcatcontext = context_coursecat::instance($subcategory->id);
        $this->assertSame(CONTEXT_COURSECAT, $subcatcontext->contextlevel);
        $this->assertSame($subcategory->id, $subcatcontext->instanceid);
        $this->assertSame($tenant->id, $subcatcontext->tenantid);
        $this->assertSame($categorycontext->path . '/' . $subcatcontext->id, $subcatcontext->path);
        $this->assertSame('3', $subcatcontext->depth);

        // Courses must inherit tenant id too.

        $course = new stdClass();
        $course->fullname = 'Long name';
        $course->shortname = 'short';
        $course->idnumber = '123';
        $course->summary = 'Awesome!';
        $course->summaryformat = FORMAT_PLAIN;
        $course->format = 'topics';
        $course->newsitems = 0;
        $course->category = $subcategory->id;
        $course = create_course($course);
        $coursecontext = context_course::instance($course->id);
        $this->assertSame(CONTEXT_COURSE, $coursecontext->contextlevel);
        $this->assertSame($course->id, $coursecontext->instanceid);
        $this->assertSame($tenant->id, $coursecontext->tenantid);
        $this->assertSame($subcatcontext->path . '/' . $coursecontext->id, $coursecontext->path);
        $this->assertSame('4', $coursecontext->depth);
    }

    public function test_course_context() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/coursecatlib.php');

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();

        $categorycontext = context_coursecat::instance($tenant->categoryid);
        $othercategory = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(['category' => $othercategory->id]);
        $coursecontext1 = context_course::instance($course1->id);
        $this->assertNull($coursecontext1->tenantid);

        $course2 = $this->getDataGenerator()->create_course(['category' => $tenant->categoryid]);
        $coursecontext2 = context_course::instance($course2->id);
        $this->assertSame($tenant->id, $coursecontext2->tenantid);
    }


    public function test_context_is_user_access_prevented() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $managerrole = $DB->get_record('role', array('shortname'=>'manager'), '*', MUST_EXIST);

        $admin = get_admin();
        $guest = guest_user();
        $frontpage = $DB->get_record('course', ['id' => SITEID], '*', MUST_EXIST);

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $tenantcategory2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);

        role_assign($managerrole->id, $guest->id, context_course::instance($course0_1->id)->id);
        role_assign($managerrole->id, $guest->id, context_course::instance($course1_1->id)->id);
        role_assign($managerrole->id, $user0_1->id, context_course::instance($course0_1->id)->id);
        role_assign($managerrole->id, $user0_1->id, context_course::instance($course1_1->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_course::instance($course0_1->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_course::instance($course1_1->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_course::instance($course2_1->id)->id);
        role_assign($managerrole->id, $user2_1->id, context_course::instance($course2_1->id)->id);

        $this->setUser(0);

        set_config('tenantsisolated', '0');

        $this->assertFalse(context_course::instance($frontpage->id)->is_user_access_prevented(0));
        $this->assertFalse(context_course::instance($frontpage->id)->is_user_access_prevented($guest));
        $this->assertFalse(context_course::instance($frontpage->id)->is_user_access_prevented($admin));
        $this->assertFalse(context_course::instance($frontpage->id)->is_user_access_prevented($user0_1));
        $this->assertFalse(context_course::instance($frontpage->id)->is_user_access_prevented($user1_1));
        $this->assertFalse(context_course::instance($frontpage->id)->is_user_access_prevented($user1_2));
        $this->assertFalse(context_course::instance($frontpage->id)->is_user_access_prevented($user2_1));
        $this->assertFalse(context_course::instance($frontpage->id)->is_user_access_prevented($user2_2));

        $this->assertFalse(context_course::instance($course0_1->id)->is_user_access_prevented(0));
        $this->assertFalse(context_course::instance($course0_1->id)->is_user_access_prevented($guest));
        $this->assertFalse(context_course::instance($course0_1->id)->is_user_access_prevented($admin));
        $this->assertFalse(context_course::instance($course0_1->id)->is_user_access_prevented($user0_1));
        $this->assertFalse(context_course::instance($course0_1->id)->is_user_access_prevented($user1_1));
        $this->assertFalse(context_course::instance($course0_1->id)->is_user_access_prevented($user1_2));
        $this->assertFalse(context_course::instance($course0_1->id)->is_user_access_prevented($user2_1));
        $this->assertFalse(context_course::instance($course0_1->id)->is_user_access_prevented($user2_2));

        $this->asserttrue(context_course::instance($course1_1->id)->is_user_access_prevented(0));
        $this->asserttrue(context_course::instance($course1_1->id)->is_user_access_prevented($guest));
        $this->assertFalse(context_course::instance($course1_1->id)->is_user_access_prevented($admin));
        $this->assertFalse(context_course::instance($course1_1->id)->is_user_access_prevented($user0_1));
        $this->assertFalse(context_course::instance($course1_1->id)->is_user_access_prevented($user1_1));
        $this->assertFalse(context_course::instance($course1_1->id)->is_user_access_prevented($user1_2));
        $this->asserttrue(context_course::instance($course1_1->id)->is_user_access_prevented($user2_1));
        $this->asserttrue(context_course::instance($course1_1->id)->is_user_access_prevented($user2_2));

        $this->assertFalse(context_system::instance()->is_user_access_prevented(0));
        $this->assertFalse(context_system::instance()->is_user_access_prevented($guest));
        $this->assertFalse(context_system::instance()->is_user_access_prevented($admin));
        $this->assertFalse(context_system::instance()->is_user_access_prevented($user0_1));
        $this->assertFalse(context_system::instance()->is_user_access_prevented($user1_1));
        $this->assertFalse(context_system::instance()->is_user_access_prevented($user1_2));
        $this->assertFalse(context_system::instance()->is_user_access_prevented($user2_1));
        $this->assertFalse(context_system::instance()->is_user_access_prevented($user2_2));

        $this->asserttrue(context_tenant::instance($tenant1->id)->is_user_access_prevented(0));
        $this->asserttrue(context_tenant::instance($tenant1->id)->is_user_access_prevented($guest));
        $this->assertFalse(context_tenant::instance($tenant1->id)->is_user_access_prevented($admin));
        $this->assertFalse(context_tenant::instance($tenant1->id)->is_user_access_prevented($user0_1));
        $this->assertFalse(context_tenant::instance($tenant1->id)->is_user_access_prevented($user1_1));
        $this->assertFalse(context_tenant::instance($tenant1->id)->is_user_access_prevented($user1_2));
        $this->asserttrue(context_tenant::instance($tenant1->id)->is_user_access_prevented($user2_1));
        $this->asserttrue(context_tenant::instance($tenant1->id)->is_user_access_prevented($user2_2));

        $this->assertFalse(context_user::instance($user0_1->id)->is_user_access_prevented(0));
        $this->assertFalse(context_user::instance($user0_1->id)->is_user_access_prevented($guest));
        $this->assertFalse(context_user::instance($user0_1->id)->is_user_access_prevented($admin));
        $this->assertFalse(context_user::instance($user0_1->id)->is_user_access_prevented($user0_1));
        $this->assertFalse(context_user::instance($user0_1->id)->is_user_access_prevented($user1_1));
        $this->assertFalse(context_user::instance($user0_1->id)->is_user_access_prevented($user1_2));
        $this->assertFalse(context_user::instance($user0_1->id)->is_user_access_prevented($user2_1));
        $this->assertFalse(context_user::instance($user0_1->id)->is_user_access_prevented($user2_2));

        $this->asserttrue(context_user::instance($user1_1->id)->is_user_access_prevented(0));
        $this->asserttrue(context_user::instance($user1_1->id)->is_user_access_prevented($guest));
        $this->assertFalse(context_user::instance($user1_1->id)->is_user_access_prevented($admin));
        $this->assertFalse(context_user::instance($user1_1->id)->is_user_access_prevented($user0_1));
        $this->assertFalse(context_user::instance($user1_1->id)->is_user_access_prevented($user1_1));
        $this->assertFalse(context_user::instance($user1_1->id)->is_user_access_prevented($user1_2));
        $this->asserttrue(context_user::instance($user1_1->id)->is_user_access_prevented($user2_1));
        $this->asserttrue(context_user::instance($user1_1->id)->is_user_access_prevented($user2_2));

        set_config('tenantsisolated', '1');

        $this->assertFalse(context_course::instance($frontpage->id)->is_user_access_prevented(0));
        $this->assertFalse(context_course::instance($frontpage->id)->is_user_access_prevented($guest));
        $this->assertFalse(context_course::instance($frontpage->id)->is_user_access_prevented($admin));
        $this->assertFalse(context_course::instance($frontpage->id)->is_user_access_prevented($user0_1));
        $this->asserttrue(context_course::instance($frontpage->id)->is_user_access_prevented($user1_1));
        $this->asserttrue(context_course::instance($frontpage->id)->is_user_access_prevented($user1_2));
        $this->asserttrue(context_course::instance($frontpage->id)->is_user_access_prevented($user2_1));
        $this->asserttrue(context_course::instance($frontpage->id)->is_user_access_prevented($user2_2));

        $this->assertFalse(context_course::instance($course0_1->id)->is_user_access_prevented(0));
        $this->assertFalse(context_course::instance($course0_1->id)->is_user_access_prevented($guest));
        $this->assertFalse(context_course::instance($course0_1->id)->is_user_access_prevented($admin));
        $this->assertFalse(context_course::instance($course0_1->id)->is_user_access_prevented($user0_1));
        $this->asserttrue(context_course::instance($course0_1->id)->is_user_access_prevented($user1_1));
        $this->asserttrue(context_course::instance($course0_1->id)->is_user_access_prevented($user1_2));
        $this->asserttrue(context_course::instance($course0_1->id)->is_user_access_prevented($user2_1));
        $this->asserttrue(context_course::instance($course0_1->id)->is_user_access_prevented($user2_2));

        $this->asserttrue(context_course::instance($course1_1->id)->is_user_access_prevented(0));
        $this->asserttrue(context_course::instance($course1_1->id)->is_user_access_prevented($guest));
        $this->assertFalse(context_course::instance($course1_1->id)->is_user_access_prevented($admin));
        $this->assertFalse(context_course::instance($course1_1->id)->is_user_access_prevented($user0_1));
        $this->assertFalse(context_course::instance($course1_1->id)->is_user_access_prevented($user1_1));
        $this->assertFalse(context_course::instance($course1_1->id)->is_user_access_prevented($user1_2));
        $this->asserttrue(context_course::instance($course1_1->id)->is_user_access_prevented($user2_1));
        $this->asserttrue(context_course::instance($course1_1->id)->is_user_access_prevented($user2_2));

        $this->assertFalse(context_system::instance()->is_user_access_prevented(0));
        $this->assertFalse(context_system::instance()->is_user_access_prevented($guest));
        $this->assertFalse(context_system::instance()->is_user_access_prevented($admin));
        $this->assertFalse(context_system::instance()->is_user_access_prevented($user0_1));
        $this->asserttrue(context_system::instance()->is_user_access_prevented($user1_1));
        $this->asserttrue(context_system::instance()->is_user_access_prevented($user1_2));
        $this->asserttrue(context_system::instance()->is_user_access_prevented($user2_1));
        $this->asserttrue(context_system::instance()->is_user_access_prevented($user2_2));

        $this->asserttrue(context_tenant::instance($tenant1->id)->is_user_access_prevented(0));
        $this->asserttrue(context_tenant::instance($tenant1->id)->is_user_access_prevented($guest));
        $this->assertFalse(context_tenant::instance($tenant1->id)->is_user_access_prevented($admin));
        $this->assertFalse(context_tenant::instance($tenant1->id)->is_user_access_prevented($user0_1));
        $this->assertFalse(context_tenant::instance($tenant1->id)->is_user_access_prevented($user1_1));
        $this->assertFalse(context_tenant::instance($tenant1->id)->is_user_access_prevented($user1_2));
        $this->asserttrue(context_tenant::instance($tenant1->id)->is_user_access_prevented($user2_1));
        $this->asserttrue(context_tenant::instance($tenant1->id)->is_user_access_prevented($user2_2));

        $this->assertFalse(context_user::instance($user0_1->id)->is_user_access_prevented(0));
        $this->assertFalse(context_user::instance($user0_1->id)->is_user_access_prevented($guest));
        $this->assertFalse(context_user::instance($user0_1->id)->is_user_access_prevented($admin));
        $this->assertFalse(context_user::instance($user0_1->id)->is_user_access_prevented($user0_1));
        $this->asserttrue(context_user::instance($user0_1->id)->is_user_access_prevented($user1_1));
        $this->asserttrue(context_user::instance($user0_1->id)->is_user_access_prevented($user1_2));
        $this->asserttrue(context_user::instance($user0_1->id)->is_user_access_prevented($user2_1));
        $this->asserttrue(context_user::instance($user0_1->id)->is_user_access_prevented($user2_2));

        $this->asserttrue(context_user::instance($user1_1->id)->is_user_access_prevented(0));
        $this->asserttrue(context_user::instance($user1_1->id)->is_user_access_prevented($guest));
        $this->assertFalse(context_user::instance($user1_1->id)->is_user_access_prevented($admin));
        $this->assertFalse(context_user::instance($user1_1->id)->is_user_access_prevented($user0_1));
        $this->assertFalse(context_user::instance($user1_1->id)->is_user_access_prevented($user1_1));
        $this->assertFalse(context_user::instance($user1_1->id)->is_user_access_prevented($user1_2));
        $this->asserttrue(context_user::instance($user1_1->id)->is_user_access_prevented($user2_1));
        $this->asserttrue(context_user::instance($user1_1->id)->is_user_access_prevented($user2_2));
    }

    public function test_has_capability() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $managerrole = $DB->get_record('role', array('shortname'=>'manager'), '*', MUST_EXIST);

        $admin = get_admin();
        $guest = guest_user();
        $frontpage = $DB->get_record('course', ['id' => SITEID], '*', MUST_EXIST);

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $tenantcategory2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);

        role_assign($managerrole->id, $guest->id, context_course::instance($course0_1->id)->id);
        role_assign($managerrole->id, $guest->id, context_course::instance($course1_1->id)->id);
        role_assign($managerrole->id, $user0_1->id, context_course::instance($course0_1->id)->id);
        role_assign($managerrole->id, $user0_1->id, context_course::instance($course1_1->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_course::instance($course0_1->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_course::instance($course1_1->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_course::instance($course2_1->id)->id);
        role_assign($managerrole->id, $user2_1->id, context_course::instance($course2_1->id)->id);

        set_config('tenantsisolated', '0');

        $this->setUser(0);
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_system::instance()));
        $this->assertFalse(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_user::instance($user1_1->id)));

        $this->setUser($guest);
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_system::instance()));
        $this->assertFalse(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_user::instance($user1_1->id)));

        $this->setUser($admin);
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_system::instance()));
        $this->assertTrue(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user1_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($frontpage->id), $admin, false));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course0_1->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course0_1->id), $admin, false));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course1_1->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course1_1->id), $admin, false));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course2_1->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course2_1->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_system::instance(), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_tenant::instance($tenant1->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user0_1->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user1_1->id), $admin, false));

        $this->setUser($user0_1);
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_system::instance()));
        $this->assertTrue(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user1_1->id)));

        $this->setUser($user1_1);
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_system::instance()));
        $this->assertTrue(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user1_1->id)));

        $this->setUser($user1_2);
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_system::instance()));
        $this->assertTrue(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user1_1->id)));

        $this->setUser($user2_1);
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_system::instance()));
        $this->assertFalse(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_user::instance($user1_1->id)));

        set_config('tenantsisolated', '1');

        $this->setUser(0);
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_system::instance()));
        $this->assertFalse(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_user::instance($user1_1->id)));

        $this->setUser($guest);
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_system::instance()));
        $this->assertFalse(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_user::instance($user1_1->id)));

        $this->setUser($admin);
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_system::instance()));
        $this->assertTrue(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user1_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($frontpage->id), $admin, false));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course0_1->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course0_1->id), $admin, false));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course1_1->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course1_1->id), $admin, false));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course2_1->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course2_1->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_system::instance(), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_tenant::instance($tenant1->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user0_1->id), $admin, false));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user1_1->id), $admin, false));

        $this->setUser($user0_1);
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_system::instance()));
        $this->assertTrue(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user1_1->id)));

        $this->setUser($user1_1);
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_system::instance()));
        $this->assertTrue(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user1_1->id)));

        $this->setUser($user1_2);
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_system::instance()));
        $this->assertTrue(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_user::instance($user1_1->id)));

        $this->setUser($user2_1);
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($frontpage->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($frontpage->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course0_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course0_1->id)));
        $this->assertFalse(has_capability('moodle/course:view', context_course::instance($course1_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_course::instance($course1_1->id)));
        $this->assertTrue(has_capability('moodle/course:view', context_course::instance($course2_1->id)));
        $this->assertTrue(has_capability('moodle/block:view', context_course::instance($course2_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_system::instance()));
        $this->assertFalse(has_capability('moodle/block:view', context_tenant::instance($tenant1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_user::instance($user0_1->id)));
        $this->assertFalse(has_capability('moodle/block:view', context_user::instance($user1_1->id)));
    }

    public function test_get_role_archetypes() {
        $archetypes = get_role_archetypes();
        $this->assertSame('tenantusermanager', $archetypes['tenantusermanager']);
        $this->assertSame('tenantdomainmanager', $archetypes['tenantdomainmanager']);
    }

    public function test_get_default_role_archetype_allows() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();

        $tenantdomainmanager = $DB->get_record('role', ['archetype' => 'tenantdomainmanager']);
        $tenantusermanager = $DB->get_record('role', ['archetype' => 'tenantusermanager']);
        $coursecreator = $DB->get_record('role', ['archetype' => 'coursecreator']);
        $editingteacher = $DB->get_record('role', ['archetype' => 'editingteacher']);
        $teacher = $DB->get_record('role', ['archetype' => 'teacher']);
        $student = $DB->get_record('role', ['archetype' => 'student']);
        $guest = $DB->get_record('role', ['archetype' => 'guest']);
        $user = $DB->get_record('role', ['archetype' => 'user']);

        $this->assertSame(
            [$tenantusermanager->id => $tenantusermanager->id],
            get_default_role_archetype_allows('assign', 'tenantusermanager')
        );
        $this->assertSame(
            [],
            get_default_role_archetype_allows('override', 'tenantusermanager')
        );
        $this->assertSame(
            [],
            get_default_role_archetype_allows('switch', 'tenantusermanager')
        );

        $this->assertSame(
            [$coursecreator->id => $coursecreator->id, $editingteacher->id => $editingteacher->id, $teacher->id => $teacher->id, $student->id => $student->id, $tenantdomainmanager->id => $tenantdomainmanager->id],
            get_default_role_archetype_allows('assign', 'tenantdomainmanager')
        );
        $this->assertSame(
            [$coursecreator->id => $coursecreator->id, $editingteacher->id => $editingteacher->id, $teacher->id => $teacher->id, $student->id => $student->id, $guest->id => $guest->id, $user->id => $user->id],
            get_default_role_archetype_allows('override', 'tenantdomainmanager')
        );
        $this->assertSame(
            [],
            get_default_role_archetype_allows('switch', 'tenantdomainmanager')
        );
    }

    public function test_get_component_string() {
        $this->assertSame('Tenant', get_component_string('core', CONTEXT_TENANT));
    }

    public function test_get_default_contextlevels() {
        $this->assertSame([CONTEXT_TENANT], get_default_contextlevels('tenantusermanager'));
        $this->assertSame([CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE], get_default_contextlevels('tenantdomainmanager'));
    }

    public function test_get_users_by_capability() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $managerrole = $DB->get_record('role', array('shortname'=>'manager'), '*', MUST_EXIST);

        $admin = get_admin();
        $guest = guest_user();
        $frontpage = $DB->get_record('course', ['id' => SITEID], '*', MUST_EXIST);

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $tenantcategory2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);

        role_assign($managerrole->id, $guest->id, context_course::instance($course0_1->id)->id);
        role_assign($managerrole->id, $guest->id, context_course::instance($course1_1->id)->id);
        role_assign($managerrole->id, $user0_1->id, context_course::instance($course0_1->id)->id);
        role_assign($managerrole->id, $user0_1->id, context_course::instance($course1_1->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_course::instance($course0_1->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_course::instance($course1_1->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_course::instance($course2_1->id)->id);
        role_assign($managerrole->id, $user2_1->id, context_course::instance($course2_1->id)->id);

        set_config('tenantsisolated', '0');

        $this->setUser(0);

        $result = get_users_by_capability(context_course::instance($frontpage->id), 'moodle/course:view', 'u.id', 'u.id ASC');
        $this->assertSame([], array_map('strval', array_keys($result)));
        $result = get_users_by_capability(context_course::instance($frontpage->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id, $user1_1->id, $user1_2->id, $user2_1->id, $user2_2->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_course::instance($course0_1->id), 'moodle/course:view', 'u.id', 'u.id ASC');
        $this->assertSame([$user0_1->id, $user1_1->id], array_map('strval', array_keys($result)));
        $result = get_users_by_capability(context_course::instance($course0_1->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id, $user1_1->id, $user1_2->id, $user2_1->id, $user2_2->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_course::instance($course1_1->id), 'moodle/course:view', 'u.id', 'u.id ASC');
        $this->assertSame([$user0_1->id, $user1_1->id], array_map('strval', array_keys($result)));
        $result = get_users_by_capability(context_course::instance($course1_1->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id, $user1_1->id, $user1_2->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_course::instance($course2_1->id), 'moodle/course:view', 'u.id', 'u.id ASC');
        $this->assertSame([$user2_1->id], array_map('strval', array_keys($result)));
        $result = get_users_by_capability(context_course::instance($course2_1->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id, $user2_1->id, $user2_2->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_system::instance(), 'moodle/course:view', 'u.id', 'u.id ASC');
        $this->assertSame([], array_map('strval', array_keys($result)));
        $result = get_users_by_capability(context_system::instance(), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id, $user1_1->id, $user1_2->id, $user2_1->id, $user2_2->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_tenant::instance($tenant1->id), 'moodle/course:view', 'u.id', 'u.id ASC');
        $this->assertSame([], array_map('strval', array_keys($result)));
        $result = get_users_by_capability(context_tenant::instance($tenant1->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id, $user1_1->id, $user1_2->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_user::instance($user0_1->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id, $user1_1->id, $user1_2->id, $user2_1->id, $user2_2->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_user::instance($user1_1->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id, $user1_1->id, $user1_2->id], array_map('strval', array_keys($result)));

        set_config('tenantsisolated', '1');

        $result = get_users_by_capability(context_course::instance($frontpage->id), 'moodle/course:view', 'u.id', 'u.id ASC');
        $this->assertSame([], array_map('strval', array_keys($result)));
        $result = get_users_by_capability(context_course::instance($frontpage->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_course::instance($course0_1->id), 'moodle/course:view', 'u.id', 'u.id ASC');
        $this->assertSame([$user0_1->id], array_map('strval', array_keys($result)));
        $result = get_users_by_capability(context_course::instance($course0_1->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_course::instance($course1_1->id), 'moodle/course:view', 'u.id', 'u.id ASC');
        $this->assertSame([$user0_1->id, $user1_1->id], array_map('strval', array_keys($result)));
        $result = get_users_by_capability(context_course::instance($course1_1->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id, $user1_1->id, $user1_2->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_course::instance($course2_1->id), 'moodle/course:view', 'u.id', 'u.id ASC');
        $this->assertSame([$user2_1->id], array_map('strval', array_keys($result)));
        $result = get_users_by_capability(context_course::instance($course2_1->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id, $user2_1->id, $user2_2->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_system::instance(), 'moodle/course:view', 'u.id', 'u.id ASC');
        $this->assertSame([], array_map('strval', array_keys($result)));
        $result = get_users_by_capability(context_system::instance(), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_tenant::instance($tenant1->id), 'moodle/course:view', 'u.id', 'u.id ASC');
        $this->assertSame([], array_map('strval', array_keys($result)));
        $result = get_users_by_capability(context_tenant::instance($tenant1->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id, $user1_1->id, $user1_2->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_user::instance($user0_1->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id], array_map('strval', array_keys($result)));

        $result = get_users_by_capability(context_user::instance($user1_1->id), 'moodle/block:view', 'u.id', 'u.id ASC');
        $this->assertSame([$admin->id, $user0_1->id, $user1_1->id, $user1_2->id], array_map('strval', array_keys($result)));
    }

    /**
     * Make sure the extra fields tenantid is returned by default.
     */
    public function test_get_role_users() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);

        $managerrole = $DB->get_record('role', array('shortname'=>'manager'), '*', MUST_EXIST);

        $frontpage = $DB->get_record('course', ['id' => SITEID], '*', MUST_EXIST);
        $context = context_course::instance($frontpage->id);
        role_assign($managerrole->id, $user1->id, $context->id);
        role_assign($managerrole->id, $user2->id, $context->id);

        $users = get_role_users($managerrole->id, $context);
        $this->assertCount(2, $users);
        $this->assertSame(null, $users[$user1->id]->tenantid);
        $this->assertSame($tenant->id, $users[$user2->id]->tenantid);
    }

    public function test_role_get_name() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();

        $role = $DB->get_record('role', ['shortname' => 'tenantusermanager']);
        $this->assertSame('Tenant User Manager', role_get_name($role, context_system::instance()));

        $role = $DB->get_record('role', ['shortname' => 'tenantdomainmanager']);
        $this->assertSame('Tenant Domain Manager', role_get_name($role, context_system::instance()));
    }

    public function test_role_get_description() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();

        $role = $DB->get_record('role', ['shortname' => 'tenantusermanager']);
        $this->assertSame('Role intended for tenant user management delegation.', role_get_description($role));

        $role = $DB->get_record('role', ['shortname' => 'tenantdomainmanager']);
        $this->assertSame('Role intended for tenant course management delegation.', role_get_description($role));
    }

    public function test_build_all_paths() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $category0 = $this->getDataGenerator()->create_category();
        $category0b = $this->getDataGenerator()->create_category(['parent' => $category0->id]);
        $category0c = $this->getDataGenerator()->create_category(['parent' => $category0b->id]);
        $category1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid]);
        $category1b = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $course0 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $course0b = $this->getDataGenerator()->create_course(['category' => $category0b->id]);
        $course0c = $this->getDataGenerator()->create_course(['category' => $category0c->id]);
        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course1b = $this->getDataGenerator()->create_course(['category' => $category1b->id]);
        $page0 = $this->getDataGenerator()->create_module('page', array('course' => $course0->id));
        $page0b = $this->getDataGenerator()->create_module('page', array('course' => $course0b->id));
        $page0c = $this->getDataGenerator()->create_module('page', array('course' => $course0c->id));
        $page1 = $this->getDataGenerator()->create_module('page', array('course' => $course1->id));
        $page1b = $this->getDataGenerator()->create_module('page', array('course' => $course1b->id));
        $block0 = $this->getDataGenerator()->create_block('online_users', array('parentcontextid' => context_course::instance($course0->id)->id));
        $block0b = $this->getDataGenerator()->create_block('online_users', array('parentcontextid' => context_course::instance($course0b->id)->id));
        $block0c = $this->getDataGenerator()->create_block('online_users', array('parentcontextid' => context_course::instance($course0c->id)->id));
        $block1 = $this->getDataGenerator()->create_block('online_users', array('parentcontextid' => context_course::instance($course1->id)->id));
        $block1b = $this->getDataGenerator()->create_block('online_users', array('parentcontextid' => context_course::instance($course1b->id)->id));

        $allcontexts = $DB->get_records('context', [], 'id ASC');

        context_helper::build_all_paths(true);
        $this->assertEquals($allcontexts, $DB->get_records('context', [], 'id ASC'));

        $DB->set_field('context', 'tenantid', null, []);
        context_helper::build_all_paths(true);
        $this->assertEquals($allcontexts, $DB->get_records('context', [], 'id ASC'));

        $DB->set_field('context', 'tenantid', $tenant2->id, []);
        context_helper::build_all_paths(true);
        $this->assertEquals($allcontexts, $DB->get_records('context', [], 'id ASC'));

        $DB->set_field_select('context', 'path', null, "contextlevel <> ".CONTEXT_SYSTEM);
        $DB->set_field_select('context', 'depth', 0, "contextlevel <> ".CONTEXT_SYSTEM);
        $DB->set_field_select('context', 'parentid', null, "contextlevel <> ".CONTEXT_SYSTEM);
        context_helper::build_all_paths(true);
        $this->assertEquals($allcontexts, $DB->get_records('context', [], 'id ASC'));

        $DB->set_field_select('context', 'path', null, "contextlevel <> ".CONTEXT_SYSTEM);
        $DB->set_field_select('context', 'depth', 0, "contextlevel <> ".CONTEXT_SYSTEM);
        $DB->set_field_select('context', 'parentid', null, "contextlevel <> ".CONTEXT_SYSTEM);
        $DB->set_field('context', 'tenantid', null, []);
        context_helper::build_all_paths(true);
        $this->assertEquals($allcontexts, $DB->get_records('context', [], 'id ASC'));
    }

    public function test_get_switchable_roles() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $managerrole = $DB->get_record('role', array('shortname'=>'manager'), '*', MUST_EXIST);
        assign_capability('moodle/role:switchroles', CAP_ALLOW, $managerrole->id, context_system::instance()); // Capability is deprecated since Totara 13.0.

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $tenantcategory2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);

        role_assign($managerrole->id, $user0_1->id, context_course::instance($course0_1->id)->id);
        role_assign($managerrole->id, $user0_1->id, context_course::instance($course1_1->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_course::instance($course0_1->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_course::instance($course1_1->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_course::instance($course2_1->id)->id);
        role_assign($managerrole->id, $user2_1->id, context_course::instance($course2_1->id)->id);

        $contexts = $DB->get_records('context');
        $users = $DB->get_records('user');

        // Evaluate all results for all users in all contexts.
        foreach ($users as $user) {
            $this->setUser($user);
            foreach ($contexts as $context) {
                if ($user->tenantid or $context->tenantid) {
                    $context = context_helper::instance_by_id($context->id);
                    $this->assertSame([], get_switchable_roles($context));
                }
            }
        }
    }
}
