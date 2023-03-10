<?php

// This file is part of the Certificate module for Moodle - http://moodle.org/
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
 * Handles viewing a certificate
 *
 * @package    mod_certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("$CFG->dirroot/mod/certificate/locallib.php");
require_once("$CFG->dirroot/mod/certificate/deprecatedlib.php");
require_once("$CFG->libdir/pdflib.php");

$id = required_param('id', PARAM_INT);    // Course Module ID
$action = optional_param('action', '', PARAM_ALPHA);
$edit = optional_param('edit', -1, PARAM_BOOL);

if (!$cm = get_coursemodule_from_id('certificate', $id)) {
    print_error('Course Module ID was incorrect');
}
if (!$course = $DB->get_record('course', array('id'=> $cm->course))) {
    print_error('course is misconfigured');
}
if (!$certificate = $DB->get_record('certificate', array('id'=> $cm->instance))) {
    print_error('course module is incorrect');
}

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/certificate:view', $context);

$event = \mod_certificate\event\course_module_viewed::create(array(
    'objectid' => $certificate->id,
    'context' => $context,
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('certificate', $certificate);
$event->trigger();

$completion=new completion_info($course);
$completion->set_module_viewed($cm);

// Initialize $PAGE, compute blocks
$PAGE->set_url('/mod/certificate/view.php', array('id' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_title(format_string($certificate->name));
$PAGE->set_heading(format_string($course->fullname));

if (($edit != -1) and $PAGE->user_allowed_editing()) {
     $USER->editing = $edit;
}

// Add block editing button
if ($PAGE->user_allowed_editing()) {
    $editvalue = $PAGE->user_is_editing() ? 'off' : 'on';
    $strsubmit = $PAGE->user_is_editing() ? get_string('blockseditoff') : get_string('blocksediton');
    $url = new moodle_url($CFG->wwwroot . '/mod/certificate/view.php', array('id' => $cm->id, 'edit' => $editvalue));
    $PAGE->set_button($OUTPUT->single_button($url, $strsubmit));
}

// Check if the user can view the certificate
if ($certificate->requiredtime && !has_capability('mod/certificate:manage', $context) && (bool)certificate_log_stores_enabled()) {
    if (certificate_get_course_time($course->id) < ($certificate->requiredtime * 60)) {
        $a = new stdClass;
        $a->requiredtime = $certificate->requiredtime;
        notice(get_string('requiredtimenotmet', 'certificate', $a), "$CFG->wwwroot/course/view.php?id=$course->id");
        die;
    }
}

// Create new certificate record, or return existing record
$certrecord = certificate_get_issue($course, $USER, $certificate, $cm);

make_cache_directory('tcpdf');

// Load the specific certificate type.
require("$CFG->dirroot/mod/certificate/type/$certificate->certificatetype/certificate.php");
$view_generator_exists = \mod_certificate\output\view_generator_factory::exists($certificate->certificatetype);

if (empty($action)) { // Not displaying PDF
    echo $OUTPUT->header();

    /// find out current groups mode
    groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/certificate/view.php?id=' . $cm->id);
    $currentgroup = groups_get_activity_group($cm);
    $groupmode = groups_get_activity_groupmode($cm);

    if (has_capability('mod/certificate:manage', $context)) {
        $numusers = count(certificate_get_issues($certificate->id, 'ci.timecreated ASC', $groupmode, $cm));
        $url = html_writer::tag('a', get_string('viewcertificateviews', 'certificate', $numusers),
            array('href' => $CFG->wwwroot . '/mod/certificate/report.php?id=' . $cm->id));
        echo html_writer::tag('div', $url, array('class' => 'reportlink'));
    }

    if (!empty($certificate->intro)) {
        echo $OUTPUT->box(format_module_intro('certificate', $certificate, $cm->id), 'generalbox', 'intro');
    }

    if ($attempts = certificate_get_attempts($certificate->id)) {
        echo certificate_print_attempts($course, $certificate, $attempts);
    }
    if ($certificate->delivery == 0)    {
        $str = get_string('openwindow', 'certificate');
    } elseif ($certificate->delivery == 1)    {
        $str = get_string('opendownload', 'certificate');
    } elseif ($certificate->delivery == 2)    {
        $str = get_string('openemail', 'certificate');
    }
    echo html_writer::tag('p', $str, array('style' => 'text-align:center'));

    $linkname = get_string('getcertificate', 'certificate');

    $link = new moodle_url('/mod/certificate/view.php?id='.$cm->id.'&action=get');
    $button = new single_button($link, $linkname);
    if ($certificate->delivery != 1) {
        $button->add_action(new popup_action('click', $link, 'view' . $cm->id, array('height' => 600, 'width' => 800)));

        if ($view_generator_exists) {
            $html_link = new moodle_url('/mod/certificate/view.php?id='.$cm->id.'&action=html');
            $html_button = new single_button($html_link, get_string('view_html_version', 'mod_certificate'));
            $html_button->add_action(new popup_action('click', $html_link, 'certificatehtml', array('height' => 600, 'width' => 800)));
        }
    }

    echo html_writer::start_div('mod_certificate__getCertificate');
    echo $OUTPUT->render($button);

    // Conditionally display the html view button (empty if force download).
    if ($view_generator_exists && !empty($html_button)) {
        echo $OUTPUT->render($html_button);
    }
    echo html_writer::end_div();
    echo $OUTPUT->footer($course);
    exit;
} else if ($action === 'get') { // Output to pdf
    // No debugging here, sorry.
    $CFG->debugdisplay = 0;
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');

    $filename = certificate_get_certificate_filename($certificate, $cm, $course) . '.pdf';

    // PDF contents are now in $file_contents as a string.
    $filecontents = $pdf->Output('', 'S');

    if ($certificate->savecert == 1) {
        certificate_save_pdf($filecontents, $certrecord->id, $filename, $context->id);
    }


    // Certificate download in the app only works reliable as a forced download
    $is_mobile_app = core_useragent::is_mobile_app();

    if (!$is_mobile_app && $certificate->delivery == 0) {
        // Open in browser.
        send_file($filecontents, $filename, 0, 0, true, false, 'application/pdf', false, ['allowpdfembedding' => true]);
    } else if ($is_mobile_app || $certificate->delivery == 1) {
        // Force download.
        send_file($filecontents, $filename, 0, 0, true, true, 'application/pdf');
    } else if ($certificate->delivery == 2) {
        certificate_email_student($course, $certificate, $certrecord, $context, $filecontents, $filename);
        // Open in browser after sending email.
        send_file($filecontents, $filename, 0, 0, true, false, 'application/pdf', false, ['allowpdfembedding' => true]);
    }
} else if ($action === 'html') {
    if (!$view_generator_exists) {
        send_file_not_found();
        die();
    }

    $view_generator = \mod_certificate\output\view_generator_factory::create($certificate, $certrecord, $course, $cm);
    echo $view_generator->generate_html();
}
