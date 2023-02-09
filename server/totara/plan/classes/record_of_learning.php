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

namespace totara_plan;

use container_course\course;
use container_site\site;

/**
 * Generic class holding relevant constants for record of learning
 */
final class record_of_learning {

    public const TYPE_COURSE = 1;
    public const TYPE_PROGRAM = 2;
    public const TYPE_CERTIFICATION = 3;

    /**
     * Inserts a new record to the record of learning for a course.
     * Won't add duplicates.
     *
     * @param int $user_id
     * @param int $course_id
     * @return void
     */
    public static function insert_course_record(int $user_id, int $course_id): void {
        global $DB;

        $sql = "
            INSERT INTO {dp_record_of_learning} (userid, instanceid, type)
            SELECT ".$user_id.", c.id, ".record_of_learning::TYPE_COURSE."
            FROM {course} c 
            LEFT JOIN {dp_record_of_learning} rol 
                ON c.id = rol.instanceid AND rol.type = :type AND rol.userid = :userid
            WHERE c.id = :courseid AND (c.containertype = :container_course OR c.containertype = :container_site)
                AND rol.id IS NULL
        
        ";

        $params = [
            'courseid' => $course_id,
            'userid' => $user_id,
            'container_course' => course::get_type(),
            'container_site' => site::get_type(),
            'type' => record_of_learning::TYPE_COURSE
        ];

        $DB->execute($sql, $params);
    }

}