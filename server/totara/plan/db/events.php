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
 * @author David Curry <david.curry@totaralms.com>
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara_plan
 */

/**
 * This file should be used for all plan event definitions and handers.
 */

use hierarchy_competency\event\competency_deleted;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

$observers = array(
    array(
        'eventname' => '\core\event\course_deleted',
        'callback'  => 'totara_plan_observer::course_deleted',
    ),
    array(
        'eventname' => '\totara_plan\event\approval_approved',
        'callback'  => 'totara_program_observer::learning_plan_approved',
    ),
    array(
        'eventname' => '\totara_plan\event\approval_declined',
        'callback'  => 'totara_program_observer::learning_plan_declined',
    ),
    array(
        'eventname' => '\totara_plan\event\component_created',
        'callback'  => 'totara_program_observer::learning_plan_component_created',
    ),
    array(
        'eventname' => '\totara_plan\event\component_deleted',
        'callback'  => 'totara_program_observer::learning_plan_component_deleted',
    ),
    array(
        'eventname' => '\totara_plan\event\plan_completed',
        'callback'  => 'totara_program_observer::learning_plan_completed',
    ),
    array(
        'eventname' => '\totara_plan\event\plan_reactivated',
        'callback'  => 'totara_program_observer::learning_plan_reactivated',
    ),
    array(
        'eventname' => '\totara_plan\event\plan_deleted',
        'callback'  => 'totara_program_observer::learning_plan_deleted',
    ),
    array(
        'eventname' => competency_deleted::class,
        'callback'  => 'totara_plan_observer::competency_deleted',
    ),
    array(
        'eventname' => '\core\event\user_enrolment_created',
        'callback'  => '\totara_plan\observer\enrolment_observer::user_enrolment_created',
    ),
    array(
        'eventname' => '\totara_completionimport\event\bulk_course_completionimport',
        'callback'  => '\totara_plan\observer\completion_observer::bulk_completion',
    ),
    array(
        'eventname' => '\totara_plan\event\component_created',
        'callback'  => '\totara_plan\observer\plan_observer::course_assigned',
    ),
    array(
        'eventname' => '\core\event\course_completed',
        'callback'  => '\totara_plan\observer\completion_observer::completed_course',
    ),
);
