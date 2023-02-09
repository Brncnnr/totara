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

use core_course\local\archive_progress_helper\output\validator\request_validator;
use core_phpunit\testcase;

/**
 * @covers core_course\local\archive_progress_helper\output\validator\request_validator
 */
class core_course_archive_progress_helper_output_validator_request_validator_testcase extends testcase {

    private $validator;

    private $secret_generated = 'hidden treasures';

    protected function setUp(): void {
        $this->validator = $this->getMockForAbstractClass(request_validator::class);

        $this->validator->method('generate_secret')->willReturn($this->secret_generated);
    }

    protected function tearDown(): void {
        $this->validator = null;
    }

    public function test_validate() {
        // Correct secret doesn't throw an exception.
        $this->validator->validate($this->secret_generated);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Archive confirmation secret does not match expected value.');
        $this->validator->validate('the unknown');
    }

    public function test_add_secret_as_param() {
        $moodle_url = new moodle_url('/hello/world.php');
        $this->validator->add_secret_as_param($moodle_url);

        $added_param = $moodle_url->param(request_validator::SECRET_KEY);
        $this->assertNotEmpty($added_param);
        $this->assertEquals($this->secret_generated, $added_param);
    }
}