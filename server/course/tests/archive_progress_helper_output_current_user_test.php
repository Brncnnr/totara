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

use core_course\local\archive_progress_helper\output\context\confirmation_page;
use core_course\local\archive_progress_helper\output\context\error_page;
use core_course\local\archive_progress_helper\output\current_user;
use core_course\local\archive_progress_helper\output\validator\request_validator;
use core_phpunit\testcase;

/**
 * @covers \core_course\local\archive_progress_helper\output\current_user
 */
class core_course_archive_progress_helper_output_current_user_testcase extends testcase {

    private function get_instance(stdClass $course, stdClass $user): current_user {
        $request_validator = $this->getMockForAbstractClass(request_validator::class);

        return new current_user(
            true,
            $course,
            $request_validator,
            [
                'programs' => [],
                'certifications' => [],
            ],
            $user
        );
    }

    public function test_set_page_url() {
        $page = new moodle_page();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->get_instance($course, $user);

        $rm = new ReflectionMethod($instance, 'set_page_url');
        $rm->setAccessible(true);
        $rm->invoke($instance, $page);

        $this->assertStringContainsString('course/archivecompletions.php', $page->url->out());
        $this->assertEquals($course->id, $page->url->param('id'));
        $this->assertEquals($user->id, $page->url->param('userid'));
    }

    public function test_get_archive_completion_url_params() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->get_instance($course, $user);

        $rm = new ReflectionMethod($instance, 'get_archive_completion_url_params');
        $rm->setAccessible(true);
        $params = $rm->invoke($instance);

        $this->assertEqualsCanonicalizing([
            'id' => $course->id,
            'userid' => $user->id
        ], $params);
    }

    public function test_get_confirmation_page_context() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->get_instance($course, $user);

        $rm = new ReflectionMethod($instance, 'get_confirmation_page_context');
        $rm->setAccessible(true);

        /** @var confirmation_page $confirmation_context */
        $confirmation_context = $rm->invoke($instance);

        $this->assertEquals(get_string('archive_current_user_heading', 'completion'), $confirmation_context->get_heading());
        $this->assertStringContainsString('course/archivecompletions.php', $confirmation_context->get_confirmation_url()->out());
        $this->assertStringContainsString('course/view.php', $confirmation_context->get_cancel_url()->out());
    }

    public function test_get_error_page_context() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->get_instance($course, $user);

        $rm = new ReflectionMethod($instance, 'get_error_page_context');
        $rm->setAccessible(true);

        /** @var error_page $error_context */
        $error_context = $rm->invoke($instance);

        $this->assertStringContainsString('course/view.php', $error_context->get_button_url()->out());
    }

    public function test_get_success_page_context() {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $instance = $this->get_instance($course, $user);

        $success_context = $instance->get_success_page_context();
        $this->assertStringContainsString('course/view.php', $success_context->redirect_url()->out());
    }
}