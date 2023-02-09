<?php
/**
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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\data_providers\activity;

use appraisal;
use coding_exception;
use core\orm\query\builder;
use dml_exception;
use feedback360;
use moodle_exception;
use moodle_url;
use totara_core\advanced_feature;

class other_historic_activities {

    /**
     * Get all appraisals for other users that current user can view
     *
     * @param int $userid
     * @return array
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function get_appraisals(int $userid): array {
        global $CFG;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');
        require_once($CFG->dirroot . '/totara/feedback360/lib.php');

        $data = [];
        if (advanced_feature::is_disabled('appraisals') ||
            !(appraisal::can_view_own_appraisals($userid) || appraisal::can_view_staff_appraisals($userid))) {
            return $data;
        }

        $roles = appraisal::get_roles();
        unset($roles[appraisal::ROLE_LEARNER]);
        foreach ($roles as $role => $value) {
            $appraisals = appraisal::get_user_appraisals_extended($userid, $role);
            foreach ($appraisals as $appraisal) {
                $params = [
                    'role' => $role,
                    'subjectid' => $appraisal->userid,
                    'appraisalid' => $appraisal->id,
                    'action' => 'stages'
                ];
                $appraisal_link = new moodle_url('/totara/appraisal/myappraisal.php', $params);
                $data[] = [
                    'activity_name' => format_string($appraisal->name),
                    'activity_link' => $appraisal_link->out(false),
                    'type' => get_string('appraisal_legacy', 'totara_appraisal'),
                    'subject_user' => fullname($appraisal->user),
                    'relationship_to' => get_string($value, 'totara_appraisal'),
                    'status' => appraisal::display_status($appraisal->status)
                ];
            }
        }
        return $data;
    }

    /**
     * Get all feedbacks for other users that current user can view
     *
     * @param int $userid
     * @return array
     * @throws dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function get_feedbacks(int $userid): array {
        global $CFG;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');
        require_once($CFG->dirroot . '/totara/feedback360/lib.php');

        $data = [];
        if (advanced_feature::is_disabled('feedback360') || !feedback360::can_view_feedback360s($userid)) {
            return $data;
        }

        $usernamefields = get_all_user_name_fields(true, 'u');
        $usernamefields_array = explode(',', $usernamefields);
        $what = ['re.*', 'fb.name', 'ua.timedue', 'ua.userid'];
        $what = array_merge($what, $usernamefields_array);

        $resp_assignments = builder::table('feedback360_resp_assignment', 're')
            ->select($what)
            ->join(['feedback360_user_assignment', 'ua'], 're.feedback360userassignmentid', 'ua.id')
            ->join(['feedback360', 'fb'], 'ua.feedback360id', 'fb.id')
            ->join(['user', 'u'], 'ua.userid', 'u.id')
            ->where('re.userid', $userid)
            ->where_raw('re.userid <> ua.userid')
            ->get();

        foreach ($resp_assignments as $resp_assignment) {
            $feedback_link = new moodle_url('/totara/feedback360/index.php');
            $data[] = [
                'activity_name' => format_string($resp_assignment->name),
                'activity_link' => $feedback_link->out(false),
                'type' => get_string('feedback360:utf8', 'totara_feedback360'),
                'subject_user' => fullname($resp_assignment),
                'relationship_to' => get_string('manager', 'totara_feedback360'),
                'status' => self::get_feedback_status($resp_assignment)
            ];
        }
        return $data;
    }

    /**
     * Get user feedback360 status depending from timecompleted or timedue
     *
     * @param $resp_assignment
     * @return string
     */
    private static function get_feedback_status($resp_assignment): string {
        if (!empty($resp_assignment->timecompleted)) {
            // Completed
            $status = get_string('completed', 'totara_feedback360');
        } else {
            if (empty($resp_assignment->timedue)) {
                // Infinite time.
                $status = '';
            } else if ($resp_assignment->timedue < time()) {
                // Overdue.
                $status = get_string('overdue', 'totara_feedback360');
            } else {
                // Pending.
                $status = get_string('pending', 'totara_feedback360');
            }
        }
        return $status;
    }
}
