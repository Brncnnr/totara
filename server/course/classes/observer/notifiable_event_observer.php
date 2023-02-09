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
 * @author David Curry <david.curry@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package core_course
 */

namespace core_course\observer;

use container_course\course;
use core\entity\user_enrolment as user_enrolment_entity;
use core\event\course_completed;
use core\event\user_enrolment_created;
use core\event\user_enrolment_deleted;
use core\orm\query\builder;
use core_course\totara_notification\resolver\activity_completed_resolver;
use core_course\totara_notification\resolver\course_completed_resolver;
use core_course\totara_notification\resolver\user_enrolled_resolver;
use core_course\totara_notification\resolver\user_unenrolled_resolver;
use totara_core\event\module_completion as activity_completed;
use totara_notification\external_helper;

class notifiable_event_observer {

    /**
     * Catch course completion events to set up relaterd notifications.
     */
    public static function course_completed(course_completed $event) {
        $data = [
            'course_id' => $event->get_data()['courseid'],
            'user_id' => $event->get_data()['relateduserid']
        ];

        external_helper::create_notifiable_event_queue(new course_completed_resolver($data));
    }

    /**
     * Catch course enrolled events to set up related notifications.
     */
    public static function user_enrolment_created(user_enrolment_created $event) {
        $data = [
            'course_id' => $event->get_data()['courseid'],
            'user_id' => $event->get_data()['relateduserid']
        ];
        if ($event->other['containertype'] !== course::get_type()) {
            return;
        }

        // Get this enrolment record
        $new_enrolment = new user_enrolment_entity($event->objectid);

        // Check whether the user has another active enrolment
        $sub_query = builder::table('user_enrolments', 'ue')
            ->as('ue')
            ->select('id')
            ->add_select_raw(
                "CASE WHEN ue.timestart IS NULL OR ue.timestart = 0 THEN ue.timecreated
                      ELSE ue.timestart
                 END AS time_enrolled")
            ->join(['enrol', 'e'], 'ue.enrolid', 'e.id')
            ->where('ue.userid', $data['user_id'])
            ->where('e.courseid', $data['course_id'])
            ->where(function (builder $builder) use ($new_enrolment) {
                $builder->where('ue.timeend', 0)
                    ->or_where('ue.timeend', '>', $new_enrolment->time_enrolled);
            });

        $exist = builder::table($sub_query)
            ->as('ss')
            ->where('time_enrolled', '<=', $new_enrolment->time_enrolled)
            ->where('id', '!=', $event->objectid)
            ->exists();

        if (!$exist) {
            external_helper::create_notifiable_event_queue(new user_enrolled_resolver($data));
        }
    }

    /**
     * Catch activity completion events to set up related notifications.
     */
    public static function activity_completed(activity_completed $event) {
        $data = $event->get_data()['other'];

        // Note that we trigger this for grade criteria as well for some reason, and we don't want to trigger this for those.
        $grade_completion = isset($data['criteriatype']) && $data['criteriatype'] == COMPLETION_CRITERIA_TYPE_GRADE;
        if (!$grade_completion) {
            $info = [
                'course_id' => $data['course'],
                'user_id' => $data['userid'],
                'course_module_id' => $data['moduleinstance'],
                'time_completed' => $data['timecompleted'] ?? time() // Not in the event, this should be close enough.
            ];

            $resolver = new activity_completed_resolver($info);
            external_helper::create_notifiable_event_queue($resolver);
        }
    }

     /**
     * Catch course enrolment deleted events to set up related notifications.
     *
     * @param user_enrolment_deleted $event
     */
    public static function user_enrolment_deleted(user_enrolment_deleted $event) {
        if ($event->other['containertype'] !== course::get_type()) {
            return;
        }

        if (!is_enrolled(\context_course::instance($event->get_data()['courseid']), $event->get_data()['relateduserid'])) {
            $data = [
                'course_id' => $event->get_data()['courseid'],
                'user_id' => $event->get_data()['relateduserid']
            ];

            // User has no enrolment in the course.
            external_helper::create_notifiable_event_queue(new user_unenrolled_resolver($data));
        }
    }
}
