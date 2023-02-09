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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package    totara_completionimport
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * @group totara_completionimport
 */
abstract class completionimport_advanced_testcase extends reportcache_advanced_testcase {

    public function import($content, $importname, $importstart) {
        \totara_completionimport\csv_import::basic_import($content, $importname, $importstart);

        // Run adhoc task to process imported data.
        if ($importname === 'course') {
            $adhoctask = new \totara_completionimport\task\import_course_completions_task();
        } else if ($importname === 'certification') {
            $adhoctask = new \totara_completionimport\task\import_certification_completions_task();
        } else {
            debugging("Failed to import for importname: " . $importname, DEBUG_DEVELOPER);
            return false;
        }

        $adhoctask->set_custom_data(['importname' => $importname, 'importtime' => $importstart, 'create_evidence' => 1]);

        \core\task\manager::queue_adhoc_task($adhoctask);
        $this->executeAdhocTasks();
    }
}