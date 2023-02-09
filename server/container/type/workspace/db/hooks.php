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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_workspace
 */

defined('MOODLE_INTERNAL') || die();

$watchers = [
    [
        'hookname' => 'report_log\hook\index_view',
        'callback' => ['container_workspace\watcher\container_watcher', 'override_navigation_breadcrumbs']
    ],
    [
        'hookname' => 'report_loglive\hook\index_view',
        'callback' => ['container_workspace\watcher\container_watcher', 'override_navigation_breadcrumbs']
    ],
    [
        'hookname' => 'report_outline\hook\index_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'report_participation\hook\index_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'gradereport_grader\hook\index_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'gradereport_history\hook\index_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'gradereport_outcomes\hook\index_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'gradereport_overview\hook\index_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'gradereport_singleview\hook\index_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'gradereport_user\hook\index_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'totara_core\hook\configure_enrol_instances',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'totara_core\hook\edit_enrol_instances',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'totara_core\hook\mod_add',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'totara_core\hook\mod_update',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_badges\hook\view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_completion\hook\completion_editor',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_completion\hook\course_archive_completion',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_completion\hook\course_completion',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_course\hook\competency_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_course\hook\course_edit_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_course\hook\course_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_course\hook\reminders_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_course\hook\reset_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_course\hook\switchrole_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_grades\hook\edit_tree_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_grades\hook\letter_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_grades\hook\outcome_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_grades\hook\scale_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_grades\hook\settings_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_question\hook\category_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_question\hook\edit_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_question\hook\export_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'core_question\hook\import_view',
        'callback' => ['container_workspace\watcher\redirect_watcher', 'redirect_to_workspace']
    ],
    [
        'hookname' => 'editor_weka\hook\find_context',
        'callback' => ['container_workspace\watcher\editor_weka_watcher', 'load_context']
    ],
    [
        'hookname' => 'core_user\hook\allow_view_profile_field',
        'callback' => ['container_workspace\watcher\core_user', 'watch_allow_profile_field']
    ],
    [
        'hookname' => 'totara_reportedcontent\hook\get_review_context',
        'callback' => ['container_workspace\watcher\reportedcontent_watcher', 'get_content']
    ],
    [
        'hookname' => 'totara_reportedcontent\hook\remove_review_content',
        'callback' => ['container_workspace\watcher\reportedcontent_watcher', 'delete_discussion']
    ],
    [
        'hookname' => 'editor_weka\hook\search_users_by_pattern',
        'callback' => ['container_workspace\watcher\editor_weka_watcher', 'on_search_users']
    ]
];