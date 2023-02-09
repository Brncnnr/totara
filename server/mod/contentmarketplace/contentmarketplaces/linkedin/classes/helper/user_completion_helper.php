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

namespace contentmarketplaceactivity_linkedin\helper;

use cm_info;
use completion_criteria_completion;
use completion_info;

class user_completion_helper {

    /**
     * Get user's completions in course and update progress.
     *
     * @param int $user_id
     * @param int $course_id
     * @param completion_info $completion_info
     * @param cm_info $cm_info
     * @param int $progress
     *
     * @return void
     */
    public static function update_user_completion(
        int $user_id,
        int $course_id,
        completion_info $completion_info,
        cm_info $cm_info,
        int $progress
    ): void {
        global $DB;

        $params = [
            'userid' => $user_id,
            'course' => $course_id
        ];
        $completions = $DB->get_records('course_completion_crit_compl', $params);
        $criteria_ids = array_column($completions, 'criteriaid');
        $completions = array_combine($criteria_ids, $completions);

        $criteria = $completion_info->get_criteria(COMPLETION_CRITERIA_TYPE_ACTIVITY);
        foreach ($criteria as $criterion_id => $criterion) {
            $completion = $completions[$criterion_id] ?? null;
            // If there is no completion record for this contentmarketplace activity for this user, then create one.
            if ($completion === null && $criterion->module == 'contentmarketplace' && $criterion->moduleinstance == $cm_info->id) {
                $completion_criteria_completion = new completion_criteria_completion(
                    [
                        'course'     => $course_id,
                        'userid'     => $user_id,
                        'criteriaid' => $criterion_id,
                    ],
                    false
                );
                $completion_criteria_completion->insert();
                break;
            }
        }

        $completion_info->update_progress($cm_info, $progress, $user_id);
        $completion_info->update_state($cm_info, COMPLETION_UNKNOWN, $user_id);
    }

}