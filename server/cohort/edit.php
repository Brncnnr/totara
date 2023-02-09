<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Cohort related management functions, this file needs to be included manually.
 *
 * @package    core_cohort
 * @copyright  2010 Petr Skoda  {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/cohort/edit_form.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

$usetags = (!empty($CFG->usetags));
if ($usetags) {
    require_once($CFG->dirroot.'/tag/lib.php');
}

$id        = optional_param('id', 0, PARAM_INT);
$contextid = optional_param('contextid', 0, PARAM_INT);

require_login();

if ($id) {
    $cohort = $DB->get_record('cohort', array('id' => $id), '*');
    if (!$cohort) {
        $url = new moodle_url('/cohort/index.php');
        redirect($url, get_string('error:badcohortid','totara_cohort'), null, \core\notification::ERROR);
    }
    if ($usetags) {
        $cohort->tags = core_tag_tag::get_item_tags_array('core', 'cohort', $cohort->id,
            \core_tag_tag::BOTH_STANDARD_AND_NOT, 0, false); // Totara: Do not encode the special characters.
    }
    $context = context::instance_by_id($cohort->contextid, MUST_EXIST);
} else {
    $context = context::instance_by_id($contextid, MUST_EXIST);
    if ($context->contextlevel != CONTEXT_COURSECAT and $context->contextlevel != CONTEXT_SYSTEM) {
        print_error('invalidcontext');
    }
    $cohort = new stdClass();
    $cohort->id          = 0;
    $cohort->contextid   = $context->id;
    $cohort->name        = '';
    $cohort->description = '';
    $cohort->tags = array();
    $cohort->cohorttype  = cohort::TYPE_STATIC;
}

require_capability('moodle/cohort:manage', $context);

$returnurl = new moodle_url('/cohort/index.php', array('contextid' => $context->id));

if (!empty($cohort->component)) {
    // We can not manually edit cohorts that were created by external systems, sorry.
    redirect($returnurl);
}

$PAGE->set_context($context);
$baseurl = new moodle_url('/cohort/edit.php', array('contextid' => $context->id, 'id' => $cohort->id));
if ($context->contextlevel == CONTEXT_SYSTEM) {
    admin_externalpage_setup('cohorts', '', [], $baseurl);
} else {
    $PAGE->set_url($baseurl);
    $PAGE->set_pagelayout('admin');
    $PAGE->set_heading($COURSE->fullname);
}
if ($cohort->id) {
    // Edit existing.
    $strheading = get_string('editcohort', 'totara_cohort');
    $PAGE->set_title($cohort->name . ' : ' . $strheading);
} else {
    // Add new.
    $strheading = get_string('addcohort', 'totara_cohort');
    $PAGE->set_title($strheading);
}

if ($context->contextlevel == CONTEXT_COURSECAT) {
    navigation_node::override_active_url($returnurl);
} else {
    navigation_node::override_active_url(new moodle_url('/cohort/index.php'));
}
totara_cohort_navlinks($cohort->id, format_string($cohort->name), $strheading);

$editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $SITE->maxbytes, 'context' => $context);
$cohort = file_prepare_standard_editor($cohort, 'description', $editoroptions, $context, 'cohort', 'description',
    ($cohort->id ?: null));
$editform = new cohort_edit_form(null, array('editoroptions' => $editoroptions, 'data' => $cohort));
if ($editform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    $oldcontextid = $context->id;
    $editoroptions['context'] = $context = context::instance_by_id($data->contextid);

    if ($data->id) {
        if ($data->contextid != $oldcontextid) {
            // Cohort was moved to another context.
            get_file_storage()->move_area_files_to_new_context($oldcontextid, $context->id,
                    'cohort', 'description', $data->id);
        }
        $data = file_postupdate_standard_editor($data, 'description', $editoroptions,
                $context, 'cohort', 'description', $data->id);
        cohort_update_cohort($data);
        $data->cohorttype = $cohort->cohorttype;
        $message = get_string('successfullyupdated', 'totara_cohort');
    } else {
        $data->descriptionformat = $data->description_editor['format'];
        $data->description = $description = $data->description_editor['text'];
        $data->id = cohort_add_cohort($data);
        $data = file_postupdate_standard_editor($data, 'description', $editoroptions,
                $context, 'cohort', 'description', $data->id);
        if ($description != $data->description) {
            $updatedata = (object)array('id' => $data->id,
                'description' => $data->description, 'contextid' => $context->id);
            cohort_update_cohort($updatedata);
        }
        if (!isset($data->cohorttype)) {
            $data->cohorttype = \cohort::TYPE_STATIC;
        }
        $message = get_string('successfullyaddedcohort', 'totara_cohort');
    }
    // Totara: handle tags and go to relevant page after insert.
    if ($usetags) {
        if (isset($data->tags)) {
            core_tag_tag::set_item_tags('core', 'cohort', $data->id, $context, $data->tags);
        }
    }
    if ($data->cohorttype == cohort::TYPE_STATIC && has_capability('moodle/cohort:assign', $context)) {
        $url = new moodle_url('/cohort/assign.php', array('id' => $data->id));
    } else if (has_capability('totara/cohort:managerules', $context)) {
        $url = new moodle_url('/totara/cohort/rules.php', array('id' => $data->id));
    } else {
        $url = new moodle_url('/cohort/view.php', array('id' => $data->id));
    }
    redirect($url, $message, null, \core\notification::SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($strheading);
if ((int)$cohort->id != 0) {
    echo cohort_print_tabs('edit', $cohort->id, $cohort->cohorttype, $cohort);
}

if (!$id && ($editcontrols = cohort_edit_controls($context, $baseurl))) {
    echo $OUTPUT->render($editcontrols);
}

echo $editform->display();
echo $OUTPUT->footer();

