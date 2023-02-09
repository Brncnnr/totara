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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_completionimport
 */

use core_phpunit\testcase;
use totara_completionimport\task\clean_certification_completion_upload_logs_task;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/completionimport/tests/completionimport_advanced_testcase.php');

/**
 * Class totara_completionimport_clean_cert_data_task_testcase
 *
 * @group totara_completionimport
 */
class totara_completionimport_clean_cert_data_task_testcase extends testcase {

    public function test_task() {
        global $DB;

        self::setAdminUser();

        // Insert some fake records.
        $a_day_ago = time() - DAYSECS;
        $a_week_ago = time() - WEEKSECS;
        $DB->insert_records('totara_compl_import_cert', [
            [
                'timecreated' => $a_day_ago,
                'timeupdated' => $a_day_ago,
                'importuserid' => 2,
                'importerror' => 0,
                'rownumber' => 1,
                'importevidence' => 0,
            ],
            [
                'timecreated' => $a_week_ago,
                'timeupdated' => $a_week_ago,
                'importuserid' => 2,
                'importerror' => 0,
                'rownumber' => 1,
                'importevidence' => 0,
            ]
        ]);

        // Set cert log lifetime to 2 days.
        $log_lifetime = 2;
        set_config('certificationloglifetime', $log_lifetime, 'complrecords');

        self::assertEquals(2, $DB->count_records('totara_compl_import_cert'));

        // Check with disabled completion import - no change in record count expected.
        advanced_feature::disable('completionimport');
        ob_start();
        $task = new clean_certification_completion_upload_logs_task();
        $task->execute();
        ob_end_clean();
        self::assertEquals(2, $DB->count_records('totara_compl_import_cert'));

        advanced_feature::enable('completionimport');
        ob_start();
        $task = new clean_certification_completion_upload_logs_task();
        $task->execute();
        ob_end_clean();
        self::assertEquals(1, $DB->count_records('totara_compl_import_cert'));
        self::assertTrue($DB->record_exists('totara_compl_import_cert', ['timecreated' => $a_day_ago]));
    }
}
