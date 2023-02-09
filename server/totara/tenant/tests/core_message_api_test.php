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

use core_message\api;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests covering changes in messaging API.
 */
class totara_tenant_core_message_api_testcase extends advanced_testcase {
    public function test_search_users() {
        global $DB;
return;
        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $guest = guest_user();
        $admin = get_admin();

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null, 'firstname' => 'Uziv0_1', 'lastname' => 'Uziv0_1']);
        $user0_2 = $this->getDataGenerator()->create_user(['tenantid' => null, 'firstname' => 'Uziv0_2', 'lastname' => 'Uziv0_2']);

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id, 'firstname' => 'Uziv1_1', 'lastname' => 'Uziv1_1']);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id, 'firstname' => 'Uziv1_2', 'lastname' => 'Uziv1_2']);
        $user1_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id, 'firstname' => 'Uziv1_3', 'lastname' => 'Uziv1_3']);
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id, 'firstname' => 'Uziv2_1', 'lastname' => 'Uziv2_1']);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id, 'firstname' => 'Uziv2_2', 'lastname' => 'Uziv2_2']);
        $user2_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id, 'firstname' => 'Uziv2_3', 'lastname' => 'Uziv2_3']);
        $tenantcategory2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);

        $this->getDataGenerator()->enrol_user($user0_1->id, $course0_1->id, 'manager');
        $this->getDataGenerator()->enrol_user($user0_1->id, $course1_1->id, 'student');
        cohort_add_member($tenant1->cohortid, $user0_1->id);

        $this->getDataGenerator()->enrol_user($user1_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user1_1->id, $course1_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user1_2->id, $course1_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user2_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course2_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user2_2->id, $course2_1->id, 'student');

        $getuseridfunction = function($obj) {
            return $obj->userid;
        };

        set_config('tenantsisolated', '0');

        list($contacts, $courses, $noncontacts) = api::search_users($guest->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($admin->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user0_1->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user0_2->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user1_1->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id,$user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user1_2->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user1_3->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user2_1->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user2_2->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user2_3->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        set_config('tenantsisolated', '0');

        list($contacts, $courses, $noncontacts) = api::search_users($guest->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($admin->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user0_1->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user0_2->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user1_1->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id,$user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user1_2->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user1_3->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user2_1->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user2_2->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user2_3->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        set_config('tenantsisolated', '0');

        list($contacts, $courses, $noncontacts) = api::search_users($guest->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($admin->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user0_1->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user0_2->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user1_1->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id,$user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user1_2->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user1_3->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user2_1->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user2_1->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user2_2->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user2_3->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        set_config('tenantsisolated', '1');

        list($contacts, $courses, $noncontacts) = api::search_users($guest->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($admin->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user0_2->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user0_1->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_2->id, $user1_1->id, $user1_2->id, $user1_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user0_2->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user1_1->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user1_2->id, $user1_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user1_2->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user1_1->id, $user1_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user1_3->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user0_1->id, $user1_1->id, $user1_2->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user2_1->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user2_2->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user2_2->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user2_1->id, $user2_3->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));

        list($contacts, $courses, $noncontacts) = api::search_users($user2_3->id, 'Uziv');
        $this->assertSame([], array_map($getuseridfunction, $contacts));
        $expected = [$user2_1->id, $user2_2->id];
        $this->assertSame($expected, array_map($getuseridfunction, $noncontacts));
    }

    public function test_can_post_message() {
        global $DB;

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $guest = guest_user();
        $admin = get_admin();

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null, 'firstname' => 'Uziv0_1', 'lastname' => 'Uziv0_1']);
        $user0_2 = $this->getDataGenerator()->create_user(['tenantid' => null, 'firstname' => 'Uziv0_2', 'lastname' => 'Uziv0_2']);

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id, 'firstname' => 'Uziv1_1', 'lastname' => 'Uziv1_1']);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id, 'firstname' => 'Uziv1_2', 'lastname' => 'Uziv1_2']);
        $user1_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id, 'firstname' => 'Uziv1_3', 'lastname' => 'Uziv1_3']);
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id, 'firstname' => 'Uziv2_1', 'lastname' => 'Uziv2_1']);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id, 'firstname' => 'Uziv2_2', 'lastname' => 'Uziv2_2']);
        $user2_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id, 'firstname' => 'Uziv2_3', 'lastname' => 'Uziv2_3']);
        $tenantcategory2 = $DB->get_record('course_categories', ['id' => $tenant2->categoryid], '*', MUST_EXIST);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);

        $this->getDataGenerator()->enrol_user($user0_1->id, $course0_1->id, 'manager');
        $this->getDataGenerator()->enrol_user($user0_1->id, $course1_1->id, 'student');
        cohort_add_member($tenant1->cohortid, $user0_1->id);

        $this->getDataGenerator()->enrol_user($user1_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user1_1->id, $course1_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user1_2->id, $course1_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user2_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course2_1->id, 'student');

        $this->getDataGenerator()->enrol_user($user2_2->id, $course2_1->id, 'student');

        set_config('tenantsisolated', '0');

        $this->assertFalse(api::can_post_message($guest, $guest));
        $this->assertFalse(api::can_post_message($admin, $guest));
        $this->assertFalse(api::can_post_message($user0_1, $guest));
        $this->assertFalse(api::can_post_message($user0_2, $guest));
        $this->assertFalse(api::can_post_message($user1_1, $guest));
        $this->assertFalse(api::can_post_message($user1_2, $guest));
        $this->assertFalse(api::can_post_message($user1_3, $guest));
        $this->assertFalse(api::can_post_message($user2_1, $guest));
        $this->assertFalse(api::can_post_message($user2_2, $guest));
        $this->assertFalse(api::can_post_message($user2_3, $guest));

        $this->assertFalse(api::can_post_message($guest, $admin));
        $this->assertFalse(api::can_post_message($admin, $admin));
        $this->assertTrue(api::can_post_message($user0_1, $admin));
        $this->assertTrue(api::can_post_message($user0_2, $admin));
        $this->assertTrue(api::can_post_message($user1_1, $admin));
        $this->assertTrue(api::can_post_message($user1_2, $admin));
        $this->assertTrue(api::can_post_message($user1_3, $admin));
        $this->assertTrue(api::can_post_message($user2_1, $admin));
        $this->assertTrue(api::can_post_message($user2_2, $admin));
        $this->assertTrue(api::can_post_message($user2_3, $admin));

        $this->assertFalse(api::can_post_message($guest, $user0_1));
        $this->assertTrue(api::can_post_message($admin, $user0_1));
        $this->assertFalse(api::can_post_message($user0_1, $user0_1));
        $this->assertTrue(api::can_post_message($user0_2, $user0_1));
        $this->assertTrue(api::can_post_message($user1_1, $user0_1));
        $this->assertTrue(api::can_post_message($user1_2, $user0_1));
        $this->assertTrue(api::can_post_message($user1_3, $user0_1));
        $this->assertTrue(api::can_post_message($user2_1, $user0_1));
        $this->assertTrue(api::can_post_message($user2_2, $user0_1));
        $this->assertTrue(api::can_post_message($user2_3, $user0_1));

        $this->assertFalse(api::can_post_message($guest, $user0_2));
        $this->assertTrue(api::can_post_message($admin, $user0_2));
        $this->assertTrue(api::can_post_message($user0_1, $user0_2));
        $this->assertFalse(api::can_post_message($user0_2, $user0_2));
        $this->assertTrue(api::can_post_message($user1_1, $user0_2));
        $this->assertTrue(api::can_post_message($user1_2, $user0_2));
        $this->assertTrue(api::can_post_message($user1_3, $user0_2));
        $this->assertTrue(api::can_post_message($user2_1, $user0_2));
        $this->assertTrue(api::can_post_message($user2_2, $user0_2));
        $this->assertTrue(api::can_post_message($user2_3, $user0_2));

        $this->assertFalse(api::can_post_message($guest, $user1_1));
        $this->assertTrue(api::can_post_message($admin, $user1_1));
        $this->assertTrue(api::can_post_message($user0_1, $user1_1));
        $this->assertTrue(api::can_post_message($user0_2, $user1_1));
        $this->assertFalse(api::can_post_message($user1_1, $user1_1));
        $this->assertTrue(api::can_post_message($user1_2, $user1_1));
        $this->assertTrue(api::can_post_message($user1_3, $user1_1));
        $this->assertTrue(api::can_post_message($user2_1, $user1_1));
        $this->assertTrue(api::can_post_message($user2_2, $user1_1));
        $this->assertTrue(api::can_post_message($user2_3, $user1_1));

        $this->assertFalse(api::can_post_message($guest, $user1_2));
        $this->assertTrue(api::can_post_message($admin, $user1_2));
        $this->assertTrue(api::can_post_message($user0_1, $user1_2));
        $this->assertTrue(api::can_post_message($user0_2, $user1_2));
        $this->assertTrue(api::can_post_message($user1_1, $user1_2));
        $this->assertFalse(api::can_post_message($user1_2, $user1_2));
        $this->assertTrue(api::can_post_message($user1_3, $user1_2));
        $this->assertTrue(api::can_post_message($user2_1, $user1_2));
        $this->assertTrue(api::can_post_message($user2_2, $user1_2));
        $this->assertTrue(api::can_post_message($user2_3, $user1_2));

        $this->assertFalse(api::can_post_message($guest, $user1_3));
        $this->assertTrue(api::can_post_message($admin, $user1_3));
        $this->assertTrue(api::can_post_message($user0_1, $user1_3));
        $this->assertTrue(api::can_post_message($user0_2, $user1_3));
        $this->assertTrue(api::can_post_message($user1_1, $user1_3));
        $this->assertTrue(api::can_post_message($user1_2, $user1_3));
        $this->assertFalse(api::can_post_message($user1_3, $user1_3));
        $this->assertTrue(api::can_post_message($user2_1, $user1_3));
        $this->assertTrue(api::can_post_message($user2_2, $user1_3));
        $this->assertTrue(api::can_post_message($user2_3, $user1_3));

        $this->assertFalse(api::can_post_message($guest, $user2_1));
        $this->assertTrue(api::can_post_message($admin, $user2_1));
        $this->assertTrue(api::can_post_message($user0_1, $user2_1));
        $this->assertTrue(api::can_post_message($user0_2, $user2_1));
        $this->assertTrue(api::can_post_message($user1_1, $user2_1));
        $this->assertTrue(api::can_post_message($user1_2, $user2_1));
        $this->assertTrue(api::can_post_message($user1_3, $user2_1));
        $this->assertFalse(api::can_post_message($user2_1, $user2_1));
        $this->assertTrue(api::can_post_message($user2_2, $user2_1));
        $this->assertTrue(api::can_post_message($user2_3, $user2_1));

        $this->assertFalse(api::can_post_message($guest, $user2_2));
        $this->assertTrue(api::can_post_message($admin, $user2_2));
        $this->assertTrue(api::can_post_message($user0_1, $user2_2));
        $this->assertTrue(api::can_post_message($user0_2, $user2_2));
        $this->assertTrue(api::can_post_message($user1_1, $user2_2));
        $this->assertTrue(api::can_post_message($user1_2, $user2_2));
        $this->assertTrue(api::can_post_message($user1_3, $user2_2));
        $this->assertTrue(api::can_post_message($user2_1, $user2_2));
        $this->assertFalse(api::can_post_message($user2_2, $user2_2));
        $this->assertTrue(api::can_post_message($user2_3, $user2_2));

        $this->assertFalse(api::can_post_message($guest, $user2_3));
        $this->assertTrue(api::can_post_message($admin, $user2_3));
        $this->assertTrue(api::can_post_message($user0_1, $user2_3));
        $this->assertTrue(api::can_post_message($user0_2, $user2_3));
        $this->assertTrue(api::can_post_message($user1_1, $user2_3));
        $this->assertTrue(api::can_post_message($user1_2, $user2_3));
        $this->assertTrue(api::can_post_message($user1_3, $user2_3));
        $this->assertTrue(api::can_post_message($user2_1, $user2_3));
        $this->assertTrue(api::can_post_message($user2_2, $user2_3));
        $this->assertFalse(api::can_post_message($user2_3, $user2_3));

        set_config('tenantsisolated', '1');

        $this->assertFalse(api::can_post_message($guest, $guest));
        $this->assertFalse(api::can_post_message($admin, $guest));
        $this->assertFalse(api::can_post_message($user0_1, $guest));
        $this->assertFalse(api::can_post_message($user0_2, $guest));
        $this->assertFalse(api::can_post_message($user1_1, $guest));
        $this->assertFalse(api::can_post_message($user1_2, $guest));
        $this->assertFalse(api::can_post_message($user1_3, $guest));
        $this->assertFalse(api::can_post_message($user2_1, $guest));
        $this->assertFalse(api::can_post_message($user2_2, $guest));
        $this->assertFalse(api::can_post_message($user2_3, $guest));

        $this->assertFalse(api::can_post_message($guest, $admin));
        $this->assertFalse(api::can_post_message($admin, $admin));
        $this->assertTrue(api::can_post_message($user0_1, $admin));
        $this->assertTrue(api::can_post_message($user0_2, $admin));
        $this->assertTrue(api::can_post_message($user1_1, $admin));
        $this->assertTrue(api::can_post_message($user1_2, $admin));
        $this->assertTrue(api::can_post_message($user1_3, $admin));
        $this->assertTrue(api::can_post_message($user2_1, $admin));
        $this->assertTrue(api::can_post_message($user2_2, $admin));
        $this->assertTrue(api::can_post_message($user2_3, $admin));

        $this->assertFalse(api::can_post_message($guest, $user0_1));
        $this->assertTrue(api::can_post_message($admin, $user0_1));
        $this->assertFalse(api::can_post_message($user0_1, $user0_1));
        $this->assertTrue(api::can_post_message($user0_2, $user0_1));
        $this->assertTrue(api::can_post_message($user1_1, $user0_1));
        $this->assertTrue(api::can_post_message($user1_2, $user0_1));
        $this->assertTrue(api::can_post_message($user1_3, $user0_1));
        $this->assertFalse(api::can_post_message($user2_1, $user0_1));
        $this->assertFalse(api::can_post_message($user2_2, $user0_1));
        $this->assertFalse(api::can_post_message($user2_3, $user0_1));

        $this->assertFalse(api::can_post_message($guest, $user0_2));
        $this->assertTrue(api::can_post_message($admin, $user0_2));
        $this->assertTrue(api::can_post_message($user0_1, $user0_2));
        $this->assertFalse(api::can_post_message($user0_2, $user0_2));
        $this->assertFalse(api::can_post_message($user1_1, $user0_2));
        $this->assertFalse(api::can_post_message($user1_2, $user0_2));
        $this->assertFalse(api::can_post_message($user1_3, $user0_2));
        $this->assertFalse(api::can_post_message($user2_1, $user0_2));
        $this->assertFalse(api::can_post_message($user2_2, $user0_2));
        $this->assertFalse(api::can_post_message($user2_3, $user0_2));

        $this->assertFalse(api::can_post_message($guest, $user1_1));
        $this->assertTrue(api::can_post_message($admin, $user1_1));
        $this->assertTrue(api::can_post_message($user0_1, $user1_1));
        $this->assertFalse(api::can_post_message($user0_2, $user1_1));
        $this->assertFalse(api::can_post_message($user1_1, $user1_1));
        $this->assertTrue(api::can_post_message($user1_2, $user1_1));
        $this->assertTrue(api::can_post_message($user1_3, $user1_1));
        $this->assertFalse(api::can_post_message($user2_1, $user1_1));
        $this->assertFalse(api::can_post_message($user2_2, $user1_1));
        $this->assertFalse(api::can_post_message($user2_3, $user1_1));

        $this->assertFalse(api::can_post_message($guest, $user1_2));
        $this->assertTrue(api::can_post_message($admin, $user1_2));
        $this->assertTrue(api::can_post_message($user0_1, $user1_2));
        $this->assertFalse(api::can_post_message($user0_2, $user1_2));
        $this->assertTrue(api::can_post_message($user1_1, $user1_2));
        $this->assertFalse(api::can_post_message($user1_2, $user1_2));
        $this->assertTrue(api::can_post_message($user1_3, $user1_2));
        $this->assertFalse(api::can_post_message($user2_1, $user1_2));
        $this->assertFalse(api::can_post_message($user2_2, $user1_2));
        $this->assertFalse(api::can_post_message($user2_3, $user1_2));

        $this->assertFalse(api::can_post_message($guest, $user1_3));
        $this->assertTrue(api::can_post_message($admin, $user1_3));
        $this->assertTrue(api::can_post_message($user0_1, $user1_3));
        $this->assertFalse(api::can_post_message($user0_2, $user1_3));
        $this->assertTrue(api::can_post_message($user1_1, $user1_3));
        $this->assertTrue(api::can_post_message($user1_2, $user1_3));
        $this->assertFalse(api::can_post_message($user1_3, $user1_3));
        $this->assertFalse(api::can_post_message($user2_1, $user1_3));
        $this->assertFalse(api::can_post_message($user2_2, $user1_3));
        $this->assertFalse(api::can_post_message($user2_3, $user1_3));

        $this->assertFalse(api::can_post_message($guest, $user2_1));
        $this->assertTrue(api::can_post_message($admin, $user2_1));
        $this->assertFalse(api::can_post_message($user0_1, $user2_1));
        $this->assertFalse(api::can_post_message($user0_2, $user2_1));
        $this->assertFalse(api::can_post_message($user1_1, $user2_1));
        $this->assertFalse(api::can_post_message($user1_2, $user2_1));
        $this->assertFalse(api::can_post_message($user1_3, $user2_1));
        $this->assertFalse(api::can_post_message($user2_1, $user2_1));
        $this->assertTrue(api::can_post_message($user2_2, $user2_1));
        $this->assertTrue(api::can_post_message($user2_3, $user2_1));

        $this->assertFalse(api::can_post_message($guest, $user2_2));
        $this->assertTrue(api::can_post_message($admin, $user2_2));
        $this->assertFalse(api::can_post_message($user0_1, $user2_2));
        $this->assertFalse(api::can_post_message($user0_2, $user2_2));
        $this->assertFalse(api::can_post_message($user1_1, $user2_2));
        $this->assertFalse(api::can_post_message($user1_2, $user2_2));
        $this->assertFalse(api::can_post_message($user1_3, $user2_2));
        $this->assertTrue(api::can_post_message($user2_1, $user2_2));
        $this->assertFalse(api::can_post_message($user2_2, $user2_2));
        $this->assertTrue(api::can_post_message($user2_3, $user2_2));

        $this->assertFalse(api::can_post_message($guest, $user2_3));
        $this->assertTrue(api::can_post_message($admin, $user2_3));
        $this->assertFalse(api::can_post_message($user0_1, $user2_3));
        $this->assertFalse(api::can_post_message($user0_2, $user2_3));
        $this->assertFalse(api::can_post_message($user1_1, $user2_3));
        $this->assertFalse(api::can_post_message($user1_2, $user2_3));
        $this->assertFalse(api::can_post_message($user1_3, $user2_3));
        $this->assertTrue(api::can_post_message($user2_1, $user2_3));
        $this->assertTrue(api::can_post_message($user2_2, $user2_3));
        $this->assertFalse(api::can_post_message($user2_3, $user2_3));
    }
}
