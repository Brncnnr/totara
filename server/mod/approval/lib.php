<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package mod_approval
 */

use core\entity\user;
use mod_approval\form_schema\field_type\application_editor;

/**
 * This plugin is a placeholder; workflow assignments are implemented in container_approval.
 */

/**
 * Required in order to prevent failures in tests.
 */
function approval_add_instance($data) {
    return null;
}

function approval_update_instance($data) {
    return true;
}

function approval_delete_instance($id) {
    return true;
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function approval_supports($feature) {
    switch ($feature) {
        case FEATURE_NO_VIEW_LINK:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMMENT:
        case FEATURE_IDNUMBER:
            return true;

        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_USES_QUESTIONS:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_ARCHIVE_COMPLETION:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_COMPLETION_TIME_IN_TIMECOMPLETED:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_MODEDIT_DEFAULT_COMPLETION:
        case FEATURE_MOD_INTRO:
        case FEATURE_GROUPINGS:
        case FEATURE_GROUPS:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_PLAGIARISM:
            return false;

        default:
            return null;
    }
}

/**
 * Serve the files for the mod_approval file component.
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function mod_approval_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    if ($filearea === application_editor::FILE_AREA) {
        $application_id = (int)array_shift($args);
        $user = user::logged_in();

        return application_editor::by_application_id($application_id, $user)->serve_file($context, $filearea, $args, $options);
    }

    return false;
}

/**
 * Return a placeholder name to prevent get_array_of_activities() from executing an extra database query.
 *
 * @param stdClass $mod {course_modules} record
 * @return cached_cm_info|stdClass
 */
function approval_get_coursemodule_info($mod) {
    $info = new cached_cm_info();
    $info->name = 'approval workflow';
    return $info;
}