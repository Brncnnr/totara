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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_perform
 */

require_once(__DIR__ . '/../../config.php');

use mod_perform\controllers\activity\edit_activity;
use mod_perform\models\activity\activity;

/**
 * This page is where a user is taken to when they click on a link for a performance activity context.
 * The link would show up in places such as the site logs.
 *
 * Since performance activities aren't actually real courses,
 * we just redirect to the management page for the activity or the homepage.
 */

$course_module_id = required_param('id', PARAM_INT);
$activity = activity::load_by_module_id($course_module_id);

if ($activity->can_manage()) {
    redirect(edit_activity::get_url(['activity_id' => $activity->id]));
} else {
    redirect(new moodle_url('/'));
}
