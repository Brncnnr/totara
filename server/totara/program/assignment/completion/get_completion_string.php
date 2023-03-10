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
 * @package totara
 * @subpackage program
 */
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot.'/totara/program/lib.php');
require_once($CFG->dirroot.'/totara/program/program_assignments.class.php');

require_login();

$completiontime = required_param('completiontime', PARAM_TEXT);
$completionevent = required_param('completionevent', PARAM_INT);
$completioninstance = required_param('completioninstance', PARAM_INT);
$completiontimehour = optional_param('completiontimehour', 0, PARAM_INT);
$completiontimeminute = optional_param('completiontimeminute', 0, PARAM_INT);

if ($completiontime == COMPLETION_TIME_NOT_SET && $completionevent == COMPLETION_EVENT_NONE && $completioninstance == 0) {
    echo get_string('setcompletion', 'totara_program');
} else {
    $string = '';
    if ($completionevent != COMPLETION_EVENT_NONE) {
        // $completiontime comes in the form '1 2' where 1 is the num and 2 is the unit.
        [$num, $unit] = explode(' ', $completiontime);
        if (isset($num) && !empty($unit)) {
            $string = prog_assignment_category::build_relative_completion_string($num, $unit, $completionevent, $completioninstance);
        }
    } else {
        $string = prog_assignment_category::build_completion_string($completiontime, null, null, $completiontimehour, $completiontimeminute);
    }
    if (trim($string) == '') {
        echo 'error';
    } else {
        echo $string;
    }
}
