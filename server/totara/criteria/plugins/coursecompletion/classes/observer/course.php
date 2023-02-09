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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package criteria_coursecompletion
 */

namespace criteria_coursecompletion\observer;

use coding_exception;
use core\event\base;
use core\event\course_completed;
use core\event\course_deleted;
use core\event\course_restored;
use core\event\course_updated;
use totara_completioneditor\event\course_completion_edited;
use totara_completionimport\event\bulk_course_completionimport;
use totara_core\event\course_completion_reset;
use totara_criteria\course_item_helper;

class course {

    /**
     * In case someone completes a course or manually edits course completion we need to make sure it gets reaggregated
     *
     * @param base|course_completed|course_completion_edited $event
     */
    public static function course_completion_changed(base $event) {
        if (!$event instanceof course_completed && !$event instanceof course_completion_edited) {
            throw new coding_exception('Expected course_completed or course_completion_edited event');
        }

        course_item_helper::course_completions_updated([$event->relateduserid => [$event->courseid]], 'coursecompletion');
    }

    /**
     * Course completion of all users have been reset for the course
     *
     * @param course_completion_reset $event
     */
    public static function course_completion_reset(course_completion_reset $event) {
        course_item_helper::course_completions_reset($event->objectid, 'coursecompletion');
    }

    /**
     * When course completion records are imported in bulk, we also need to make sure competency achievement is reaggregated
     *
     * @param bulk_course_completionimport $event
     */
    public static function bulk_course_completions_imported(bulk_course_completionimport $event) {

        // Get courses per user
        $user_courses = [];
        foreach ($event->other[bulk_course_completionimport::PAYLOAD_KEY] as $user_completion) {
            $user_id = $user_completion['userid'];
            $course_id = $user_completion['courseid'];
            if (!isset($user_courses[$user_id])) {
                $user_courses[$user_id] = [];
            }
            $user_courses[$user_id][] = $course_id;
        }

        course_item_helper::course_completions_updated($user_courses, 'coursecompletion');
    }

    /**
     * When a course is deleted, we re-evaluate the validity of all criteria.
     * A validity_changed hook is executed with the ids of all criteria that were affected
     *
     * @param course_deleted $event
     */
    public static function course_deleted(course_deleted $event) {
        course_item_helper::course_deleted($event->objectid, 'coursecompletion');
    }

    /**
     * When a course is restored, we update all items with the original course id to restored course id.
     * Then we re-evaluate the validity of all criteria using this course.
     * A validity_changed hook is executed with the ids of all criteria that were affected
     *
     * @param course_restored $event
     */
    public static function course_restored(course_restored $event) {
        $restored_course_id = $event->courseid;
        $original_course_id = $event->other['originalcourseid'] ?? $restored_course_id;
        course_item_helper::course_restored($original_course_id, $restored_course_id, 'coursecompletion');
    }

    /**
     * We are only interested in whether the course's completion is tracked or not. Unfortunately we can only
     * determine that some course settings have been changed. When this happens we re-evaluate the validity
     * of  all criteria that uses the course
     *
     * @param course_updated $event
     */
    public static function course_updated(course_updated $event) {
        course_item_helper::course_settings_changed($event->objectid, 'coursecompletion');
    }

}
