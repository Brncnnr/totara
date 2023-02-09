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
use core_course\local\archive_progress_helper\output\page_output;
use core_course\local\archive_progress_helper\output\validator\request_validator;
use core_phpunit\testcase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \core_course\local\archive_progress_helper\output\page_output
 */
class core_course_archive_progress_helper_output_page_output_testcase extends testcase {

    /**
     * @param bool $confirming_reset
     *
     * @return page_output|MockObject
     */
    private function get_instance(bool $confirming_reset = false): page_output {
        $request_validator = $this->getMockForAbstractClass(request_validator::class);
        $request_validator->method('generate_secret')->willReturn('encrypted_key');
        $course = $this->getDataGenerator()->create_course();
        $page_output = $this->getMockForAbstractClass(page_output::class, [
            $confirming_reset,
            $course,
            $request_validator
        ], '', true);

        // Setup method returns
        $page_output->method('get_archive_completion_url_params')->willReturn([]);
        $page_output->method('get_confirmation_page_context')->willReturnCallback(function() {
            return new confirmation_page(
                "On top",
                "Going for it?",
                new moodle_url('yes.php'),
                new moodle_url('no.php')
            );
        });
        $page_output->method('get_error_page_context')->willReturnCallback(function() {
            return new error_page(
                "On top",
                "Yea na",
                ['P1'],
                ['C1'],
                new moodle_url('back.php')
            );
        });

        return $page_output;
    }

    public function test_get_archive_completion_url() {
        $page_output = $this->get_instance();
        $completion_url = $page_output->get_archive_completion_url();
        $this->assertInstanceOf(moodle_url::class, $completion_url);
        $this->assertStringContainsString('/course/archivecompletions.php', $completion_url->out());
    }

    public function test_get_confirmation_url() {
        $page_output = $this->get_instance();
        $rm = new ReflectionMethod($page_output, 'get_confirmation_url');
        $rm->setAccessible(true);

        /** @var moodle_url $confirmation_url*/
        $confirmation_url = $rm->invoke($page_output);

        $this->assertStringContainsString('/course/archivecompletions.php', $confirmation_url->out());

        $params = $confirmation_url->params();
        $this->assertArrayHasKey('sesskey', $params);
        $this->assertArrayHasKey(request_validator::SECRET_KEY, $params);
    }

    public function test_render_confirmation_page() {
        global $PAGE;
        $page = new moodle_page();
        $page->set_url('/course/archivecompletions.php');

        // Set page as global page to skip debugging errors.
        $PAGE = $page;
        $page_output = $this->get_instance(true);
        $page_output->expects($this->once())->method('get_confirmation_page_context');
        $page_output->expects($this->never())->method('get_error_page_context');
        $page_output->render($page);
    }

    public function test_render_error_page() {
        global $PAGE;
        $page = new moodle_page();
        $page->set_url('/course/archivecompletions.php');

        // Set page as global page to skip debugging errors.
        $PAGE = $page;
        $page_output = $this->get_instance();
        $page_output->expects($this->once())->method('get_error_page_context');
        $page_output->expects($this->never())->method('get_confirmation_page_context');
        $page_output->render($page);
    }
}