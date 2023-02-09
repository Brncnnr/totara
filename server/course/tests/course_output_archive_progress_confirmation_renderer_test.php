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
use core_course\output\archive_progress_confirmation_renderer;
use core_phpunit\testcase;

/**
 * @covers \core_course\output\archive_progress_confirmation_renderer
 */
class core_course_output_archive_progress_confirmation_renderer_testcase extends testcase {

    public function test_page_get_renderer() {
        $page = new moodle_page();
        $renderer = $page->get_renderer('core_course', 'archive_progress_confirmation');
        $this->assertInstanceOf(archive_progress_confirmation_renderer::class, $renderer);
    }

    public function test_archive_progress_renderer_shows_confirmation_modal() {
        $page = new moodle_page();
        /** @var archive_progress_confirmation_renderer $renderer*/
        $renderer = $page->get_renderer('core_course', 'archive_progress_confirmation');

        // When rendering with linked programs and certifications.
        $page_properties = new confirmation_page(
            'Hello world',
            'are you sure about this?',
            new moodle_url('hello/confirm.php'),
            new moodle_url('hello/cancel.php')
        );
        $output = $renderer->page($page_properties);

        // Then confirmation modal is shown.
        $this->assertStringContainsString('are you sure about this?', $output);
        $this->assertStringContainsString('Continue', $output);
        $this->assertStringContainsString('hello/confirm.php', $output);
        $this->assertStringContainsString('Cancel', $output);
        $this->assertStringContainsString('hello/cancel.php', $output);

        // And Error message is not rendered.
        $this->assertStringNotContainsString(get_string('error:cannotarchiveprogcourse', 'completion'), $output);
    }
}
