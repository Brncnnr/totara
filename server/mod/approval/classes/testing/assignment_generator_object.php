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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

namespace mod_approval\testing;

use mod_approval\entity\assignment\assignment;
use mod_approval\model\status;

/**
 * Class assignment_generator_object
 *
 * Provides a structured interface for passing properties to the assignment generator.
 *
 * @package mod_approval\testing
 */
final class assignment_generator_object {
    public $course;
    public $id_number;
    public $is_default = false;
    public $assignment_type;
    public $assignment_identifier;
    public $status = status::ACTIVE;
    public $to_be_deleted = false;

    /**
     * Assignment_generator_object constructor, captures required properties.
     *
     * @param int $course_id
     * @param int $assignment_type;
     * @param int $assignment_identifier
     */
    public function __construct(int $course_id, int $assignment_type, int $assignment_identifier) {
        $this->course = $course_id;
        $this->assignment_type = $assignment_type;
        $this->assignment_identifier = $assignment_identifier;
        // Generate a default id_number
        $this->id_number = uniqid('assignment');
    }
}