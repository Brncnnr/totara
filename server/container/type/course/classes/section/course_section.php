<?php
/**
 * This file is part of Totara LMS
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_course
 */
namespace container_course\section;

use core\event\course_section_created;
use core_container\section\section;

/**
 * Section class that represent for course.
 */
class course_section extends section {
    /**
     * @param int  $course_id
     * @param int  $position
     *
     * @return section
     */
    public static function create(int $course_id, int $position): section {
        global $DB, $CFG;

        $course = $DB->get_record('course', ['id' => $course_id], '*', MUST_EXIST);
        $last_section = (int) $DB->get_field('course_sections', 'MAX(section)', ['course' => $course_id]);

        if (!function_exists('course_add_section')) {
            require_once("{$CFG->dirroot}/course/lib.php");
        }

        $cw = course_add_section($course_id, $last_section + 1, false);
        if (!$cw) {
            throw new \coding_exception('Unknown error creating new section');
        }

        $section = static::from_record($cw);
        if ($position > 0 && $position <= $last_section) {
            $result = move_section_to($course, $cw->section, $position, true);
            if (!$result) {
                debugging("Error moving new section to position '$position'", DEBUG_DEVELOPER);
            }

            $cw = $DB->get_record('course_sections', ['id' => $cw->id], '*', MUST_EXIST);
            $section = static::from_record($cw);
        }

        $event = course_section_created::create_from_section($cw);
        $event->add_record_snapshot('course_sections', $cw);
        $event->add_record_snapshot('course', $course);
        $event->trigger();

        return $section;
    }

    /**
     * @param bool $force_delete
     * @return bool
     */
    public function delete(bool $force_delete = true): bool {
        global $CFG;

        if (!function_exists('course_delete_section')) {
            require_once("{$CFG->dirroot}/course/lib.php");
        }

        $course = $this->get_container();

        $section_record = $this->to_record();
        $course_record = $course->to_record();

        return course_delete_section(
            $course_record,
            $section_record,
            $force_delete
        );
    }
}