<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package totara_appraisal
 */
namespace totara_appraisal\task;

defined('MOODLE_INTERNAL') || die();

use \core\task\adhoc_task;
use \core\task\manager;

/**
 * Updates learner assignments for a given appraisal. This is different from
 * the update_learner_assignments_task which updates *all* appraisals as needed.
 */
final class update_assignments_adhoc_task extends adhoc_task {
    /**
     * Creates an instance of the adhoc task to update the given appraisal.
     *
     * @param int $appraisal_id appraisal whose assignments are to be updated.
     *
     * @return bool true if the task was enqueued successfully.
     */
    public static function enqueue(int $appraisal_id): bool {
        if (update_assignments_adhoc_task::is_queued($appraisal_id)) {
            return true;
        }

        $task = new update_assignments_adhoc_task();
        $task->set_custom_data($appraisal_id);
        $task->set_component('totara_appraisal');

        return manager::queue_adhoc_task($task);
    }

    /**
     * Indicates whether there is an adhoc task has already been queued for the
     * given appraisal id.
     *
     * @param int $appraisal_id appraisal whose adhoc task is to be checked.
     *
     * @return bool True if there already is an adhoc task for the appraisal.
     */
    public static function is_queued($appraisal_id) {
        // This has to check against the DB table because \core\task\manager and
        // \core\task\adhoc_task do not have APIs do so. A terrible hack, but no
        // choice.
        global $DB;

        $appraisal_filter = $DB->sql_compare_text('customdata');
        $class_filter = $DB->sql_like('classname', ':task_class');
        $sql = "
            SELECT 1
            FROM {task_adhoc}
            WHERE $appraisal_filter = :appraisal_id
            AND component = :component
            AND $class_filter
        ";
        $filters = [
            'appraisal_id' => $appraisal_id,
            'component' => 'totara_appraisal',
            'task_class' => '%update_assignments_adhoc_task'
        ];

        return $DB->record_exists_sql($sql, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function execute() {
        // Since this is an adhoc task, it can run when no there are scheduled
        // appraisal tasks to run during this time. Hence this require statement
        // here; otherwise there will be a "unknown class - appraisal" error.
        global $CFG;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');

        $id = $this->get_custom_data();
        $appraisal = new \appraisal($id);

        mtrace("[totara_appraisal] doing user assignments for appraisal '$id'");
        $appraisal->check_assignment_changes();
    }
}
