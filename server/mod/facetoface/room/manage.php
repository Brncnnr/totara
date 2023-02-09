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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use mod_facetoface\room;

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$debug = optional_param('debug', 0, PARAM_INT);
$sid = optional_param('sid', '0', PARAM_INT);
$published = optional_param('published', false, PARAM_INT);

$baseurl = new moodle_url('/mod/facetoface/room/manage.php');
// Check permissions.
if (is_siteadmin()) {
    admin_externalpage_setup('modfacetofacerooms', '', null, $baseurl);
    \totara_core\quickaccessmenu\helper::add_quickaction_page_button($PAGE, 'modfacetofacerooms');
} else {
    $context = context_system::instance();
    $PAGE->set_pagelayout('standard');
    $PAGE->set_context($context);
    $PAGE->set_url($baseurl);
    require_login(0, false);
    require_capability('mod/facetoface:managesitewiderooms', $context);
}
$PAGE->set_title(get_string('managerooms', 'mod_facetoface'));

$config = (new rb_config())->set_sid($sid)->set_embeddata(['published' => $published]);
$report = reportbuilder::create_embedded('facetoface_rooms', $config);

// Handle actions.
if ($action === 'delete') {
    if (empty($id)) {
        redirect($baseurl, get_string('error:roomdoesnotexist', 'mod_facetoface'), null, \core\notification::ERROR);
    }

    $room = new room($id);
    if ($room->get_custom()) {
        redirect($baseurl, get_string('error:roomnotpublished', 'mod_facetoface'), null, \core\notification::ERROR);
    }
    if ($room->is_used()) {
        redirect($baseurl, get_string('error:roomisinuse', 'mod_facetoface'), null, \core\notification::ERROR);
    }

    if (!$confirm) {
        echo $OUTPUT->header();
        $confirmurl = new moodle_url(
            new moodle_url('/mod/facetoface/room/manage.php'),
            ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]
        );
        echo $OUTPUT->confirm(
            get_string('deleteroomconfirm', 'mod_facetoface', format_string($room->get_name())),
            $confirmurl,
            $baseurl
        );
        echo $OUTPUT->footer();
        die;
    }

    require_sesskey();
    $room->delete();
    unset($room);
    redirect($baseurl, get_string('roomdeleted', 'mod_facetoface'), null, \core\notification::SUCCESS);

} else if ($action === 'show') {
    if (empty($id)) {
        redirect($baseurl, get_string('error:roomdoesnotexist', 'mod_facetoface'), null, \core\notification::ERROR);
    }

    require_sesskey();
    $room = new room($id);
    if ($room->get_custom()) {
        redirect($baseurl, get_string('error:roomnotpublished', 'mod_facetoface'), null, \core\notification::ERROR);
    }
    $room->show();
    $room->save();
    redirect($baseurl, get_string('roomshown', 'mod_facetoface'), null, \core\notification::SUCCESS);

} else if ($action === 'hide') {
    if (empty($id)) {
        redirect($baseurl, get_string('error:roomdoesnotexist', 'mod_facetoface'), null, \core\notification::ERROR);
    }

    require_sesskey();
    $room = new room($id);
    if ($room->get_custom()) {
        redirect($baseurl, get_string('error:roomnotpublished', 'mod_facetoface'), null, \core\notification::ERROR);
    }
    $room->hide();
    $room->save();
    redirect($baseurl, get_string('roomhidden', 'mod_facetoface'), null, \core\notification::SUCCESS);
}

$PAGE->set_button($report->edit_button() . $PAGE->button);
/** @var totara_reportbuilder_renderer $reportrenderer */
$reportrenderer = $PAGE->get_renderer('totara_reportbuilder');

echo $OUTPUT->header();

$report->include_js();
$report->display_restrictions();

echo $OUTPUT->heading(get_string('managerooms', 'mod_facetoface'));

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $reportrenderer->report_html($report, $debug);
echo $debughtml;
echo $reportrenderer->print_description($report->description, $report->_id);

// Print saved search options and filters.
$report->display_saved_search_options();
$report->display_search();
$report->display_sidebar_search();
echo $reporthtml;

echo $OUTPUT->container_start('buttons');
echo $OUTPUT->single_button(new moodle_url('/mod/facetoface/room/edit.php'), get_string('addnewroom', 'mod_facetoface'), 'post');
echo $OUTPUT->container_end();
echo $OUTPUT->footer();
