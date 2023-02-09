<?php
/*
 * This file is part of Totara LMS
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_mobile
 */

defined('MOODLE_INTERNAL') || die();

$watchers = [
    [
        // The priority of this watcher should be after the core_edit_form from totara.
        'hookname' => '\core_course\hook\edit_form_definition_complete',
        'callback' => '\totara_mobile\watcher\course_form_watcher::add_mobilecompatibility_to_course_form',
        'priority' => 200
    ],
    [
        'hookname' => '\core_course\hook\edit_form_save_changes',
        'callback' => '\totara_mobile\watcher\course_form_watcher::process_mobilecompatibility_for_course'
    ],
    [
        'hookname' => '\core\hook\login_page_start',
        'callback' => '\totara_mobile\watcher\login_page_watcher::webview_login_setup',
    ],
    [
        'hookname' => '\core\hook\login_page_login_complete',
        'callback' => '\totara_mobile\watcher\login_page_watcher::webview_login_complete'
    ],
    [
        'hookname' => '\message_totara_airnotifier\hook\airnotifier_device_discovery',
        'callback' => '\totara_mobile\watcher\message_totara_airnotifier_watcher::discover_mobile_devices'
    ],
    [
        'hookname' => '\core\hook\renderer_standard_footer_html_complete',
        'callback' => '\totara_mobile\watcher\renderer_watcher::add_mobile_banner'
    ],
    [
        'hookname' => '\core\hook\page_initialize_body_classes',
        'callback' => '\totara_mobile\watcher\page_initialization_watcher::inject_mobile_classes'
    ]
];
