<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core_course
 */
namespace core_course\local;

use core\orm\query\builder;

/**
 * Grade helper helps determine grade-related things about courses.
 */
class grade_helper {

    /**
     * @var int Cutoff for number of grade items a course has before async regrading is used.
     */
    private const REGRADE_GRADE_ITEMS_ASYNC = 100;

    /**
     * @var int Cutoff for number of enrolments a course has before async regrading is used.
     */
    private const REGRADE_ENROLMENTS_ASYNC = 100;

    /**
     * grade_helper constructor.
     * Prevent this class from instantiation.
     */
    private function __construct() {
    }

    /**
     * Determines whether a course has grade items that need updating.
     *
     * @param int $course_id
     * @return bool
     */
    public static function does_course_need_regrade(int $course_id): bool {
        return builder::table('grade_items')
            ->where('courseid', '=', $course_id)
            ->where('needsupdate', '=', 1)
            ->exists();
    }

    /**
     * Determines whether a course should use asynchronous regrading, because real-time regrading is
     * likely to be too slow due to the number of grade items and/or enrolments.
     *
     * @param int $course_id
     * @return bool
     */
    public static function use_async_course_regrade(int $course_id): bool {
        $grade_items_async = get_config('core', 'course_regrade_grade_items_async') ?: self::REGRADE_GRADE_ITEMS_ASYNC;
        $enrolments_async = get_config('core', 'course_regrade_enrolments_async') ?: self::REGRADE_ENROLMENTS_ASYNC;

        $grade_items = builder::table('grade_items')
            ->where('courseid', '=', $course_id)
            ->count();
        if ($grade_items > $grade_items_async) {
            return true;
        }
        $enrolments = builder::table('user_enrolments')
            ->as('ue')
            ->select_raw('DISTINCT ue.userid')
            ->join(['enrol', 'e'], function (builder $join) use ($course_id) {
                $join->where_field('e.id', '=', 'ue.enrolid')
                    ->where('e.courseid', '=', $course_id);
            })
            ->count();
        if ($enrolments > $enrolments_async) {
            return true;
        }
        return false;
    }
}