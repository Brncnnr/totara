<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_completionimport
 */

namespace totara_completionimport\task;

use totara_core\advanced_feature;

class import_certification_completions_task extends \core\task\adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('importcertificationcompletionstask', 'totara_completionimport');
    }

    /**
     * Import pending certification completion records
     */
    public function execute() {
        global $DB, $CFG, $OUTPUT;

        if (advanced_feature::is_enabled('certifications')) {
            require_once($CFG->dirroot . '/totara/completionimport/lib.php');
            require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

            // Get customdata for adhoc task
            $customdata = $this->get_custom_data();
            $importname = $customdata->importname;
            $importtime = $customdata->importtime;
            $create_evidence = $customdata->create_evidence ?? 0;
            $evidence_type_id = $customdata->evidence_type_id ?? 0;

            // Get the users who uploaded certification completion in the given time to notify them after importing.
            $userstonotify = \totara_completionimport\helper::get_list_of_import_users('certification', $importtime);

            import_data_checks($importname, $importtime);
            import_data_adjustments($importname, $importtime);

            $numerrors = $DB->count_records('totara_compl_import_cert', ['timecreated' => $importtime, 'importerror' => 1]);

            try {
                if ($create_evidence) {
                    // Put into evidence any courses / certifications not found.
                    create_evidence($importname, $importtime, $evidence_type_id);
                }

                // Run the specific course enrolment / certification assignment.
                $functionname = 'import_' . $importname;
                $functionname($importname, $importtime);

                PHPUNIT_TEST || mtrace("Certification completion import with timestamp '$importtime' processed");

                // Update processed flag flag of records
                $params = ['timecreated' => $importtime];
                $DB->execute("UPDATE {totara_compl_import_cert} SET processed = 1 WHERE timecreated = :timecreated", $params);

                // Purge the progress caches to ensure course and program progress is re-calcuated
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
                        ['importname' => $importname, 'timecreated' => $importtime, 'importuserid' => $userto->id, 'clearfilters' => 1]);
                    $a->reportlink = $OUTPUT->action_link($reporturl, get_string('report_certification', 'totara_completionimport'));

                    if ($numerrors) {
                        $a->numerrors = $numerrors;
                        $event->subject = get_string('importsuccessfulwitherrorscertsubject', 'totara_completionimport');
                        $event->fullmessage = get_string('importsuccessfulwitherrorscertfullmessage', 'totara_completionimport', $a);
                    } else {
                        $event->subject = get_string('importsuccessfulcertsubject', 'totara_completionimport');
                        $event->fullmessage = get_string('importsuccessfulcertfullmessage', 'totara_completionimport', $a);
                    }

                    // Send success alert
                    tm_alert_send($event);
                }

            } catch (\Exception $e) {
                debugging("Exception encountered in 'import_certification_completions_task' class: " . $e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());

                foreach ($userstonotify as $userto) {
                    $event = new \stdClass();
                    $event->userfrom = \core_user::get_noreply_user();
                    $event->component = 'totara_completionimport';
                    $event->userto = $userto;

                    $uploadtime = userdate($importtime, get_string('strftimedatetime'));

                    $event->subject = get_string('importfailedcertsubject', 'totara_completionimport');
                    $event->fullmessage = get_string('importfailedcertfullmessage', 'totara_completionimport', $uploadtime);

                    // Send failure alert
                    tm_alert_send($event);
                }

                // Update processed flag and add error message
                $params = ['timecreated' => $importtime];
                $updatesql = "UPDATE {totara_compl_import_cert}
                              SET
                                processed = 1,
                                importerror = 1,
                                importerrormsg = 'errorunknown'
                              WHERE timecreated = :timecreated";
                $DB->execute($updatesql, $params);

                // Mark as finished to prevent failed task re-running each cron run.
                \core\task\manager::adhoc_task_complete($this);
            }
        }
    }
}
