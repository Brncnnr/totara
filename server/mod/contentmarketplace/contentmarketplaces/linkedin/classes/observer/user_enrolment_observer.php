<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package contentmarketplaceactivity_linkedin
 */

namespace contentmarketplaceactivity_linkedin\observer;

use completion_info;
use contentmarketplaceactivity_linkedin\helper\user_completion_helper;
use contentmarketplace_linkedin\model\learning_object;
use contentmarketplace_linkedin\model\user_progress;
use core\event\user_enrolment_created;
use mod_contentmarketplace\entity\content_marketplace;

class user_enrolment_observer {

    public static function user_enrolment_created(user_enrolment_created $event) {
        global $CFG;

        // First check course completion is enabled on this site
        if (empty($CFG->enablecompletion)) {
            return;
        }

        // Get course.
        $course = get_course($event->courseid);

        // Skip if completion is not enabled for course.
        require_once($CFG->libdir . '/completionlib.php');
        $completion_info = new completion_info($course);
        if (!$completion_info->is_enabled()) {
            return;
        }

        // Skip if course does not contains content marketplace activities.
        $repo = content_marketplace::repository();
        $content_marketplaces = $repo->find_by_course_id_and_component($course->id, 'contentmarketplace_linkedin');
        if ($content_marketplaces->count() === 0) {
            return;
        }

        /** @var $content_marketplace content_marketplace */
        foreach ($content_marketplaces as $content_marketplace) {
            $course_module = $content_marketplace->course_module()->one();

            // Completion is not enabled for this course module.
            $cm_info = \cm_info::create($course_module->to_record(), $event->relateduserid);
            if (!$completion_info->is_enabled($cm_info)) {
                continue;
            }

            // Get learning object linked to the course module.
            $learning_object = learning_object::load_by_id($content_marketplace->learning_object_id);

            // Skip if we cannot find any existing records in the user progress table for this user and learning object.
            $user_progress = user_progress::load_for_user_and_learning_object_id(
                $event->relateduserid,
                $learning_object->id
            );
            if (!$user_progress) {
                continue;
            }

            // Update user progress for this learning object.
            user_completion_helper::update_user_completion(
                $event->relateduserid,
                $event->courseid,
                $completion_info,
                $cm_info,
                $user_progress->progress
            );
        }
    }

}