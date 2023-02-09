<?php
/*
* This file is part of Totara Learn
*
* Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use mod_facetoface\facilitator;

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$debug = optional_param('debug', 0, PARAM_INT);
$sid = optional_param('sid', '0', PARAM_INT);
$published = optional_param('published', false, PARAM_INT);

$baseurl = new moodle_url('/mod/facetoface/facilitator/manage.php');
// Check permissions.
if (is_siteadmin()) {
    admin_externalpage_setup('modfacetofacefacilitators', '', null, $baseurl);
    \totara_core\quickaccessmenu\helper::add_quickaction_page_button($PAGE, 'modfacetofacefacilitators');
} else {
    $context = context_system::instance();
    $PAGE->set_pagelayout('standard');
    $PAGE->set_context($context);
    $PAGE->set_url($baseurl);
    require_login(0, false);
    require_capability('mod/facetoface:managesitewidefacilitators', $context);
}
$PAGE->set_title(get_string('managefacilitators', 'mod_facetoface'));

$config = (new rb_config())->set_sid($sid)->set_embeddata(['published' => $published]);
$report = reportbuilder::create_embedded('facetoface_facilitators', $config);

// Handle actions.
if ($action === 'delete') {
    if (empty($id)) {
        redirect($baseurl, get_string('error:facilitatordoesnotexist', 'mod_facetoface'), null, \core\notification::ERROR);
    }

    $facilitator = new facilitator($id);
    if ($facilitator->get_custom()) {
        redirect($baseurl, get_string('error:facilitatornotpublished', 'mod_facetoface'), null, \core\notification::ERROR);
    }
    if ($facilitator->is_used()) {
        redirect($baseurl, get_string('error:facilitatorisinuse', 'mod_facetoface'), null, \core\notification::ERROR);
    }

    if (!$confirm) {
        echo $OUTPUT->header();
        $confirmurl = new moodle_url(
            '/mod/facetoface/facilitator/manage.php',
            ['action' => $action, 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]
        );
        echo $OUTPUT->confirm(
            get_string('deletefacilitatorconfirm', 'mod_facetoface', format_string($facilitator->get_name())),
            $confirmurl,
            $baseurl
        );
        echo $OUTPUT->footer();
        die;
    }

    require_sesskey();
    $facilitator->delete();
    unset($facilitator);
    redirect($baseurl, get_string('facilitatordeleted', 'mod_facetoface'), null, \core\notification::SUCCESS);

} else if ($action === 'show') {
    if (empty($id)) {
        redirect($baseurl, get_string('error:facilitatordoesnotexist', 'mod_facetoface'), null, \core\notification::ERROR);
    }

    require_sesskey();
    $facilitator = new facilitator($id);
    if (!$facilitator->can_show()) {
        redirect($baseurl, get_string('facilitatoruserdeleted', 'mod_facetoface'), null, \core\notification::ERROR);
    }
    $facilitator->show();
    $facilitator->save();
    redirect($baseurl, get_string('facilitatorshown', 'mod_facetoface'), null, \core\notification::SUCCESS);

} else if ($action === 'hide') {
    if (empty($id)) {
        redirect($baseurl, get_string('error:facilitatordoesnotexist', 'mod_facetoface'), null, \core\notification::ERROR);
    }

    require_sesskey();
    $facilitator = new facilitator($id);
    if ($facilitator->get_custom()) {
        redirect($baseurl, get_string('error:facilitatornotpublished', 'mod_facetoface'), null, \core\notification::ERROR);
    }
    $facilitator->hide();
    $facilitator->save();
    redirect($baseurl, get_string('facilitatorhidden', 'mod_facetoface'), null, \core\notification::SUCCESS);
}

$PAGE->set_button($report->edit_button() . $PAGE->button);
/** @var totara_reportbuilder_renderer $reportrenderer */
$reportrenderer = $PAGE->get_renderer('totara_reportbuilder');

echo $OUTPUT->header();

$report->include_js();
$report->display_restrictions();

echo $OUTPUT->heading(get_string('managefacilitators', 'mod_facetoface'));

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
echo $OUTPUT->single_button(new moodle_url('/mod/facetoface/facilitator/edit.php'), get_string('addnewfacilitator', 'mod_facetoface'), 'post');
echo $OUTPUT->container_end();
echo $OUTPUT->footer();
