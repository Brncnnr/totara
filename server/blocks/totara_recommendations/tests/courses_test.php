<?php
/**
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package block_totara_recommendations
 */

use block_totara_recommendations\repository\recommendations_repository;
use block_totara_recommendations\testing\recommendations_service_mock_trait;
use core_phpunit\testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * Testing the behaviour of the courses block with the built in recommenders service.
 *
 * @group block_totara_recommendations
 */
class block_totara_recommendations_courses_testcase extends testcase {
    use recommendations_service_mock_trait;

    /**
     * Simple data helper to run the sets of tests with & without legacy recommendations.
     *
     * @return array
     */
    public function simple_toggle_data_provider(): array {
        return [
            [false],
            [true],
        ];
    }

    /**
     * Assert that only visible, self-enrollment enabled & not-enrolled courses are seen through courses
     * recommendations.
     *
     * @param bool $legacy
     * @return void
     * @dataProvider simple_toggle_data_provider
     */
    public function test_courses_block(bool $legacy): void {
        global $DB;
        list($courses, $users) = $this->generate_data();

        $this->toggle_legacy_service($legacy);

        // User 1 should not see any recommendations
        $this->commit_recommendations($users[1]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[1]->id);
        self::assertEmpty($records);

        // User 2 should see Course 1 recommended
        $this->commit_recommendations($users[2]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[2]->id);
        self::assertNotEmpty($records);
        self::assertCount(1, $records);

        $record = current($records);
        self::assertEquals($courses[1]->id, $record->item_id);

        // Now unenrol user 1 from course 1, then see if it's recommended
        $plugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $courses[1]->id, 'enrol' => 'manual']);
        $plugin->unenrol_user($instance, $users[1]->id);

        // User 1 should see Course 1 recommended
        $this->commit_recommendations($users[1]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[1]->id);
        self::assertNotEmpty($records);
        self::assertCount(1, $records);

        $record = current($records);
        self::assertEquals($courses[1]->id, $record->item_id);

        // Now make visible course 2 and see if it's recommended to user 2
        $courses[2]->visible = 1;
        $courses[2]->visibleold = 1;
        $DB->update_record('course', $courses[2]);

        $this->commit_recommendations($users[2]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[2]->id);
        self::assertNotEmpty($records);
        self::assertCount(2, $records);
    }

    /**
     * Assert that courses are filtered based on audience visibility rules
     * @dataProvider simple_toggle_data_provider
     */
    public function test_courses_with_audience_visibility(bool $legacy): void {
        global $CFG, $DB;

        $this->toggle_legacy_service($legacy);

        // Enable audience visibility rules
        $CFG->audiencevisibility = 1;

        // Courses will default to audience visibility of COHORT_VISIBLE_ALL
        list($courses, $users) = $this->generate_data();

        // User 1 should not see any recommendations
        $this->commit_recommendations($users[1]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[1]->id);
        self::assertEmpty($records);

        // User 2 should see course 1 & course 2 recommended (audience visibility took over)
        $this->commit_recommendations($users[2]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[2]->id);
        self::assertNotEmpty($records);
        self::assertCount(2, $records);

        self::assertEqualsCanonicalizing([$courses[1]->id, $courses[2]->id], array_column($records, 'item_id'));

        // Set courses to be audience visibility = Nobody
        $DB->execute("UPDATE {course} SET audiencevisible = ?", [COHORT_VISIBLE_NOUSERS]);

        // User 1 should not see any recommendations
        $this->commit_recommendations($users[1]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[1]->id);
        self::assertEmpty($records);

        // User 2 should not see any recommendations
        $this->commit_recommendations($users[2]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[2]->id);
        self::assertEmpty($records);

        // Set courses to be audience visibility = Enrolled
        $DB->execute("UPDATE {course} SET audiencevisible = ?", [COHORT_VISIBLE_ENROLLED]);

        // This is a trick - only enrolled users should see the course, but the act of enrolling should hide it
        // therefore we should not see anything for either user
        $this->commit_recommendations($users[1]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[1]->id);
        self::assertEmpty($records);

        $this->commit_recommendations($users[2]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[2]->id);
        self::assertEmpty($records);

        // Set courses to be audience visibility = Audience
        $DB->execute("UPDATE {course} SET audiencevisible = ?", [COHORT_VISIBLE_AUDIENCE]);

        // Create a new audience
        $audience = $this->getDataGenerator()->create_cohort();

        // Attach the audience to each course
        foreach ($courses as $course) {
            totara_cohort_add_association(
                $audience->id,
                $course->id,
                COHORT_ASSN_ITEMTYPE_COURSE,
                COHORT_ASSN_VALUE_PERMITTED
            );
        }

        // Confirm the courses are not visible
        $this->commit_recommendations($users[1]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[1]->id);
        self::assertEmpty($records);

        $this->commit_recommendations($users[2]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[2]->id);
        self::assertEmpty($records);

        // Enrol user 2 in the audience
        cohort_add_member($audience->id, $users[2]->id);

        // User 1 should not see any recommendations
        $this->commit_recommendations($users[1]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[1]->id);
        self::assertEmpty($records);

        // User 2 should see course 1 & course 2 recommended (audience visibility took over)
        $this->commit_recommendations($users[2]->id);
        $records = recommendations_repository::get_recommended_courses(8, $users[2]->id);
        self::assertNotEmpty($records);
        self::assertCount(2, $records);

        self::assertEqualsCanonicalizing([$courses[1]->id, $courses[2]->id], array_column($records, 'item_id'));
    }

    /**
     * Assert that we do not recommend courses across tenant boundaries
     *
     * @return void
     * @dataProvider simple_toggle_data_provider
     */
    public function test_course_multitenancy(bool $legacy): void {
        $generator = $this->getDataGenerator();
        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');

        $tenant_generator->enable_tenants();
        set_config('tenantsisolated', 0);

        $this->toggle_legacy_service($legacy);

        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();
        $course_t1 = $generator->create_course(['category' => $tenant1->categoryid]);
        $course_t2 = $generator->create_course(['category' => $tenant2->categoryid]);
        $course_sys = $generator->create_course();
        $courses = [$course_t1, $course_t2, $course_sys];

        // Enable self-enrollment on all the course
        foreach ($courses as $course) {
            $this->enable_self_enrollment($course->id);
        }

        $user_t1 = $generator->create_user();
        $user_t2 = $generator->create_user();
        $user_p1 = $generator->create_user();
        $user_sys = $generator->create_user();
        $tenant_generator->migrate_user_to_tenant($user_t1->id, $tenant1->id);
        $tenant_generator->migrate_user_to_tenant($user_t2->id, $tenant2->id);
        $tenant_generator->set_user_participation($user_p1->id, [$tenant1->id]);

        // Recommend to all the users
        $user_ids = $this->pluck([$user_t1, $user_t2, $user_p1, $user_sys], 'id');
        $course_ids = $this->pluck($courses, 'id');
        foreach ($user_ids as $user_id) {
            $this->recommend($course_ids, $user_id);
        }

        // Confirm that tenant members only see their tenant courses & system courses (isolation disabled)
        $this->commit_recommendations($user_t1->id);
        $recommended = recommendations_repository::get_recommended_courses(10, $user_t1->id);
        self::assertCount(2, $recommended);
        $expected_ids = $this->pluck([$course_t1, $course_sys], 'id');
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        $this->commit_recommendations($user_t2->id);
        $recommended = recommendations_repository::get_recommended_courses(10, $user_t2->id);
        self::assertCount(2, $recommended);
        $expected_ids = $this->pluck([$course_t2, $course_sys], 'id');
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        // System User can be recommended any
        $this->commit_recommendations($user_sys->id);
        $recommended = recommendations_repository::get_recommended_courses(10, $user_sys->id);
        self::assertCount(3, $recommended);
        $actual_ids = $this->pluck($recommended, 'item_id');
        $expected_ids = $this->pluck([$course_t1, $course_t2, $course_sys], 'id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        $this->commit_recommendations($user_p1->id);
        $recommended = recommendations_repository::get_recommended_courses(10, $user_p1->id);
        self::assertCount(3, $recommended);
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        // Isolation mode enabled, only see the tenant
        set_config('tenantsisolated', 1);

        $this->commit_recommendations($user_t1->id);
        $recommended = recommendations_repository::get_recommended_courses(10, $user_t1->id);
        self::assertCount(1, $recommended);
        $expected_ids = $this->pluck([$course_t1], 'id');
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        $this->commit_recommendations($user_t2->id);
        $recommended = recommendations_repository::get_recommended_courses(10, $user_t2->id);
        self::assertCount(1, $recommended);
        $expected_ids = $this->pluck([$course_t2], 'id');
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        // System User can still be recommended any
        $this->commit_recommendations($user_sys->id);
        $recommended = recommendations_repository::get_recommended_courses(10, $user_sys->id);
        self::assertCount(3, $recommended);
        $expected_ids = $this->pluck([$course_t1, $course_t2, $course_sys], 'id');
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);

        $this->commit_recommendations($user_p1->id);
        $recommended = recommendations_repository::get_recommended_courses(10, $user_p1->id);
        self::assertCount(3, $recommended);
        $actual_ids = $this->pluck($recommended, 'item_id');
        self::assertEqualsCanonicalizing($expected_ids, $actual_ids);
    }

    /**
     * Pre-test step to include the local library for enrollment
     */
    protected function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/enrol/locallib.php');

        $this->start_mock_service('container_course');
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();

        $this->clean_mock_service();
    }

    /**
     * Generate the courses & users & test data
     *
     * @return array
     */
    private function generate_data(): array {
        $gen = $this->getDataGenerator();

        $courses = [];
        $courses[1] = $gen->create_course(['fullname' => 'self-enrol + recommended + visible']);
        $courses[2] = $gen->create_course(['fullname' => 'self-enrol + recommended + not visible', 'visible' => 0]);
        $courses[3] = $gen->create_course(['fullname' => 'self-enrol + not recommended + visible']);
        $courses[4] = $gen->create_course(['fullname' => 'self-enrol + not recommended + not visible', 'visible' => 0]);
        $courses[5] = $gen->create_course(['fullname' => 'no self-enrol + recommended + visible']);
        $courses[6] = $gen->create_course(['fullname' => 'no self-enrol + recommended + not visible']);
        $courses[7] = $gen->create_course(['fullname' => 'no self-enrol + not recommended + visible']);
        $courses[8] = $gen->create_course(['fullname' => 'no self-enrol + not recommended + not visible']);

        $users = [];
        $users[1] = $gen->create_user(['username' => 'user1']);
        $users[2] = $gen->create_user(['username' => 'user2']);

        // Enable self-enrollments for Course 1 - 4
        foreach ([1, 2, 3, 4] as $course_key) {
            $this->enable_self_enrollment($courses[$course_key]->id);
        }

        // Recommend course 1, 2, 5 & 6
        foreach ($users as $user) {
            $item_ids = [];
            foreach ([1, 2, 5, 6] as $course_key) {
                $item_ids[] = $courses[$course_key]->id;
            }
            $this->recommend($item_ids, $user->id);
        }

        // User 1 is enrolled, user 2 is not
        foreach ($courses as $course) {
            $gen->enrol_user($users[1]->id, $course->id);
        }

        return [$courses, $users];
    }

    /**
     * Enable the self-enrollment plugin for the specified course
     *
     * @param int $course_id
     */
    private function enable_self_enrollment(int $course_id): void {
        global $DB;
        $enrol = $DB->get_record('enrol', array('courseid' => $course_id, 'enrol' => 'self'), '*', MUST_EXIST);
        $enrol->status = ENROL_INSTANCE_ENABLED;
        $DB->update_record('enrol', $enrol);
    }

    /**
     * @param array $collection
     * @param string $column
     * @return array
     */
    private function pluck(array $collection, string $column): array {
        return array_map(function ($item) use ($column) {
            return $item->{$column};
        }, $collection);
    }
}