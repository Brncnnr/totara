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

use core_user\access_controller;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests covering core_user\access_controller tenant support.
 */
class totara_tenant_core_user_access_controller_testcase extends advanced_testcase {

    private function disable_engage_features() {
        advanced_feature::disable('engage_resources');
        access_controller::clear_instance_cache();
    }

    public function setUp(): void {
        parent::setUp();

        // Engage allows several properties of users to become visible to all other users. To test that user
        // properties are hidden when appropritate, we need to disable engage.
        $this->disable_engage_features();
    }

    public function test_can_view_profile() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $admin = get_admin();
        $guest = guest_user();
        $frontpage = $DB->get_record('course', ['id' => SITEID], '*', MUST_EXIST);

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user0_2 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $this->getDataGenerator()->enrol_user($user0_1->id, $course0_1->id, 'manager');

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);
        $this->getDataGenerator()->enrol_user($user1_1->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user1_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user1_2->id, $course1_1->id, 'student');

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $tenantcategory2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);
        $this->getDataGenerator()->enrol_user($user2_1->id, $course2_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_2->id, $course2_1->id, 'student');

        set_config('tenantsisolated', '0');

        $this->setUser(0);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($guest);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($admin);

        $this->assertTrue(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user0_1);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user0_2);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user1_1);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user1_2);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user1_3);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user2_1);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user2_2);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user2_3);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        set_config('tenantsisolated', '1');

        $this->setUser(0);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($guest);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($admin);

        $this->assertTrue(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user0_1);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user0_2);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user1_1);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user1_2);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user1_3);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user2_1);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user2_2);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertTrue(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());

        $this->setUser($user2_3);

        $this->assertFalse(access_controller::for($admin)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($admin, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user0_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user1_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_view_profile());

        $this->assertFalse(access_controller::for($user2_2)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_view_profile());

        $this->assertTrue(access_controller::for($user2_3)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_view_profile());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_view_profile());
    }

    public function test_can_loginas() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        $user0_0 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        role_assign($managerrole->id, $user0_0->id, context_system::instance()->id);

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user0_2 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $this->getDataGenerator()->enrol_user($user0_1->id, $course0_1->id, 'manager');

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        role_assign($managerrole->id, $user1_3->id, context_system::instance()->id);
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);
        $this->getDataGenerator()->enrol_user($user1_1->id, $course1_1->id, 'manager');
        $this->getDataGenerator()->enrol_user($user1_1->id, $course0_1->id, 'manager');
        $this->getDataGenerator()->enrol_user($user1_2->id, $course1_1->id, 'manager');

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $tenantcategory2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);
        $this->getDataGenerator()->enrol_user($user2_1->id, $course2_1->id, 'manager');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course1_1->id, 'manager');
        $this->getDataGenerator()->enrol_user($user2_2->id, $course2_1->id, 'manager');

        $allcourses = $DB->get_records('course', []);
        $allusers = $DB->get_records('user', []);

        set_config('tenantsisolated', '0');

        $this->setUser($user0_0);

        $this->assertFalse(access_controller::for($user0_0)->can_loginas());
        $this->assertFalse(access_controller::for($user0_0, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_0, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_0, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user0_1)->can_loginas());
        $this->assertTrue(access_controller::for($user0_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user0_2)->can_loginas());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user1_1)->can_loginas());
        $this->assertTrue(access_controller::for($user1_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user1_1)->can_loginas());
        $this->assertTrue(access_controller::for($user1_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user1_2)->can_loginas());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user1_3)->can_loginas());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user2_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user2_2)->can_loginas());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user2_3)->can_loginas());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_loginas());

        $this->setUser($user0_1);

        $this->assertFalse(access_controller::for($user0_0)->can_loginas());
        $this->assertFalse(access_controller::for($user0_0, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_0, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_0, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user0_2)->can_loginas());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user1_1)->can_loginas());
        $this->assertTrue(access_controller::for($user1_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user1_1)->can_loginas());
        $this->assertTrue(access_controller::for($user1_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user1_2)->can_loginas());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user1_3)->can_loginas());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user2_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user2_2)->can_loginas());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user2_3)->can_loginas());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_loginas());

        $this->setUser($user1_3);

        foreach ($allusers as $user) {
            $this->assertFalse(access_controller::for($user)->can_loginas());
            foreach ($allcourses as $course) {
                $this->assertFalse(access_controller::for($user, $course)->can_loginas());
            }
        }

        set_config('tenantsisolated', '1');

        $this->setUser($user0_0);

        $this->assertFalse(access_controller::for($user0_0)->can_loginas());
        $this->assertFalse(access_controller::for($user0_0, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_0, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_0, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user0_1)->can_loginas());
        $this->assertTrue(access_controller::for($user0_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user0_2)->can_loginas());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user1_2)->can_loginas());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user1_3)->can_loginas());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user2_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user2_2)->can_loginas());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_loginas());

        $this->assertTrue(access_controller::for($user2_3)->can_loginas());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_loginas());

        $this->setUser($user0_1);

        $this->assertFalse(access_controller::for($user0_0)->can_loginas());
        $this->assertFalse(access_controller::for($user0_0, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_0, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_0, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_1, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user0_2)->can_loginas());
        $this->assertFalse(access_controller::for($user0_2, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_2, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user0_2, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_1, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user1_2)->can_loginas());
        $this->assertFalse(access_controller::for($user1_2, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_2, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_2, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user1_3)->can_loginas());
        $this->assertFalse(access_controller::for($user1_3, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_3, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user1_3, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user2_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_1, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_1, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_1, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user2_2)->can_loginas());
        $this->assertFalse(access_controller::for($user2_2, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_2, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_2, $course2_1)->can_loginas());

        $this->assertFalse(access_controller::for($user2_3)->can_loginas());
        $this->assertFalse(access_controller::for($user2_3, $course0_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_3, $course1_1)->can_loginas());
        $this->assertFalse(access_controller::for($user2_3, $course2_1)->can_loginas());

        $this->setUser($user1_3);

        foreach ($allusers as $user) {
            $this->assertFalse(access_controller::for($user)->can_loginas());
            foreach ($allcourses as $course) {
                $this->assertFalse(access_controller::for($user, $course)->can_loginas());
            }
        }
    }
}
