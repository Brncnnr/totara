<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

use totara_competency\linked_courses;

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/evidenceitem/type/abstract.php');


///
/// Setup / loading data
///

$sitecontext = context_system::instance();

// Get params
$id     = required_param('id', PARAM_INT);
// Delete confirmation hash
$delete = optional_param('delete', '', PARAM_ALPHANUM);
// Course id (if coming from the course view)
$course = optional_param('course', 0, PARAM_INT);

// Check if Competencies are enabled.
competency::check_feature_enabled();

$sql =
    "SELECT cc.id as criteria_id,
            cc.itemtype as criteria_type,
            cc.iteminstance as course_id,
            cc.timemodified,
            comp.id as competency_id,
            comp.fullname as competency_name,
            course.fullname as course_name
       FROM {comp_criteria} cc 
       JOIN {comp} comp
         ON cc.competencyid = comp.id
       JOIN {course} course
         ON cc.iteminstance = course.id
      WHERE cc.id = :criteriaid";
$item = $DB->get_record_sql($sql, ['criteriaid' => $id], MUST_EXIST);

// Check capabilities
require_capability('totara/hierarchy:updatecompetency', $sitecontext);

// Setup page and check permissions
admin_externalpage_setup('competencymanage');


///
/// Display page
///

$return = optional_param('returnurl', '', PARAM_LOCALURL);

// Cancel/return url
if (empty($return)) {
    $return = new moodle_url('/course/competency.php', array('id' => $$item->course));
}


if (!$delete) {
    $message = get_string('evidenceitemremovecheck', 'totara_hierarchy', format_string($item->course_name)) . html_writer::empty_tag('br') . html_writer::empty_tag('br');
        $message .= format_string($item->competency_name .' ('. $item->criteria_type.')');

    $actionurlparams = array('id' => $id, 'delete' => md5($item->timemodified), 'sesskey' => $USER->sesskey, 'returnurl' => $return);

    // If called from the course view
    if ($course) {
        $actionurlparams['course'] = $course;
    }
    $action = new moodle_url("/totara/hierarchy/prefix/competency/course/remove.php", $actionurlparams);

    echo $OUTPUT->header();

    echo $OUTPUT->confirm($message, $action, $return);

    echo $OUTPUT->footer();
    exit;
}


///
/// Delete
///

if ($delete != md5($item->timemodified)) {
    print_error('checkvariable', 'totara_hierarchy');
}

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad', 'error');
}

linked_courses::remove_linked_course($id);

$message = get_string('removedcompetencyevidenceitem', 'totara_hierarchy', format_string($item->competency_name .' ('. $item->criteria_type.')'));

\core\notification::success($message);
redirect($return);
