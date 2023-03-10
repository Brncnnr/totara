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
 * Block for displaying logged in user's course completion status
 *
 * @package    block_completionstatus
 * @copyright  2009-2012 Catalyst IT Ltd
 * @author     Aaron Barnes <aaronb@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once("{$CFG->libdir}/completionlib.php");

// Load data.
$id = required_param('course', PARAM_INT);
$userid = optional_param('user', 0, PARAM_INT);

// Load course.
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

// Load user.
if ($userid) {
    $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
} else {
    $user = $USER;
}

// Check permissions.
require_login();

if (!completion_can_view_data($user->id, $course)) {
    print_error('cannotviewreport');
}

// Load completion data.
$info = new completion_info($course);

$returnurl = new moodle_url('/course/view.php', array('id' => $id));

// Don't display if completion isn't enabled.
if (!$info->is_enabled()) {
    print_error('completionnotenabled', 'completion', $returnurl);
}

// Is course complete?
$coursecomplete = $info->is_course_complete($user->id);

// Has this user completed any criteria?
$criteriacomplete = $info->count_course_user_data($user->id);

// Load course completion.
$params = array(
    'userid' => $user->id,
    'course' => $course->id,
);
$ccompletion = new completion_completion($params);

// Check this user is enrolled or otherwise has a completion status.
if (!$info->user_has_completion_status($user->id)) {
    if ($USER->id == $user->id) {
        print_error('notenroled', 'completion', $returnurl);
    } else {
        print_error('usernotenroled', 'completion', $returnurl);
    }
}

// Display page.

$PAGE->set_context(context_course::instance($course->id));
$PAGE->set_course($course);

// Print header.
$page = get_string('completionprogressdetails', 'block_completionstatus');
$title = format_string($course->fullname) . ': ' . $page;

$PAGE->navbar->add(get_string('courses'));
$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array('id'=>$course->id)));
$PAGE->navbar->add($page);
$PAGE->set_pagelayout('report');
$PAGE->set_url('/blocks/completionstatus/details.php', array('course' => $course->id, 'user' => $user->id));
$PAGE->set_title(get_string('course') . ': ' . $course->fullname);
$PAGE->set_heading($title);
echo $OUTPUT->header();


// Display completion status.
echo html_writer::start_tag('dl', array('class' => 'dl-horizontal'));

// If not display logged in user, show user name.
if ($USER->id != $user->id) {
    $userlink = fullname($user);
    $url = user_get_profile_url($user, $course);
    if ($user) {
        $userlink = html_writer::link($url, $userlink);
    }
    echo html_writer::tag('dt', get_string('showinguser', 'completion'));
    echo html_writer::tag('dd', $userlink);
}


if ($coursecomplete) {
    // Check for RPL
    // Totara: Prevent passing null as input parameter in PHP 8.1
    if (!is_null($ccompletion->rpl) && strlen($ccompletion->rpl)) {
        $statusstring = get_string('completeviarpl', 'completion');
    } else {
        $statusstring = get_string('complete');
    }

}
else {
    // [TL 8078] the original code computed status text by checking for criteria
    // completion AND the time the course started. However, for some reason when
    // a SCORM activity was already in progress (or at least viewed), the time
    // started was still 0. Which then caused the status to show "not started".
    // So now the code has been changed to show a generic "incomplete" - which
    // is always correct because of the 'if' condition this else is attached to.
    //
    // The fundamental problem here is not the SCORM module or any of the other
    // activity modules. The problem is the disconnect and the fragility of the
    // implied contracts and assumptions between courses and the activities it
    // contains. Fixing the faulty interactions between the course and activity
    // is out of scope for this fix.
    $statusstring = html_writer::tag('em', get_string('notcompleted', 'completion'));
}

echo html_writer::tag('dt', get_string('status', 'core'));
echo html_writer::tag('dd', $statusstring);

// Show RPL
// Totara: Prevent passing null as input parameter in PHP 8.1
if (isset($ccompletion) && !is_null($ccompletion->rpl) && strlen($ccompletion->rpl)) {
    echo html_writer::tag('dt', get_string('courserpl', 'completion'));
    echo html_writer::tag('dd', format_string($ccompletion->rpl));
}

// Load criteria to display
$completions = $info->get_completions($user->id);

