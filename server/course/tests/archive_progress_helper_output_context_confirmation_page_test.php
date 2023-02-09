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
use core_phpunit\testcase;

/**
 * @covers \core_course\local\archive_progress_helper\output\confirmation_page_context
 */
class core_course_archive_progress_helper_output_context_confirmation_page_testcase extends testcase {

    public function test_get_context_properties() {
        $page_properties = new confirmation_page(
            'Hello world',
            'lorem ipsum',
            new moodle_url('/hello/world.php'),
            new moodle_url('/kia/ora.php')
        );

        $this->assertEquals('Hello world', $page_properties->get_heading());
        $this->assertEquals('lorem ipsum', $page_properties->get_message());
        $this->assertStringContainsString('hello/world.php', $page_properties->get_confirmation_url()->out());
        $this->assertStringContainsString('kia/ora.php', $page_properties->get_cancel_url()->out());
    }
}

