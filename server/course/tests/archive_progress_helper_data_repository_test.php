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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use core_course\local\archive_progress_helper\data_repository;
use core_phpunit\testcase;

/**
 * @covers \core_course\local\archive_progress_helper\data_repository
 */
class core_course_archive_progress_helper_data_repository_testcase extends testcase {

    public function test_user_has_progress() {
        $data = $this->setup_course_with_completed_users(1);
        $user = $this->getDataGenerator()->create_user();
        $has_progress = data_repository::user_has_progress($data['course']->id, $user->id);
        $this->assertFalse($has_progress);

        $learner = reset($data['users']);
        $has_progress = data_repository::user_has_progress($data['course']->id, $learner->id);
        $this->assertTrue($has_progress);
    }

    public function test_get_course_completed_users_count() {
        $data = $this->setup_course_with_completed_users(10);
        $result = data_repository::get_course_completed_users_count($data['course']->id);
        $this->assertEquals(10, $result);
    }

    public function test_get_course_completed_users() {
        $data = $this->setup_course_with_completed_users(10);
        $result = data_repository::get_course_completed_users($data['course']->id);

        $user_ids = array_map(function($user) {
            return $user->id;
        }, $data['users']);

        $this->assertEqualsCanonicalizing($user_ids, $result);
    }

    /**
     * Sets ups users that have completed a course.
     *
     * @param int $user_count
     * @return array
     */
    private function setup_course_with_completed_users(int $user_count): array {
        $course = $this->getDataGenerator()->create_course();

        $completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');
        $completion_generator->enable_completion_tracking($course);

        $users = [];

        for ($i = 0; $i < $user_count; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
            $completion_generator->complete_course($course, $user, time());
            $users[] = $user;
        }

        return [
            'course' => $course,
            'users' => $users,
        ];
    }

    public function test_get_linked_programs_and_certifications_names() {
        $generator = $this->getDataGenerator();

        // When a course does not have linked programs and certifications.
        $course = $generator->create_course();

        $result = data_repository::get_linked_programs_and_certifications_names($course->id);

        // Then the result is empty.
        $this->assertEmpty($result);

        // When the course has a program and certification.
        /** @var \totara_program\testing\generator $program_generator */
        $program_generator = $generator->get_plugin_generator('totara_program');
        $program = $program_generator->create_program();
        $certification = $program_generator->create_certification();
        $program_generator->legacy_add_courseset_program($program->id, [$course->id]);
        $program_generator->legacy_add_courseset_program($certification->id, [$course->id]);

        // Then there are two items in the result.
        $result = data_repository::get_linked_programs_and_certifications_names($course->id);
        $this->assertCount(2, $result);
        $certification_count = 0;
        $program_count = 0;
        foreach ($result as $item) {
            if (!empty($item->certifid)) {
                $certification_count++;
            } else {
                $program_count++;
            }
        }

        $this->assertEquals(1, $certification_count);
        $this->assertEquals(1, $program_count);
    }
}