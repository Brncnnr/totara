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

namespace core_course\local\archive_progress_helper\output\validator;

use stdClass;

/**
 * Validator for resetting course progress for a single user.
 */
class single_user extends request_validator {

    /**
     * @var stdClass
     */
    private $course;

    /**
     * @var int
     */
    private $user_id;

    /**
     * Constructor
     *
     * @param stdClass $course
     * @param int $user_id
     */
    public function __construct(stdClass $course, int $user_id) {
        $this->course = $course;
        $this->user_id = $user_id;
    }

    /**
     * @inheritDoc
     */
    public function generate_secret(): string {
        $string = sprintf('%s-%s-%s', $this->course->id, $this->course->timemodified, $this->user_id);

        return sha1($string);
    }
}