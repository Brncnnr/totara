<?php
/*
 * This file is part of Totara LMS
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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort\task;

use coding_exception;

/**
 * Update cohort roles.
 */
class update_cohort_roles_task extends \core\task\adhoc_task {

    /**
     * @return \lang_string|string
     */
    public function get_name() {
        return get_string('taskupdatecohortroles', 'totara_cohort');
    }

    /**
     * Update cohort roles
     */
    public function execute() {
        global $CFG, $DB;
        require_once("$CFG->dirroot/totara/cohort/lib.php");
        require_once("$CFG->dirroot/lib/accesslib.php");

        $custom_data = $this->get_custom_data();
        $cohort_id = $custom_data->cohort_id;
        $current_roles = totara_get_cohort_roles($cohort_id);

        if (empty($cohort_id)) {
            throw new coding_exception('No cohort id set.');
        }

        if (!empty($current_roles)) {
            // Get cohort record.
            $cohort = $DB->get_record('cohort', array('id' => $cohort_id));
            $cohort_context = \context::instance_by_id($cohort->contextid);

            // Get members of the cohort.
            $memberids = array();
            if ($members = totara_get_members_cohort($cohort_id)) {
                $memberids = array_keys($members);
            }

            // Unassign roles.
            totara_unassign_roles_cohort($current_roles, $cohort_id, $memberids);

            // Assign here the current assigned roles IF they are valid roles to be assigned in the new cohort context.
            $roles_to_assign = [];
            $assignable_roles = get_assignable_roles($cohort_context, ROLENAME_BOTH, false);
            foreach ($assignable_roles as $key => $role_name) {
                if (isset($current_roles[$key])) {
                    $roleobj = new \stdClass();
                    $roleobj->roleid = $key;
                    $roleobj->contextid = $cohort_context->id;
                    $roles_to_assign[$key] = $roleobj;
                }
            }

            // Assign roles to cohort.
            if (!empty($roles_to_assign)) {
                totara_assign_roles_cohort($roles_to_assign, $cohort_id, $memberids);
            }

            // Give some info and context about this task.
            if (defined('PHPUNIT_TEST')) {
                $trace = new \null_progress_trace();
            } else {
                $trace = new \text_progress_trace();
            }

            $trace->output('Audience roles updated for Audience ID '.$cohort_id);
        }
    }
}

