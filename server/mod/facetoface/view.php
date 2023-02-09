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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */

use mod_facetoface\dashboard\filter_list;
use mod_facetoface\seminar;
use mod_facetoface\output\seminarevent_dashboard;

require_once '../../config.php';
require_once $CFG->dirroot . '/mod/facetoface/lib.php';
require_once $CFG->dirroot . '/mod/facetoface/renderer.php';
require_once($CFG->dirroot . '/totara/customfield/field/location/field.class.php');

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$f = optional_param(filter_list::PARAM_FILTER_F2FID, 0, PARAM_INT); // facetoface ID
// Only admins can see debug information.
$debug = optional_param('debug', false, PARAM_BOOL) && is_siteadmin();

$filters = filter_list::from_query_params();

if ($id) {
    if (!$cm = get_coursemodule_from_id('facetoface', $id)) {
        print_error('error:incorrectcoursemoduleid', 'facetoface');
    }
    $seminar = new seminar($cm->instance);
} else if ($f) {
    $seminar = new seminar($f);
    $cm = $seminar->get_coursemodule();
} else {
    print_error('error:mustspecifycoursemodulefacetoface', 'facetoface');
}
if (!$course = $DB->get_record('course', array('id' => $seminar->get_course()))) {
    print_error('error:coursemisconfigured', 'facetoface');
}

$context = context_module::instance($cm->id);
$PAGE->set_url($filters->to_url($seminar));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');

// Check for auto nofication duplicates.
if (has_capability('moodle/course:manageactivities', $context)) {
    require_once($CFG->dirroot.'/mod/facetoface/notification/lib.php');
    if (facetoface_notification::has_auto_duplicates($seminar->get_id())) {
        $url = new moodle_url('/mod/facetoface/notification/index.php', array('update' => $cm->id));
        \core\notification::error(get_string('notificationduplicatesfound', 'facetoface', $url->out()));
    }
}

require_login($course, true, $cm);
require_capability('mod/facetoface:view', $context);

$event = \mod_facetoface\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot($cm->modname, $seminar->get_properties());
$event->trigger();

$title = format_string($seminar->get_name());

$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

/** @var mod_facetoface_renderer $f2f_renderer */
$f2f_renderer = $PAGE->get_renderer('mod_facetoface');
$f2f_renderer->setcontext($context);

$completion = new \completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header();

if (empty($cm->visible) and !has_capability('mod/facetoface:viewemptyactivities', $context)) {
    notice(get_string('activityiscurrentlyhidden'));
}

echo $f2f_renderer->render_from_template(
    seminarevent_dashboard::TEMPLATE_NAME,
    seminarevent_dashboard::create($seminar, $context, $cm, $course, $filters, $debug)->export_for_template($f2f_renderer)
);

echo $OUTPUT->footer();
