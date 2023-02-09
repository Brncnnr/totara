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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_program
 */

namespace totara_program\assignment;

class plan extends base {

    const ASSIGNTYPE_PLAN = 7;

    private $userfullname;

    protected function __construct($id = 0) {
        global $DB;

        if ($id !== 0) {
            $sql = "SELECT pa.assignmenttypeid AS id, pa.programid AS programid,
                           pa.includechildren, pa.completiontime,
                           pa.completionevent, pa.completioninstance,
                           pa.completionoffsetamount, pa.completionoffsetunit
                     FROM {prog_assignment} pa
                    WHERE pa.id = :assignmentid";

            $record = $DB->get_record_sql($sql, ['assignmentid' => $id]);

            // Load into object.
            $this->id = $id;
            $this->programid = $record->programid;
            $this->includechildren = $record->includechildren;
            $this->completiontime = $record->completiontime;
            $this->completionoffsetamount = $record->completionoffsetamount;
            $this->completionoffsetunit = $record->completionoffsetunit;
            $this->completionevent = $record->completionevent;
            $this->completioninstance = $record->completioninstance;

            $this->typeid = self::ASSIGNTYPE_PLAN;
            $this->instanceid = $record->id;
        }
    }

    /**
     * Should the assignment type be used via the user interface?
     *
     * @return bool
     */
    public static function show_in_ui() : bool {
        // learning plan assignment types have no user interface within the program assignment page. They are configured
        // via the learning plan only.
        return false;
    }

    /**
     * Can the assignment be updated?
     *
     * @param int $programid
     * @return bool
     */
    public static function can_be_updated(int $programid) : bool {
        // Learning Plan assignments are different. They have no front-end and are syncing assignments that have already
        // gone though the relevant capability checks from withing the learning plan module.
        return true;
    }

    /**
     * Get type for this assignment
     */
    public function get_type(): int {
        return self::ASSIGNTYPE_PLAN;
    }

    /**
     * Get the plan name
     *
     * @return String
     */
    public function get_name(): string {
        global $DB;

        $name = $DB->get_field('dp_plan', 'name', ['id' => $this->instanceid]);
        return format_string($name);
    }

    /**
     * Return learner count, for plan this is always 1 or 0 as a plan can only ever have 1 user
     *
     * @return int
     */
    public function get_user_count() : int {
        global $DB;

        $sql = "SELECT COUNT(u.id)
                  FROM {dp_plan} p
                  JOIN {dp_plan_program_assign} ppa on ppa.planid = p.id
                  JOIN {user} u ON u.id = p.userid
                 WHERE p.id = :assignmenttypeid
                   AND ppa.programid = :programid
                   AND (p.status = :status_approved OR p.status = :status_complete)
                   AND ppa.approved = :approved
                   AND u.deleted = 0";

        $params = [
            'programid' => $this->get_programid(),
            'assignmenttypeid' => $this->get_instanceid(),
            'status_approved' => DP_PLAN_STATUS_APPROVED,
            'status_complete' => DP_PLAN_STATUS_COMPLETE,
            'approved' => DP_APPROVAL_APPROVED,
        ];

        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Update all program assigments for the given user and plan
     *
     * @param int $userid
     * @param int $planid
     * @return bool
     */
    public static function update_plan_assignments(int $userid, int $planid) : bool {
        global $DB;

        // Get existing assigned programs via the learning plan.
        $currentassigments = self::get_user_assignments($userid, $planid);

        // Flip so we get array of programid => assignmentid
        $currentassigments = array_flip($currentassigments);

        foreach (self::get_users_plan_programs($userid, $planid) as $programid) {
            if (isset($currentassigments[$programid])) {
                // Already assigned, remove from $currentassigments so we can delete any left later.
                unset($currentassigments[$programid]);
            } else {
                // Add new assignment.
                $assignment = self::create_from_instance_id($programid, ASSIGNTYPE_PLAN, $planid);
                $assignment->save();
            }
        }

        // Remove any assignments that are no longer part of the plan.
        foreach ($currentassigments as $programid => $assignmentid) {
            // NOTE: Can't use $assignment->remove() here as get_affected_users_by_assignment() will not return the
            // user to remove as the plan may have already been deleted or the program removed.

            // Does the user have other assignments for the program?
            $sql = "SELECT 1
                      FROM {prog_user_assignment}
                     WHERE programid = :programid
                       AND assignmentid != :assignmentid
                       AND userid = :userid";
            $otherassignments = $DB->record_exists_sql($sql, ['programid' => $programid, 'assignmentid' => $assignmentid, 'userid' => $userid]);

            if (!$otherassignments) {
                $program = new \program($programid);
                $program->unassign_learners([$userid]);
            } else {
                $DB->delete_records('prog_user_assignment', ['assignmentid' => $assignmentid]);
            }

            $DB->delete_records('prog_assignment', ['id' => $assignmentid]);
        }

        return true;
    }

    /**
     * Get all approved programs where the plan is approved and not complete
     *
     * @param int $userid
     * @param int $planid
     * @return array
     */
    public static function get_users_plan_programs(int $userid, int $planid) : array {
        global $DB;

        $sql = "SELECT ppa.id, ppa.programid
                  FROM {dp_plan_program_assign} ppa
                  JOIN {dp_plan} p ON p.id = ppa.planid
                  JOIN {user} u ON u.id = p.userid
                  JOIN {prog} prog ON prog.id = ppa.programid
                 WHERE p.id = :planid
                   AND u.id = :userid
                   AND prog.certifid IS NULL
                   AND u.deleted = 0
                   AND ppa.approved = :approval_approved
                   AND p.status = :status_approved
                   AND p.status != :status_complete";

        $params = [
            'planid' => $planid,
            'userid' => $userid,
            'status_approved' => DP_PLAN_STATUS_APPROVED,
            'status_complete' => DP_PLAN_STATUS_COMPLETE,
            'approval_approved' => DP_APPROVAL_APPROVED,
        ];

        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Get all the programs that the given user is assigned to via the plan
     *
     * @param int $userid
     * @param int $planid
     * @return array assignmentid => programid
     */
    public static function get_user_assignments(int $userid, int $planid) : array {
        global $DB;

        $sql = "SELECT pa.id, pa.programid
                  FROM {prog_assignment} pa
                  JOIN {prog_user_assignment} pua ON pua.assignmentid = pa.id
                 WHERE pua.userid = :userid
                   AND pa.assignmenttypeid = :planid
                   AND pa.assignmenttype = :assignmenttype";

        $params = [
            'userid' => $userid,
            'planid' => $planid,
            'assignmenttype' => self::ASSIGNTYPE_PLAN
        ];

        return $DB->get_records_sql_menu($sql, $params);
    }
}
