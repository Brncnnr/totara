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

use core\event\course_completed;
use totara_completionimport\event\bulk_course_completionimport;
use totara_plan\record_of_learning;

/**
 * Observer for course completion events
 */
class completion_observer {

    public static function bulk_completion(bulk_course_completionimport $event) {
        global $DB;

        $sql_ors = [];
        $completions = [];
        foreach ($event->get_completions() as $completion) {
            $user_id = (int) $completion['userid'];
            $course_id = (int) $completion['courseid'];
            $sql_ors[] = "(userid = {$user_id} AND instanceid = {$course_id})";

            $new_rol_record = (object)[
                'userid' => $user_id,
                'instanceid' => $course_id,
                'type' => record_of_learning::TYPE_COURSE
            ];
            $completions[$user_id.'/'.$course_id] = $new_rol_record;
        }
        $sql_ors = implode(' OR ', $sql_ors);

        $records = $DB->get_records_select(
            'dp_record_of_learning',
            "({$sql_ors}) AND type = :type",
            ['type' => record_of_learning::TYPE_COURSE]
        );

        // Now remove the ones we have records for already
        foreach ($records as $record) {
            unset($completions[$record->userid.'/'.$record->instanceid]);
        }

        // And insert the leftovers in the least amount of inserts
        if (!empty($completions)) {
            $DB->insert_records_via_batch('dp_record_of_learning', $completions);
        }
    }

    /**
     * Creates a record of learning record when a user completes a course
     *
     * @param course_completed $event
     */
    public static function completed_course(course_completed $event) {
        $user_id = (int) $event->relateduserid;
        $course_id = (int) $event->courseid;

        record_of_learning::insert_course_record($user_id, $course_id);
    }

}