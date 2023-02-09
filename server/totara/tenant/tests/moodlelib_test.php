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
 * Tests covering multitenancy related changes in lib/moodlelib.php
 */
class totara_tenant_moodlelib_testcase extends advanced_testcase {
    /**
     * Test that require_login() respects tenant separation.
     */
    public function test_require_login() {
        global $DB, $PAGE;

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

        $page = $this->getDataGenerator()->create_module('page', array('course' => $frontpage->id));
        $cm = get_coursemodule_from_instance('page', $page->id);
        $page0_1 = $this->getDataGenerator()->create_module('page', array('course' => $course0_1->id));
        $cm0_1 = get_coursemodule_from_instance('page', $page0_1->id);
        $page1_1 = $this->getDataGenerator()->create_module('page', array('course' => $course1_1->id));
        $cm1_1 = get_coursemodule_from_instance('page', $page1_1->id);
        $page2_1 = $this->getDataGenerator()->create_module('page', array('course' => $course2_1->id));
        $cm2_1 = get_coursemodule_from_instance('page', $page2_1->id);

        $this->getDataGenerator()->enrol_user($user0_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user0_1->id, $course1_1->id, 'student'); // hack
        $this->getDataGenerator()->enrol_user($user1_1->id, $course1_1->id, 'student');

        role_assign($managerrole->id, $user2_1->id, context_course::instance($frontpage->id)->id);
        role_assign($managerrole->id, $user2_1->id, context_course::instance($course0_1->id)->id);
        role_assign($managerrole->id, $user2_1->id, context_course::instance($course1_1->id)->id);
        role_assign($managerrole->id, $user2_1->id, context_course::instance($course2_1->id)->id);

        set_config('tenantsisolated', '0');

        $this->setUser($admin);

        $PAGE = new moodle_page();
        require_login(null);
        $PAGE = new moodle_page();
        require_login($frontpage);
        $PAGE = new moodle_page();
        require_login($frontpage, false, $cm);
        $PAGE = new moodle_page();
        require_login($course0_1);
        $PAGE = new moodle_page();
        require_login($course0_1, false, $cm0_1);
        $PAGE = new moodle_page();
        require_login($course1_1);
        $PAGE = new moodle_page();
        require_login($course1_1, false, $cm1_1);

        $this->setUser($user0_1);

        $PAGE = new moodle_page();
        require_login(null, false, null, false, true);
        $PAGE = new moodle_page();
        require_login($frontpage, false, null, false, true);
        $PAGE = new moodle_page();
        require_login($frontpage, false, $cm, false, true);
        $PAGE = new moodle_page();
        require_login($course0_1, false, null, false, true);
        $PAGE = new moodle_page();
        require_login($course0_1, false, $cm0_1, false, true);
        $PAGE = new moodle_page();
        require_login($course1_1,false, null, false, true);
        $PAGE = new moodle_page();
        require_login($course1_1, false, $cm1_1, false, true);
        try {
            $PAGE = new moodle_page();
            require_login($course2_1,false, null, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        try {
            $PAGE = new moodle_page();
            require_login($course2_1, false, $cm2_1, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }

        $this->setUser($user1_1);

        $PAGE = new moodle_page();
        require_login(null, false, null, false, true);
        $PAGE = new moodle_page();
        require_login($frontpage, false, null, false, true);
        $PAGE = new moodle_page();
        require_login($frontpage, false, $cm, false, true);
        try {
            $PAGE = new moodle_page();
            require_login($course0_1, false, null, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        try {
            $PAGE = new moodle_page();
            require_login($course0_1, false, $cm0_1, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        $PAGE = new moodle_page();
        require_login($course1_1,false, null, false, true);
        $PAGE = new moodle_page();
        require_login($course1_1, false, $cm1_1, false, true);
        try {
            $PAGE = new moodle_page();
            require_login($course2_1,false, null, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        try {
            $PAGE = new moodle_page();
            require_login($course2_1, false, $cm2_1, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }

        $this->setUser($user2_1);

        $PAGE = new moodle_page();
        require_login(null, false, null, false, true);
        $PAGE = new moodle_page();
        require_login($frontpage, false, null, false, true);
        $PAGE = new moodle_page();
        require_login($frontpage, false, $cm, false, true);
        $PAGE = new moodle_page();
        require_login($course0_1, false, null, false, true);
        $PAGE = new moodle_page();
        require_login($course0_1, false, $cm0_1, false, true);
        try {
            $PAGE = new moodle_page();
            require_login($course1_1,false, null, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        try {
            $PAGE = new moodle_page();
            require_login($course1_1, false, $cm1_1, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        $PAGE = new moodle_page();
        require_login($course2_1,false, null, false, true);
        $PAGE = new moodle_page();
        require_login($course2_1, false, $cm2_1, false, true);

        set_config('tenantsisolated', '1');

        $this->setUser($admin);

        $PAGE = new moodle_page();
        require_login(null);
        $PAGE = new moodle_page();
        require_login($frontpage);
        $PAGE = new moodle_page();
        require_login($frontpage, false, $cm);
        $PAGE = new moodle_page();
        require_login($course0_1);
        $PAGE = new moodle_page();
        require_login($course0_1, false, $cm0_1);
        $PAGE = new moodle_page();
        require_login($course1_1);
        $PAGE = new moodle_page();
        require_login($course1_1, false, $cm1_1);

        $this->setUser($user0_1);

        $PAGE = new moodle_page();
        require_login(null, false, null, false, true);
        $PAGE = new moodle_page();
        require_login($frontpage, false, null, false, true);
        $PAGE = new moodle_page();
        require_login($frontpage, false, $cm, false, true);
        $PAGE = new moodle_page();
        require_login($course0_1, false, null, false, true);
        $PAGE = new moodle_page();
        require_login($course0_1, false, $cm0_1, false, true);
        $PAGE = new moodle_page();
        require_login($course1_1,false, null, false, true);
        $PAGE = new moodle_page();
        require_login($course1_1, false, $cm1_1, false, true);
        try {
            $PAGE = new moodle_page();
            require_login($course2_1,false, null, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        try {
            $PAGE = new moodle_page();
            require_login($course2_1, false, $cm2_1, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }

        $this->setUser($user1_1);

        $PAGE = new moodle_page();
        require_login(null, false, null, false, true);
        $PAGE = new moodle_page();
        require_login($frontpage, false, null, false, true);
        try {
            $PAGE = new moodle_page();
            require_login($frontpage, false, $cm, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        try {
            $PAGE = new moodle_page();
            require_login($course0_1, false, null, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        try {
            $PAGE = new moodle_page();
            require_login($course0_1, false, $cm0_1, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        $PAGE = new moodle_page();
        require_login($course1_1,false, null, false, true);
        $PAGE = new moodle_page();
        require_login($course1_1, false, $cm1_1, false, true);
        try {
            $PAGE = new moodle_page();
            require_login($course2_1,false, null, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        try {
            $PAGE = new moodle_page();
            require_login($course2_1, false, $cm2_1, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }

        $this->setUser($user2_1);

        $PAGE = new moodle_page();
        require_login(null, false, null, false, true);
        $PAGE = new moodle_page();
        require_login($frontpage, false, null, false, true);
        try {
            $PAGE = new moodle_page();
            require_login($frontpage, false, $cm, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        try {
            $PAGE = new moodle_page();
            require_login($course0_1, false, null, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        try {
            $PAGE = new moodle_page();
            require_login($course0_1, false, $cm0_1, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        try {
            $PAGE = new moodle_page();
            require_login($course1_1,false, null, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        try {
            $PAGE = new moodle_page();
            require_login($course1_1, false, $cm1_1, false, true);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(require_login_exception::class, $e);
        }
        $PAGE = new moodle_page();
        require_login($course2_1,false, null, false, true);
        $PAGE = new moodle_page();
        require_login($course2_1, false, $cm2_1, false, true);
    }

    /**
     * Make sure is tenants disabled or tenant suspended users cannot log in.
     */
    public function test_authenticate_user_login() {
        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $admin = get_admin();

        $tenant = $tenantgenerator->create_tenant(['suspended' => 0]);
        $user = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id, 'username' => 'myuser', 'password' => 'mypassword']);

        $failurereason = null;
        $result = authenticate_user_login('myuser', 'mypassword', true, $failurereason);
        $this->assertSame(0, $failurereason);
        $this->assertSame($user->id, $result->id);

        $data = (array)$tenant;
        $data['suspended'] = 1;
        $tenant = \totara_tenant\local\util::update_tenant($data);

        $failurereason = null;
        $result = authenticate_user_login('myuser', 'mypassword', true, $failurereason);
        $this->assertSame(2, $failurereason);
        $this->assertFalse($result);

        $data = (array)$tenant;
        $data['suspended'] = 0;
        $tenant = \totara_tenant\local\util::update_tenant($data);
        $tenantgenerator->disable_tenants();

        $failurereason = null;
        $result = authenticate_user_login('myuser', 'mypassword', true, $failurereason);
        $this->assertSame(2, $failurereason);
        $this->assertFalse($result);
    }

    /**
     * Make sure emails do nto go out to users in disabled tenants.
     */
    public function test_email_to_user() {
        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $admin = get_admin();

        $tenant = $tenantgenerator->create_tenant(['suspended' => 0]);
        $user = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);

        $sink = $this->redirectEmails();

        $result = email_to_user($user, $admin, 'subject', 'message');
        $this->assertTrue($result);
        $this->assertCount(1, $sink->get_messages());
        $sink->clear();

        $data = (array)$tenant;
        $data['suspended'] = 1;
        $tenant = \totara_tenant\local\util::update_tenant($data);

        $result = email_to_user($user, $admin, 'subject', 'message');
        $this->assertTrue($result);
        $this->assertCount(0, $sink->get_messages());
        $sink->clear();
    }

    public function test_get_home_page() {

        set_config('allowdefaultpageselection', '1');

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();

        $user0 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant->id]);

        $this->setUser($user0);
        $this->assertSame(HOMEPAGE_TOTARA_DASHBOARD, get_home_page());
        set_user_preference('user_home_page_preference', HOMEPAGE_SITE);
        $this->assertSame(HOMEPAGE_SITE, get_home_page());

        $this->setUser($user1);
        set_config('tenantsisolated', '0');
        $this->assertSame(HOMEPAGE_TOTARA_DASHBOARD, get_home_page());

        set_user_preference('user_home_page_preference', HOMEPAGE_SITE);
        $this->assertSame(HOMEPAGE_SITE, get_home_page());

        set_config('tenantsisolated', '1');
        $this->assertSame(HOMEPAGE_TOTARA_DASHBOARD, get_home_page());
    }
}
