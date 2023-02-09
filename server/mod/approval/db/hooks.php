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

use core_container\hook\module_supported_in_container;
use core_role\hook\core_role_potential_assignees_container;
use core_user\hook\allow_view_profile;
use core_user\hook\allow_view_profile_field;
use editor_weka\hook\find_context;
use mod_approval\watcher\activity;
use mod_approval\watcher\core_user;
use mod_approval\watcher\core_role_container;
use mod_approval\watcher\userdata_label;
use mod_approval\watcher\editor_context_watcher;
use mod_approval\watcher\performance;
use totara_core\hook\navigation\global_navigation_for_ajax_intialise;
use totara_core\hook\navigation\global_navigation_initialise;
use totara_core\hook\navigation\settings_navigation_initialise;
use totara_userdata\hook\userdata_normalise_label;

$watchers = [
    [
        'hookname' => module_supported_in_container::class,
        'callback' => [activity::class, 'filter_module'],
    ],
    [
        'hookname' => allow_view_profile_field::class,
        'callback' => [core_user::class, 'allow_view_profile_field'],
    ],
    [
        'hookname' => userdata_normalise_label::class,
        'callback' => [userdata_label::class, 'normalise'],
    ],
    [
        'hookname' => find_context::class,
        'callback' => [editor_context_watcher::class, 'set_context'],
    ],
    [
        'hookname' => allow_view_profile::class,
        'callback' => [core_user::class, 'handle_allow_view_profile'],
    ],
    [
        'hookname' => core_role_potential_assignees_container::class,
        'callback' => [core_role_container::class, 'get_potential_assignees']
    ],
    [
        'hookname' => global_navigation_for_ajax_intialise::class,
        'callback' => [performance::class, 'override_global_navigation_for_ajax']
    ],
    [
        'hookname' => global_navigation_initialise::class,
        'callback' => [performance::class, 'override_global_navigation']
    ],
    [
        'hookname' => settings_navigation_initialise::class,
        'callback' => [performance::class, 'override_settings_navigation']
    ],
];
