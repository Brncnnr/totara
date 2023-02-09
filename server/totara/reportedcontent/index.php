<?php
/**
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_reportedcontent
 */

use totara_core\advanced_feature;
use totara_core\feature_not_available_exception;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

// Can only access if either workspaces or resources are enabled
if (advanced_feature::is_disabled('engage_resources')
    && advanced_feature::is_disabled('container_workspace')) {
    throw new feature_not_available_exception('engage_resources');
}

require_login();

$sid = optional_param('sid', 0, PARAM_INT);
$format = optional_param('format', '', PARAM_ALPHANUMEXT);
$debug = optional_param('debug', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/totara/reportedcontent/index.php');
$PAGE->set_totara_menu_selected('\totara_core\totara\menu\myreports');
$PAGE->set_pagelayout('noblocks');
$PAGE->navbar->add(get_string('reports', 'totara_core'), new moodle_url('/my/reports.php'));
$PAGE->navbar->add(get_string('sourcetitle', 'rb_source_reportedcontent'));

$config = (new rb_config())->set_sid($sid);
$report = reportbuilder::create_embedded('reportedcontent', $config);

$PAGE->set_button($report->edit_button() . $PAGE->button);

$heading = get_string('sourcetitle', 'rb_source_reportedcontent');
$PAGE->set_title($heading);
$PAGE->set_heading(format_string($SITE->fullname));

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

echo $output->heading(get_string('sourcetitle', 'rb_source_reportedcontent') . ': ' . $output->result_count_info($report));

// Print saved search options and filters.
$report->display_search();
$report->display_sidebar_search();

echo $reporthtml;

$output->export_select($report, $sid);

echo $output->footer();
