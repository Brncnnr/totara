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
use totara_topic\output\bulk_add_button;

require_once(__DIR__ . "/../../config.php");
global $PAGE, $OUTPUT, $DB, $CFG;

require_login();

$sid = optional_param('sid', 0, PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);

$url = new moodle_url(
    "/totara/topic/index.php",
    [
        'sid' => $sid,
        'debug' => $debug
    ]
);

require_once("{$CFG->dirroot}/lib/adminlib.php");
admin_externalpage_setup('managetopics', '', null, $url, ['pagelayout' => 'report']);

$heading = get_string('managetopics', 'totara_topic');
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

$shortname = 'topic';
$reportrecord = $DB->get_record('report_builder', ['shortname' => $shortname]);
$grr = rb_global_restriction_set::create_from_page_parameters($reportrecord);

$config = new rb_config();
$config->set_sid($sid);
$config->set_global_restriction_set($grr);

$report = reportbuilder::create_embedded($shortname, $config);
$report->include_js();
$report->set_baseurl($url);

$PAGE->set_button($report->edit_button());

// Prevent the ability to trigger the event multiple times.
$event = report_viewed::create_from_report($report);
$event->trigger();

/** @var totara_reportbuilder_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');
[$reporthtml, $debughtml] = $renderer->report_html($report, $debug);

echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->heading);
echo $OUTPUT->render(bulk_add_button::create());

$report->display_restrictions();

$report->display_saved_search_options();
$report->display_search();
$report->display_sidebar_search();

echo $debughtml;
echo $reporthtml;

echo $OUTPUT->footer();