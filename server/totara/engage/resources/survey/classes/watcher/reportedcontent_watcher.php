<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package engage_survey
 */

namespace engage_survey\watcher;

use engage_survey\totara_engage\resource\survey;
use totara_reportedcontent\hook\get_review_context;
use totara_reportedcontent\hook\remove_review_content;

/**
 * Get the content & context of a resource/article comment
 *
 * @package engage_survey\watcher
 */
final class reportedcontent_watcher {
    /**
     * Whole surveys are reportable
     *
     * @param get_review_context $hook
     * @return void
     */
    public static function get_content(get_review_context $hook): void {
        // Only valid for whole surveys
        if ('engage_survey' !== $hook->component || '' !== $hook->area) {
            return;
        }

        // It's the survey itself
        $survey = survey::from_resource_id($hook->item_id);

        $content = $survey->get_name(FORMAT_PLAIN);
        $format = FORMAT_PLAIN;
        $time_created = $survey->get_timecreated();
        $user_id = $survey->get_userid();

        $hook->context_id = $survey->get_context()->id;
        $hook->content = $content;
        $hook->format = $format;
        $hook->time_created = $time_created;
        $hook->user_id = $user_id;

        $hook->success = true;
    }

    /**
     * @param remove_review_content $hook
     * @return void
     */
    public static function delete_survey(remove_review_content $hook): void {
        global $DB;

        // Only valid for surveys
        if ('engage_survey' !== $hook->review->get_component() || '' !== $hook->review->get_area()) {
            return;
        }

        // It's possible this resource may have been removed already, so if it has we're going to
        // just accept it.
        if (!$DB->record_exists('engage_resource', ['id' => $hook->review->get_item_id()])) {
            $hook->success = true;
            return;
        }

        /** @var survey $survey */
        $survey = survey::from_resource_id($hook->review->get_item_id());
        $survey->delete();

        $hook->success = true;
    }
}