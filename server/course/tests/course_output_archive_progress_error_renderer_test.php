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

use core_course\local\archive_progress_helper\output\context\error_page;
use core_course\output\archive_progress_error_renderer;
use core_phpunit\testcase;

/**
 * @covers core_course\output\archive_progress_error_renderer
 */
class core_course_output_archive_progress_error_renderer_testcase extends testcase {

    public function test_page_get_renderer() {
        $page = new moodle_page();
        $renderer = $page->get_renderer('core_course', 'archive_progress_error');
        $this->assertInstanceOf(archive_progress_error_renderer::class, $renderer);
    }

    public function test_renders_output_with_no_linked_programs_and_certifications() {
        $page = new moodle_page();

        /** @var archive_progress_error_renderer $renderer*/
        $renderer = $page->get_renderer('core_course', 'archive_progress_error');

        // When rendering with linked programs and certifications.
        $page_properties = new error_page(
            'Hello world',
            'are you sure about this?',
            [],
            [],
            new moodle_url('back/confirm.php'),
        );
        $output = $renderer->page($page_properties);

        $this->assertStringContainsString('Hello world', $output);

        // No progress message
        $this->assertStringContainsString('are you sure about this?', $output);

        // Continue button exists
        $this->assertStringContainsString('Ok', $output);
        $this->assertStringContainsString('back/confirm.php', $output);
    }

    public function test_renders_output_with_linked_programs_and_certifications() {
        $page = new moodle_page();

        /** @var archive_progress_error_renderer $renderer*/
        $renderer = $page->get_renderer('core_course', 'archive_progress_error');

        // When rendering with linked programs and certifications.
        $page_properties = new error_page(
            'Hello world',
            'are you sure about this?',
            ['P1', 'P2'],
            ['C1', 'C2'],
            new moodle_url('back/confirm.php'),
        );
        $output = $renderer->page($page_properties);

        $this->assertStringContainsString('Hello world', $output);
        $this->assertStringNotContainsString('are you sure about this?', $output);
        // Error message is shown.
        $this->assertStringContainsString(get_string('error:cannotarchiveprogcourse', 'completion'), $output);

        // Programs and certifications are listed.
        $this->assertStringContainsString('P1', $output);
        $this->assertStringContainsString('P2', $output);
        $this->assertStringContainsString('C1', $output);
        $this->assertStringContainsString('C2', $output);

        // Continue button exists
        $this->assertStringContainsString('Ok', $output);
        $this->assertStringContainsString('back/confirm.php', $output);
    }
}
