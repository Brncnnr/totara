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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_course
 */

namespace core_course\local\archive_progress_helper;

use stdClass;

/**
 * Factory helper, used to get an appropriate helper.
 *
 * Can be instantiated, but isn't required.
 *
 * @internal
 * @final This class should not be extended.
 */
final class factory {

    /**
     * Return an appropriate helper to aid in archiving and reseting progress and completion.
     *
     * @param stdClass $course The course we are archiving in.
     * @param stdClass|null $user The user we are archiving, if a single user.
     * @return base|single_user|current_user|completed_users
     */
    final public static function get_helper(stdClass $course, stdClass $user = null): base {
        global $USER;
        if ($user !== null) {
            if ($USER->id == $user->id) {
                return new current_user($course);
            } else {
                return new single_user($course, $user);
            }
        } else {
            return new completed_users($course);
        }
    }

}