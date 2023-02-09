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
 * @package contentmarketplace_linkedin
 */

/**
 * Populate the progress column in the course_modules_completion table with the correct corresponding LinkedIn progress.
 */
function contentmarketplace_linkedin_create_activity_progress_entries() {
    global $DB;

    $progress_records = $DB->get_records_sql("
        SELECT cm_completion.id, linkedin_progress.progress
        FROM {marketplace_linkedin_user_progress} linkedin_progress
        INNER JOIN {marketplace_linkedin_learning_object} learning_object
                ON linkedin_progress.learning_object_urn = learning_object.urn 
        INNER JOIN {totara_contentmarketplace_course_module_source} cm_source
                ON learning_object.id = cm_source.learning_object_id
               AND cm_source.marketplace_component = 'contentmarketplace_linkedin'
        INNER JOIN {course_modules_completion} cm_completion
                ON cm_source.cm_id = cm_completion.coursemoduleid
               AND linkedin_progress.user_id = cm_completion.userid
        WHERE cm_completion.progress IS NULL
    ");

    $DB->transaction(function () use ($DB, $progress_records) {
        foreach ($progress_records as $progress) {
            $DB->update_record('course_modules_completion', $progress, true);
        }
    });
}
