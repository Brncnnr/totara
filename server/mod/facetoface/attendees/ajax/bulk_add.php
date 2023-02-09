<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralms.com>
 * @package mod_facetoface
 */
define('AJAX_SCRIPT', true);

require_once('../../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

// Face-to-face session ID
$s = required_param('s', PARAM_INT);
$listid = optional_param('listid', '',PARAM_ALPHANUM); // Session key to list of users to add.

$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = $seminarevent->get_seminar();
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

// Capability checks
require_login($seminar->get_course(), false, $cm);
require_capability('mod/facetoface:addattendees', $context);

$error = null;
if (!empty($listid)) {
    $list = new \mod_facetoface\bulk_list($listid);
    $userresults = $list->get_validation_results();
} else if (isset($_SESSION['f2f-bulk-results'][$seminarevent->get_id()])) {
    $bulkresults = $_SESSION['f2f-bulk-results'][$seminarevent->get_id()];
    // $bulkresults[0] is for added users and $bulkrestults[1] is for users with errors.
    // In this case, we want them combined into one list of users, each with their results.
    $userresults = array_merge($bulkresults[0], $bulkresults[1]);
} else {
    $error = get_string('unknownbackupexporterror', 'error');
}

if ($error) {
    $notification = new \core\output\notification($error, \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->render($notification);
} else {
    $table = new html_table();
    $table->head = [get_string('name'), get_string('result', 'mod_facetoface')];
    $table->data = [];
    foreach ($userresults as $result) {
        $name = new html_table_cell($result['name']);
        $message = new html_table_cell($result['result']);
        $row = new html_table_row(array($name, $message));
        $table->data[] = $row;
    }
    echo $OUTPUT->render($table);
}

