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

use container_course\course;
use core_container\factory;
use totara_tenant\local\util as tenant_util;

/**
 * Tests covering multitenancy related changes in course category related code.
 */
class totara_tenant_coursecat_testcase extends advanced_testcase {
    public function test_update() {
        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();
        $tenantcat = coursecat::get($tenant->categoryid);

        $category = $this->getDataGenerator()->create_category();

        // Prevent tenant cat moves.
        $data = $tenantcat->get_db_record();
        $data->parent = $category->id;
        try {
            $tenantcat->update($data);
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(coding_exception::class, $e);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Top level tenant categories cannot be moved', $e->getMessage());
        }

        // Detect data for different category.
        $data = $tenantcat->get_db_record();
        try {
            $category->update($data);
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(coding_exception::class, $e);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Incorrect ID in data parameter', $e->getMessage());
        }
    }

    public function test_is_user_visible() {
        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $admin = get_admin();
        $guest = guest_user();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $category0 = $this->getDataGenerator()->create_category();
        $category0b = $this->getDataGenerator()->create_category(['parent' => $category0->id]);
        $category0c = $this->getDataGenerator()->create_category(['parent' => $category0b->id]);
        $category1 = coursecat::get($tenant1->categoryid);
        $category1b = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $category2 = coursecat::get($tenant2->categoryid);

        set_config('tenantsisolated', '0');
        cache_helper::purge_by_event('changesincoursecat');

        $this->setUser(0);
        $this->assertTrue($category0->is_uservisible());
        $this->assertTrue($category0b->is_uservisible());
        $this->assertFalse($category1->is_uservisible());
        $this->assertFalse($category1b->is_uservisible());
        $this->assertFalse($category2->is_uservisible());

        $this->setUser($guest);
        $this->assertTrue($category0->is_uservisible());
        $this->assertTrue($category0b->is_uservisible());
        $this->assertFalse($category1->is_uservisible());
        $this->assertFalse($category1b->is_uservisible());
        $this->assertFalse($category2->is_uservisible());

        $this->setUser($admin);
        $this->assertTrue($category0->is_uservisible());
        $this->assertTrue($category0b->is_uservisible());
        $this->assertTrue($category1->is_uservisible());
        $this->assertTrue($category1b->is_uservisible());
        $this->assertTrue($category2->is_uservisible());

        $this->setUser($user0_1);
        $this->assertTrue($category0->is_uservisible());
        $this->assertTrue($category0b->is_uservisible());
        $this->assertTrue($category1->is_uservisible());
        $this->assertTrue($category1b->is_uservisible());
        $this->assertTrue($category2->is_uservisible());

        $this->setUser($user1_1);
        $this->assertTrue($category0->is_uservisible());
        $this->assertTrue($category0b->is_uservisible());
        $this->assertTrue($category1->is_uservisible());
        $this->assertTrue($category1b->is_uservisible());
        $this->assertFalse($category2->is_uservisible());

        set_config('tenantsisolated', '1');
        cache_helper::purge_by_event('changesincoursecat');

        $this->setUser(0);
        $this->assertTrue($category0->is_uservisible());
        $this->assertTrue($category0b->is_uservisible());
        $this->assertFalse($category1->is_uservisible());
        $this->assertFalse($category1b->is_uservisible());
        $this->assertFalse($category2->is_uservisible());

        $this->setUser($guest);
        $this->assertTrue($category0->is_uservisible());
        $this->assertTrue($category0b->is_uservisible());
        $this->assertFalse($category1->is_uservisible());
        $this->assertFalse($category1b->is_uservisible());
        $this->assertFalse($category2->is_uservisible());

        $this->setUser($admin);
        $this->assertTrue($category0->is_uservisible());
        $this->assertTrue($category0b->is_uservisible());
        $this->assertTrue($category1->is_uservisible());
        $this->assertTrue($category1b->is_uservisible());
        $this->assertTrue($category2->is_uservisible());

        $this->setUser($user0_1);
        $this->assertTrue($category0->is_uservisible());
        $this->assertTrue($category0b->is_uservisible());
        $this->assertTrue($category1->is_uservisible());
        $this->assertTrue($category1b->is_uservisible());
        $this->assertTrue($category2->is_uservisible());

        $this->setUser($user1_1);
        $this->assertFalse($category0->is_uservisible());
        $this->assertFalse($category0b->is_uservisible());
        $this->assertTrue($category1->is_uservisible());
        $this->assertTrue($category1b->is_uservisible());
        $this->assertFalse($category2->is_uservisible());
    }

