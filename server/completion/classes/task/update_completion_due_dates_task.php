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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package core_completion
 */

namespace core_completion\task;

use core\entity\course as course_entity;
use core\entity\course_completion as course_completion_entity;
use core\task\adhoc_task;
use core_completion\helper as course_completion_helper;
use core_completion\model\course_completion as course_completion_model;

/**
 * Ad-hoc task to update completion due dates when course settings changed
 */
class update_completion_due_dates_task extends adhoc_task {

    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        if (empty($data->course_id)) {
            throw new \coding_exception('Missing course_id in update_completion_due_dates_task');
        }

        /** @var course_entity $course */
        $course = course_entity::repository()->find($data->course_id);
        if (!$course) {
            throw new \coding_exception('Unknown course_id in update_completion_due_dates_task');
        }

        if (!$course->duedateoffsetunit) {
            // Due date is either not set, or set to a fixed date - we can simply update all enrolled users' due dates to the duedate value
            $sql =
                "UPDATE {course_completions}
                    SET duedate = :duedate
                  WHERE course = :course_id";
            $params = ['course_id' => $course->id, 'duedate' => $course->duedate];
            $DB->execute($sql, $params);

            course_completion_helper::save_completion_log(
                $course->id,
                null,
                "Due date updated in update_completion_due_dates_task to " .
                    course_completion_helper::format_log_date($course->duedate)
            );
        } else {
            // For relative due dates we need to update each user's completion record individually
            $completion_records = course_completion_entity::repository()
                ->where('course', $course->id)
                ->get_lazy();
            foreach ($completion_records as $record) {
                $model = course_completion_model::load_by_entity($record);
                $model->update_duedate();
            }
        }
    }

}
