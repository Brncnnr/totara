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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package container_approval
 */

use container_approval\approval_enrolment;
use container_approval\watcher\course as course_watcher;

defined('MOODLE_INTERNAL') || die();

// This is a long list - try keep it alphabetical
$watchers = [
    /*
     * The following hook is for handling container enrolment.
     */
    [
        'hookname' => \totara_core\hook\enrol_plugins::class,
        'callback' => [approval_enrolment::class, 'append_workflow_enrolment_plugin'],
    ],

    /*
     * The following hooks are for showing the page but without the course navigation and settings blocks.
     */
    [
        'hookname' => \report_log\hook\index_view::class,
        'callback' => [course_watcher::class, 'remove_nav_breadcrumbs'],
    ],
    [
        'hookname' => \report_loglive\hook\index_view::class,
        'callback' => [course_watcher::class, 'remove_nav_breadcrumbs'],
    ],

    /*
     * The following hooks are for unsupported pages.
     * They redirect the page back to the activity edit page (if has permission) with an error message.
     */
    [
        'hookname' => \core_badges\hook\index_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_badges\hook\new_badge_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_badges\hook\view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_completion\hook\completion_editor::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_completion\hook\course_archive_completion::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_completion\hook\course_completion::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_course\hook\competency_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_course\hook\course_create_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_course\hook\course_edit_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_course\hook\course_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_course\hook\reminders_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_course\hook\reset_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_course\hook\switchrole_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_grades\hook\edit_tree_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_grades\hook\letter_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_grades\hook\outcome_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_grades\hook\scale_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_grades\hook\settings_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_question\hook\category_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_question\hook\edit_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_question\hook\export_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \core_question\hook\import_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \gradereport_grader\hook\index_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \gradereport_history\hook\index_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \gradereport_outcomes\hook\index_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \gradereport_overview\hook\index_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \gradereport_singleview\hook\index_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \gradereport_user\hook\index_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \report_outline\hook\index_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \report_participation\hook\index_view::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \totara_core\hook\configure_enrol_instances::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \totara_core\hook\edit_enrol_instances::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \totara_core\hook\enrol_index_page::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \totara_core\hook\mod_add::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
    [
        'hookname' => \totara_core\hook\mod_update::class,
        'callback' => [course_watcher::class, 'redirect_to_workflow'],
    ],
];
