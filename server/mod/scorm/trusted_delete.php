<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package mod_scorm
 */

require('../../config.php');

$contenthash = required_param('contenthash', PARAM_ALPHANUM);
$reportid = optional_param('reportid', 0, PARAM_INT);

$syscontext = context_system::instance();

$PAGE->set_context($syscontext);
$PAGE->set_url('/mod/scorm/trusted_delete.php', array('contenthash' => $contenthash, 'reportid' => $reportid));
$PAGE->set_pagelayout('noblocks');
$PAGE->set_title(get_string('packagedeletetrust', 'mod_scorm'));

$returnurlfunction = function () use ($reportid): string {
    global $CFG;
    require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

    if (!$reportid) {
        return $CFG->wwwroot . '/';
    }
    $report = reportbuilder::create($reportid);
    return $report->report_url();
};

require_login();
require_capability('mod/scorm:managetrustedpackages', $syscontext);

if (!$DB->record_exists('scorm_trusted_packages', ['contenthash' => $contenthash])) {
    redirect($returnurlfunction());
}

$currentdata = new stdClass();
$currentdata->contenthash = $contenthash;
$currentdata->reportid = $reportid;

$confirmform = new mod_scorm\form\trusted_delete_confirm($currentdata);

if ($confirmform->is_cancelled()) {
    redirect($returnurlfunction());
}

if ($data = $confirmform->get_data()) {
    $DB->delete_records('scorm_trusted_packages', ['contenthash' => $contenthash]);
    redirect($returnurlfunction());
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('packagedeletetrust', 'mod_scorm'));
echo $confirmform->render();
echo $OUTPUT->footer();
