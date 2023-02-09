<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core_course
 */

use core\orm\entity\filter\in;
use core\orm\entity\filter\like;
use core_course\data_provider\course as course_provider;
use core_course\entity\filter\course_filter_factory;
use core_course\entity\filter\course_progress;
use core_course\entity\filter\user_courses;
use core_phpunit\testcase;

class core_course_data_provider_testcase extends testcase {

    /**
     * @return void
     */
    public function test_filter_factory(): void {
        $filter_factory = new course_filter_factory();

        // Confirm user filter is correct.
        $filter = $filter_factory->create('user_id', 2);
        $this->assertInstanceOf(user_courses::class, $filter);

        // Confirm ids filter is correct.
        $filter = $filter_factory->create('ids', [1]);
        $this->assertInstanceOf(in::class, $filter);

        // Confirm search filter is correct.
        $filter = $filter_factory->create('search', 'blah');
        $this->assertInstanceOf(like::class, $filter);

        // Confirm progress filter is correct.
        $filter = $filter_factory->create('progress', 'Completed');
        $this->assertInstanceOf(course_progress::class, $filter);

        // Confirm invalid filter gets expected result.
        $filter = $filter_factory->create('unknown', '');
        $this->assertNull($filter);
    }

    /**
     * @return void
     */
    public function test_provider(): void {
        $this->setAdminUser();

        // Create courses.
        $courses = $this->create_courses(3);

        // Create an instance of the data provider.
        $data_provider = course_provider::create();

        // Confirm that we get any data back (without any filters applied).
        $result = $data_provider->fetch();
        $this->assertNotNull($courses);

        // The courses will contain even the site course.
        $this->assertEquals(4, $result->count());
        foreach (['1', $courses[0]->id, $courses[1]->id, $courses[2]->id] as $course_id) {
            $this->assertTrue($result->has('id', $course_id));
        }

        // Confirm pagination works.
        $result = $data_provider->set_page_size(2)->fetch_paginated();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('next_cursor', $result);

        /** @var array $items **/
        $items = $result['items'];
        $this->assertCount(2, $items);

        $total = $result['total'];
        $this->assertEquals(4, $total);

        $next_cursor = $result['next_cursor'];
        $this->assertNotEmpty($next_cursor);

        // Provider should throw exception if filter factory not set.
        try {
            $data_provider->set_filters(['foo' => 'bar'])->fetch();
            $this->fail('Exception expected');
        } catch (Exception $e) {
            $this->assertEquals(
                'Coding error detected, it must be fixed by a programmer: No filter factory registered',
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_user_filter(): void {
        $gen = $this->getDataGenerator();

        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $user3 = $gen->create_user();
        $user4 = $gen->create_user();

        $this->setUser($user1);

        // Create courses.
        $courses = $this->create_courses(5);

        // Enroll user into some courses.
        $gen->enrol_user($user1->id, $courses[0]->id);
        $gen->enrol_user($user1->id, $courses[2]->id);
        $gen->enrol_user($user1->id, $courses[4]->id);

        // Enroll other users.
        $gen->enrol_user($user2->id, $courses[0]->id);
        $gen->enrol_user($user2->id, $courses[1]->id);
        $gen->enrol_user($user3->id, $courses[2]->id);
        $gen->enrol_user($user3->id, $courses[3]->id);
        $gen->enrol_user($user3->id, $courses[4]->id);

        // Confirm that we don't find any results.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider->set_filters(['user_id' => $user4->id])->fetch();
        $this->assertEquals(0, $result->count());

        // Confirm that we get the correct courses back for a specific user.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider->set_filters(['user_id' => $user1->id])->fetch();
        foreach ([$courses[0]->id, $courses[2]->id, $courses[4]->id] as $course_id) {
            $this->assertTrue($result->has('id', $course_id));
        }

        // Confirm that we got the same result back as user courses.
        $courses = enrol_get_all_users_courses($user1->id, true);
        $this->assertIsArray($courses);
        $this->assertCount(3, $courses);
        foreach ($courses as $course) {
            $this->assertTrue($result->has('id', $course->id));
        }
    }

    /**
     * @return void
     */
    public function test_user_filter_for_tenant(): void {
        $gen = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $gen->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $tenant1_user = $gen->create_user([
            'firstname' => 'tenant_user',
            'lastname' => 'tenant_user',
            'tenantid' => $tenant1->id
        ]);

        $tenant2_user = $gen->create_user([
            'firstname' => 'tenant_user',
            'lastname' => 'tenant_user',
            'tenantid' => $tenant2->id
        ]);

        $tenant3_user = $gen->create_user([
            'firstname' => 'tenant_user',
            'lastname' => 'tenant_user',
            'tenantid' => $tenant1->id
        ]);

        $courses = $this->create_courses(4);

        // Enroll tenant1 user into some courses.
        $gen->enrol_user($tenant1_user->id, $courses[0]->id);
        $gen->enrol_user($tenant1_user->id, $courses[3]->id);

        // Enroll tenant2 user into some courses.
        $gen->enrol_user($tenant2_user->id, $courses[1]->id);
        $gen->enrol_user($tenant2_user->id, $courses[2]->id);

        // Confirm that we don't find any results.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider->set_filters(['user_id' => $tenant3_user->id])->fetch();
        $this->assertEquals(0, $result->count());

        // Confirm that we get the correct courses back for a specific user.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider->set_filters(['user_id' => $tenant1_user->id])->fetch();
        foreach ([$courses[0]->id, $courses[3]->id] as $course_id) {
            $this->assertTrue($result->has('id', $course_id));
        }

        // Confirm that we got the same result back as user courses.
        $courses = enrol_get_all_users_courses($tenant1_user->id, true);
        $this->assertIsArray($courses);
        $this->assertCount(2, $courses);
        foreach ($courses as $course) {
            $this->assertTrue($result->has('id', $course->id));
        }
    }

    /**
     * @return void
     */
    public function test_ids_filter(): void {
        // Create courses.
        $courses = $this->create_courses(10);

        // Confirm that we don't find any results.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider->set_filters(['ids' => [$courses[0]->id + 50, $courses[1]->id + 50]])->fetch();
        $this->assertEquals(0, $result->count());

        // Confirm that we get the correct courses back based on IDs.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider->set_filters(['ids' => [$courses[3]->id, $courses[7]->id]])->fetch();
        $this->assertEquals(2, $result->count());
        foreach ([$courses[3]->id, $courses[7]->id] as $course_id) {
            $this->assertTrue($result->has('id', $course_id));
        }
    }

    /**
     * @return void
     */
    public function test_search_filter(): void {
        $gen = $this->getDataGenerator();

        // Create courses.
        $this->create_courses(5);
        $gen->create_course([
            'fullname' => 'Vanilla Pepsi',
        ]);

        // Confirm that we don't find any results.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider->set_filters(['search' => 'this course does not exist'])->fetch();
        $this->assertEquals(0, $result->count());

        // Confirm that we get the correct courses back based on name match.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider->set_filters(['search' => 'Vanilla Pepsi'])->fetch();
        $this->assertEquals(1, $result->count());
        $this->assertTrue($result->has('fullname', 'Vanilla Pepsi'));
    }

    /**
     * @return void
     */
    public function test_progress_filter(): void {
        $gen = $this->getDataGenerator();
        $completion_gen = $gen->get_plugin_generator('core_completion');

        // Create user.
        $user1 = $gen->create_user();

        // Create courses.
        $courses = $this->create_courses(5);

        // Start tracking course.
        $completion_gen->enable_completion_tracking($courses[3]);

        // Get not-tracked course.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider->set_filters(['progress' => 'NOT_TRACKED'])->fetch();
        $this->assertTrue($result->has('id', $courses[3]->id));

        // Enrol user and track completion.
        $gen->enrol_user($user1->id, $courses[3]->id);

        // Course should not be 'not tracked' anymore.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider->set_filters(['progress' => 'NOT_TRACKED'])->fetch();
        $this->assertFalse($result->has('id', $courses[3]->id));

        // Get not started course.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider->set_filters(['progress' => 'NOT_STARTED'])->fetch();
        $this->assertTrue($result->has('id', $courses[3]->id));

        $completion = new completion_completion(array('course' => $courses[3]->id));
        $completion->mark_inprogress(time());

        // Get in progress course.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider->set_filters(['progress' => 'IN_PROGRESS'])->fetch();
        $this->assertTrue($result->has('id', $courses[3]->id));

        // Complete course.
        $completion_gen->complete_course($courses[3], $user1);

        // Get completed course.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider->set_filters(['progress' => 'COMPLETED'])->fetch();
        $this->assertTrue($result->has('id', $courses[3]->id));
    }

    /**
     * @return void
     */
    public function test_multi_filter(): void {
        $gen = $this->getDataGenerator();

        // Create users.
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();

        // Create courses.
        $courses = $this->create_courses(10);

        // Enroll users into some courses.
        $gen->enrol_user($user1->id, $courses[1]->id);
        $gen->enrol_user($user1->id, $courses[2]->id);
        $gen->enrol_user($user1->id, $courses[3]->id);
        $gen->enrol_user($user2->id, $courses[0]->id);
        $gen->enrol_user($user2->id, $courses[1]->id);
        $gen->enrol_user($user2->id, $courses[6]->id);

        // Confirm that we get the correct courses back for a specific user.
        $data_provider = course_provider::create(new course_filter_factory());
        $result = $data_provider
            ->set_filters([
                'user_id' => $user1->id,
                'ids' => [$courses[2]->id, $courses[1]->id, $courses[6]->id]
            ])
            ->fetch();

        // User 1 is not enrolled in course6 so that should not be part of result.
        $this->assertEquals(2, $result->count());

        foreach ([$courses[1]->id, $courses[2]->id] as $course_id) {
            $this->assertTrue($result->has('id', $course_id));
        }
    }

    /**
     * @param int $total
     * @return array
     */
    private function create_courses(int $total): array {
        $gen = $this->getDataGenerator();

        $courses = [];
        for ($x = 1; $x <= $total; ++$x) {
            $courses[] = $gen->create_course();
        }
        return $courses;
    }

}