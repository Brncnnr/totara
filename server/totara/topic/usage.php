<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_topic
 */
use totara_reportbuilder\event\report_viewed;

require_once(__DIR__ . "/../../config.php");
global $PAGE, $OUTPUT, $DB, $CFG;

require_login();

$sid = optional_param('sid', 0, PARAM_INT);
$format = optional_param('format', '', PARAM_ALPHANUMEXT);
$debug = optional_param('debug', 0, PARAM_INT);

$url = new moodle_url(
    "/totara/topic/usage.php",
    [
        'sid' => $sid,
        'debug' => $debug
    ]
);

require_once("{$CFG->dirroot}/lib/adminlib.php");
admin_externalpage_setup('topicusage', '', null, $url, ['pagelayout' => 'report']);

$heading = get_string('usageoftopics', 'totara_topic');
$PAGE->set_heading($heading);
$PAGE->set_heading($heading);

$shortname = 'topic_usage';
$reportrecord = $DB->get_record('report_builder', ['shortname' => $shortname]);
$grr = rb_global_restriction_set::create_from_page_parameters($reportrecord);

$config = new rb_config();
$config->set_sid($sid);
$config->set_global_restriction_set($grr);

$report = reportbuilder::create_embedded($shortname, $config);
if (!empty($format)) {
    $report->export_data($format);
    die;
}

$report->include_js();
$PAGE->set_button($report->edit_button() . $PAGE->button);

$event = report_viewed::create_from_report($report);
$event->trigger();

/** @var totara_reportbuilder_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');
[$html, $debughtml] = $renderer->report_html($report, $debug);

echo $OUTPUT->header();
echo $debughtml;

$report->display_restrictions();
echo $OUTPUT->heading($PAGE->heading);

$report->display_saved_search_options();
$report->display_search();

echo $html;
$renderer->export_select($report, $sid);

echo $OUTPUT->footer();