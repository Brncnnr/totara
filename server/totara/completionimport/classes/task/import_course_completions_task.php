<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_completionimport
 */

namespace totara_completionimport\task;

class import_course_completions_task extends \core\task\adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() :string {
        return get_string('importcoursecompletionstask', 'totara_completionimport');
    }

    /**
     * Import pending course completion records
     */
    public function execute() {
        global $DB, $CFG, $OUTPUT;

        require_once($CFG->dirroot . '/totara/completionimport/lib.php');
        require_once($CFG->dirroot . '/completion/completion_completion.php');
        require_once($CFG->dirroot . '/blocks/totara_stats/locallib.php');

        // Get customdata for adhoc task.
        $customdata = $this->get_custom_data();
        $importtime = $customdata->importtime;
        $create_evidence = $customdata->create_evidence ?? 0;
        $evidence_type_id = $customdata->evidence_type_id ?? 0;

        $userstonotify = \totara_completionimport\helper::get_list_of_import_users('course', $importtime);

        import_data_checks('course', $importtime);
        import_data_adjustments('course', $importtime);

        $numerrors = $DB->count_records('totara_compl_import_course', ['timecreated' => $importtime, 'importerror' => 1]);

        try {
            if ($create_evidence) {
                // Put into evidence any courses not found.
                create_evidence('course', $importtime, $evidence_type_id);
            }

            // Run the specific course enrolment.
            import_course('course', $importtime);

            PHPUNIT_TEST || mtrace("Course completion import with timestamp '$importtime' processed");

            // Update processed flag of records
            $params = ['timecreated' => $importtime];
            $DB->execute("UPDATE {totara_compl_import_course} SET processed = 1 WHERE timecreated = :timecreated", $params);

            // Purge the progress caches to ensure course and program progress is re-calcuated.
            \totara_program\progress\program_progress_cache::purge_progressinfo_caches();
            \completion_info::purge_progress_caches();

            foreach ($userstonotify as $userto) {
                $event = new \stdClass();
                $event->userfrom = \core_user::get_noreply_user();
                $event->component = 'totara_completionimport';
                $event->userto = $userto;

                $a = new \stdClass();
                $a->uploadtime = userdate($importtime, get_string('strftimedatetime'));
                $reporturl = new \moodle_url('/totara/completionimport/viewreport.php',
                    ['importname' => 'course', 'timecreated' => $importtime, 'importuserid' => $userto->id, 'clearfilters' => 1]);
                $a->reportlink = $OUTPUT->action_link($reporturl, get_string('report_course', 'totara_completionimport'));

                if ($numerrors) {
                    $a->numerrors = $numerrors;
                    $event->subject = get_string('importsuccessfulwitherrorscoursesubject', 'totara_completionimport');
                    $event->fullmessage = get_string('importsuccessfulwitherrorscoursefullmessage', 'totara_completionimport', $a);
                } else {
                    $event->subject = get_string('importsuccessfulcoursesubject', 'totara_completionimport');
                    $event->fullmessage = get_string('importsuccessfulcoursefullmessage', 'totara_completionimport', $a);
                }

                // Send success alert.
                tm_alert_send($event);
            }
        } catch (\Exception $e) {
            debugging("Exception encountered in 'import_course_completions_task' class: " . $e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());

            foreach ($userstonotify as $userto) {
                $event = new \stdClass();
                $event->userfrom = \core_user::get_noreply_user();
                $event->component = 'totara_completionimport';
                $event->userto = $userto;

                $uploadtime = userdate($importtime, get_string('strftimedatetime'));

                $event->subject = get_string('importfailedcoursesubject', 'totara_completionimport');
                $event->fullmessage = get_string('importfailedcoursefullmessage', 'totara_completionimport', $uploadtime);

                // Send failure alert.
                tm_alert_send($event);
            }

            // Update processed flag and add error message.
            $params = ['timecreated' => $importtime];
            $updatesql = "UPDATE {totara_compl_import_course}
                             SET processed = 1,
                                 importerror = 1,
                                 importerrormsg = 'errorunknown'
                           WHERE timecreated = :timecreated";
            $DB->execute($updatesql, $params);

            // Mark as finished to prevent failed task re-running each cron run.
            \core\task\manager::adhoc_task_complete($this);
        }
    }
}
