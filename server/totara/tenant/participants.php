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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$id = optional_param('id', 0, PARAM_INT);
$format = optional_param('format', '', PARAM_ALPHANUMEXT);
$debug = optional_param('debug', 0, PARAM_INT);

require_login(null, false);
if (empty($CFG->tenantsenabled)) {
    redirect(new moodle_url('/'));
}

if (!$id) {
    $context = context_system::instance();
    $PAGE->set_context($context);

    // This cap is for viewing tenantsmanage external page.
    if (has_capability('totara/tenant:view', $context)) {
        admin_externalpage_setup('tenantsmanage');

        echo $OUTPUT->header();
        $url = new moodle_url('/totara/tenant/index.php');
        echo $OUTPUT->container(get_string('participantsselect', 'totara_tenant', $url->out()));
        echo $OUTPUT->footer();
    } else {
        // Just display error without capability.
        print_error('missingparam', '', '', 'id');
    }
    exit;
}

$PAGE->set_url('/totara/tenant/participants.php', ['id' => $id]);

$tenant = \core\record\tenant::fetch($id);
$tenantcontext = context_tenant::instance($tenant->id);
$categorycontext = context_coursecat::instance($tenant->categoryid);
$systemcontext = context_system::instance();
$PAGE->set_context($tenantcontext);

if (!has_capability('totara/tenant:viewparticipants', $categorycontext)) {
    require_capability('totara/tenant:view', $tenantcontext);
    require_capability('moodle/user:viewalldetails', $tenantcontext);
}

if ($USER->tenantid) {
    $embeddedname = 'tenant_users';
    $strheading = get_string('users');
} else {
    $embeddedname = 'tenant_participants';
    $strheading = get_string('participants', 'totara_tenant');
}
// Set some title in case this is not an admin page.
$PAGE->set_title($strheading . ': ' . format_string($tenant->name));

// Select appropriate admin external page to make the quick access work properly,
// this must match logic in admin/settings/users.php
if (!empty($USER->tenantid) and !has_capability('moodle/user:viewalldetails', $systemcontext)) {
    if (has_capability('totara/tenant:view', $tenantcontext) and has_capability('moodle/user:viewalldetails', $tenantcontext)) {
        admin_externalpage_setup('tenantusers');
    } else if (has_capability('totara/tenant:viewparticipants', $categorycontext)) {
        admin_externalpage_setup('tenantusers');
    }
}

$config = new rb_config();
$config->set_embeddata(['participantstenantid' => $id]);
$report = reportbuilder::create_embedded($embeddedname, $config);

$PAGE->set_button($report->edit_button() . $PAGE->button);

$buttons = [];
if (has_capability('totara/tenant:usercreate', $tenantcontext)) {
    $buttons[] = $OUTPUT->single_button(new moodle_url('/totara/tenant/user_create.php', ['tenantid' => $tenant->id]), get_string('createuser'), 'get');
}
if (has_capability('totara/tenant:userupload', $tenantcontext)) {
    $buttons[] = $OUTPUT->single_button(new moodle_url('/totara/tenant/user_upload.php', ['tenantid' => $tenant->id]), get_string('uploadusers', 'totara_tenant'), 'get');
}
if (has_capability('totara/tenant:manageparticipants', $systemcontext)) {
    $buttons[] = $OUTPUT->single_button(new moodle_url('/totara/tenant/participants_other.php', ['id' => $tenant->id]), get_string('participantsother', 'totara_tenant'), 'get');
}
if ($buttons) {
    // TODO: add some layout to fix the buttons, the CSS '.buttons' class totally breaks page layout by hiding the report table, why???
    $buttons = '<div>' . implode('', $buttons) . '</div>';
} else {
    $buttons = '';
}

/** @var totara_reportbuilder_renderer|core_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');

if (!empty($format)) {
    $report->export_data($format);
    die;
}

$report->include_js();

echo $output->header();
list($reporthtml, $debughtml) = $output->report_html($report, $debug);
echo $debughtml;

echo $output->heading($strheading . ': ' . $output->result_count_info($report));

// Print saved search options and filters.
$report->display_search();
$report->display_sidebar_search();

echo $buttons;
echo $reporthtml;

$output->export_select($report, 0);

echo $output->footer();
