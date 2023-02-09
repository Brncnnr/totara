<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package totara_tenant
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests covering multitenancy changes in totara_core\local\visibility class.
 */
class totara_tenant_totara_visibility_testcase extends advanced_testcase {

    public function test_tenant_member_without_isolation() {
        global $CFG;
        require_once($CFG->dirroot . '/course/renderer.php');

        $gen = self::getDataGenerator();
        /** @var \totara_tenant\testing\generator $multitenancy */
        $multitenancy = $gen->get_plugin_generator('totara_tenant');

        $chelper = new coursecat_helper();
        $chelper->set_show_courses(core_course_renderer::COURSECAT_SHOW_COURSES_EXPANDED)
            ->set_courses_display_options(['recursive' => true, 'limit' => 200]);

        $multitenancy->enable_tenants();
        set_config('tenantsisolated', 0);

        // Create tenants.
        $tenant1 = $multitenancy->create_tenant();
        $tenant2 = $multitenancy->create_tenant();
        $tenant3 = $multitenancy->create_tenant(['suspended' => 1]);

        // Create users.
        $user1 = $gen->create_user(['tenantmember' => $tenant1->idnumber]);
        $user2 = $gen->create_user(['tenantmember' => $tenant2->idnumber]);
        $user3 = $gen->create_user(['tenantmember' => $tenant3->idnumber]);
        $participant = $gen->create_user(['tenantparticipant' => "{$tenant1->idnumber}, {$tenant3->idnumber}"]);

        // Create courses.
        $c0A = $gen->create_course(['fullname' => 'Course 0A', 'shortname' => 'COURSE0A']);
        $c0B = $gen->create_course(['fullname' => 'Course 0B', 'shortname' => 'COURSE0B']);
        $c1A = $gen->create_course(['fullname' => 'Course 1A', 'shortname' => 'COURSE1A', 'category' => $tenant1->categoryid]);
        $c1B = $gen->create_course(['fullname' => 'Course 1B', 'shortname' => 'COURSE1B', 'category' => $tenant1->categoryid]);
        $c2A = $gen->create_course(['fullname' => 'Course 2A', 'shortname' => 'COURSE2A', 'category' => $tenant2->categoryid]);
        $c2B = $gen->create_course(['fullname' => 'Course 2B', 'shortname' => 'COURSE2B', 'category' => $tenant2->categoryid]);
        $c3A = $gen->create_course(['fullname' => 'Course 3A', 'shortname' => 'COURSE3A', 'category' => $tenant3->categoryid]);
        $c3B = $gen->create_course(['fullname' => 'Course 3B', 'shortname' => 'COURSE3B', 'category' => $tenant3->categoryid]);

        // Enrol users into courses.
        $gen->enrol_user($user1->id, $c0A->id, 'student');
        $gen->enrol_user($user1->id, $c1A->id, 'student');
        $gen->enrol_user($user1->id, $c3A->id, 'student');
        $gen->enrol_user($user2->id, $c2A->id, 'student');
        $gen->enrol_user($participant->id, $c0B->id, 'student');
        $gen->enrol_user($participant->id, $c1B->id, 'student');
        $gen->enrol_user($participant->id, $c2B->id, 'student');
        $gen->enrol_user($participant->id, $c3B->id, 'student');

        self::setUser($user1);

        $courses = coursecat::get(0)->get_courses($chelper->get_courses_display_options());
        $totalcount = coursecat::get(0)->get_courses_count($chelper->get_courses_display_options());

        self::assertEquals(4, $totalcount);

        self::assertEqualsCanonicalizing([$c0A->id, $c0B->id, $c1A->id, $c1B->id], array_keys($courses));
        self::assertArrayNotHasKey($c2A->id, $courses);
        self::assertArrayNotHasKey($c2B->id, $courses);
        self::assertArrayNotHasKey($c3A->id, $courses);
        self::assertArrayNotHasKey($c3B->id, $courses);

        $cache = cache::make('core', 'coursecat');
        $cache->purge();

        self::setUser($participant);

        $courses = coursecat::get(0)->get_courses($chelper->get_courses_display_options());
        $totalcount = coursecat::get(0)->get_courses_count($chelper->get_courses_display_options());

        self::assertEquals(6, $totalcount);

        self::assertEqualsCanonicalizing([$c0A->id, $c0B->id, $c1A->id, $c1B->id, $c2A->id, $c2B->id], array_keys($courses));
        self::assertArrayNotHasKey($c3A->id, $courses);
        self::assertArrayNotHasKey($c3B->id, $courses);
    }

