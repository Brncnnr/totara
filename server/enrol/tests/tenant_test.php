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
 * @package core_enrol
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Test tenant support for enrolment subsystem.
 */
class core_enrol_tenant_testcase extends advanced_testcase {
    public function test_course_enrolment_manager() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/enrol/locallib.php');
        require_once($CFG->dirroot . '/cohort/lib.php');

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $admin = get_admin();
        $guest = guest_user();

        $category0 = $this->getDataGenerator()->create_category();
        $course0_1 = $this->getDataGenerator()->create_course(['category' => $category0->id]);
        $enrol0_1 = $DB->get_record('enrol', array('enrol'=>'manual', 'courseid' => $course0_1->id));
        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user0_2 = $this->getDataGenerator()->create_user(['tenantid' => null]);
        $user0_3 = $this->getDataGenerator()->create_user(['tenantid' => null]);

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $tenantcategory1 = coursecat::get($tenant1->categoryid);
        $course1_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory1->id]);
        $enrol1_1 = $DB->get_record('enrol', array('enrol'=>'manual', 'courseid' => $course1_1->id));

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_3 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $tenantcategory2 = coursecat::get($tenant2->categoryid);
        $course2_1 = $this->getDataGenerator()->create_course(['category' => $tenantcategory2->id]);
        $enrol2_1 = $DB->get_record('enrol', array('enrol'=>'manual', 'courseid' => $course2_1->id));

        $this->getDataGenerator()->enrol_user($user0_1->id, $course0_1->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($user1_1->id, $course1_1->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($user1_1->id, $course0_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user1_2->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course2_1->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($user2_1->id, $course1_1->id, 'student');
        $this->getDataGenerator()->enrol_user($user2_2->id, $course2_1->id, 'student');

        cohort_add_member($tenant2->cohortid, $user0_3->id);

        $page = new moodle_page();

        set_config('tenantsisolated', '0');

        $this->setUser($admin);

        $manager = new course_enrolment_manager($page, $course0_1);
        list('users' => $users) = $manager->get_potential_users($enrol0_1->id);
        ksort($users);
        $this->assertEquals([$admin->id, $user0_2->id, $user0_3->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$admin->id, $user0_2->id, $user0_3->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id], array_keys($users));

        $manager = new course_enrolment_manager($page, $course1_1);
        list('users' => $users) = $manager->get_potential_users($enrol1_1->id);
        ksort($users);
        $this->assertEquals([$user1_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$user1_3->id], array_keys($users));

        $manager = new course_enrolment_manager($page, $course2_1);
        list('users' => $users) = $manager->get_potential_users($enrol2_1->id);
        ksort($users);
        $this->assertEquals([$user0_3->id, $user2_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$user0_3->id, $user2_3->id], array_keys($users));

        $this->setUser($user0_1);

        $manager = new course_enrolment_manager($page, $course0_1);
        list('users' => $users) = $manager->get_potential_users($enrol0_1->id);
        ksort($users);
        $this->assertEquals([$admin->id, $user0_2->id, $user0_3->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$admin->id, $user0_2->id, $user0_3->id, $user1_2->id, $user1_3->id, $user2_1->id, $user2_2->id, $user2_3->id], array_keys($users));

        $manager = new course_enrolment_manager($page, $course1_1);
        list('users' => $users) = $manager->get_potential_users($enrol1_1->id);
        ksort($users);
        $this->assertEquals([$user1_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$user1_3->id], array_keys($users));

        $this->setUser($user1_1);

        $manager = new course_enrolment_manager($page, $course1_1);
        list('users' => $users) = $manager->get_potential_users($enrol1_1->id);
        ksort($users);
        $this->assertEquals([$user1_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$user1_3->id], array_keys($users));

        $this->setUser($user2_1);

        $manager = new course_enrolment_manager($page, $course2_1);
        list('users' => $users) = $manager->get_potential_users($enrol2_1->id);
        ksort($users);
        $this->assertEquals([$user0_3->id, $user2_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$user0_3->id, $user2_3->id], array_keys($users));

        set_config('tenantsisolated', '1');

        $this->setUser($admin);

        $manager = new course_enrolment_manager($page, $course0_1);
        list('users' => $users) = $manager->get_potential_users($enrol0_1->id);
        ksort($users);
        $this->assertEquals([$admin->id, $user0_2->id, $user0_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$admin->id, $user0_2->id, $user0_3->id], array_keys($users));

        $manager = new course_enrolment_manager($page, $course1_1);
        list('users' => $users) = $manager->get_potential_users($enrol1_1->id);
        ksort($users);
        $this->assertEquals([$user1_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$user1_3->id], array_keys($users));

        $manager = new course_enrolment_manager($page, $course2_1);
        list('users' => $users) = $manager->get_potential_users($enrol2_1->id);
        ksort($users);
        $this->assertEquals([$user0_3->id, $user2_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$user0_3->id, $user2_3->id], array_keys($users));

        $this->setUser($user0_1);

        $manager = new course_enrolment_manager($page, $course0_1);
        list('users' => $users) = $manager->get_potential_users($enrol0_1->id);
        ksort($users);
        $this->assertEquals([$admin->id, $user0_2->id, $user0_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$admin->id, $user0_2->id, $user0_3->id], array_keys($users));

        $manager = new course_enrolment_manager($page, $course1_1);
        list('users' => $users) = $manager->get_potential_users($enrol1_1->id);
        ksort($users);
        $this->assertEquals([$user1_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$user1_3->id], array_keys($users));

        $this->setUser($user1_1);

        $manager = new course_enrolment_manager($page, $course1_1);
        list('users' => $users) = $manager->get_potential_users($enrol1_1->id);
        ksort($users);
        $this->assertEquals([$user1_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$user1_3->id], array_keys($users));

        $this->setUser($user2_1);

        $manager = new course_enrolment_manager($page, $course2_1);
        list('users' => $users) = $manager->get_potential_users($enrol2_1->id);
        ksort($users);
        $this->assertEquals([$user0_3->id, $user2_3->id], array_keys($users));
        list('users' => $users) = $manager->search_other_users();
        ksort($users);
        $this->assertEquals([$user0_3->id, $user2_3->id], array_keys($users));

    }
}
