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
 * @package totara_program
 */

namespace totara_program\task;

use totara_core\advanced_feature;

/**
 * Cleans up program assignments where the item (audience, prog, org, etc) they
 * are related to has been deleted from the system
 */
class clean_program_assignments_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanprogramassignmentstask', 'totara_program');
    }

    /**
     * Removes program assignments if the item they associated with no longer
     * exists or is flagged as deleted. This help maintain consistency if a triggered
     * event doesn't run or is interrupted.
     */
    public function execute() {
        global $DB;

        if (advanced_feature::is_enabled('programs') || advanced_feature::is_enabled('certifications')) {
            // Cohort
            $sql = 'SELECT pa.id FROM {prog_assignment} pa WHERE pa.assignmenttype = :cohorttype AND NOT EXISTS (SELECT 1 FROM {cohort} c WHERE pa.assignmenttypeid = c.id)';
            $params = ['cohorttype' => \totara_program\assignment\cohort::ASSIGNTYPE_COHORT];
            $cohort_assignments = $DB->get_fieldset_sql($sql, $params);
            foreach ($cohort_assignments as $key => $assignmentid) {
                $assignment = \totara_program\assignment\cohort::create_from_id($assignmentid);
                $assignment->remove();
            }

            // Position
            $sql = 'SELECT pa.id FROM {prog_assignment} pa WHERE pa.assignmenttype = :positiontype AND NOT EXISTS (SELECT 1 FROM {pos} p WHERE pa.assignmenttypeid = p.id)';
            $params = ['positiontype' => \totara_program\assignment\position::ASSIGNTYPE_POSITION];
            $position_assignments = $DB->get_fieldset_sql($sql, $params);
            foreach ($position_assignments as $key => $assignmentid) {
                $assignment = \totara_program\assignment\position::create_from_id($assignmentid);
                $assignment->remove();
            }

            // Organisation
            $sql = 'SELECT pa.id FROM {prog_assignment} pa WHERE pa.assignmenttype = :organisationtype AND NOT EXISTS (SELECT 1 FROM {org} o WHERE pa.assignmenttypeid = o.id)';
            $params = ['organisationtype' => \totara_program\assignment\organisation::ASSIGNTYPE_ORGANISATION];
            $organisation_assignments = $DB->get_fieldset_sql($sql, $params);
            foreach ($organisation_assignments as $key => $assignmentid) {
                $assignment = \totara_program\assignment\organisation::create_from_id($assignmentid);
                $assignment->remove();
            }

            // Manager hierarchy
            $sql = 'SELECT pa.id FROM {prog_assignment} pa WHERE pa.assignmenttype = :managertype AND NOT EXISTS (SELECT 1 FROM {job_assignment} ja WHERE pa.assignmenttypeid = ja.id)';
            $params = ['managertype' => \totara_program\assignment\manager::ASSIGNTYPE_MANAGER];
            $manager_assignments = $DB->get_fieldset_sql($sql, $params);
            foreach ($manager_assignments as $key => $assignmentid) {
                $assignment = \totara_program\assignment\manager::create_from_id($assignmentid);
                $assignment->remove();
            }

            // Individual
            $sql = 'SELECT pa.id FROM {prog_assignment} pa WHERE pa.assignmenttype = :individualtype AND NOT EXISTS (SELECT 1 FROM {user} u WHERE pa.assignmenttypeid = u.id AND u.deleted = 0)';
            $params = ['individualtype' => \totara_program\assignment\individual::ASSIGNTYPE_INDIVIDUAL];
            $individual_assignments = $DB->get_fieldset_sql($sql, $params);
            foreach ($individual_assignments as $key => $assignmentid) {
                $assignment = \totara_program\assignment\individual::create_from_id($assignmentid);
                $assignment->remove();
            }

            // Learning plan
            if (advanced_feature::is_enabled('learningplans')) {
                $sql = 'SELECT pa.id FROM {prog_assignment} pa WHERE pa.assignmenttype = :plantype AND NOT EXISTS (SELECT 1 FROM {dp_plan} p WHERE pa.assignmenttypeid = p.id)';
                $params = ['plantype' => \totara_program\assignment\plan::ASSIGNTYPE_PLAN];
                $manager_assignments = $DB->get_fieldset_sql($sql, $params);
                foreach ($manager_assignments as $key => $assignmentid) {
                    $assignment = \totara_program\assignment\plan::create_from_id($assignmentid);
                    $assignment->remove();
                }
            }
        }
    }
}