    /**
     * Basic regression tests only, we have switched to DB call to find if user has any caps.
     */
    public function test_has_capability_on_any() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $managerrole = $DB->get_record('role', array('shortname'=>'manager'), '*', MUST_EXIST);

        $admin = get_admin();
        $guest = guest_user();

        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user0_2 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $category0_1 = $this->getDataGenerator()->create_category();
        $category0_2 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0_1->id]);

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

        role_assign($managerrole->id, $guest->id, context_coursecat::instance($category0_1->id)->id); // Show be ignored.
        role_assign($managerrole->id, $user0_1->id, context_course::instance($course0_1->id)->id); // Should be ignored.
        role_assign($managerrole->id, $user0_2->id, context_coursecat::instance($category0_2->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_coursecat::instance($tenantcategory1->id)->id);
        role_assign($managerrole->id, $user2_1->id, context_coursecat::instance($tenantcategory1->id)->id); // Should be ignored.
        role_assign($managerrole->id, $user2_2->id, context_coursecat::instance($category0_2->id)->id);

        /* @var cache_session $cache */
        $cache = cache::make('core', 'coursecat');

        set_config('tenantsisolated', '0');

        $this->setUser(0);
        $cache->purge();
        $this->assertFalse(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($guest);
        $cache->purge();
        $this->assertFalse(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($admin);
        $cache->purge();
        $this->assertTrue(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($user0_1);
        $cache->purge();
        $this->assertFalse(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($user0_2);
        $cache->purge();
        $this->assertTrue(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($user1_1);
        $cache->purge();
        $this->assertTrue(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($user1_2);
        $cache->purge();
        $this->assertFalse(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($user2_1);
        $cache->purge();
        $this->assertFalse(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($user2_2);
        $cache->purge();
        $this->assertTrue(coursecat::has_capability_on_any('moodle/category:manage'));

        set_config('tenantsisolated', '1');

        $this->setUser(0);
        $cache->purge();
        $this->assertFalse(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($guest);
        $cache->purge();
        $this->assertFalse(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($admin);
        $cache->purge();
        $this->assertTrue(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($user0_1);
        $cache->purge();
        $this->assertFalse(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($user0_2);
        $cache->purge();
        $this->assertTrue(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($user1_1);
        $cache->purge();
        $this->assertTrue(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($user1_2);
        $cache->purge();
        $this->assertFalse(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($user2_1);
        $cache->purge();
        $this->assertFalse(coursecat::has_capability_on_any('moodle/category:manage'));

        $this->setUser($user2_2);
        $cache->purge();
        $this->assertFalse(coursecat::has_capability_on_any('moodle/category:manage'));
    }

    public function test_can_delete() {
        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();

        $this->setAdminUser();

        $category = $this->getDataGenerator()->create_category();
        $this->assertTrue($category->can_delete());

        $tenant = $tenantgenerator->create_tenant();
        $tenantcat = coursecat::get($tenant->categoryid);
        $this->assertFalse($tenantcat->can_delete());
    }

    public function test_can_delete_full() {
        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();

        $this->setAdminUser();

        $category = $this->getDataGenerator()->create_category();
        $this->assertTrue($category->can_delete_full());

        $tenant = $tenantgenerator->create_tenant();
        $tenantcat = coursecat::get($tenant->categoryid);
        $this->assertFalse($tenantcat->can_delete_full());
    }

    public function test_delete_full() {
        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();

        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();
        $tenantcat = coursecat::get($tenant->categoryid);

        try {
            $tenantcat->delete_full();
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(coding_exception::class, $e);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Tenant category cannot be deleted', $e->getMessage());
        }
    }

    public function test_delete_move() {
        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();

        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();
        $tenantcat = coursecat::get($tenant->categoryid);
        $category = $this->getDataGenerator()->create_category();

        try {
            $tenantcat->delete_move($category->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(coding_exception::class, $e);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Tenant category cannot be deleted', $e->getMessage());
        }
    }

    public function test_can_change_parent() {
        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();

        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();
        $tenantcat = coursecat::get($tenant->categoryid);
        $category = $this->getDataGenerator()->create_category();

        $this->assertFalse($tenantcat->can_change_parent($category->id));
    }

    public function test_change_parent() {
        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();

        $this->setAdminUser();

        $tenant = $tenantgenerator->create_tenant();
        $tenantcat = coursecat::get($tenant->categoryid);
        $category = $this->getDataGenerator()->create_category();

        try {
            $tenantcat->change_parent($category);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf(coding_exception::class, $e);
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Tenant category cannot be moved', $e->getMessage());
        }
    }

    public function test_make_categories_list() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $managerrole = $DB->get_record('role', array('shortname'=>'manager'), '*', MUST_EXIST);

        $admin = get_admin();
        $guest = guest_user();

        $misccat = $DB->get_record('course_categories', ['id' => course::get_default_category_id()]);

        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user0_2 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $category0_1 = $this->getDataGenerator()->create_category();
        $category0_2 = $this->getDataGenerator()->create_category(['parent' => $category0_1->id]);
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0_1->id]);

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

        role_assign($managerrole->id, $guest->id, context_coursecat::instance($category0_1->id)->id); // Show be ignored.
        role_assign($managerrole->id, $user0_1->id, context_course::instance($course0_1->id)->id); // Should be ignored.
        role_assign($managerrole->id, $user0_2->id, context_coursecat::instance($category0_2->id)->id);
        role_assign($managerrole->id, $user1_1->id, context_coursecat::instance($tenantcategory1->id)->id);
        role_assign($managerrole->id, $user2_1->id, context_coursecat::instance($tenantcategory1->id)->id); // Should be ignored.
        role_assign($managerrole->id, $user2_2->id, context_coursecat::instance($category0_2->id)->id);

        /* @var cache_session $cache */
        $cache = cache::make('core', 'coursecat');

        set_config('tenantsisolated', '0');

        $this->setUser(0);
        $cache->purge();
        $expected = [
            $misccat->id => $misccat->name,
            $category0_1->id => $category0_1->name,
            $category0_2->id => $category0_1->name . ' / ' . $category0_2->name,
        ];
        $this->assertSame($expected, coursecat::make_categories_list());
        $this->assertSame(5, coursecat::count_all());

        $this->setUser($guest);
        $cache->purge();
        $expected = [
            $misccat->id => $misccat->name,
            $category0_1->id => $category0_1->name,
            $category0_2->id => $category0_1->name . ' / ' . $category0_2->name,
        ];
        $this->assertSame($expected, coursecat::make_categories_list());
        $this->assertSame(5, coursecat::count_all());

        $this->setUser($admin);
        $cache->purge();
        $expected = [
            $misccat->id => $misccat->name,
            $category0_1->id => $category0_1->name,
            $category0_2->id => $category0_1->name . ' / ' . $category0_2->name,
            $tenantcategory1->id => $tenantcategory1->name,
            $tenantcategory2->id => $tenantcategory2->name,
        ];
        $this->assertSame($expected, coursecat::make_categories_list());
        $this->assertSame(5, coursecat::count_all());

        $this->setUser($user0_1);
        $cache->purge();
        $expected = [
            $misccat->id => $misccat->name,
            $category0_1->id => $category0_1->name,
            $category0_2->id => $category0_1->name . ' / ' . $category0_2->name,
            $tenantcategory1->id => $tenantcategory1->name,
            $tenantcategory2->id => $tenantcategory2->name,
        ];
        $this->assertSame($expected, coursecat::make_categories_list());
        $this->assertSame(5, coursecat::count_all());

        $this->setUser($user1_1);
        $cache->purge();
        $expected = [
            $misccat->id => $misccat->name,
            $category0_1->id => $category0_1->name,
            $category0_2->id => $category0_1->name . ' / ' . $category0_2->name,
            $tenantcategory1->id => $tenantcategory1->name,
        ];
        $this->assertSame($expected, coursecat::make_categories_list());
        $this->assertSame(5, coursecat::count_all());

        $this->setUser($user2_1);
        $cache->purge();
        $expected = [
            $misccat->id => $misccat->name,
            $category0_1->id => $category0_1->name,
            $category0_2->id => $category0_1->name . ' / ' . $category0_2->name,
            $tenantcategory2->id => $tenantcategory2->name,
        ];
        $this->assertSame($expected, coursecat::make_categories_list());
        $this->assertSame(5, coursecat::count_all());

        set_config('tenantsisolated', '1');

        $this->setUser(0);
        $cache->purge();
        $expected = [
            $misccat->id => $misccat->name,
            $category0_1->id => $category0_1->name,
            $category0_2->id => $category0_1->name . ' / ' . $category0_2->name,
        ];
        $this->assertSame($expected, coursecat::make_categories_list());
        $this->assertSame(5, coursecat::count_all());

        $this->setUser($guest);
        $cache->purge();
        $expected = [
            $misccat->id => $misccat->name,
            $category0_1->id => $category0_1->name,
            $category0_2->id => $category0_1->name . ' / ' . $category0_2->name,
        ];
        $this->assertSame($expected, coursecat::make_categories_list());
        $this->assertSame(5, coursecat::count_all());

        $this->setUser($admin);
        $cache->purge();
        $expected = [
            $misccat->id => $misccat->name,
            $category0_1->id => $category0_1->name,
            $category0_2->id => $category0_1->name . ' / ' . $category0_2->name,
            $tenantcategory1->id => $tenantcategory1->name,
            $tenantcategory2->id => $tenantcategory2->name,
        ];
        $this->assertSame($expected, coursecat::make_categories_list());
        $this->assertSame(5, coursecat::count_all());

        $this->setUser($user0_1);
        $cache->purge();
        $expected = [
            $misccat->id => $misccat->name,
            $category0_1->id => $category0_1->name,
            $category0_2->id => $category0_1->name . ' / ' . $category0_2->name,
            $tenantcategory1->id => $tenantcategory1->name,
            $tenantcategory2->id => $tenantcategory2->name,
        ];
        $this->assertSame($expected, coursecat::make_categories_list());
        $this->assertSame(5, coursecat::count_all());

        $this->setUser($user1_1);
        $cache->purge();
        $expected = [
            $tenantcategory1->id => $tenantcategory1->name,
        ];
        $this->assertSame($expected, coursecat::make_categories_list());
        $this->assertSame(5, coursecat::count_all());

        $this->setUser($user2_1);
        $cache->purge();
        $expected = [
            $tenantcategory2->id => $tenantcategory2->name,
        ];
        $this->assertSame($expected, coursecat::make_categories_list());
        $this->assertSame(5, coursecat::count_all());
    }

    public function test_get_default() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $admin = get_admin();
        $guest = guest_user();

        $misccat = $DB->get_record('course_categories', ['id' => course::get_default_category_id()]);

        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $category0_1 = $this->getDataGenerator()->create_category();
        $category0_2 = $this->getDataGenerator()->create_category(['parent' => $category0_1->id]);

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $tenantcategory2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);

        $this->setUser(0);
        $default = coursecat::get_default();
        $this->assertSame($misccat->id, $default->id);

        $this->setUser($guest);
        $default = coursecat::get_default();
        $this->assertSame($misccat->id, $default->id);

        $this->setUser($admin);
        $default = coursecat::get_default();
        $this->assertSame($misccat->id, $default->id);

        $this->setUser($user0_1);
        $default = coursecat::get_default();
        $this->assertSame($misccat->id, $default->id);

        $this->setUser($user1_1);
        $default = coursecat::get_default();
        $this->assertSame($tenant1->categoryid, $default->id);

        $this->setUser($user2_1);
        $default = coursecat::get_default();
        $this->assertSame($tenant2->categoryid, $default->id);
    }

    public function test_delete_full_with_system_maintained() {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/container/tests/fixtures/core_container_mock_container.php");

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = \totara_tenant\testing\generator::instance();
        $tenant_generator->enable_tenants();

        $this->setAdminUser();

        $tenant1 = $tenant_generator->create_tenant(null);
        $tenant1_user = $this->getDataGenerator()->create_user(
            ['tenantid' => $tenant1->id, 'tenantusermanager' => $tenant1->idnumber, 'tenantdomainmanager' => $tenant1->idnumber]
        );

        // Set up mock container type, and all the hooks mapping.
        factory::phpunit_add_mock_container_class(
            core_container_mock_container::class,
            core_container_mock_container::class
        );

        $misc_category_id = $DB->get_field('course_categories', 'id', ['name' => 'Miscellaneous']);

        // tenant1 - for delete_full
        $this->setUser($tenant1_user);

        // Tenant categories and courses not in the system container
        $tenant_course = $this->getDataGenerator()->create_course(['idnumber' => 'tenant_course', 'category' => $tenant1->categoryid]);
        $parent_category = $this->getDataGenerator()->create_category(['parent' => $tenant1->categoryid, 'idnumber' => 'p']);
        $child_category = $this->getDataGenerator()->create_category(['idnumber' => 'p-c', 'parent' => $parent_category->id]);
        $child_course = $this->getDataGenerator()->create_course(['idnumber' => 'child_course', 'category' => $child_category->id]);

        // Now the system container with its course_container
        $container_category_id = core_container_mock_container::get_default_category_id();
        $container_course = $this->getDataGenerator()->create_course(['idnumber' => 'container_course', 'category' => $container_category_id]);

        $this->assertTrue($DB->record_exists('course_categories', ['id' => $tenant1->categoryid, 'visible' => 1]));
        $this->assertTrue($DB->record_exists('course_categories', ['id' => $container_category_id, 'parent' => $tenant1->categoryid]));
        $this->assertFalse($DB->record_exists('course', ['category' => $misc_category_id]));
        $this->assertTrue($DB->record_exists('course', ['category' => $tenant1->categoryid]));
        $this->assertTrue($DB->record_exists('course', ['category' => $container_category_id]));

        $this->setAdminUser();

        tenant_util::delete_tenant($tenant1->id, tenant_util::DELETE_TENANT_USER_DELETE);

        $this->assertTrue($DB->record_exists('course_categories', ['id' => $tenant1->categoryid, 'visible' => 0]));
        $this->assertTrue($DB->record_exists('course_categories', ['id' => $container_category_id, 'parent' => $tenant1->categoryid]));
        $this->assertFalse($DB->record_exists('course', ['category' => $misc_category_id]));
        $this->assertTrue($DB->record_exists('course', ['category' => $tenant1->categoryid]));
        $this->assertTrue($DB->record_exists('course', ['category' => $container_category_id]));

        $tenantcat = coursecat::get($tenant1->categoryid);
        [$deleted_courses, $deleted_programs, $deleted_certifs] = $tenantcat->delete_full();

        $this->assertCount(3, $deleted_courses);
        $this->assertCount(0, $deleted_programs);
        $this->assertCount(0, $deleted_certifs);

        $expected_course_ids = [$tenant_course->id, $child_course->id, $container_course->id];
        $actual_course_ids = array_map(
            function ($course) {
                return $course->id;
            },
            $deleted_courses
        );
        $this->assertEqualsCanonicalizing($expected_course_ids, $actual_course_ids);

        $this->assertFalse($DB->record_exists('course_categories', ['id' => $tenant1->categoryid]));
        $this->assertFalse($DB->record_exists('course_categories', ['id' => $container_category_id, 'parent' => $tenant1->categoryid]));
        $this->assertFalse($DB->record_exists('course_categories', ['id' => $parent_category->id]));
        $this->assertFalse($DB->record_exists('course_categories', ['parent' => $parent_category->id]));
        $this->assertFalse($DB->record_exists('course', ['category' => $misc_category_id]));
        $this->assertFalse($DB->record_exists('course', ['category' => $tenant1->categoryid]));
        $this->assertFalse($DB->record_exists('course', ['category' => $container_category_id]));
        $this->assertFalse($DB->record_exists('course', ['category' => $child_category->id]));
    }

    public function test_delete_move_with_system_maintained() {
        global $CFG, $DB;
        require_once("{$CFG->dirroot}/container/tests/fixtures/core_container_mock_container.php");

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = \totara_tenant\testing\generator::instance();
        $tenant_generator->enable_tenants();

        $this->setAdminUser();

        $tenant1 = $tenant_generator->create_tenant(null);
        $tenant1_user = $this->getDataGenerator()->create_user(
            ['tenantid' => $tenant1->id, 'tenantusermanager' => $tenant1->idnumber, 'tenantdomainmanager' => $tenant1->idnumber]
        );

        // Set up mock container type, and all the hooks mapping.
        factory::phpunit_add_mock_container_class(
            core_container_mock_container::class,
            core_container_mock_container::class
        );

        $misc_category_id = $DB->get_field('course_categories', 'id', ['name' => 'Miscellaneous']);

        // tenant1 - for delete_full
        $this->setUser($tenant1_user);

        // A tenant course not in the system container
        $this->getDataGenerator()->create_course(['category' => $tenant1->categoryid]);

        // Now the system container with its course_container
        $container_category_id = core_container_mock_container::get_default_category_id();
        $container_course = $this->getDataGenerator()->create_course(['category' => $container_category_id]);

        $this->assertTrue($DB->record_exists('course_categories', ['id' => $tenant1->categoryid, 'visible' => 1]));
        $this->assertTrue($DB->record_exists('course_categories', ['id' => $container_category_id, 'parent' => $tenant1->categoryid]));
        $this->assertFalse($DB->record_exists('course', ['category' => $misc_category_id]));
        $this->assertTrue($DB->record_exists('course', ['category' => $tenant1->categoryid]));
        $this->assertTrue($DB->record_exists('course', ['category' => $container_category_id]));

        $this->setAdminUser();

        tenant_util::delete_tenant($tenant1->id, tenant_util::DELETE_TENANT_USER_DELETE);

        $this->assertTrue($DB->record_exists('course_categories', ['id' => $tenant1->categoryid, 'visible' => 0]));
        $this->assertTrue($DB->record_exists('course_categories', ['id' => $container_category_id, 'parent' => $tenant1->categoryid]));
        $this->assertFalse($DB->record_exists('course', ['category' => $misc_category_id]));
        $this->assertTrue($DB->record_exists('course', ['category' => $tenant1->categoryid]));
        $this->assertTrue($DB->record_exists('course', ['category' => $container_category_id]));

        $tenantcat = coursecat::get($tenant1->categoryid);
        $tenantcat->delete_move($misc_category_id);

        $this->assertFalse($DB->record_exists('course_categories', ['id' => $tenant1->categoryid]));
        $this->assertFalse($DB->record_exists('course_categories', ['id' => $container_category_id, 'parent' => $tenant1->categoryid]));
        $this->assertTrue($DB->record_exists('course', ['category' => $misc_category_id]));
        $this->assertFalse($DB->record_exists('course', ['category' => $tenant1->categoryid]));
        $this->assertFalse($DB->record_exists('course', ['category' => $container_category_id]));
    }

}

