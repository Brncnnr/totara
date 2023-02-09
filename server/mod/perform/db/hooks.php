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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_perform
 */

use core\hook\admin_setting_changed;
use core_container\hook\module_supported_in_container;
use mod_perform\hook\pre_activity_deleted;
use mod_perform\hook\pre_section_deleted;
use mod_perform\hook\pre_section_element_deleted;
use mod_perform\watcher\activity;
use mod_perform\hook\subject_instances_created;
use mod_perform\watcher\activity_deletion_check;
use mod_perform\watcher\section_deletion_check;
use mod_perform\watcher\section_element_deletion_check;
use mod_perform\watcher\subject_static_instances;
use mod_perform\watcher\participant_instances;
use mod_perform\watcher\participant_sections;
use mod_perform\hook\participant_instances_created;
use core_user\hook\allow_view_profile_field;
use mod_perform\watcher\notification;
use mod_perform\watcher\core_user;
use mod_perform\watcher\suspended_users;
use mod_perform\watcher\userdata_label;
use totara_userdata\hook\userdata_normalise_label;

$watchers = [
    [
        'hookname' => module_supported_in_container::class,
        'callback' => [activity::class, 'filter_module'],
    ],
    [
        'hookname' => subject_instances_created::class,
        'callback' => [participant_instances::class, 'create_participants'],
    ],
    [
        'hookname' => participant_instances_created::class,
        'callback' => [participant_sections::class, 'create_participant_sections'],
    ],
    [
        'hookname' => allow_view_profile_field::class,
        'callback' => [core_user::class, 'allow_view_profile_field'],
    ],
    [
        'hookname' => participant_instances_created::class,
        'callback' => [notification::class, 'create_participant_instances'],
    ],
    [
        'hookname' => userdata_normalise_label::class,
        'callback' => [userdata_label::class, 'normalise'],
    ],
    [
        'hookname' => subject_instances_created::class,
        'callback' => [subject_static_instances::class, 'create_subject_static_instances'],
    ],
    [
        'hookname' => pre_activity_deleted::class,
        'callback' => [activity_deletion_check::class, 'can_delete'],
    ],
    [
        'hookname' => pre_section_deleted::class,
        'callback' => [section_deletion_check::class, 'can_delete'],
    ],
    [
        'hookname' => pre_section_element_deleted::class,
        'callback' => [section_element_deletion_check::class, 'can_delete'],
    ],
    [
        'hookname' => admin_setting_changed::class,
        'callback' => [suspended_users::class, 'admin_setting_changed'],
    ],
];
