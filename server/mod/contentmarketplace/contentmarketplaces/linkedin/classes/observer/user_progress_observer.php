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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplaceactivity_linkedin
 */

namespace contentmarketplaceactivity_linkedin\observer;

use cm_info;
use completion_criteria_completion;
use completion_info;
use contentmarketplace_linkedin\entity\user_progress;
use contentmarketplace_linkedin\event\user_progress_updated;
use contentmarketplaceactivity_linkedin\helper\user_completion_helper;
use context_course;
use totara_contentmarketplace\entity\course_module_source;

final class user_progress_observer {

    /**
     * Update the activity progress and completion status for LinkedIn Learning content marketplace activities.
     *
     * @param user_progress_updated $event
     */
    public static function user_progress_updated(user_progress_updated $event): void {
        global $CFG;
        require_once("$CFG->dirroot/lib/completionlib.php");

        /** @var user_progress $user_progress */
        $user_progress = $event->get_entity_snapshot(user_progress::class, $event->objectid);

        // Get all the courses that are linked with this very learning object.
        $course_module_sources = course_module_source::repository()
            ->with('module.course_entity')
            ->filter_by_external_id_and_component($user_progress->learning_object_urn, 'contentmarketplace_linkedin')
            ->get();

        foreach ($course_module_sources as $course_module_source) {
            $course = $course_module_source->module->course_entity;

            // Check if the user is enrolled into this course or not.
            if (!is_enrolled(context_course::instance($course->id), $user_progress->user_id)) {
                continue;
            }

            $completion_info = new completion_info($course->to_record());
            if (!$completion_info->is_enabled()) {
                // Course's completion is not marked as enabled.
                continue;
            }

            // Get all the cm from the courses that are mod contentmarketplace
            $cm_info = cm_info::create($course_module_source->module->to_record(), $user_progress->user_id);

            if (!$completion_info->is_enabled($cm_info)) {
                // Completion is not enabled for this course module contentmarketplace.
                continue;
            }

            // Update user progress for this learning object.
            user_completion_helper::update_user_completion(
                $user_progress->user_id,
                $course->id,
                $completion_info,
                $cm_info,
                $user_progress->progress
            );
        }
    }

}
