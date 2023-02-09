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

use core_course\local\archive_progress_helper\base;
use core_course\local\archive_progress_helper\output\page_output;
use core_course\local\archive_progress_helper\output\validator\request_validator;

class archive_progress_helper_mock extends base {

    public function __construct(stdClass $course, bool $has_progress = false) {
        $this->course = $course;
        $this->has_progress = $has_progress;
    }

    public function archive_and_reset(): void {
        throw new coding_exception('Abstract method, not tested on the base class');
    }

    public function get_page_output(): page_output {
        throw new coding_exception('Abstract method, not tested on the base class');
    }

    public function get_validator(): request_validator {
        throw new coding_exception('Abstract method, not tested on the base class');
    }

}