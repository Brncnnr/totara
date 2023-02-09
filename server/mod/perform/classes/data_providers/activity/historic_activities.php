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
use feedback360;
use moodle_exception;
use moodle_url;
use totara_core\advanced_feature;

class historic_activities {

    /**
     * Get all appraisals for user
     *
     * @param int $user_id
     * @return array
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function get_appraisals(int $user_id): array {
        global $CFG;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');
        require_once($CFG->dirroot . '/totara/feedback360/lib.php');

        $data = [];

        if (advanced_feature::is_disabled('appraisals') || !appraisal::can_view_own_appraisals($user_id)) {
            return $data;
        }

        $appraisals = appraisal::get_user_appraisals_extended($user_id, appraisal::ROLE_LEARNER);
        foreach ($appraisals as $appraisal) {
            $params = [
                'role' => appraisal::ROLE_LEARNER,
                'subjectid' => $appraisal->userid,
                'appraisalid' => $appraisal->id,
                'action' => 'stages'
            ];
            $appraisal_link = new moodle_url('/totara/appraisal/myappraisal.php', $params);

            $data[] = [
                'activity_name' => format_string($appraisal->name),
                'activity_link' => $appraisal_link->out(false),
                'type' => get_string('appraisal_legacy', 'totara_appraisal'),
                'status' => appraisal::display_status($appraisal->status)
            ];
        }
        return $data;
    }

    /**
     * Get all feedbacks for user
     *
     * @param int $user_id
     * @return array
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function get_feedbacks(int $user_id): array {
        global $CFG;
        require_once($CFG->dirroot . '/totara/appraisal/lib.php');
        require_once($CFG->dirroot . '/totara/feedback360/lib.php');

        $data = [];

        if (advanced_feature::is_disabled('feedback360') || !feedback360::can_view_feedback360s($user_id)) {
            return $data;
        }

        // Join the user assignment to the feedback360 so we have the name later.
        $user_assignments = builder::table('feedback360_user_assignment', 'ua')
            ->join(['feedback360', 'fb'], 'fb.id', 'ua.feedback360id')
            ->select(['ua.*', 'fb.name', 'fb.anonymous', 'fb.status'])
            ->where('ua.userid', $user_id)
            ->get();

        foreach ($user_assignments as $user_assignment) {
            $feedback_link = new moodle_url('/totara/feedback360/index.php');
            $data[] = [
                'activity_name' => format_string($user_assignment->name),
                'activity_link' => $feedback_link->out(false),
                'type' => get_string('feedback360:utf8', 'totara_feedback360'),
                'status' => feedback360::display_status($user_assignment->status)
            ];
        }
        return $data;
    }
}
