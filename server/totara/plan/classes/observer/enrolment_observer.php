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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_plan
 */

namespace totara_plan\observer;

use container_course\course;
use core\event\user_enrolment_created;
use totara_plan\record_of_learning;

/**
 * Observer for enrolment events
 */
class enrolment_observer {

    public static function user_enrolment_created(user_enrolment_created $event) {
        $user_id = (int) $event->relateduserid;
        $course_id = (int) $event->courseid;

        // Totara: Skip if containertype is not container_course.
        if ($event->other['containertype'] !== course::get_type()) {
            return;
        }

        record_of_learning::insert_course_record($user_id, $course_id);
    }

}