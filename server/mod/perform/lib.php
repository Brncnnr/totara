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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

use core_user\output\myprofile\category;
use core_user\output\myprofile\node;
use core_user\output\myprofile\tree;
use mod_perform\controllers\reporting\performance\activity_response_data;
use mod_perform\util;
use totara_core\advanced_feature;

/**
 * Required in order to prevent failures in tests.
 */
function perform_add_instance($data) {
    return null;
}

function perform_update_instance($data) {
    return true;
}

function perform_delete_instance($id) {
    return true;
}

/**
 * Add user performance activity list link to the user profile page
 *
 * @param tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $this_user
 * @return bool
 * @throws coding_exception
 * @throws moodle_exception
 */
function mod_perform_myprofile_navigation(tree $tree, $user, $is_current_user) {

    $category = new category('development', get_string('development', 'mod_perform'), 'mylearning');
    $tree->add_category($category);

    if (!advanced_feature::is_disabled('performance_activities')) {
        if ($is_current_user && util::can_potentially_report_on_subjects($user->id)) {
            $tree->add_node(
                new node(
                    'development',
                    'performance_activities_response_data_report_export',
                    get_string('response_data_report_link_text', 'mod_perform'),
                    null,
                    activity_response_data::get_url()
                )
            );
        }
    }

    return true;
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function perform_supports($feature) {
    switch ($feature) {
        case FEATURE_NO_VIEW_LINK:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_USES_QUESTIONS:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_ARCHIVE_COMPLETION:
        case FEATURE_COMPLETION_HAS_RULES:
        case FEATURE_COMPLETION_TIME_IN_TIMECOMPLETED:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_COMMENT:
        case FEATURE_MODEDIT_DEFAULT_COMPLETION:
        case FEATURE_MOD_INTRO:
        case FEATURE_GROUPINGS:
        case FEATURE_GROUPS:
        case FEATURE_IDNUMBER:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_PLAGIARISM:
            return false;
        default:
            return null;
    }
}