    public function test_tenant_member_with_isolation() {
        global $CFG;
        require_once($CFG->dirroot . '/course/renderer.php');

        $gen = self::getDataGenerator();
        /** @var \totara_tenant\testing\generator $multitenancy */
        $multitenancy = $gen->get_plugin_generator('totara_tenant');

        $chelper = new coursecat_helper();
        $chelper->set_show_courses(core_course_renderer::COURSECAT_SHOW_COURSES_EXPANDED)
            ->set_courses_display_options(['recursive' => true, 'limit' => 200]);

        $multitenancy->enable_tenants();
        set_config('tenantsisolated', 1);

        // Create tenants.
        $tenant1 = $multitenancy->create_tenant();
        $tenant2 = $multitenancy->create_tenant();
        $tenant3 = $multitenancy->create_tenant(['suspended' => 1]);

        // Create users.
        $user1 = $gen->create_user(['tenantmember' => $tenant1->idnumber]);
        $user2 = $gen->create_user(['tenantmember' => $tenant2->idnumber]);
        $user3 = $gen->create_user(['tenantmember' => $tenant3->idnumber]);
        $participant = $gen->create_user(['tenantparticipant' => "{$tenant1->idnumber}, {$tenant3->idnumber}"]);

        // Create courses.
        $c0A = $gen->create_course(['fullname' => 'Course 0A', 'shortname' => 'COURSE0A']);
        $c0B = $gen->create_course(['fullname' => 'Course 0B', 'shortname' => 'COURSE0B']);
        $c1A = $gen->create_course(['fullname' => 'Course 1A', 'shortname' => 'COURSE1A', 'category' => $tenant1->categoryid]);
        $c1B = $gen->create_course(['fullname' => 'Course 1B', 'shortname' => 'COURSE1B', 'category' => $tenant1->categoryid]);
        $c2A = $gen->create_course(['fullname' => 'Course 2A', 'shortname' => 'COURSE2A', 'category' => $tenant2->categoryid]);
        $c2B = $gen->create_course(['fullname' => 'Course 2B', 'shortname' => 'COURSE2B', 'category' => $tenant2->categoryid]);
        $c3A = $gen->create_course(['fullname' => 'Course 3A', 'shortname' => 'COURSE3A', 'category' => $tenant3->categoryid]);
        $c3B = $gen->create_course(['fullname' => 'Course 3B', 'shortname' => 'COURSE3B', 'category' => $tenant3->categoryid]);

        // Enrol users into courses.
        $gen->enrol_user($user1->id, $c0A->id, 'student');
        $gen->enrol_user($user1->id, $c1A->id, 'student');
        $gen->enrol_user($user1->id, $c3A->id, 'student');
        $gen->enrol_user($user2->id, $c2A->id, 'student');
        $gen->enrol_user($participant->id, $c0B->id, 'student');
        $gen->enrol_user($participant->id, $c1B->id, 'student');
        $gen->enrol_user($participant->id, $c2B->id, 'student');
        $gen->enrol_user($participant->id, $c3B->id, 'student');

        self::setUser($user1);

        // User 1 is restricted to their tenant category only
        self::assertEquals(2, coursecat::get(0)->get_courses_count($chelper->get_courses_display_options()));
        self::assertEquals(2, coursecat::get($tenant1->categoryid)->get_courses_count($chelper->get_courses_display_options()));

        $courses = coursecat::get(0)->get_courses($chelper->get_courses_display_options());

        self::assertEqualsCanonicalizing([$c1A->id, $c1B->id], array_keys($courses));
        self::assertArrayNotHasKey($c0A->id, $courses);
        self::assertArrayNotHasKey($c0B->id, $courses);
        self::assertArrayNotHasKey($c2A->id, $courses);
        self::assertArrayNotHasKey($c2B->id, $courses);
        self::assertArrayNotHasKey($c3A->id, $courses);
        self::assertArrayNotHasKey($c3B->id, $courses);

        $cache = cache::make('core', 'coursecat');
        $cache->purge();

        // Isolation mode or not, nothing should change for participant.
        self::setUser($participant);

        $courses = coursecat::get(0)->get_courses($chelper->get_courses_display_options());
        $totalcount = coursecat::get(0)->get_courses_count($chelper->get_courses_display_options());

        self::assertEquals(6, $totalcount);

        self::assertEqualsCanonicalizing([$c0A->id, $c0B->id, $c1A->id, $c1B->id, $c2A->id, $c2B->id], array_keys($courses));
        self::assertArrayNotHasKey($c3A->id, $courses);
        self::assertArrayNotHasKey($c3B->id, $courses);
    }
}