// Check if this course has any criteria.
if (empty($completions)) {
    echo html_writer::end_tag('dl');
    echo $OUTPUT->box(get_string('nocriteriaset', 'completion'), 'noticebox');
} else {
    // Get overall aggregation method.
    $overall = $info->get_aggregation_method();

    if ($overall == COMPLETION_AGGREGATION_ALL) {
        $criteriastr = get_string('criteriarequiredall', 'completion');
    } else {
        $criteriastr = get_string('criteriarequiredany', 'completion');
    }

    echo html_writer::tag('dt', get_string('required', 'core'));
    echo html_writer::tag('dd', $criteriastr);
    echo html_writer::end_tag('dl');

    // Generate markup for criteria statuses.
    echo html_writer::start_tag('table',
            array('class' => 'generalbox logtable boxaligncenter', 'id' => 'criteriastatus', 'width' => '100%'));
    echo html_writer::start_tag('tbody');
    echo html_writer::start_tag('tr', array('class' => 'ccheader'));
    echo html_writer::tag('th', get_string('criteriagroup', 'block_completionstatus'), array('class' => 'c0 header', 'scope' => 'col'));
    echo html_writer::tag('th', get_string('criteria', 'completion'), array('class' => 'c1 header', 'scope' => 'col'));
    echo html_writer::tag('th', get_string('requirement', 'block_completionstatus'), array('class' => 'c2 header', 'scope' => 'col'));
    echo html_writer::tag('th', get_string('status'), array('class' => 'c3 header', 'scope' => 'col'));
    echo html_writer::tag('th', get_string('complete'), array('class' => 'c4 header', 'scope' => 'col'));
    echo html_writer::tag('th', get_string('completiondate', 'report_completion'), array('class' => 'c5 header', 'scope' => 'col'));
    echo html_writer::end_tag('tr');

    // Save row data.
    $rows = array();

    // Organise activity completions according to the course display order.
    // Obtain the display order of activity modules.
    $sections = $DB->get_records('course_sections', array('course' => $course->id), 'section ASC', 'id, sequence');
    $moduleorder = array();
    foreach ($sections as $section) {
        if (!empty($section->sequence)) {
            $moduleorder = array_merge(array_values($moduleorder), array_values(explode(',', $section->sequence)));
        }
    }

    $orderedcompletions = array();
    $modulecriteria = array();
    $activitycompletions = array();
    $nonactivitycompletions = array();
    foreach($completions as $completion) {
        $criteria = $completion->get_criteria();
        if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
            if (!empty($criteria->moduleinstance)) {
                $modulecriteria[$criteria->moduleinstance] = $completion;
            }
        } else {
            $nonactivitycompletions[] = $completion;
        }
    }
    // Compare to the course module order to put the activities in the same order as on the course view.
    foreach($moduleorder as $module) {
        // Some modules may not have completion criteria and can be ignored.
        if (isset($modulecriteria[$module])) {
            $activitycompletions[] = $modulecriteria[$module];
        }
    }

    // Put the activity completions at the top.
    foreach ($activitycompletions as $completion) {
        $orderedcompletions[] = $completion;
    }
    foreach ($nonactivitycompletions as $completion) {
        $orderedcompletions[] = $completion;
    }

    // Loop through course criteria.
    foreach ($orderedcompletions as $completion) {
        $criteria = $completion->get_criteria();

        $row = array();
        $row['type'] = $criteria->criteriatype;
        $row['title'] = $criteria->get_title();
        $row['status'] = $completion->get_status();
        $row['complete'] = $completion->is_complete();
        $row['timecompleted'] = $completion->timecompleted;
        $row['details'] = $criteria->get_details($completion);
        $rows[] = $row;
    }

    // Print table.
    $last_type = '';
    $agg_type = false;
    $oddeven = 0;

    foreach ($rows as $row) {

        echo html_writer::start_tag('tr', array('class' => 'r' . $oddeven));
        // Criteria group.
        echo html_writer::start_tag('td', array('class' => 'cell c0'));
        if ($last_type !== $row['details']['type']) {
            $last_type = $row['details']['type'];
            echo $last_type;

            // Reset agg type.
            $agg_type = true;
        } else {
            // Display aggregation type.
            if ($agg_type) {
                $agg = $info->get_aggregation_method($row['type']);
                echo '('. html_writer::start_tag('i');
                if ($agg == COMPLETION_AGGREGATION_ALL) {
                    $aggstr = core_text::strtolower(get_string('all', 'completion'));
                } else {
                    $aggstr = core_text::strtolower(get_string('any', 'completion'));
                }

                echo html_writer::end_tag('i') .core_text::strtolower(get_string('xrequired', 'block_completionstatus', $aggstr)).')';
                $agg_type = false;
            }
        }
        echo html_writer::end_tag('td');

        // Criteria title.
        echo html_writer::start_tag('td', array('class' => 'cell c1'));
        echo $row['details']['criteria'];
        echo html_writer::end_tag('td');

        // Requirement.
        echo html_writer::start_tag('td', array('class' => 'cell c2'));
        echo $row['details']['requirement'];
        echo html_writer::end_tag('td');

        // Status.
        echo html_writer::start_tag('td', array('class' => 'cell c3'));
        echo $row['details']['status'];
        echo html_writer::end_tag('td');

        // Is complete.
        echo html_writer::start_tag('td', array('class' => 'cell c4'));
        echo $row['complete'] ? get_string('yes') : get_string('no');
        echo html_writer::end_tag('td');

        // Completion data.
        echo html_writer::start_tag('td', array('class' => 'cell c5'));
        if ($row['timecompleted']) {
            echo userdate($row['timecompleted'], get_string('strftimedate', 'langconfig'));
        } else {
            echo '-';
        }
        echo html_writer::end_tag('td');
        echo html_writer::end_tag('tr');
        // For row striping.
        $oddeven = $oddeven ? 0 : 1;
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
}
$courseurl = new moodle_url("/course/view.php", array('id' => $course->id));
echo html_writer::start_tag('div', array('class' => 'buttons'));
echo $OUTPUT->single_button($courseurl, get_string('returntocourse', 'block_completionstatus'), 'get');
echo html_writer::end_tag('div');
echo $OUTPUT->footer();
