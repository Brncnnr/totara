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
 * @package totara_plan
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/plan/lib.php');

require_login();

// Check if Learning plans are enabled.
check_learningplan_enabled();

// Check sesskey.
if (!confirm_sesskey()) {
    print_error('confirmsesskeybad', 'error');
}

$userid = optional_param('userid', $USER->id, PARAM_INT);
$planid = required_param('planid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

$plan = new development_plan($planid);

// Check the plan can be viewed.
if (!$plan->can_view()) {
    print_error('error:nopermissions', 'totara_plan');
}

// Check the course is in the plan.
if (!$DB->record_exists('dp_plan_course_assign', ['planid' => $planid, 'courseid' => $courseid])) {
    print_error('error:itemnotinplan', 'totara_plan');
}

if ($CFG->audiencevisibility && $DB->record_exists('course', ['audiencevisible' => COHORT_VISIBLE_NOUSERS])) {
    print_error('coursehidden');
}

$url = new moodle_url('/course/view.php', ['id' => $courseid]);

// Check $user == $USER, don't let managers trigger users enrolments.
if ($userid != $USER->id) {
    // It's not the learner so lets just redirect to the course.
    redirect($url);
}

// Check for existing enrolment, no point trying to enrol someone who is already enrolled.
$sql = "SELECT ue.id
          FROM {user_enrolments} ue
          JOIN {enrol} e
            ON ue.enrolid = e.id
         WHERE ue.userid = :uid
           AND e.courseid = :cid";
$params = ['uid' => $userid, 'cid' => $courseid];
if ($DB->record_exists_sql($sql, $params)) {
    redirect($url);
}

// Run the enrolments, note: taken from require_login().
$params = ['courseid' => $courseid, 'status' => ENROL_INSTANCE_ENABLED];
$instances = $DB->get_records('enrol', $params, 'sortorder, id ASC');
$enrols = enrol_get_plugins(true);
// First ask all enabled enrol instances in course if they want to auto enrol user.
foreach ($instances as $instance) {
    if (!isset($enrols[$instance->enrol])) {
        continue;
    }
    // Get a duration for the enrolment, a timestamp in the future, 0 (always) or false.
    $until = $enrols[$instance->enrol]->try_autoenrol($instance);
    if ($until !== false) {
        if ($until == 0) {
            $until = ENROL_MAX_TIMESTAMP;
        }
        $USER->enrol['enrolled'][$courseid] = $until;
        break;
    }
}

// Finally redirect to the course view page.
redirect($url);