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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package core_cohort
 */

require_once(dirname(dirname(__FILE__)) . '/config.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$showall = optional_param('showall', 0, PARAM_INT);
$contextid = optional_param('contextid', 1, PARAM_INT);

$cohort = $DB->get_record('cohort', array('id' => $id));
if (!$cohort) {
    print_error('error:doesnotexist', 'cohort');
}

$url = new \moodle_url('/cohort/delete.php', ['id' => $id]);

$context = context::instance_by_id($cohort->contextid, MUST_EXIST);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('noblocks');

$PAGE->set_title(get_string('delcohort', 'totara_cohort'));

require_capability('moodle/cohort:manage', $context);

if ($confirm && confirm_sesskey()) {
    $roles = totara_get_cohort_roles($cohort->id);
    // Get members of the cohort.
    $members = totara_get_members_cohort($cohort->id);
    $memberids = array_keys($members);
    // Unassign members from roles.
    totara_unset_role_assignments_cohort($roles, $cohort->id, $memberids);

    cohort_delete_cohort($cohort);
    \core\notification::success(get_string('successfullydeleted', 'totara_cohort'));

    $redirect = new \moodle_url('/cohort/index.php');
    $redirect->param('contextid', $cohort->contextid);
    $redirect->param('showall', $showall);
    redirect($redirect);
}

echo $OUTPUT->header();

// Goals and roles
$goals_count = $DB->count_records('goal_grp_cohort', ['cohortid' => $cohort->id]);
$roles_count = $DB->count_records('cohort_role', ['cohortid' => $cohort->id]);

$audience_members = totara_get_members_cohort($cohort->id);
$member_count = count($audience_members);

// Enrolled Containers
$sql = "SELECT c.containertype, COUNT(e.courseid) AS containercount FROM {enrol} e
            LEFT JOIN {course} c ON c.id = e.courseid
            WHERE e.enrol = 'cohort' AND e.customint1 = :cohortid
            GROUP BY c.containertype";
$params = ['cohortid' => $cohort->id];
$container_counts = $DB->get_records_sql($sql, $params);

$enrolled_container_counts = [];
foreach ($container_counts as $container_count) {
    $enrolled_container_counts[$container_count->containertype] = (int)$container_count->containercount;
}

// Enrolled programs / certifications
$cohort_assignment_type = \totara_program\assignment\cohort::ASSIGNTYPE_COHORT;
$sql = "SELECT * FROM {prog} p JOIN {prog_assignment} pa ON p.id = pa.programid
    WHERE pa.assignmenttype = :cohorttype AND assignmenttypeid = :audienceid";
$params = ['cohorttype' => $cohort_assignment_type, 'audienceid' => $cohort->id];

$enrolled_prog_count = 0;
$enrolled_cert_count = 0;
$enrolled_progs = $DB->get_records_sql($sql, $params);
foreach ($enrolled_progs as $prog) {
    if (empty($prog->certifid)) {
        $enrolled_prog_count++;
    } else {
        $enrolled_cert_count++;
    }
}

// Visible courses, progs, certs
$visible_course_count = $DB->count_records('cohort_visibility', ['instancetype' => COHORT_ASSN_ITEMTYPE_COURSE, 'cohortid' => $cohort->id]);
$visible_prog_count = $DB->count_records('cohort_visibility', ['instancetype' => COHORT_ASSN_ITEMTYPE_PROGRAM, 'cohortid' => $cohort->id]);
$visible_cert_count = $DB->count_records('cohort_visibility', ['instancetype' => COHORT_ASSN_ITEMTYPE_CERTIF, 'cohortid' => $cohort->id]);

$audiencedata = new \stdClass();
$audiencedata->name = format_string($cohort->name);
$audiencedata->id = $cohort->id;
$audiencedata->idnumber = $cohort->idnumber;
$audiencedata->contextid = $contextid; // This is the contextid from the url NOT the audience
$audiencedata->showall = $showall;

$audiencedata->enrolled_course_count = $enrolled_container_counts['container_course'] ?? 0;
$audiencedata->enrolled_workspace_count = $enrolled_container_counts['container_workspace'] ?? 0;
$audiencedata->enrolled_program_count = $enrolled_prog_count;
$audiencedata->enrolled_certification_count = $enrolled_cert_count;
$audiencedata->goals_count = $goals_count;
$audiencedata->roles_count = $roles_count;

$audiencedata->visible_courses = $visible_course_count;
$audiencedata->visible_progs = $visible_prog_count;
$audiencedata->visible_certs = $visible_cert_count;

$hook = new \totara_cohort\hook\delete_affects($cohort->id);
$hook->execute();
$audiencedata->affected_plugins = $hook->get_affected();

$unenrolaction = get_config('enrol_cohort', 'unenrolaction');

if ((int)$unenrolaction === 0) { // Unenrol user from course
    $audiencedata->unenrol = true;
} else {
    $audiencedata->unenrol = false;
}

$data = \totara_cohort\output\delete::create_from_audience($audiencedata);

echo $OUTPUT->render($data);

