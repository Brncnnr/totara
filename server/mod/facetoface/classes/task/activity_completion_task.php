<?php
/*
 * This file is part of Totara LMS
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
 * @author Chris Snyder <chris.snyder@totaralms.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\task;
use mod_facetoface\signup\state\attendance_state;

require_once($CFG->libdir.'/completionlib.php');


/**
 * Send facetoface notifications
 */
class activity_completion_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('activitycompletiontask', 'mod_facetoface');
    }

    /**
     * Periodic activity completion check.
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/mod/facetoface/lib.php');

        // Load signups with attendance states and delayed activity completion, and put them through facetoface_get_completion_state()
        $attendance_codes = attendance_state::get_all_attendance_code();
        list($attendance_codes_sql, $attendance_codes_params) = $DB->get_in_or_equal($attendance_codes, SQL_PARAMS_NAMED);

        $sql = "SELECT c.*, f.id AS facetofaceid, fsu.userid AS userid
                FROM {facetoface_signups_status} fsus
                JOIN {facetoface_signups} fsu ON fsu.id = fsus.signupid
                JOIN {facetoface_sessions} fs ON fs.id = fsu.sessionid
                JOIN {facetoface_sessions_dates} fsd ON fsd.sessionid = fs.id
                JOIN {facetoface} f ON f.id = fs.facetoface
                JOIN {course_modules} cm ON cm.instance = f.id
                JOIN {course} c ON c.id = cm.course
                JOIN {modules} m ON m.id = cm.module
                LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = fsu.userid
                WHERE ( cmc.id IS NULL OR cmc.timecompleted IS NULL )
                AND m.name = 'facetoface'
                AND cm.completion = :cta
                AND f.completiondelay IS NOT NULL
                AND fsd.timefinish < (:now - (:daysecs * f.completiondelay))
                AND fsus.superceded = 0
                AND fsus.statuscode {$attendance_codes_sql} 
                ";
        $params = array('cta' => COMPLETION_TRACKING_AUTOMATIC, 'now' => time(), 'daysecs' => DAYSECS) + $attendance_codes_params;

        $rs = $DB->get_recordset_sql($sql, $params);
        $total = $processed = 0;
        foreach ($rs as $rec) {
            $total++;
            $completion = new \completion_info($rec);
            if ($completion->is_enabled()) {
                $processed++;
                $course_module = get_coursemodule_from_instance('facetoface', $rec->facetofaceid, $rec->id);
                $completion->update_state($course_module, COMPLETION_UNKNOWN, $rec->userid);
                $completion->invalidatecache($rec->id, $rec->userid);
            }
        }
        $rs->close();

        mtrace("Found {$total} delayed facetoface activity completion records, processed {$processed}.");
    }
}
