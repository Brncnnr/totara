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
 * @package    course
 * @author     Russell England <russell.england@catalyst-eu.net>
 */

/**
 * Archives activity progress and completion state, and then resets completion state.
 *
 * @global moodle_page $PAGE
 * @global moodle_database $DB
 */

use core\output\notification;
use core_completion\hook\course_archive_completion;
use core_course\local\archive_progress_helper\factory;
use core_course\local\archive_progress_helper\output\validator\request_validator;

require_once(dirname(dirname(__FILE__)) . '/config.php');

$courseid = required_param('id', PARAM_INT);
$userid = optional_param('userid', null, PARAM_INT); // If provided reset for just a single user, otherwise reset all completed users.
$confirmed = optional_param(request_validator::SECRET_KEY, false, PARAM_ALPHANUMEXT);

// Allow the plugins to redirect away if course is not a legacy course.
(new course_archive_completion($courseid))->execute();

// Setup course and interacting user entities.
$course = get_course($courseid);
$user = null;

if (!is_null($userid)) {
    $user = $DB->get_record('user', ['id' => $userid], 'id, ' . get_all_user_name_fields(true), MUST_EXIST);
}

// Normal course require_login, but don't set wantsurl if we are processing the action.
require_login($courseid, false, null, !empty($confirmed));

// Prepare course and user so that we can instantiate a helper - it'll do everything for us.
$helper = factory::get_helper($course, $user);

// Ensure the user can archive completions.
$unable_to_archive_reason = $helper->get_unable_to_archive_reason();
if (!is_null($unable_to_archive_reason)) {
    throw new moodle_exception('invalidaccess', 'error', '', null, $unable_to_archive_reason);
}

if (!empty($confirmed)) {
    // User is confirming, we can attempt to process the action.
    require_sesskey(); // Essential.
    $helper->get_validator()->validate($confirmed); // Confirm the secret is as expected.
    $helper->archive_and_reset(); // Process the action
    // Redirect the user and show a success notification on the landing page.
    $success_context = $helper->get_page_output()->get_success_page_context();
    redirect($success_context->redirect_url(), $success_context->message(), null, notification::NOTIFY_SUCCESS);
}

echo $helper->get_page_output()->render($PAGE);
