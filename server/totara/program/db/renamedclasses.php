<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package totara_program
 */

/**
 * This assists with autoloading when a class or its namespace has been renamed.
 * See lib/db/renamedclasses.php for further information on this type of file.
 */

defined('MOODLE_INTERNAL') || die();

$renamedclasses = [
    'program_utilities' => \totara_program\utils::class,
    'prog_exceptions_manager' => \totara_program\exception\manager::class,
    'prog_exception' => \totara_program\exception\base::class,
    'time_allowance_exception' => \totara_program\exception\time_allowance::class,
    'already_assigned_exception' => \totara_program\exception\already_assigned::class,
    'duplicate_course_exception' => \totara_program\exception\duplicate_course::class,
    'completion_time_unknown_exception' => \totara_program\exception\completion_time_unknown::class,
    'unknown_exception' => \totara_program\exception\unknown::class
];
