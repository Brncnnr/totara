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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 *
 * @package modules
 * @subpackage facetoface
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$s = required_param('s', PARAM_INT); // Facetoface session ID.

$seminar = (new \mod_facetoface\seminar_event($s))->get_seminar();
$cm = $seminar->get_coursemodule();
$context = context_module::instance($cm->id);

$PAGE->set_context($context);

if ($seminar->get_approvaltype() != \mod_facetoface\seminar::APPROVAL_SELF) {
    // This should not happen unless there is a concurrent change of settings.
    print_error('error');
}

require_login($seminar->get_course(), false, $cm);
require_capability('mod/facetoface:view', $context);

$mform = new \mod_facetoface\form\signup_tsandcs(null, array('tsandcs' => $seminar->get_approvalterms(), 's' => $s));

// This should be json_encoded, but for now we need to use html content
// type to not break $.get().
header('Content-type: text/html; charset=utf-8');
$mform->display();
