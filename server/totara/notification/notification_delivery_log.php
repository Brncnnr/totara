<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_notification
 */

use mod_perform\views\override_nav_breadcrumbs;
use totara_core\extended_context;
use totara_notification\exception\notification_exception;
use totara_notification\factory\notifiable_event_resolver_factory;

global $CFG, $DB, $OUTPUT, $PAGE, $USER;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_global_restriction_set.php');

// Get URL parameters
$context_id = optional_param('context_id', context_system::instance()->id, PARAM_INT);
$component = optional_param('component', extended_context::NATURAL_CONTEXT_COMPONENT, PARAM_TEXT);
$area = optional_param('area', extended_context::NATURAL_CONTEXT_AREA, PARAM_TEXT);
$item_id = optional_param('item_id', extended_context::NATURAL_CONTEXT_ITEM_ID, PARAM_INT);
$user_id = optional_param('user_id', null, PARAM_INT);
$notification_log_id = optional_param('notification_log_id', null, PARAM_INT);
$notification_event_log_id = optional_param('notification_event_log_id', null, PARAM_INT);

// Report builder params.
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT); // Export format.
$debug  = optional_param('debug', 0, PARAM_INT);

// Calculate what context we're in.
$extended_context = extended_context::make_with_id($context_id, $component, $area, $item_id);
$context = $extended_context->get_context();
$is_system_context = $extended_context->get_context_level() == CONTEXT_SYSTEM && $extended_context->is_natural_context();

// Require auditing capability to access this page
if (!empty($user_id)) {
    $logs_enabled = get_config('core','notificationlogs');
    if (empty($logs_enabled)) {
        throw notification_exception::on_audit();
    }
    $user_context = context_user::instance($user_id);
    $is_system_context = false;

    if ($user_id == $USER->id) {
        $audit_own_notification = has_capability('totara/notification:auditownnotifications', $user_context);
        $audit_all_notification = has_capability('totara/notification:auditnotifications', $user_context);

        if (!($audit_own_notification || $audit_all_notification)) {
            throw notification_exception::on_audit();
        }
    } else {
        require_capability('totara/notification:auditnotifications', $user_context);
    }
} else {
    if (!notifiable_event_resolver_factory::context_has_resolvers_with_capabilities($extended_context, null, true)) {
        throw notification_exception::on_audit();
    }
}

// Set up the page.
$PAGE->set_context($extended_context->get_context());

$url = new moodle_url("/totara/notification/notification_delivery_log.php", [
    'context_id' => $extended_context->get_context_id(),
]);
if (!$extended_context->is_natural_context()) {
    $url->params([
        'component' => $extended_context->get_component(),
        'area' => $extended_context->get_area(),
        'item_id' => $extended_context->get_item_id(),
    ]);
}
if ($user_id) {
    $url->param('user_id', $user_id);
}
if ($notification_log_id) {
    $url->param('notification_log_id', $notification_log_id);
}
// required for back links to a particular notification event
if ($notification_event_log_id) {
    $url->param('notification_event_log_id', $notification_event_log_id);
}
$PAGE->set_url($url);

$page_title = get_string('log_delivery_title', 'totara_notification');
$back_to = get_string('log_title', 'totara_notification');
$back_to_url = '/totara/notification/notification_log.php';
if ($is_system_context) {
    admin_externalpage_setup('notifications_setup', '', null, $url, ['pagelayout' => 'noblocks']);
    if ($user_id) {
        $user = $DB->get_record('user', ['id' => $user_id]);
        $back_to = get_string('log_title_for', 'totara_notification', fullname($user));
        $page_title = get_string('log_delivery_title_for', 'totara_notification', fullname($user));
    }
    $PAGE->set_button('');
} else {
    if ($context->contextlevel === CONTEXT_COURSE) {
        $course = get_course($context->instanceid);
        require_login($course);
        $PAGE->set_pagelayout('admin');
        $back_to = get_string('log_title_context', 'totara_notification', $course->fullname);
        $page_title = get_string('log_delivery_title_context', 'totara_notification', $course->fullname);
    } else if ($context->contextlevel === CONTEXT_MODULE) {
        [$course, $cm] = get_course_and_cm_from_cmid($context->instanceid);
        require_login($course, true, $cm);

        if ($course->containertype === 'container_perform') {
            $PAGE->set_pagelayout('noblocks');
            $page_title = get_string('log_delivery_title_context', 'totara_notification', $cm->get_name());
            $back_to = get_string('log_title_context', 'totara_notification', $cm->get_name());
            override_nav_breadcrumbs::remove_nav_breadcrumbs($PAGE);
        } else {
            $PAGE->set_pagelayout('admin');
            $page_title = get_string('log_event_title_context', 'totara_notification', $course->fullname.': '. $cm->get_name());
            $back_to = get_string('notifications_for', 'totara_notification', $course->fullname.': '. $cm->get_name());
        }
    } else if ($context->contextlevel === CONTEXT_PROGRAM) {
        require_login();
        $PAGE->set_url(
            new moodle_url('/totara/program/edit_notifications.php'),
            ['context_id' => $context_id, 'id' => $context->instanceid]
        );
        $program = $DB->get_record('prog', array('id' => $context->instanceid), '*', MUST_EXIST);
        $PAGE->set_pagelayout('admin');
        $back_to = get_string('log_title_context', 'totara_notification', $program->fullname);
        $page_title = get_string('log_delivery_title_context', 'totara_notification', $program->fullname);
    } else if ($context->contextlevel === CONTEXT_USER || ($context->contextlevel === CONTEXT_SYSTEM && !empty($user_id))) {
        require_login();
        $PAGE->set_pagelayout('admin');
        $user = $DB->get_record('user', ['id' => $user_id]);
        $page_title = get_string('log_delivery_title_for', 'totara_notification', fullname($user));
        if (!empty($user_id) && $user_id !== $USER->id) {
            $back_to = get_string('log_title_for', 'totara_notification', fullname($user));
        }
    } else {
        require_login();
        $PAGE->set_pagelayout('noblocks');
    }
}
$PAGE->set_title($page_title);

// Load the embedded report.
$data = [
    'context_id' => $extended_context->get_context_id(),
    'component' => $extended_context->get_component(),
    'area' => $extended_context->get_area(),
    'item_id' => $extended_context->get_item_id(),
    'user_id' => $user_id,
    'notification_log_id' => $notification_log_id,
];
$reportrecord = $DB->get_record('report_builder', array('shortname' => 'notification_delivery_log'));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);
$config = (new rb_config())->set_sid($sid)->set_global_restriction_set($globalrestrictionset);
$config->set_embeddata($data);
$report = reportbuilder::create_embedded('notification_delivery_log', $config);

// Handle a request for export
if ($format != '') {
    $report->export_data($format);
    die;
}

// Output the page.
echo $OUTPUT->header();

// Display the back link.
$back_url = new moodle_url($back_to_url, $PAGE->url->params());
echo html_writer::start_div('row');
echo html_writer::start_div('col-sm-12');
echo html_writer::link($back_url, get_string('log_event_back_to', 'totara_notification', $back_to));
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::empty_tag('br');

echo $OUTPUT->heading($page_title);

/** @var totara_reportbuilder_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');

// This must be done after the header and before any other use of the report.
list($reporthtml, $debughtml) = $output->report_html($report, $debug);
echo $debughtml;

$report->display_restrictions();

// Print saved search options and filters.
$report->display_saved_search_options();
$report->display_search();
$report->display_sidebar_search();

echo $reporthtml;
$output->export_select($report, $sid);

echo $OUTPUT->footer();