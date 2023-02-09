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

use core_course\local\archive_progress_helper\output\context\success_page;
use core_phpunit\testcase;

/**
 * @covers \core_course\local\archive_progress_helper\output\success_page_context
 */
class core_course_archive_progress_helper_output_context_success_page_testcase extends testcase {

    public function test_page_render_properties() {
        $properties = new success_page(
            new moodle_url('/hello/world.php'),
            'lorem ipsum',
        );

        $this->assertStringContainsString('hello/world.php', $properties->redirect_url()->out());
        $this->assertEquals('lorem ipsum', $properties->message());
    }
}

