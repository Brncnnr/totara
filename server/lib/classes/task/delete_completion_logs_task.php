<?php
/**
 * This file is part of Totara Core
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package core
 */

namespace core\task;

/**
 * Task to delete completion logs.
 */
class delete_completion_logs_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('deletecompletionlogs', 'totara_core');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        /** @var \core_config $CFG */
        /** @var \moodle_database $DB */
        global $CFG, $DB;

        if (empty($CFG->deletecompletionlogs)) {
            return;
        }
        // Delete course completion logs records
        $time = time();
        $timemodified = $time - ($CFG->deletecompletionlogs * 3600 * 24);
        if ($DB->delete_records_select('course_completion_log', 'timemodified < ?', [$timemodified])) {
            mtrace("    Deleted old course completion log records from 'course_completion_log'");
        }
    }
}